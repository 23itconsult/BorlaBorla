<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WastePickupPayment extends Model
{
    public function exportColumns(): array
    {
        return  [
            'ride_id' => [
                'name' => 'User/Household',
                'callback' =>  function ($item) {
                    return @$item->rider->username;
                }
            ],
            'driver_id' => [
                'name' => 'driver',
                'callback' =>  function ($item) {
                    return @$item->driver->username;
                }
            ],
            "amount" => [
                'name' => "amount",
                'callback' => function ($item) {
                    return showAmount($item->amount);
                }
            ],
            "created_at" => [
                'name' => "date",
                'callback' => function ($item) {
                    return showDateTime($item->created_at, lang: 'en');
                }
            ]
        ];
    }

    public function driver()
    {
        return $this->belongsTo(Collector::class, 'collector_id');
    }

    public function rider()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function ride()
    {
        return $this->belongsTo(WastePickup::class, 'pickup_id');
    }
}
