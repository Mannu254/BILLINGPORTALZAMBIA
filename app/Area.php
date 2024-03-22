<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $connection = 'service_manager';
    protected $table ='Areas';
    public $timestamps = false;
}
