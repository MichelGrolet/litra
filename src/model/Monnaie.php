<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Model;

class Monnaie extends Model {
	public $timestamps = false;
	protected $table = 'monnaie';
	protected $primaryKey = 'id_monnaie';

	public function compte(): \Illuminate\Database\Eloquent\Relations\HasMany {
		return $this->hasMany('mywishlist\models\RcompteMonnaie', ['id_compte', 'id_monnaie']);
	}

	public function compteCreateur(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
		return $this->belongsTo('mywishlist\models\Compte', ['id_compte']);
	}
}
