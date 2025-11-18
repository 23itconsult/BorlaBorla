<?php

namespace App\Http\Controllers\Api\User;

use App\Events\NewPickup;
use App\Http\Controllers\Controller;
use App\Models\Collector;
use App\Models\WasteService;
use Illuminate\Http\Request;
use App\Models\WastePickup;
use App\Models\Zone;
use App\Models\Coupon;
use App\Models\Driver;
use App\Models\Service;
use App\Models\SosAlert;
use App\Constants\Status;
use App\Events\NewRide;
// use App\Events\Ride as EventsRide;
use App\Events\WastePickup as EventsWastePickup;
use App\Models\GatewayCurrency;
use App\Models\AdminNotification;
use App\Models\Bid;
// use App\Gateway\Calbank\CashCollectionUniversalController;
use App\Models\Deposit;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Gateway\Calbank\CashCollectionUniversalController;

use App\Http\Controllers\Driver\WasteCollectionController as DriverWasteCollection;



class WasteCollectionController extends Controller
{

    public function findWasteAmount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'waste_type' => 'required',
            'price_model' => ['required', Rule::in(['per_bag', 'per_weight'])],
            'quantity' => 'required_if:price_model,per_bag|numeric|min:1',
            'weight' => 'required_if:price_model,per_weight|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }


        $data['amount_due'] = $this->calculateAmount($request);
        return apiResponse("pickup_data", 'success', $data);
    }

    public function calculateAmount(Request $request)
    {
        $service = WasteService::active()->find($request->waste_type);

        if (!$service) {
            $notify[] = 'This waste type is currently unavailable';
            return apiResponse("not_found", 'error', $notify);
        }

        // $wasteType = $request->waste_type;
        $pricePerBag = $service["price_per_bag"];
        $pricePerKg = $service["price_per_kg"];

        $calculatedAmount = 0;

        switch ($request->price_model) {
            case 'per_bag':
                $calculatedAmount = $pricePerBag * $request->quantity;
                break;
            case 'per_weight':
                $calculatedAmount = $pricePerKg * $request->weight;
                break;
        }



        return $calculatedAmount;
    }
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'waste_type' => 'required|integer|exists:waste_services,id',
            'pickup_latitude' => 'required|numeric',
            'pickup_longitude' => 'required|numeric',
            'note' => 'nullable|string',
            'price_model' => ['required', Rule::in(['per_bag', 'per_weight'])],
            'quantity' => 'required_if:price_model,per_bag|numeric|min:1',
            'weight' => 'required_if:price_model,per_weight|numeric|min:1',
            'amount_due' => 'required|numeric',
            'payment_type' => ['required', Rule::in(Status::PAYMENT_TYPE_GATEWAY, Status::PAYMENT_TYPE_CASH)],
            'gateway_currency_id' => $request->payment_type == Status::PAYMENT_TYPE_GATEWAY ? 'required|exists:gateway_currencies,id' : 'nullable',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $service = WasteService::active()->find($request->waste_type);

        if (!$service) {
            $notify[] = 'This waste type is currently unavailable';
            return apiResponse("not_found", 'error', $notify);
        }

        $calculatedAmount = $this->calculateAmount($request);

        $wasteType = $request->waste_type;
        $commissionPercentage = $service["commission"];

        $commission = ($request->amount_due * $commissionPercentage) / 100;
        $calculatedAmount = $this->calculateAmount($request);
        if ($request->amount_due != $calculatedAmount) {
            $notify[] = 'The offer amount must be equal to the total amount to be paid: ' . showAmount($calculatedAmount);
            return apiResponse('offer_amount_mismatch', 'error', $notify);
        }

        $user = auth()->user();

        $uncompletedPickups = WastePickup::where('user_id', $user->id)
            ->whereIn('status', ['pickup_pending', 'pickup_active', 'pickup_running'])
            ->exists();

        if ($uncompletedPickups) {
            $notify[] = 'You already have an uncompleted pickup. Please complete or cancel it before creating a new one.';
            return apiResponse('uncompleted_pickup_exists', 'error', $notify);
        }

        $pickupZone = $this->getZone($request)['pickup_zone'] ?? null;

        if ($pickupZone == null) {
            $notify[] = 'Your location is not within the zone you have selected';
            return apiResponse('location_error', 'error', $notify);
        }

        do {
            $otp = mt_rand(100000, 999999);
        } while (WastePickup::where('otp', $otp)->exists());

        $googleMapData = $this->getGoogleMapData($request);

        if (!isset($googleMapData['pickup_location'])) {
            $notify[] = $googleMapData['message'] ?? 'Unable to retrieve pickup location.';
            return apiResponse('pickup_location_error', 'error', $notify);
        }

        $waste = new WastePickup();
        $waste->uid = getTrx(10);
        $waste->user_id = $user->id;
        $waste->pickup_location = $googleMapData['pickup_location'];
        $waste->pickup_latitude = $request->pickup_latitude;
        $waste->pickup_longitude = $request->pickup_longitude;
        $waste->pickup_zone_id = optional($pickupZone)->id;
        $waste->note = $request->note;
        $waste->waste_type = $wasteType;
        $waste->amount = $calculatedAmount;
        $waste->commission_percentage = $commissionPercentage;
        $waste->commission_amount = $commission;
        $waste->payment_type = $request->payment_type;
        $waste->gateway_currency_id = $request->payment_type == Status::PAYMENT_TYPE_GATEWAY ? $request->gateway_currency_id : 0;
        $waste->otp = $otp;
        $waste->status = 'pickup_pending';
        $waste->save();

        $waste_collectors = Collector::active()
            ->where('online_status', Status::YES)
            ->where('zone_id', $waste->pickup_zone_id)
            ->where('dv', Status::VERIFIED)
            ->where('vv', Status::VERIFIED)
            ->notRunning()
            ->get();

        // $shortCode = [
        //     'pickup_id' => $waste->uid,
        //     'waste_type' => $service->name,
        //     'pickup_location' => 'Lat: ' . $waste->pickup_latitude . ', Lng: ' . $waste->pickup_longitude,
        //     'amount_due' => $waste->amount_due,
        // ];

        $shortCode = [
            'pickup_id' => $waste->uid,
            'pickup_location' => $waste->pickup_location,
            'waste_type' => $waste->waste_type,
            'otp' => $otp,
        ];

        $waste->load('user', 'collector', 'service');

        initializePusher();

        foreach ($waste_collectors as $collector) {
            notify($collector, 'NEW_WASTE_PICKUP', $shortCode);
            event(new NewPickup("new-waste-for-collector-$collector->id", [
                'pickup' => $waste,
                'collector_image_path' => getFilePath('collector'),
                'user_image_path' => getFilePath('user'),
            ]));
        }

        $notify[] = 'Waste pickup created successfully';
        return apiResponse('pickup_create_success', 'success', $notify, [
            'pickup' => $waste
        ]);
    }
    public function details($id)
    {
        $pickup = WastePickup::with(['bids', 'userReview', 'driver', 'service', 'driver.brand'])->where('user_id', auth()->id())->find($id);

        if (!$pickup) {
            $notify[] = 'Invalid pickup';
            return apiResponse('not_found', 'error', $notify);
        }
        $notify[] = 'pickup Details';
        return apiResponse('pickup_details', 'success', $notify, [
            'ride' => $pickup,
            'service_image_path' => getFilePath('service'),
            'brand_image_path' => getFilePath('brand'),
            'user_image_path' => getFilePath('user'),
            'driver_image_path' => getFilePath('driver'),
        ]);
    }

    public function cancel(Request $request, $id)
    {

        $validator = Validator::make($request->all(), [
            'cancel_reason' => 'required',
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }

        $pickup = WastePickup::whereIn('status', [Status::PICKUP_PENDING, Status::PICKUP_ACTIVE])->where('user_id', auth()->id())->find($id);

        if (!$pickup) {
            $notify[] = 'Pickup not found';
            return apiResponse("not_found", 'error', $notify);
        }

        $cancelRideCount = WastePickup::where('user_id', auth()->id())
            ->where('canceled_user_type', Status::USER)
            ->count();

        if ($cancelRideCount >= gs('user_cancellation_limit')) {
            $notify[] = 'You have already exceeded the cancellation limit for this month';
            return apiResponse("limit_exceeded", 'error', $notify);
        }

        $pickup->cancel_reason = $request->cancel_reason;
        $pickup->canceled_user_type = Status::USER;
        $pickup->status = Status::PICKUP_CANCELED;
        $pickup->cancelled_at = now();
        $pickup->save();

        if ($pickup->status == Status::PICKUP_ACTIVE) {
            notify($pickup->driver, 'CANCEL_PICKUP', [
                'ride_id' => $pickup->uid,
                'reason' => $pickup->cancel_reason,
                'amount' => showAmount($pickup->amount, currencyFormat: false),
                'pickup_location' => $pickup->pickup_location,
            ]);
        }

        $notify[] = 'Pickup canceled successfully';
        return apiResponse("canceled_ride", 'success', $notify);
    }

    public function sos(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'message' => 'nullable',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $ride = WastePickup::running()->where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'The ride is not found';
            return apiResponse('invalid_ride', 'error', $notify);
        }

        $sosAlert = new SosAlert();
        $sosAlert->ride_id = $id;
        $sosAlert->latitude = $request->latitude;
        $sosAlert->longitude = $request->longitude;
        $sosAlert->message = $request->message;
        $sosAlert->save();

        $adminNotification = new AdminNotification();
        $adminNotification->user_id = $ride->user->id;
        $adminNotification->title = 'A new SOS Alert has been created, please take action';
        $adminNotification->click_url = urlPath('admin.pickups.detail', $ride->id);
        $adminNotification->save();

        $notify[] = 'SOS request successfully';
        return apiResponse("sos_request", "success", $notify);
    }


    public function list()
    {
        $pickup = WastePickup::with(['driver', 'user', 'service'])
            ->filter(['waste_type', 'status'])
            ->where('user_id', auth()->id())
            ->orderBy('id', 'desc')
            ->paginate(getPaginate());

        $notify[] = "Get the waste pickup list";
        $data['pickup'] = $pickup;
        return apiResponse("pickup_list", 'success', $notify, $data);
    }

    private function getZone($request): array
    {
        $zones = Zone::active()->get();
        $pickupAddress = ['lat' => $request->pickup_latitude, 'long' => $request->pickup_longitude];
        $pickupZone = null;

        foreach ($zones as $zone) {
            $pickupZone = insideZone($pickupAddress, $zone);
            if ($pickupZone) {
                $pickupZone = $zone;
                break;
            }
        }

        if (!$pickupZone) {
            return [
                'status' => 'error',
                'message' => 'The pickup location is not inside any of our zones'
            ];
        }



        return [
            'pickup_zone' => $pickupZone,
            // 'destination_zone' => $destinationZone,
            'status' => 'success'
        ];
    }
    private function getGoogleMapData($request)
    {
        $apiKey = gs('google_maps_api'); // Ensure you have the Google Maps API key in your settings
        $pickupLatitude = $request->pickup_latitude;
        $pickupLongitude = $request->pickup_longitude;

        // Use the Geocoding API to get the address from latitude and longitude
        $geocodeUrl = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$pickupLatitude},{$pickupLongitude}&key={$apiKey}";

        // \Log::info('Geocoding Request URL: ' . $geocodeUrl);


        // Fetch data from the Geocoding API
        $geocodeResponse = file_get_contents($geocodeUrl);

        // Check if the API call failed
        if ($geocodeResponse === false) {
            return [
                'status' => 'error',
                'message' => 'Failed to connect to Google Maps API.'
            ];
        }

        $geocodeData = json_decode($geocodeResponse, true); // Decode as associative array

        // Check if the Geocoding API response is valid
        if ($geocodeData['status'] != 'OK') {
            return [
                'status' => 'error',
                'message' => 'Failed to fetch pickup location from Google Maps.'
            ];
        }

        // Extract the formatted address from the Geocoding API response
        if (empty($geocodeData['results'][0]['formatted_address'])) {
            return [
                'status' => 'error',
                'message' => 'No address found for the given coordinates.'
            ];
        }

        $pickupLocation = $geocodeData['results'][0]['formatted_address'];

        return [
            'status' => 'success',
            'pickup_location' => $pickupLocation, // Pickup location address
        ];
    }

    public function bids($id)
    {
        $ride = WastePickup::where('user_id', auth()->id())->find($id);

        if (!$ride) {
            $notify[] = 'The ride is not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $bids = Bid::with(['driver', 'driver.service', 'driver.brand'])->where('ride_id', $ride->id)->whereIn('status', [Status::BID_PENDING, Status::BID_ACCEPTED])->get();
        $notify[] = 'All Bid';

        return apiResponse("bids", "success", $notify, [
            'bids' => $bids,
            'ride' => $ride,
            'driver_image_path' => getFilePath('driver'),
            'user_image_path' => getFilePath('user'),
        ]);
    }

    public function accept($bidId)
    {
        $bid = Bid::pending()->with('ride')->whereHas('ride', function ($q) {
            return $q->pending()->where('user_id', auth()->id());
        })->find($bidId);

        if (!$bid) {
            $notify[] = 'Invalid bid';
            return apiResponse('not_found', 'error', $notify);
        }

        $bid->status = Status::BID_ACCEPTED;
        $bid->accepted_at = now();
        $bid->save();

        //all the bid rejected after the one accept this bid
        Bid::where('id', '!=', $bid->id)->where('ride_id', $bid->ride_id)->update(['status' => Status::BID_REJECTED]);

        $ride = $bid->ride;
        $ride->status = Status::PICKUP_ACTIVE;
        $ride->driver_id = $bid->driver_id;
        $ride->otp = getNumber(6);
        $ride->amount = $bid->bid_amount;
        $ride->save();

        $ride->load('driver', 'driver.brand', 'service', 'user');

        initializePusher();

        event(new NewRide("new-ride-for-driver-$ride->driver_id", ['ride' => $ride], 'bid_accept'));

        notify($ride->driver, 'ACCEPT_RIDE', [
            'ride_id' => $ride->uid,
            'amount' => showAmount($ride->amount),
            'rider' => $ride->user->username,
            'service' => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination' => $ride->destination,
            'duration' => $ride->duration,
            'distance' => $ride->distance
        ]);

        $notify[] = 'Bid accepted successfully';
        return apiResponse('accepted', 'success', $notify, [
            'ride' => $ride
        ]);
    }

    public function reject($id)
    {
        $bid = Bid::pending()->with('ride')->find($id);

        if (!$bid) {
            $notify[] = 'Invalid bid';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride = $bid->ride;
        if ($ride->user_id != auth()->id()) {
            $notify[] = 'This ride is not for this rider';
            return apiResponse('unauthenticated', 'error', $notify);
        }

        $bid->status = Status::BID_REJECTED;
        $bid->save();

        initializePusher();

        event(new EventsRide($ride, 'bid_reject'));

        notify($ride->user, 'BID_REJECT', [
            'ride_id' => $ride->uid,
            'amount' => showAmount($bid->bid_amount),
            'service' => $ride->service->name,
            'pickup_location' => $ride->pickup_location,
            'destination' => $ride->destination,
            'duration' => $ride->duration,
            'distance' => $ride->distance
        ]);

        $notify[] = 'Bid rejected successfully';

        return apiResponse('rejected_bid', 'success', $notify);
    }

    public function payment($id)
    {
        $ride = WastePickup::where('user_id', auth()->id())->find($id);
       
        if (!$ride) {
            $notify[] = 'The waste pickup is not found';
            return apiResponse('not_found', 'error', $notify);
        }

        $ride->load('collector', 'collector.brand', 'user', 'coupon');

        $gatewayCurrency = GatewayCurrency::whereHas('method', function ($gate) {
            $gate->active()->automatic();
        })->with('method')->orderby('method_code')->get();

        $notify[] = "Waste Pickup Payments";
        return apiResponse('payment', 'success', $notify, [
            'gateways' => $gatewayCurrency,
            'image_path' => getFilePath('gateway'),
            'pickup' => $ride,
            'coupons' => Coupon::orderBy('id', 'desc')->active()->get(),
            'driver_image_path' => getFilePath('driver'),
        ]);
    }

    public function paymentSave(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'payment_type' => ['required', Rule::in(Status::PAYMENT_TYPE_GATEWAY, Status::PAYMENT_TYPE_CASH)],
            'method_code' => 'required_if:payment_type,1',
            'currency' => 'required_if:payment_type,1',
            'paymentgateway'=>'required'
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }
        $pickup = WastePickup::Select("waste_services.*","waste_pickups.*")->where('waste_pickups.user_id', auth()->id())
        ->where('waste_pickups.id',$id)
    ->join('waste_services','waste_services.id','=','waste_pickups.waste_type')
        ->first();
        if (!$pickup) {
            $notify[] = 'The pickup is not found';
            return apiResponse('not_found', 'error', $notify);
        }
        if ($request->payment_type == Status::PAYMENT_TYPE_GATEWAY) {
            return $this->paymentViaGateway($request, $pickup);
        } else {
            initializePusher();
            $pickup->load('collector', 'user', 'service');
            event(new EventsWastePickup($pickup, 'cash-payment-request'));
            $notify[] = "Please give the waste collector " . showAmount($pickup->amount) . " in cash.";
            return apiResponse('cash_payment', 'success', $notify, [
                'pickup' => $pickup
            ]);
        }
    }

    private function paymentViaGateway($request, $pickup)
    {
        $amount = $pickup->amount - $pickup->discount_amount;

        $calBankpayment=app(CashCollectionUniversalController::class);

        $gateway = GatewayCurrency::whereHas('method', function ($gateway) {
            $gateway->active()->automatic();
        })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();

        if (!$gateway) {
            $notify[] = "Invalid gateway selected";
            return apiResponse('not_found', 'error', $notify);
        }

        if ($gateway->min_amount > $amount) {
            $notify[] = 'Minimum limit for this gateway is ' . showAmount($gateway->min_amount);
            return apiResponse('limit_exists', 'error', $notify);
        }
        if ($gateway->max_amount < $amount) {
            $notify[] = 'Maximum limit for this gateway is ' . showAmount($gateway->max_amount);
            return apiResponse('limit_exists', 'error', $notify);
        }

        $charge = 0;
        $payable = $amount + $charge;
        $finalAmount = $payable * $gateway->rate;
        $trx=getTrx();
        $user = auth()->user();
        $discount=$pickup->discount_amount;
        $paymentgateway=$request->paymentgateway;
//         $payment = $calBankpayment->CreateIvoiceAndPayment($trx,$PaymentGateType,
// $payment_account,$itemsArray,$Item_name,$qty);
// return $pickup;
$payment = $calBankpayment->CreateIvoiceAndPayment($trx,$pickup,
$user, $finalAmount,$request->input("payment_number"),$discount,$paymentgateway);

//  return $payment;
  $jsonString = $payment->getContent();
                    // Decode the JSON string into an associative array
 $payment_res = json_decode($jsonString, true);

if($payment_res['status']=='error'){
     return apiResponse('not_found', 'error',[
        'message'=>$payment_res['message']
     ]);
}
  $payment_data= $payment_res['message']['RESULT'][0];
 
        $data = new Deposit();
        $data->from_api = 1;
        $data->user_id = $user->id;
        $data->collector_id = $pickup->collector_id;
        $data->method_code = $gateway->method_code;
        $data->method_currency = strtoupper($gateway->currency);
        $data->amount = $amount;
        $data->charge = $charge;
        $data->rate = $gateway->rate;
        $data->final_amount = $finalAmount;
        $data->pickup_id = $pickup->id;
        $data->btc_amount = 0;
        $data->btc_wallet = "";
        $data->success_url = urlPath('user.deposit.history');
        $data->failed_url = urlPath('user.deposit.history');
        $data->trx = $trx;
        $data->payment_token=$payment_data['PAYMENTTOKEN'];
        $data->save();
        $notify[] = "Online Payment";
        return apiResponse("gateway_payment", "success", $notify, [
            'deposit' => $data,
            'url_payment'=>$payment_data['APIPAYREDIRECTURL'],
            'payment_token'=>$payment_data['PAYMENTTOKEN'],
            'redirect_url' => route('deposit.app.confirm', encrypt($data->id))
        ]);
    }

    // private function paymentViaGateway($request, $pickup)
    // {
    //     $amount = $pickup->amount - $pickup->discount_amount;

    //     $gateway = GatewayCurrency::whereHas('method', function ($gateway) {
    //         $gateway->active()->automatic();
    //     })->where('method_code', $request->method_code)->where('currency', $request->currency)->first();

    //     if (!$gateway) {
    //         $notify[] = "Invalid gateway selected";
    //         return apiResponse('not_found', 'error', $notify);
    //     }

    //     if ($gateway->min_amount > $amount) {
    //         $notify[] = 'Minimum limit for this gateway is ' . showAmount($gateway->min_amount);
    //         return apiResponse('limit_exists', 'error', $notify);
    //     }
    //     if ($gateway->max_amount < $amount) {
    //         $notify[] = 'Maximum limit for this gateway is ' . showAmount($gateway->max_amount);
    //         return apiResponse('limit_exists', 'error', $notify);
    //     }

    //     $charge = 0;
    //     $payable = $amount + $charge;
    //     $finalAmount = $payable * $gateway->rate;
    //     $user = auth()->user();

    //     $data = new Deposit();
    //     $data->from_api = 1;
    //     $data->user_id = $user->id;
    //     $data->collector_id = $pickup->collector_id;
    //     $data->method_code = $gateway->method_code;
    //     $data->method_currency = strtoupper($gateway->currency);
    //     $data->amount = $amount;
    //     $data->charge = $charge;
    //     $data->rate = $gateway->rate;
    //     $data->final_amount = $finalAmount;
    //     $data->pickup_id = $pickup->id;
    //     $data->btc_amount = 0;
    //     $data->btc_wallet = "";
    //     $data->success_url = urlPath('user.deposit.history');
    //     $data->failed_url = urlPath('user.deposit.history');
    //     $data->trx = getTrx();
    //     $data->save();

    //     $notify[] = "Online Payment";

    //     return apiResponse("gateway_payment", "success", $notify, [
    //         'deposit' => $data,
    //         'redirect_url' => route('deposit.app.confirm', encrypt($data->id))
    //     ]);
    // }


    public function ConfirmPayment(request $request){
        $validator = Validator::make($request->all(), [
            'TokenId'=>'required'
        ]);

        if ($validator->fails()) {
            return apiResponse("validation_error", 'error', $validator->errors()->all());
        }
        $token_id=$request->input('TokenId');
        $check_token= Deposit::where('payment_token', $token_id)->first();
        if(!$check_token){
        return apiResponse("validation_error", 'error',['invalide payment token id']);  
        }
        $calBankpayment=app(CashCollectionUniversalController::class);
        $Confirm_payment = $calBankpayment->ConfirmPayment($token_id);
          return $Confirm_payment;
  $jsonString =  $Confirm_payment->getContent();


                    // Decode the JSON string into an associative array
 $payment_res = json_decode($jsonString, true);

if($payment_res['status']=='error'){
     return apiResponse('not_found', 'error',[
        'message'=>$payment_res['message']
     ]);
}

$payment_data= $payment_res['message']['RESULT'][0];

$payment_data=["TRNID"=>$payment_data["TRNID"],"FINALSTATUS"=>$payment_data["FINALSTATUS"]];


if($payment_data["TRNID"]==''){
    return apiResponse('payment_pending', 'error',[
        'message'=>'Payment pending approval'
     ]);    
}

$FinalisePyament=app(DriverWasteCollection::class);
$payment_recieve=$FinalisePyament;

    return json_encode($payment_data);
}
//       "TRNID": "",
//   "QSESSIONID": null,
//   "CPWALLETGHSMAXAMT": 500,
//   "FINALSTATUS": "PENDING",

    public function collectorDetails($id)
    {
        $collector = Collector::select(
            'collectors.id',
            'collectors.firstname',
            'collectors.lastname',
            'collectors.username',
            'collectors.mobile',
            'collectors.avg_rating',
            'collectors.total_reviews',
            'collectors.image',
            'brands.name as brand_name' // Select brand name
        )
            ->leftJoin('brands', 'collectors.brand_id', '=', 'brands.id')
            ->where('collectors.id', $id)
            ->first();
        if (!$collector) {
            $notify[] = 'Collector not found';
            return apiResponse('not_found', 'error', $notify);
        }
        $notify[] = $collector;
        return apiResponse("collector_data", 'success', $notify);
    }

    public function pickupStatus($status)
    {
        $user = auth()->user();

        $status = (int) $status;
        $pickup = WastePickup::where('user_id', $user->id)
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
        $user = auth()->user();

        $pickup = WastePickup::select('status')->where('user_id', $user->id)
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
}
