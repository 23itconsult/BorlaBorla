<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Models\Bid;
use App\Models\Ride;
use App\Models\Message;
use App\Http\Controllers\Controller;
use App\Models\WastePickup;

class ManageRideController extends Controller
{
    public function allRides()
    {
        $pageTitle = 'All Waste Pickup';
        extract($this->rideData());
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $rides = $baseQuery->paginate(getPaginate());
        return view('admin.pickups.list', compact('pageTitle', 'rides'));
    }

    public function new()
    {
        $pageTitle = 'Pending Waste Pickup';
        extract($this->rideData('pending'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $rides = $baseQuery->paginate(getPaginate());
        return view('admin.pickups.list', compact('pageTitle', 'rides'));
    }

    public function running()
    {
        $pageTitle = 'Running Waste Pickup';
        extract($this->rideData('running'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $rides = $baseQuery->paginate(getPaginate());
        return view('admin.pickups.list', compact('pageTitle', 'rides'));
    }

    public function completed()
    {
        $pageTitle = 'Completed Waste Pickup';
        extract($this->rideData('completed'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $rides = $baseQuery->paginate(getPaginate());
        return view('admin.pickups.list', compact('pageTitle', 'rides'));
    }
    public function canceled()
    {
        $pageTitle = 'Canceled Waste Pickup';
        extract($this->rideData('canceled'));
        if (request()->export) {
            return $this->callExportData($baseQuery);
        }
        $rides = $baseQuery->paginate(getPaginate());
        return view('admin.pickups.list', compact('pageTitle', 'rides'));
    }

    protected function rideData($scope = 'query')
    {
        $baseQuery = WastePickup::$scope()->with(['user', 'driver', 'sosAlert'])->withCount('bids')->searchable(['uid', 'user:username', 'driver:username'])->filter(['user_id', 'collector_id', 'applied_coupon_id'])->orderBy('id', 'desc');
        
        return [
            'baseQuery' => $baseQuery
        ];
    }
    public function detail($id)
    {
        $pageTitle         = 'Pickup Details';
        $ride              = WastePickup::with(['bids'])->findOrFail($id);
        $totalUserCancel   = WastePickup::where('user_id', $ride->user_id)->where('status', Status::RIDE_CANCELED)->where('canceled_user_type', Status::USER)->count();
        $totalDriverCancel = WastePickup::where('collector_id', $ride->collector_id)->where('status', Status::RIDE_CANCELED)->where('canceled_user_type', Status::DRIVER)->count();
        return view('admin.pickups.details', compact('pageTitle', 'ride', 'totalUserCancel', 'totalDriverCancel'));
    }

    public function bid($id)
    {
        $ride      = Ride::FindOrFail($id);
        $pageTitle = "Bid List of Ride:" . $ride->uid;
        $bids      = Bid::with('driver')->searchable(['driver:username', 'bid_amount'])->where('ride_id', $ride->id)->paginate(getPaginate());
        return view('admin.rides.bid', compact('pageTitle', 'bids'));
    }


    private function callExportData($baseQuery)
    {
        return exportData($baseQuery, request()->export, "wastePickup", "A4 landscape");
    }
}
