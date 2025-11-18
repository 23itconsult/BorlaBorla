@props(['widget'])
<div class="row responsive-row">
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.collector.all')" variant="primary" title="Total Waste Collector" :value="$widget['total_driver']"
            icon="las la-users" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.collector.active')" variant="success" title="Active Waste Collector" :value="$widget['active_driver']"
            icon="las la-user-check" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.collector.unverified')" variant="warning" title="Document Unnerified Waste Collector" :value="$widget['document_unverified_driver']"
            icon="la la-list" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.collector.vehicle.unverified')" variant="danger" title="Vehicle Unnerified Waste Collector" :value="$widget['vehicle_unverified_driver']"
            icon="las la-car" :currency="false" />
    </div>
</div>
