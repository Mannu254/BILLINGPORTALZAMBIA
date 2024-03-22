<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class _smtblCodeMaster extends Model
{
    protected $connection = 'service_manager';
    protected $table ='_smtblCodeMaster';
    public $timestamps = false;
}

