<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class MDSSalesRpt implements FromView,ShouldAutoSize
{
    public function __construct($con_sales)
    {
    $this->sales=$con_sales;
    
       
    }

    public function view(): View
    {
   return view('MDSSales.mdsSales',['sales'=>$this->sales]);
  
    }
    
}
