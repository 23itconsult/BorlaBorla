<?php

namespace App\Models;
use App\Constants\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class WastePickup extends Model
{
    protected $guard = ['id'];

    public function exportColumns(): array
    {
        return [
            'rider_id' => [
                'name' => "User",
                "callback" => function ($item) {
                    return @$item->user->username;
                }
            ],
            'collector_id' => [
                'name' => "Collector",
                "callback" => function ($item) {
                    return @$item->driver->username;
                }
            ],
            'pickup_location' => [
                'name' => "Pickup Location",
            ],
            'disposal_site' => [
                'name' => "Disposal Site",
                "callback" => function ($item) {
                    return $item->disposal_site ?? 'N/A';
                }
            ],
            'amount' => [
                'name' => "Pickup Fee",
                "callback" => function ($item) {
                    return showAmount($item->amount);
                }
            ],
            'cancel_reason' => [
                'name' => "Cancel Reason",
                "callback" => function ($item) {
                    return $item->cancel_reason ?? 'N/A';
                }
            ],
            'canceled_user_type' => [
                'name' => "Cancel By",
                "callback" => function ($item) {
                    if ($item->canceled_user_type == 1) {
                        return 'User';
                    }
                    elseif ($item->canceled_user_type == 2) {
                        return 'Waste Collector';
                    }
                    else{
                        return 'N/A';
                    }
                }
            ],
        ];
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'applied_coupon_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function sosAlert()
    {
        return $this->hasMany(SosAlert::class);
    }

    public function userReview()
    {
        return $this->hasOne(Review::class)->where('collector_id', '0');
    }

    public function driverReview()
    {
        return $this->hasOne(Review::class)->where('user_id', 0);
    }

    public function payment()
    {
        return $this->hasOne(Deposit::class, 'pickup_id')->where('status', Status::PAYMENT_SUCCESS);
    }

    public function pickupZone()
    {
        return $this->belongsTo(Zone::class, 'pickup_zone_id');
    }
    public function destinationZone()
    {
        return $this->belongsTo(Zone::class, 'destination_zone_id');
    }
    public function acceptBid()
    {
        return $this->hasOne(Bid::class)->where('status', Status::BID_ACCEPTED);
    }
    public function driver()
    {
        return $this->belongsTo(Collector::class);
    }

    public function collector()
    {
        return $this->belongsTo(Collector::class, 'collector_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function service()
    {
        return $this->belongsTo(WasteService::class, 'id');
    }

    public function scopeCheckNoBid($query)
    {
        return $query->whereDoesntHave('bids', function ($q) {
            $q->where('driver_id', '=', auth()->id());
        });
    }

    public function scopeCanceled($query)
    {
        return $query->where('status', Status::PICKUP_CANCELED);
    }

    public function scopePending($query)
    {
        return $query->where('status', Status::PICKUP_PENDING);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', Status::PICKUP_RUNNING);
    }

    public function scopeNotRunning($query)
    {
        return $query->where('status', "!=", Status::PICKUP_RUNNING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', Status::PICKUP_COMPLETED);
    }

    public function scopeRidePaymentSuccess($query)
    {
        return $query->where('payment_status', Status::PAYMENT_SUCCESS);
    }

    public function statusBadge(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->status == Status::PICKUP_PENDING) {
                $html = '<span class="badge badge--primary">' . trans('Pending') . '</span>';
            } elseif ($this->status == Status::PICKUP_COMPLETED) {
                $html = '<span class="badge badge--success">' . trans('Completed') . '</span>';
            } elseif ($this->status == Status::PICKUP_ACTIVE) {
                $html = '<span class="badge badge--info">' . trans('Active') . '</span>';
            } elseif ($this->status == Status::PICKUP_RUNNING) {
                $html = '<span class="badge badge--warning">' . trans('Running') . '</span>';
            } elseif ($this->status == Status::PICKUP_CANCELED) {
                $html = '<span class="badge badge--danger">' . trans('Canceled') . '</span>';
            }
            return $html;
        });
    }

    public function paymentTypes(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->payment_type == Status::PAYMENT_TYPE_GATEWAY) {
                $html = '<span class="badge badge--warning">' . '<i class="far fa-credit-card me-2"></i>' . trans('Gateway') . '</span>';
            } elseif ($this->payment_type == Status::PAYMENT_TYPE_CASH) {
                $html = '<span class="badge badge--success">' . '<i class="fas fa-money-bill me-2"></i>' . trans('Cash') . '</span>';
            } else {
                $html = '<span class="badge badge--primary">' . '<i class="fas fa-wallet me-2"></i>' . trans('Wallet') . '</span>';
            }
            return $html;
        });
    }

    public function paymentStatusType(): Attribute
    {
        return new Attribute(function () {
            $html = '';
            if ($this->payment_status == Status::PAYMENT_SUCCESS) {
                $html = '<span class="badge badge--success">' . '<i class="las la-check me-2"></i>' . trans('Paid') . '</span>';
            } else {
                $html = '<span class="badge badge--warning">' . '<i class="las la-redo-alt me-2"></i>' . trans('Pending') . '</span>';
            }
            return $html;
        });
    }
}
