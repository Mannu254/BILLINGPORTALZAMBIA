<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClientDS extends Model
{
    protected $connection = 'service_manager';
    protected $table ='Client';
    public $timestamps = false;
}
