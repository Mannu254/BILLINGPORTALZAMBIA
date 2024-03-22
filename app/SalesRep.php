<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalesRep extends Model
{
    protected $connection = 'service_manager';
    protected $table ='SalesRep';
    public $timestamps = false;
}
