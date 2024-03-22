<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class stkSegment extends Model
{
    protected $connection = 'service_manager';
    protected $table ='_bvstockfullsegments';
    public $timestamps = false;
}
