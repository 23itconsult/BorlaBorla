<?php

namespace App\Lib;

use App\Constants\Status;
use App\Models\WastePickupPayment;
use App\Models\Transaction;

class RidePaymentManager
{
    public function payment($ride, $paymentType)
    {
        $amount = $ride->amount - $ride->discount_amount;
        $collector = $ride->collector;
        $user   = $ride->user;
        
        \Log::info('ride driver: ' . $ride->driver);

        if ($paymentType ==  Status::PAYMENT_TYPE_GATEWAY) {

            $user->balance -= $amount;
            $user->save();

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '-';
            $transaction->trx          = $ride->uid;
            $transaction->remark       = 'payment';
            $transaction->details      = 'Waste pickup payment ' . showAmount($amount) . ' and waste pickup uid ' . $ride->uid . '';
            $transaction->save();
        }

        $this->ridePayment($ride, $paymentType);

        if ($paymentType ==  Status::PAYMENT_TYPE_GATEWAY) {

            \Log::info('driver: ' .  $collector->balance);

            $collector->balance += $amount;
            $collector->save();

            $transaction               = new Transaction();
            $transaction->collector_id    = $collector->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $collector->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->trx          = $ride->uid;
            $transaction->remark       = 'payment_received';
            $transaction->details      = 'Ride payment received ' . showAmount($amount) . ' and ride uid ' . $ride->uid . '';
            $transaction->save();

        }

        $commissionAmount  = $ride->commission_amount;
        $collector->balance  -= $commissionAmount;
        $collector->save();

        $transaction               = new Transaction();
        $transaction->collector_id    = $collector->id;
        $transaction->amount       = $commissionAmount;
        $transaction->post_balance = $collector->balance;
        $transaction->charge       = 0;
        $transaction->trx_type     = '-';
        $transaction->trx          = $ride->uid;
        $transaction->remark       = 'ride_commission';
        $transaction->details      = 'Subtract ride commission amount ' . showAmount($commissionAmount) . ' and ride uid ' . $ride->uid . '';
        $transaction->save();
    }

    public function ridePayment($ride, $paymentType)
    {
        $payment               = new WastePickupPayment();
        $payment->pickup_id      = $ride->id;
        $payment->user_id     = $ride->user_id;
        $payment->collector_id    = $ride->collector_id;
        $payment->amount       = $ride->amount - $ride->discount_amount;
        $payment->payment_type = $paymentType;
        $payment->save();

        $ride->payment_status = Status::PAID;
        $ride->payment_type   = $paymentType;
        $ride->status         = Status::PICKUP_COMPLETED;
        $ride->save();
    }
}
