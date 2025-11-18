<?php

namespace App\Models;


use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Admin extends Authenticatable
{
    // use HasRoles;
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $guarded=[];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function imageSrc(): Attribute
    {
        return new Attribute(
            get: fn() => $this->image  ? getImage(getFilePath('admin') . '/' . $this->image, getFileSize('admin')) : siteFavicon(),
        );
    }
}
