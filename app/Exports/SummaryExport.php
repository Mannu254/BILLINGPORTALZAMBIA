<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


class SummaryExport implements FromView,ShouldAutoSize,WithStyles
{
    
    public function __construct($today,$summary_cons,$individual_asset,$rental,$total_a3c,$total_a3m,$total_scn,$total_mon,$total_col,$individual_asset_totals,$details)
    {
    $this->today=$today;
    $this->summary_cons=$summary_cons;
    $this->individual_asset=$individual_asset;
     $this->total_a3c=$total_a3c;
     $this->rental=$rental;
    $this->total_a3m=$total_a3m;
     $this->total_scn=$total_scn;
     $this->individual_asset_totals=$individual_asset_totals;

     $this->total_mon=$total_mon;
     $this->total_col=$total_col;
     $this->details =$details;
       

          
    }

    public function view(): View
    {

        return view('VolumeAnalysis.CustomerSummary',['summary_cons'=>$this->summary_cons,'today'=>$this->today,'individual_asset'=>$this->individual_asset,'total_a3c'=>$this->total_a3c,'rental'=>$this->rental,'total_a3m'=>$this->total_a3m,'total_scn'=>$this->total_scn,'total_mon'=>$this->total_mon,'total_col'=>$this->total_col,'individual_asset_totals'=>$this->individual_asset_totals,'details'=>$this->details]);
    }
   
        public function styles(Worksheet $sheet)
        {
            return [
                // Style the first row as bold text.
                6   => ['font' => ['bold' => true]],
    
                // Styling a specific cell by coordinate.
                // 'B2' => ['font' => ['italic' => true]],
    
                // Styling an entire column.
                       
                
              
               
                
               
            ];
        }

   
  


  
    }


