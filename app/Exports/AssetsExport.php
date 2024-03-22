<?php
namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class AssetsExport implements FromView,WithStyles
{
    public function __construct($assets)
    {
    $this->assets=$assets;

    
    }

    public function view(): View
    {
   return view('SM.assets',['assets'=>$this->assets]);
  
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
