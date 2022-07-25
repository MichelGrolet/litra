<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Model;

class Transactions extends Model
{
    protected $table = 'transactions';
    protected $primaryKey = 'id_transac';
    public $timestamps = false;
}
