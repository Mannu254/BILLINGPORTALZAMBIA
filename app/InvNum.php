<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class InvNum extends Model
{
 protected $table ='InvNum';
 protected $guarded = [];
 public $timestamps = false;
 protected $primaryKey = 'AutoIndex';


}
