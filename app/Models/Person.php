<?php

namespace App\Models;

use App\Http\Resources\Tenant\PersonCollection;
use App\Models\Traits\FormattedDates;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

/**
 * Class Person
 *
 * @package Acacha\Relationships\Models
 */
class Person extends Model implements HasMedia
{
    use HasMediaTrait, FormattedDates;


    protected $guarded = [];

    protected $appends = [
        'name',
        'fullname'
    ];

    /**
     * Get people.
     *
     * @return mixed
     */
    public static function getPeople()
    {
        return (new PersonCollection(Person::with(['user','user.googleUser'])->get()))->transform();
    }

    /**
     * Get the user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the identifier
     */
    public function identifier()
    {
        return $this->belongsTo(Identifier::class);
    }

    /**
     * Get the birthplace.
     */
    public function birthplace()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the address.
     */
    public function address()
    {
        return $this->hasOne(Address::class);
    }

    /**
     * Get the fullname.
     *
     * @param  string  $value
     * @return string
     */
    public function getFullnameAttribute($value)
    {
        return fullname($this->givenName, $this->sn1,  $this->sn2);
    }

    /**
     * Get the name.
     *
     * @param  string  $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        return name($this->givenName,$this->sn1, $this->sn2);
    }

    /**
     * Find by Identifier.
     *
     * @param $code
     * @return mixed
     */
    public static function findByIdentifier($identifier,$type = null)
    {
        if ($type === null) {
            $type = IdentifierType::findByName('NIF')->id;
        } else {
            if (is_object($type)) $type = $type->id;
            if (is_string($type)) {
                $type = IdentifierType::findByName($type)->id;
            }
        }
        $identifier = Identifier::where('value',$identifier)->where('type_id',$type)->first();
        if(!$identifier) return null;
        return self::where('identifier_id', $identifier->id)->first();
    }

    public function map()
    {
        return [
            'id' => $this->id,
            'userId' => optional($this->user)->id,
            'givenName' => $this->givenName,
            'sn1' => $this->sn1,
            'sn2' => $this->sn2,
            'name' => optional($this->user)->name,
            'email' => $this->email,
            'userEmail' => optional($this->user)->email,
            'corporativeEmail' => optional(optional($this->user)->googleUser)->google_email,
            'googleId' => optional(optional($this->user)->googleUser)->google_id,
            'email_verified_at' => optional($this->user)->email_verified_at,
            'mobile' => $this->mobile,
            'last_login' => optional($this->user)->last_login,
            'last_login_ip' => optional($this->user)->last_login_ip,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'formatted_created_at' => $this->formatted_created_at,
            'formatted_updated_at' => $this->formatted_updated_at,
            'created_at_timestamp' => $this->created_at_timestamp,
            'updated_at_timestamp' => $this->updated_at_timestamp,
            'admin' => optional($this->user)->admin,
            'hash_id' => optional($this->user)->hash_id,
        ];
    }
}
