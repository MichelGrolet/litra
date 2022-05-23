<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Model;
use litra\model\Evenement;

class Compte extends Model {
	public $timestamps = false;
	protected $table = 'compte';
	protected $primaryKey = 'id_compte';
	protected $fillable = ['num_tel', 'url_p'];
	private mixed $p_url;

	public function monnaies(): \Illuminate\Database\Eloquent\Relations\HasMany {
		return $this->hasMany('litra\model\RcompteMonnaie', ['id_compte', 'id_monnaie']);
	}

	public function creaMonnaies(): \Illuminate\Database\Eloquent\Relations\HasMany {
		return $this->hasMany('litra\model\Monnaie', ['id_compte']);
	}

	public function creaEvenements(): \Illuminate\Database\Eloquent\Relations\HasMany {
		return $this->hasMany('litra\model\Evenement', ['id_compte']);
	}

	public function evenementVendeur(): \Illuminate\Database\Eloquent\Relations\BelongsToMany {
		return $this->belongsToMany('litra\model\Evenement', 'rvendeurevenement', 'id_vendeur', 'id_evenement');
	}
}
