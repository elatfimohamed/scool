<?php

namespace App\Models;

use App\Revisionable\Revisionable;

/**
 * Class Family.
 *
 * @package App\Models
 */
class Family extends Revisionable
{
    protected $guarded = [];

    /**
     * Identifiable name.
     *
     * @return string
     */
    public function identifiableName()
    {
        return $this->code;
    }

    /**
     * Find by code.
     *
     * @param $code
     * @return mixed
     */
    public static function findByCode($code)
    {
        return static::where('code','=',$code)->first();

    }

    /**
     * Get the jobs associated to the family.
     */
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
