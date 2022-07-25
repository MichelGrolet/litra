<?php

namespace litra\model;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use litra\model\Evenement;

class RVendeurEvenement extends Model {
	public $timestamps = false;
	public $incrementing = false;
	protected $table = 'rvendeurevenement';
	protected $primaryKey = ['id_vendeur', 'id_evenement'];

	/**
	 * Set the keys for a save update query.
	 *
	 * @param Builder $query
	 * @return Builder
	 */
	protected function setKeysFor(Builder $query): Builder {
		$keys = $this->getKeyName();
		if (!is_array($keys)) {
			return parent::setKeysForSaveQuery($query);
		}

		foreach ($keys as $keyName) {
			$query->where($keyName, '=', $this->getKeyForSaveQuery($keyName));
		}

		return $query;
	}

	/**
	 * Get the primary key value for a save query.
	 *
	 * @param mixed|null $keyName
	 * @return mixed
	 */
	protected function getKeyFor(mixed $keyName = null): mixed {
		if (is_null($keyName)) {
			$keyName = $this->getKeyName();
		}

		if (isset($this->original[$keyName])) {
			return $this->original[$keyName];
		}

		return $this->getAttribute($keyName);
	}
}
