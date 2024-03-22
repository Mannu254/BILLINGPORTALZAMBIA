<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReadingTemp implements FromView,ShouldAutoSize
{
  
    public function __construct($clients_machines)
    {
    $this->clients_machines=$clients_machines;
       
    }

    public function view(): View
    {
   return view('billing.reading_temp',['clients_machines'=>$this->clients_machines]);
  
    }
    
}

