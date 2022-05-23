<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Model;

class Evenement extends Model {
	public $timestamps = false;
	protected $table = 'evenement';
	protected $primaryKey = 'id_evenement';
	protected $fillable = ['img'];

	public function compteCreateur(): \Illuminate\Database\Eloquent\Relations\BelongsTo {
		return $this->belongsTo('mywishlist\models\Compte', ['id_compte']);
	}
}
