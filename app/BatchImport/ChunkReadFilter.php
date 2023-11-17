<?php

namespace App\BatchImport;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    /**
     * Start row.
     *
     * @var int
     */
    private $startRow = 0;

    /**
     * End row.
     *
     * @var int
     */
    private $endRow = 0;

    /**
     * Set the start and end rows.
     *
     * @param int $endRow
     * @param mixed $chunkSize
     */
    public function setRows(int $startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    /**
     * Should the cell be read.
     * @param mixed $column
     * @param mixed $row
     * @param mixed $worksheetName
     */
    public function readCell($column, $row, $worksheetName = ''): bool
    {
        /**
         * Only read the first (header) row, or rows within the chunksize.
         */
        return ($row == 1) || ($row >= $this->startRow && $row < $this->endRow);
    }
}
