<?php

namespace App\BatchImport;

use PhpOffice\PhpSpreadsheet\IOFactory;

class SpreadsheetParser
{
    /**
     * The spreadsheet import / export library.
     *
     * @var \PhpOffice\PhpSpreadsheet\Reader\Xlsx|\PhpOffice\PhpSpreadsheet\Reader\Xls
     */
    protected $reader;

    /**
     * Path to the spreadsheet file.
     *
     * @var string
     */
    protected $spreadsheetPath;

    /**
     * Reader filter to break file into chunks.
     *
     * @var ChunkReadFilter
     */
    protected $chunkFilter;

    /**
     * The spreadsheet reader chunk size.
     *
     * @var int
     */
    protected $chunkSize = 2048;

    /**
     * The imported header row.
     *
     * @var \array
     */
    public $headers = [];

    /**
     * The imported rows.
     *
     * @var \Illuminate\Support\Collection
     */
    public $rows = [];

    /**
     * Rows which failed to validate.
     *
     * @var \Illuminate\Support\Collection
     */
    public $errors;

    /**
     * Constructor.
     *
     * @param mixed $chunkSize
     */
    public function __construct($chunkSize = 2048)
    {
        $this->chunkFilter = new ChunkReadFilter();
        $this->chunkSize = $chunkSize;

        return $this;
    }

    /**
     * Import the Spreadsheet.
     */
    public function import(string $spreadsheetPath)
    {
        $this->spreadsheetPath = $spreadsheetPath;

        /**
         * Create the relevant reader based on the file type.
         */
        $fileType = IOFactory::identify($this->spreadsheetPath);
        $this->reader = IOFactory::createReader($fileType);
        $this->reader->setReadDataOnly(true);

        /**
         * Set the read filter.
         */
        $this->reader->setReadFilter($this->chunkFilter);

        return $this;
    }

    /**
     * Read the spreadsheet headers.
     *
     * @param type name
     *
     * @author
     */
    public function readHeaders()
    {
        $this->chunkFilter->setRows(1, 0);
        $spreadsheet = $this->reader->load($this->spreadsheetPath);
        $worksheet = $spreadsheet->getActiveSheet();

        /**
         * Limit the row iterator to the first row.
         */
        $headerRow = $worksheet->getRowIterator(1, 1)->current();

        /**
         * Build the headers row.
         * By default the cellIterator will only return populated cells.
         */
        foreach ($headerRow->getCellIterator() as $cell) {
            if (trim($cell->getValue())) {
                $this->headers[$cell->getColumn()] = $cell->getValue();
            }
        }

        /**
         * Free the spreadsheet from memory.
         */
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    /**
     * Read the spreadsheet in chunks
     * This method is an iterator and yields the rows.
     *
     * @param type name
     */
    public function readRows()
    {
        for ($startRow = 2; $startRow <= 65536; $startRow += $this->chunkSize) {
            $this->chunkFilter->setRows($startRow, $this->chunkSize);
            $spreadsheet = $this->reader->load($this->spreadsheetPath);
            $worksheet = $spreadsheet->getActiveSheet();

            /**
             * The read filter allows for the header row, so after all data rows have been
             * read the highest data row will be 1 (the header row). So if this is the
             * highest row we need to bail.
             */
            if ($worksheet->getHighestDataRow() == 1) {
                break;
            }

            /**
             * Iterate over the rows in chunks defined by $this->chunkSize.
             */
            foreach ($worksheet->getRowIterator($startRow) as $rowIterator) {
                $row = [];

                /**
                 * Limit the cell iterator by the columns used in the heading row.
                 */
                $cellIterator = $rowIterator->getCellIterator(array_key_first($this->headers), array_key_last($this->headers));

                /**
                 * Accept empty cells as not all cells will be populated.
                 */
                $cellIterator->setIterateOnlyExistingCells(false);

                /**
                 * Build the row from the cells.
                 */
                foreach ($cellIterator as $cell) {
                    if (isset($this->headers[$cell->getColumn()])) {
                        $row[$this->headers[$cell->getColumn()]] = $cell->getValue();
                    }
                }

                /**
                 * Yield the row.
                 */
                yield $row;
            }

            /**
             * Free the spreadsheet from memory.
             */
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }
}
