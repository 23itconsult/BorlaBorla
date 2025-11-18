<?php

namespace App\Http\Controllers\Api\Driver;

use App\Models\Ride;
use App\Constants\Status;
use App\Events\Ride as EventsRide;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Lib\RidePaymentManager;
use App\Models\Zone;
use Illuminate\Support\Facades\Validator;

class RideController extends Controller
{
    public function details($id)
    {
        $ride = Ride::with(['bids', 'user', 'driver', 'service', 'userReview', 'driverReview', 'driver.brand', 'service'])->find($id);

        if (!$ride) {
            $notify[] = 'This ride is unavailable';
            return apiResponse("not_found", 'error', $notify);
        }

        $notify[] = 'Ride Details';
        return apiResponse("ride_details", 'success', $notify, [
            'ride'            => $ride,
            'user_image_path' => getFilePath('user'),
        ]);
    }

    public function start(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6'
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $driver = auth()->user();
        $ride   = Ride::where('status', Status::RIDE_ACTIVE)->where('driver_id', $driver->id)->find($id);

        if (!$ride) {
            $notify[] = 'The ride not found or the ride not eligible to start yet';
            return apiResponse('not_found', 'error', $notify);
        }

        $hasRunningRide = Ride::running()->where('driver_id', $driver->id)->first();

        if ($hasRunningRide) {
            $notify[] = 'You have another running ride. You have to complete that running ride first.';
            return apiResponse('complete', 'error', $notify);
        }

        if ($ride->otp != $request->otp) {
            $notify[] = 'The OTP code is invalid';
            return apiResponse('invalid', 'error', $notify);
        }

        $commission              = $ride->amount / 100 * $ride->commission_percentage;
        $ride->start_time        = now();
        $ride->status            = Status::RIDE_RUNNING;
        $ride->commission_amount = $commission;
        $ride->save();

        initializePusher();

        $ride->load('driver', 'driver.brand', 'service', 'user');

        event(new EventsRide($ride, 'pick_up'));

        notify($ride->user, 'START_RIDE', [
            'ride_id'         => $ride->uid,
            'amount'          => showAmount($ride->amount, currencyFormat: false),
            'rider'           => $ride->user->username,
            'service'         => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination'     => $ride->destination,
            'duration'        => $ride->duration,
            'distance'        => $ride->distance,
            'pickup_time'     => showDateTime(now())
        ]);

        $notify[] = 'The ride has been started';
        return apiResponse("ride_start", "success", $notify);
    }
    

    public function end(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }


        $driver = auth()->user();
        $ride   = Ride::running()
            ->where('driver_id', $driver->id)
            ->find($id);

        if (!$ride) {
            $notify[] = 'The ride not found';
            return apiResponse('not_found', 'error', $notify);
        }

        // update the driver current zone
        if ($ride->ride_type == Status::INTER_CITY_RIDE) {
            $zones       = Zone::active()->get();
            $address     = ['lat' => $request->latitude, 'long' => $request->longitude];
            $currentZone = null;

            foreach ($zones as $zone) {
                $findZone = insideZone($address, $zone);
                if ($findZone) {
                    $currentZone = $zone;
                    break;
                }
            }
            if ($currentZone) {
                $driver->zone_id = $currentZone->id;
                $driver->save();
            }
        }

        $ride->payment_status = Status::PAYMENT_PENDING;
        $ride->status         = Status::RIDE_END;
        $ride->end_time       = now();
        $ride->save();

        initializePusher();

        $ride->load('driver', 'driver.brand', 'service', 'user');

        event(new EventsRide($ride, 'ride_end'));


        $notify[] = 'The ride is now available for payment';
        return apiResponse("ride_complete", 'success', $notify);
    }

    public function list()
    {
        $driver = auth()->user();
        $query  = Ride::with('user')->orderBy('id', 'desc')->where('service_id', @$driver->service_id);

        if (request()->status == 'accept') {
            $query->pending()
                ->whereHas('bids', function ($q) use ($driver) {
                    $q->where('status', Status::BID_PENDING)->where("driver_id", $driver->id);
                })
                ->filter(['ride_type']);
        } elseif (request()->status == 'new') {
            $query->where('pickup_zone_id', $driver->zone_id)
                ->pending()
                ->whereDoesntHave('bids', function ($q) use ($driver) {
                    $q->where("driver_id", $driver->id);
                })
                ->filter(['ride_type']);
        } elseif (request()->status == 'all') {
            $query->where('driver_id', auth()->id())
                ->orWhereHas('bids', function ($q) use ($driver) {
                    $q->where('status', Status::BID_PENDING)->where("driver_id", $driver->id);
                })
                ->filter(['ride_type']);
        } else {
            $query->where('driver_id', auth()->id())->filter(['status', 'ride_type']);
        }

        $rides    = $query->paginate(getPaginate());
        $notify[] = 'Ride list';

        if (request()->status == 'new' && $driver->online_status != Status::YES) {
            $rides = null;
        }

        return apiResponse('ride_list', 'success', $notify, [
            'rides'           => $rides,
            'user_image_path' => getFilePath('user'),
        ]);
    }

    public function receivedCashPayment($id)
    {
        $driver = auth()->user();
        $ride   = Ride::where('status', Status::RIDE_END)->where('driver_id', $driver->id)->find($id);

        if (!$ride) {
            $notify[] = 'The ride not found';
            return apiResponse('not_found', 'error', $notify);
        }

        if (!$ride) {
            $notify[] = 'The ride is invalid';
            return apiResponse('not_found', 'error', $notify);
        }

        (new RidePaymentManager())->payment($ride, Status::PAYMENT_TYPE_CASH);

        initializePusher();


        $ride->load('bids', 'user', 'driver', 'service', 'userReview', 'driverReview', 'driver.brand');
        event(new EventsRide($ride, 'cash-payment-received'));

        $notify[] = 'Payment received successfully';
        return apiResponse('payment_received', 'success', $notify, [
            'ride' => $ride
        ]);
    }

    public function liveLocation(Request $request, $id)
    {
        // var_dump($request);
        // exit();
        $validator = Validator::make($request->all(), [
            'latitude'  => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $ride   = Ride::find($id);
        if (!$ride) {
            $notify[] = 'The ride not found';
            return apiResponse('not_found', 'error', $notify);
        }

        if ($ride->status == Status::RIDE_ACTIVE || $ride->status == Status::RIDE_RUNNING) {
            initializePusher();
            event(new EventsRide($ride, 'live_location', $request->only(['latitude', 'longitude'])));
        }

        $notify[] = "live location change";
        return apiResponse("live_location", 'success', $notify);
    }
}
