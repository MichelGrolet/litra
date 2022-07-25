<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Model;
use litra\model\Evenement;

class Logrfid extends Model {
	public $timestamps = false;
	protected $table = 'logrfid';
	protected $primaryKey = 'id_log';
}
