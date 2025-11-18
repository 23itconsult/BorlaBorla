@props(['widget'])
<div class="row responsive-row">
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.pickups.running')" variant="primary" title="Running Waste Pickup" :value="$widget['running_ride']"
            icon="las la-car" :currency="false" />
    </div>

    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.pickups.completed')" variant="success" title="Completed Waste Pickup" :value="$widget['completed_ride']"
            icon="las la-route" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.pickups.canceled')" variant="danger" title="Canceled Waste Pickup" :value="$widget['canceled_ride']"
            icon="las la-times-circle" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.pickups.all')" variant="info" title="Total Waste Pickup" :value="$widget['total_ride']"
            icon="las la-list" :currency="false" />
    </div>
</div>
