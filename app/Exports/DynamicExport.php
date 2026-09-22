<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DynamicExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $tableName;
    protected $columns;
    protected $data;
    protected $headings;
    protected $sheetTitle;

    public function __construct($tableName, $columns = [], $data = null, $sheetTitle = null)
    {
        $this->tableName = $tableName;
        // $this->columns = $columns;
        $this->columns = $this->moveDeletedAtToEnd($columns);
        $this->data = $data;
        $this->sheetTitle = $sheetTitle ?: $this->formatHeading($tableName);

        // headings તૈયાર કરો (કોલમના નામને વધારે readable બનાવો)
        $this->headings = array_map(function($column) {
            return $this->formatHeading($column);
        // }, $columns);
        }, $this->columns);
    }

    public function title(): string
    {
        // Excel sheet titles are capped at 31 chars
        return substr($this->sheetTitle, 0, 31);
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        if ($this->data) {
            // return collect($this->data);
             $data = collect($this->data);
        } else {
            // જો data ના આપ્યો હોય તો DB માંથી લો
            $data = DB::table($this->tableName)->select($this->columns)->get();
        }
        
        // જો data ના આપ્યો હોય તો DB માંથી લો
        // return DB::table($this->tableName)->select($this->columns)->get();

        if (!in_array('deleted_at', $this->columns, true)) {
            return $data;
        }

        // Keep active records first and move soft-deleted records to the bottom.
        return $data
            ->sortBy(fn ($row) => is_null(data_get($row, 'deleted_at')) ? 0 : 1)
            ->values();
    }

    /**
    * @return array
    */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
    * @param mixed $row
    * @return array
    */
    public function map($row): array
    {
        $mappedRow = [];
        
        foreach ($this->columns as $column) {
            $value = $row->$column ?? '';
            
            // JSON અથવા Array ડેટા માટે
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            
            // Null વેલ્યુ માટે
            if (is_null($value)) {
                $value = '';
            }
            
            $mappedRow[] = $value;
        }
        
        return $mappedRow;
    }

    /**
    * Excel સ્ટાઇલ માટે
    */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
    * હેડિંગને ફોર્મેટ કરો
    */
    private function formatHeading($column)
    {
        // underscore ને space માં બદલો અને પહેલા અક્ષર કેપિટલ કરો
        return ucwords(str_replace('_', ' ', $column));
    }

     /**
     * Keep soft-delete metadata as the final Excel column when it is exported.
     */
    private function moveDeletedAtToEnd(array $columns): array
    {
        if (!in_array('deleted_at', $columns, true)) {
            return $columns;
        }

        return [
            ...array_values(array_filter($columns, fn ($column) => $column !== 'deleted_at')),
            'deleted_at',
        ];
    }
}