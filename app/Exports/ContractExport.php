<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;



class ContractExport implements FromView,WithStyles
{
   
    public function __construct($contracts)
    {
    $this->contracts=$contracts;

    
    }

    public function view(): View
    {
   return view('MDS.contractsExport',['contracts'=>$this->contracts]);
  
    }
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],

            // Styling a specific cell by coordinate.
            // 'B2' => ['font' => ['italic' => true]],

            
           
        ];
    }
}
