<?php

namespace App\Http\Controllers\Api\Driver;

use App\Models\WastePickup;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Constants\Status;
use App\Events\NewPickup as EventsPickup;
use App\Lib\RidePaymentManager;
use App\Models\Zone;
use Illuminate\Support\Facades\Validator;

class WasteCollectionController extends Controller
{
    public function details($id)
    {
        $pickup = WastePickup::with(['bids', 'user', 'driver', 'service', 'userReview', 'driverReview', 'driver.brand'])->find($id);

        if (!$pickup) {
            $notify[] = 'This ride is unavailable';
            return apiResponse("not_found", 'error', $notify);
        }

        $notify[] = 'Pickup Details';
        return apiResponse("pickup_details", 'success', $notify, [
            'pickup' => $pickup,
            'user_image_path' => getFilePath('user'),
        ]);
    }


    // public function start(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'otp' => 'required|digits:6'
    //     ]);

    //     if ($validator->fails()) {
    //         return apiResponse('validation_error', 'error', $validator->errors()->all());
    //     }

    //     $wasteCollector = auth()->user();

    //     $pickup = WastePickup::where('collector_id', $wasteCollector->id)
    //         ->where('status', Status::PICKUP_ACTIVE)
    //         ->find($id);

    //     if (!$pickup) {
    //         $notify[] = 'The pickup not found or the pickup is not eligible to start yet.';
    //         return apiResponse('not_found', 'error', $notify);
    //     }

    //     $hasRunningPickup = WastePickup::where('collector_id', $wasteCollector->id)
    //         ->where('status', Status::PICKUP_PENDING)
    //         ->exists();

    //     if ($hasRunningPickup) {
    //         $notify[] = 'You have another running pickup. You must complete that pickup first.';
    //         return apiResponse('complete', 'error', $notify);
    //     }

    //     if ($pickup->otp != $request->otp) {
    //         $notify[] = 'The OTP code is invalid.';
    //         return apiResponse('invalid', 'error', $notify);
    //     }

    //     $commission = ($pickup->amount * $pickup->commission_percentage) / 100;

    //     $pickup->start_time = now();
    //     $pickup->status = Status::PICKUP_RUNNING; // Set status to running
    //     $pickup->commission_amount = $commission;
    //     $pickup->save();

    //     initializePusher();

    //     $pickup->load('collector', 'service', 'user');

    //     event(new EventsPickup($pickup, 'pick_up'));

    //     notify($pickup->user, 'START_PICKUP', [
    //         'pickup_id' => $pickup->uid,
    //         'amount' => showAmount($pickup->amount, currencyFormat: false),
    //         'collector' => $pickup->collector->username,
    //         'service' => $pickup->service->name,
    //         'pickup_location' => $pickup->pickup_location,
    //         'start_time' => showDateTime($pickup->start_time),
    //     ]);

    //     $notify[] = 'The pickup has been started successfully.';
    //     return apiResponse("pickup_start", "success", $notify);
    // }

    public function start(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $collector = auth()->user();

        // Find unassigned pending pickup
        $waste = WastePickup::where('status', Status::PICKUP_PENDING)
            ->where('collector_id', 0) // Not assigned to anyone yet
            ->find($id);

        if (!$waste) {
            $notify[] = 'The waste pickup request was not found or has already been taken';
            return apiResponse('not_found', 'error', $notify);
        }

        // Check if collector already has a running pickup
        $hasRunningPickup = WastePickup::running()
            ->where('collector_id', $collector->id)
            ->first();

        if ($hasRunningPickup) {
            $notify[] = 'You have another ongoing pickup. Please complete it before starting a new one.';
            return apiResponse('already_running', 'error', $notify);
        }

        // Verify OTP
        if ($waste->otp != $request->otp) {
            $notify[] = 'The OTP code is invalid';
            return apiResponse('invalid_otp', 'error', $notify);
        }

        // Calculate commission
        $commission = ($waste->amount * $waste->commission_percentage) / 100;

        // Update pickup - assign collector and start
        $waste->start_time = now();
        $waste->collector_id = $collector->id; // Assign to current collector
        $waste->status = Status::PICKUP_RUNNING;
        $waste->commission_amount = $commission;
        $waste->save();

        // Initialize Pusher and trigger event
        initializePusher();
        $waste->load('collector', 'service', 'user');
        event(new EventsPickup($waste, 'pick_up'));

        // Notify user
        notify($waste->user, 'WASTE_PICKUP_STARTED', [
            'pickup_id' => $waste->uid,
            'amount' => showAmount($waste->amount, currencyFormat: false),
            'collector' => $collector->username,
            'pickup_time' => showDateTime(now()),
        ]);

        $notify[] = 'The waste pickup has been started successfully';
        return apiResponse("pickup_started", "success", $notify);
    }

    public function end(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $collector = auth()->user();

        $pickup = WastePickup::where('collector_id', $collector->id)
            ->where('status', Status::PICKUP_RUNNING)
            ->find($id);

        if (!$pickup) {
            $notify[] = 'The pickup not found or is not eligible to end yet.';
            return apiResponse('not_found', 'error', $notify);
        }

        $pickup->payment_status = Status::PAYMENT_PENDING;
        $pickup->status = Status::PICKUP_COMPLETED;
        $pickup->end_time = now();
        $pickup->save();

        initializePusher();

        $pickup->load('collector', 'service', 'user');

        event(new EventsPickup($pickup, 'pickup_end'));

        notify($pickup->user, 'PICKUP_COMPLETED', [
            'pickup_id' => $pickup->uid,
            'amount' => showAmount($pickup->amount, currencyFormat: false),
            'collector' => $pickup->collector->username,
            // 'service' => $pickup->service->name,
            'pickup_location' => $pickup->pickup_location,
            'end_time' => showDateTime($pickup->end_time),
        ]);

        $notify[] = 'The pickup has been completed successfully. It is now available for payment.';
        return apiResponse("pickup_complete", 'success', $notify);
    }

    public function acceptPickup(Request $request, $id)
    {
        $collector = auth()->user();

        $hasRunningPickup = WastePickup::where('collector_id', $collector->id)
            ->where('status', Status::PICKUP_RUNNING)
            ->exists();

        if ($hasRunningPickup) {
            $notify[] = 'You already have a running pickup. Complete it before accepting a new one.';
            return apiResponse('already_has_running_pickup', 'error', $notify);
        }

        $pickup = WastePickup::where('id', $id)
            ->where('status', 'pickup_pending')
            ->where(function ($q) {
                $q->whereNull('collector_id')->orWhere('collector_id', 0);
            })
            ->first();

        if (!$pickup) {
            $notify[] = 'Pickup not found or already accepted by another collector.';
            return apiResponse('not_found', 'error', $notify);
        }

        $pickup->collector_id = $collector->id;
        $pickup->status = Status::PICKUP_RUNNING;
        $pickup->save();

        notify($pickup->user, 'PICKUP_ACCEPTED', [
            'pickup_id' => $pickup->uid,
            'collector' => $collector->username,
            'estimated_arrival' => '15 minutes'
        ]);

        $notify[] = 'Pickup accepted successfully';
        return apiResponse('pickup_accepted', 'success', $notify, [
            'pickup' => $pickup,
            'otp' => $pickup->otp
        ]);
    }

    public function list()
    {
        $collector = auth()->user();
        $query = WastePickup::with('user')->orderBy('id', 'desc')
            ->where('collector_id', $collector->id);

        if (request()->status == 'pending') {
            $query->whereNull('collector_id')->where('status', Status::PICKUP_PENDING)
                ->filter(['waste_type']);
        } elseif (request()->status == 'assigned') {
            $query->where('collector_id', $collector->id)
                ->where('status', Status::PICKUP_ACTIVE)
                ->filter(['waste_type']);
        } elseif (request()->status == 'completed') {
            $query->where('collector_id', $collector->id)
                ->where('status', Status::PICKUP_COMPLETED)
                ->filter(['waste_type']);
        } else {
            $query->where('collector_id', $collector->id)->filter(['status', 'waste_type']);
        }

        $pickups = $query->paginate(getPaginate());
        $notify[] = 'Pickup list';

        if (request()->status == 'pending' && $collector->online_status != Status::YES) {
            $pickups = null;
        }

        return apiResponse('pickup_list', 'success', $notify, [
            'pickups' => $pickups,
            'user_image_path' => getFilePath('user'),
        ]);
    }
    public function receivedCashPayment($id)
    {
        $driver = auth()->user();
        $pickup = WastePickup::where('status', Status::PICKUP_COMPLETED)->where('collector_id', $driver->id)->find($id);

        if (!$pickup) {
            $notify[] = 'The pickup not found';
            return apiResponse('not_found', 'error', $notify);
        }

        if (!$pickup) {
            $notify[] = 'The pickup is invalid';
            return apiResponse('not_found', 'error', $notify);
        }

        (new RidePaymentManager())->payment($pickup, Status::PAYMENT_TYPE_CASH);

        initializePusher();


        $pickup->load('user', 'collector', 'userReview', 'driverReview');
        event(new EventsPickup($pickup, 'cash-payment-received'));

        $notify[] = 'Payment received successfully';
        return apiResponse('payment_received', 'success', $notify, [
            'ride' => $pickup
        ]);
    }

    public function liveLocation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $collector = auth()->user();

        $collector->latitude = $request->latitude;
        $collector->longitude = $request->longitude;
        $collector->save();

        $pickup = WastePickup::where('collector_id', $collector->id)
            ->whereIn('status', [Status::PICKUP_ACTIVE, Status::PICKUP_RUNNING])
            ->first();

        if ($pickup) {
            initializePusher();
            event(new EventsPickup($pickup, 'live_location', $request->only(['latitude', 'longitude'])));
        }

        $notify[] = "Live location updated successfully";
        return apiResponse("live_location_updated", 'success', $notify, [
            'latitude' => $collector->latitude,
            'longitude' => $collector->longitude,
        ]);
    }

    public function rejectPickup(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $pickup = WastePickup::whereIn('status', [Status::PICKUP_PENDING, Status::PICKUP_RUNNING, Status::PICKUP_ACTIVE])->find($id);

        if (!$pickup) {
            $notify[] = 'Pickup not found';
            return apiResponse("not_found", 'error', $notify);
        }

        $cancelRideCount = WastePickup::where('collector_id', auth()->id())
            ->where('canceled_user_type', Status::DRIVER)
            ->count();

        if ($cancelRideCount >= gs('user_cancellation_limit')) {
            $notify[] = 'You have already exceeded the cancellation limit for this month';
            return apiResponse("limit_exceeded", 'error', $notify);
        }

        $pickup->cancel_reason = $request->cancel_reason;
        $pickup->canceled_user_type = Status::DRIVER;
        $pickup->status = Status::PICKUP_CANCELED;
        $pickup->cancelled_at = now();
        $pickup->save();

        if ($pickup->status == Status::PICKUP_ACTIVE) {
            notify($pickup->driver, 'CANCEL_pickup', [
                'ride_id' => $pickup->uid,
                'reason' => $pickup->cancel_reason,
                'amount' => showAmount($pickup->amount, currencyFormat: false),
                'service' => $pickup->service->name,
                'pickup_location' => $pickup->pickup_location,
            ]);
        }

        $notify[] = 'Pickup canceled successfully';
        return apiResponse("canceled_waste_pickup", 'success', $notify);
    }

    public function pickupStatus($status)
    {
        $collector = auth()->user();

        $status = (int) $status;
        $pickup = WastePickup::where('collector_id', $collector->id)
            ->where('status', $status)
            ->with('user', 'service')
            ->get();

        if (!$pickup) {
            $notify[] = 'Waste Pickup not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $notify[] = 'Waste Pickup list';
        $data['pickup'] = $pickup;
        return apiResponse('waste_pickup_list', 'success', $notify, $data);
    }

    public function getPickupStatus($id)
    {
        $collector = auth()->user();


        $pickup = WastePickup::select('status')
            ->where('id', $id)
            ->first();

        if (!$pickup) {
            $notify[] = 'Waste Pickup not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $notify[] = 'Waste Pickup status';
        $data['pickup'] = $pickup['status'];
        return apiResponse('waste_pickup_status', 'success', $notify, $data);
    }
    public function getPaymentStatus($id)
    {
        
        $pickup = WastePickup::select('payment_status')
            ->where('id', $id)
            ->first();

        if (!$pickup) {
            $notify[] = 'Waste Pickup not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $notify[] = 'Payment status';
        $data['pickup'] = $pickup['payment_status'];
        return apiResponse('waste_payment_status', 'success', $notify, $data);
    }
}
