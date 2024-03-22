<?php
namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class VolAnaysisExport implements FromView,ShouldAutoSize
{
  
    public function __construct($clients,$period)
    {
    $this->clients=$clients;
    $this->period=$period;
       
    }

    public function view(): View
    {
   return view('VolumeAnalysis.volanalysis',['clients'=>$this->clients],['period'=>$this->period]);
  
    }
    
}