<?php

namespace Tests\Integration;

use App\BatchImport\SpreadsheetParser;
use App\Models\Organisation;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class SpreadsheetParserTest extends TestCase
{
    private $spreadsheet;

    private $xlsFilepath = 'test-spreadsheet.xls';

    private $xlsxFilepath = 'test-spreadsheet.xlsx';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $organisations = Organisation::factory()->count(20)->create();

        $headers = [
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $this->spreadsheet = self::createSpreadsheets($organisations->all(), $headers);

        self::writeSpreadsheetsToDisk($this->spreadsheet, $this->xlsxFilepath, $this->xlsFilepath);
    }

    public static function createSpreadsheets(array $rows, array $headers)
    {
        /** Create a new Spreadsheet Object **/
        $spreadsheet = new Spreadsheet();
        $columns = [];

        foreach ($headers as $key => $value) {
            $columns[] = $key < 26 ? chr($key + 65) : 'A'.chr(($key % 26) + 65);
        }

        /**
         * Create the headers in row 1
         */
        foreach ($headers as $i => $header) {
            $spreadsheet->getActiveSheet()->setCellValue($columns[$i].'1', $header);
        }

        /**
         * Populate all the other rows with data
         */
        $rowCount = 1;
        foreach ($rows as $row) {
            $rowCount++;
            foreach ($headers as $i => $header) {
                $spreadsheet->getActiveSheet()->setCellValue($columns[$i].$rowCount, $row[$header] ?? null);
            }
        }

        return $spreadsheet;
    }

    public static function writeSpreadsheetsToDisk(Spreadsheet $spreadsheet, string $xlsxFilepath, string $xlsFilepath)
    {
        $xlsxWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $xlsWriter = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xls');

        try {
            $xlsxWriter->save(Storage::disk('local')->path($xlsxFilepath));
            $xlsWriter->save(Storage::disk('local')->path($xlsFilepath));
        } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
            dump($e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
        Storage::disk('local')->delete([$this->xlsFilepath, $this->xlsxFilepath]);

        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_can_import_a_xls_spreadsheet(): void
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsFilepath));

        $spreadsheetParser->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetParser->headers);
    }

    /**
     * @test
     */
    public function it_can_import_a_xlsx_spreadsheet(): void
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsxFilepath));

        $spreadsheetParser->readHeaders();

        $this->assertEquals(['A' => 'name', 'B' => 'description', 'C' => 'url', 'D' => 'email', 'E' => 'phone'], $spreadsheetParser->headers);
    }

    /**
     * @test
     */
    public function it_can_read_rows_from_a_xls_spreadsheet(): void
    {
        $spreadsheetParser = new SpreadsheetParser();

        $spreadsheetParser->Import(Storage::disk('local')->path($this->xlsFilepath));

        $organisations = Organisation::all();

        $spreadsheetParser->readHeaders();

        foreach ($spreadsheetParser->readRows() as $row) {
            $this->assertTrue($organisations->contains('name', $row['name']));
        }
    }
}
