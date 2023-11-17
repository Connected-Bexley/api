<?php

namespace App\Http\Controllers\Core\V1\Organisation;

use App\BatchImport\SpreadsheetParser;
use App\BatchImport\StoresSpreadsheets;
use App\Exceptions\DuplicateContentException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organisation\ImportRequest;
use App\Models\Organisation;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    use StoresSpreadsheets;

    /**
     * Number of rows to import at once.
     */
    const ROW_IMPORT_BATCH_SIZE = 100;

    /**
     * Characters which will be replaced with empty string to normalise name field.
     *
     * @var string
     */
    protected $normalisedCharacters = '-, _.\'"';

    /**
     * OrganisationController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display the specified resource.
     */
    public function __invoke(ImportRequest $request): JsonResponse
    {
        $this->ignoreDuplicateIds = $request->input('ignore_duplicates', []);
        $this->processSpreadsheet($request->input('spreadsheet'));

        $responseStatus = 201;
        $response = ['imported_row_count' => $this->imported];

        if (count($this->rejected)) {
            $responseStatus = 422;
            $response['errors'] = ['spreadsheet' => $this->rejected];
        }

        if (count($this->duplicates)) {
            $responseStatus = 422;
            $response['duplicates'] = $this->duplicates;
        }

        return response()->json([
            'data' => $response,
        ], $responseStatus);
    }

    /**
     * Validate the spreadsheet rows.
     *
     * @return array
     */
    public function validateSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        $spreadsheetParser->readHeaders();

        $rejectedRows = $rowIds = [];

        foreach ($spreadsheetParser->readRows() as $i => $row) {
            $rejectedRow = null;
            $validator = Validator::make(
                $row,
                [
                    'id' => ['required', 'string', 'uuid', 'unique:organisations,id'],
                    'name' => ['required', 'string', 'min:1', 'max:255'],
                    'description' => ['required', 'string', 'min:1', 'max:10000'],
                    'url' => ['present', 'url', 'max:255'],
                    'email' => ['present', 'nullable', 'required_without:phone', 'email', 'max:255'],
                    'phone' => [
                        'present',
                        'nullable',
                        'required_without:email',
                        'string',
                        'min:1',
                        'max:255',
                    ],
                ],
                [
                    'uuid' => 'The :attribute is not a valid UUID',
                ]
            );

            $row['index'] = $i + 2;
            if ($validator->fails()) {
                $rejectedRow = ['row' => $row, 'errors' => $validator->errors()];
            }

            /**
             * Check for duplicate IDs in the spreadsheet.
             */
            if (false !== array_search($row['id'], $rowIds)) {
                $error = ['id' => ['The ID is used elsewhere in the spreadsheet.']];
                if ($rejectedRow) {
                    $rejectedRow['errors']->merge($error);
                } else {
                    $rejectedRow = [
                        'row' => $row,
                        'errors' => new MessageBag($error),
                    ];
                }
            }
            if ($rejectedRow) {
                $rejectedRows[] = $rejectedRow;
            }
            $rowIds[] = $row['id'];
        }

        return $rejectedRows;
    }

    /**
     * Find exisiting Orgaisations that match rows in the spreadsheet.
     *
     * @return array
     */
    public function rowsExist(array $rowIndex)
    {
        $normaliseCharacters = mb_str_split($this->normalisedCharacters);

        $normalisedNames = array_map(function ($row) {
            return $row['normalisedName'];
        }, $rowIndex);

        /**
         * Concatenate with ';' ids and name columns and count grouped rows.
         */
        $sql = [
            implode(',', [
                'select group_concat(distinct id order by id separator ";") as ids',
                'group_concat(distinct name order by name separator ";") as results',
                'count(name) as row_count',
                $this->buildSqlReplaceCharacterSet('lower(trim(name))', $normaliseCharacters) . ' as normalised_col',
            ]),
        ];
        $sql[] = 'FROM organisations';

        /**
         * Ignore those organisations where the user has flagged the duplicate as allowed.
         */
        if (count($this->ignoreDuplicateIds)) {
            $sql[] = 'where id NOT IN ("' . implode('","', $this->ignoreDuplicateIds) . '")';
        }

        $sql[] = 'group by normalised_col';

        /**
         * Filter to only take organisations that match with imported rows, or all existing duplicate named organisations are included.
         */
        $sql[] = 'having normalised_col IN ("' . implode('","', $normalisedNames) . '")';
        $sql[] = 'and row_count > 1';

        return DB::select(implode(' ', $sql));
    }

    /**
     * Wrap a string in SQL replace functions for a character set.
     *
     * @return string
     */
    public function buildSqlReplaceCharacterSet(string $string, array $replace, string $replacement = '')
    {
        $sql = $string;
        foreach ($replace as $chr) {
            if ($chr === "'" || $chr === '"') {
                $chr = '\\' . $chr;
            }
            $sql = 'replace(' . $sql . ',"' . $chr . '","' . $replacement . '")';
        }

        return $sql;
    }

    /**
     * Format the duplicate Organisations and store details of them.
     *
     * @throws App\Exceptions\DuplicateContentException
     */
    public function formatDuplicates(array $duplicates, array $headers, array $nameIndex)
    {
        foreach ($duplicates as $duplicate) {
            /**
             * Get the IDs of the duplicate Organisations.
             */
            $organisationIds = explode(';', $duplicate->ids);

            /**
             * Get the names which were duplicates.
             */
            $names = explode(';', $duplicate->results);

            foreach ($names as $i => $name) {
                /**
                 * Find the imported row details for the duplicate name.
                 */
                $rowIndex = array_search($name, array_column($nameIndex, 'name', 'index'));
                if (false !== $rowIndex) {
                    /**
                     * Get the details of the row that was being imported.
                     */
                    $duplicateRow = DB::table('organisations')
                        ->where('id', $nameIndex[$rowIndex]['id'])
                        ->select($headers)
                        ->first();
                    break;
                }
            }

            /**
             * Get the details of the rows the import row clashes with.
             */
            unset($organisationIds[array_search($nameIndex[$rowIndex]['id'], $organisationIds)]);
            $originalRows = DB::table('organisations')
                ->whereIn('id', $organisationIds)
                ->select(array_merge(['id'], $headers))
                ->get();

            /**
             * Strip out IDs from duplicates that were created during this process.
             * i.e If duplicate rows were in the spreadsheet, the duplicate row willbe in $organisationIds
             * but it will exist only in the transaction and will be unavailable for use in $this->ignoreDuplicateIds.
             */
            $originalRows = $originalRows->map(function ($row) use ($nameIndex) {
                if (array_search($row->id, array_column($nameIndex, 'id'))) {
                    $row->id = null;
                }

                return $row;
            });

            /**
             * Add the result to the duplicates array.
             */
            $this->duplicates[] = [
                'row' => array_merge(['index' => $rowIndex], json_decode(json_encode($duplicateRow), true)),
                'originals' => $originalRows,
            ];
        }

        throw new DuplicateContentException();
    }

    /**
     * Import the uploaded file contents.
     */
    public function importSpreadsheet(string $filePath)
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->import(Storage::disk('local')->path($filePath));

        /**
         * Load the first row of the Spreadsheet as column names.
         */
        $spreadsheetParser->readHeaders();

        $importedRows = 0;

        DB::transaction(function () use ($spreadsheetParser, &$importedRows) {
            $globalUserIds = Role::globalAdmin()->users()->pluck('users.id');
            $organisationAdminId = Role::organisationAdmin()->id;
            $organisationRowBatch = $rowIndex = $userRoleBatch = [];
            foreach ($spreadsheetParser->readRows() as $i => $organisationRow) {
                /**
                 * Normalise the Organistion name
                 * and add the meta fields to the Organisation row.
                 */
                $organisationRow['name'] = preg_replace('/[^a-zA-Z0-9,\.\'\&\-" ]/', '', $organisationRow['name']);
                $organisationRow['slug'] = Str::slug($organisationRow['name'] . ' ' . uniqid(), '-');
                $organisationRow['created_at'] = Date::now();
                $organisationRow['updated_at'] = Date::now();

                /**
                 * Build the row index in case of clashes.
                 */
                $rowIndex[$i + 2] = [
                    'id' => $organisationRow['id'],
                    'name' => $organisationRow['name'],
                    'normalisedName' => str_replace(mb_str_split($this->normalisedCharacters), '', mb_strtolower(trim($organisationRow['name']))),
                    'index' => $i + 2,
                ];

                /**
                 * Create the User permission rows.
                 */
                foreach ($globalUserIds as $userId) {
                    $userRoleBatch[] = [
                        'id' => uuid(),
                        'user_id' => $userId,
                        'role_id' => $organisationAdminId,
                        'service_id' => null,
                        'organisation_id' => $organisationRow['id'],
                        'created_at' => Date::now(),
                        'updated_at' => Date::now(),
                    ];
                }

                /**
                 * Add the row to the batch array.
                 */
                $organisationRowBatch[] = $organisationRow;

                /**
                 * If the batch array has reach the import batch size create the insert queries.
                 */
                if (count($organisationRowBatch) === self::ROW_IMPORT_BATCH_SIZE) {
                    DB::table((new Organisation())->getTable())->insert($organisationRowBatch);
                    DB::table((new UserRole())->getTable())->insert($userRoleBatch);
                    $importedRows += self::ROW_IMPORT_BATCH_SIZE;
                    $organisationRowBatch = $userRoleBatch = [];
                }
            }

            /**
             * If there are a final batch that did not meet the import batch size, create queries for these.
             */
            if (count($organisationRowBatch) && count($organisationRowBatch) !== self::ROW_IMPORT_BATCH_SIZE) {
                DB::table((new Organisation())->getTable())->insert($organisationRowBatch);
                DB::table((new UserRole())->getTable())->insert($userRoleBatch);
                $importedRows += count($organisationRowBatch);
            }

            /**
             * Look for duplicates in the database.
             */
            $duplicates = $this->rowsExist($rowIndex);

            if (count($duplicates)) {
                /**
                 * If there are still duplicates despite having ignore_duplicates IDs.
                 * As this is an atomic process, all duplicates should be returned as none have been inserted.
                 */
                if (count($this->ignoreDuplicateIds)) {
                    $this->ignoreDuplicateIds = [];
                    $duplicates = $this->rowsExist($rowIndex);
                }
                /**
                 * Throws an exception which will be caught in self::processSpreadsheet.
                 */
                $this->formatDuplicates($duplicates, $spreadsheetParser->headers, $rowIndex);
            }
        }, 5);

        return $importedRows;
    }
}
