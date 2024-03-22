<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;



class AssetExportAll implements FromView,ShouldAutoSize,WithStyles
{
    public function __construct($assets_all)
    {
    $this->assets_all=$assets_all;

    
    }

    public function view(): View
    {
   return view('SM.assetsAll',['assets_all'=>$this->assets_all]);
  
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
