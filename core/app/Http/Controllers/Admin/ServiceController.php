<?php

namespace App\Http\Controllers\Admin;

use App\Models\Service;
use App\Models\WasteService;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    public function index()
    {
        $pageTitle = 'All Waste Services';
        $wasteServices = WasteService::searchable(['name'])->orderBy('id', getOrderBy())->paginate(getPaginate());
        return view('admin.services.index', compact('pageTitle', 'wasteServices'));
    }

    public function store(Request $request, $id = 0)
    {
        // Validation rules for waste services
        $request->validate([
            'image' => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'name' => 'required|unique:waste_services,name,' . $id,
            'price_per_bag' => 'nullable|numeric|gt:0',
            'price_per_kg' => 'nullable|numeric|gt:0',
            'commission' => 'required|numeric|gt:0|lt:100',
        ]);

        // Fetch or create the waste service
        if ($id) {
            $service = WasteService::findOrFail($id);
            $notification = 'Waste service updated successfully';
        } else {
            $service = new WasteService();
            $notification = 'Waste service added successfully';
        }

        // Handle image upload
        if ($request->hasFile('image')) {
            try {
                $service->image = fileUploader($request->image, getFilePath('waste_service'), getFileSize('waste_service'), @$service->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        // Save waste service data
        $service->name = $request->name;
        $service->price_per_bag = $request->price_per_bag;
        $service->price_per_kg = $request->price_per_kg;
        $service->commission = $request->commission;
        $service->save();

        // Notify user
        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Service::changeStatus($id);
    }
}
