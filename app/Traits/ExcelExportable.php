<?php

namespace App\Traits;

use App\Exports\DynamicExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait ExcelExportable
{
    /**
     * ડાયનેમિક Excel ડાઉનલોડ ફંક્શન
     * 
     * @param string $tableName ટેબલનું નામ
     * @param array $customColumns (ઓપ્શનલ) જો ચોક્કસ કોલમ જોઈતી હોય તો
     * @param array $conditions (ઓપ્શનલ) જેમ કે where conditions
     * @param string $fileName (ઓપ્શનલ) ફાઇલનું નામ
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportToExcel($tableName, $customColumns = [], $conditions = [], $fileName = null)
    {
        try {
            // 1. ટેબલની બધી કોલમ મેળવો (live schema પરથી, જેથી hardcoded list DB સાથે drift ના થાય)
            $allColumns = $this->getDynamicColumns($tableName);
            
            // 2. કઈ કોલમ export કરવી તે નક્કી કરો
            $exportColumns = !empty($customColumns) ? $customColumns : $allColumns;
            
            // 3. ડેટા મેળવો (જો conditions હોય તો)
            $query = DB::table($tableName)->select($exportColumns);
            
            // Conditions એપ્લાય કરો
            foreach ($conditions as $condition) {
                if (count($condition) == 3) {
                    $query->where($condition[0], $condition[1], $condition[2]);
                } elseif (count($condition) == 2) {
                    $query->where($condition[0], $condition[1]);
                }
            }
            
            $data = $query->get();
            
            // 4. ફાઇલનું નામ નક્કી કરો
            if (!$fileName) {
                $fileName = $tableName . '_' . date('Y-m-d_His') . '.xlsx';
            }
            
            // 5. Excel ડાઉનલોડ કરો
            return Excel::download(
                new DynamicExport($tableName, $exportColumns, $data), 
                $fileName
            );
            
        } catch (\Exception $e) {
            return back()->with('error', 'Excel export failed: ' . $e->getMessage());
        }
    }
    
    /**
     * ટેબલની live કોલમ DB સ્કીમા પરથી મેળવો
     */
    private function getDynamicColumns($tableName)
    {
        if (Schema::hasTable($tableName)) {
            return Schema::getColumnListing($tableName);
        }
        
        throw new \Exception("Table '{$tableName}' not found");
    }
}