<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class _smtblWorker extends Model
{
    protected $connection = 'service_manager';
    protected $table ='_smtblWorker';
    public $timestamps = false;
}
