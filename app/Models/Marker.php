<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marker extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'url',
        'checked',
        'price',
        'text_price',
        'type',
        'lat',
        'lon',
        'type',
        'currency',
        'usable_area',
        'rooms',
        'seller_type',
    ];

    const TYPE_RENT = 1;

    const CURRENCY_EURO = 1;
    const CURRENCY_RON = 2;

    const SELLER_OWNER = 0;
    const SELLER_AGENCY = 1;
}
