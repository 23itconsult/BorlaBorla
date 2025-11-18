<div class="row responsive-row">
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.user.all')" variant="primary" title="Total Waste Collector" :value="$widget['all']"
            icon="las la-car" :currency=false />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four url="{{ route('admin.user.all') }}?date={{ now()->toDateString() }}" variant="danger"
            title="Waste Collector Joined Today" :value="$widget['today']" icon="las la-clock" :currency=false />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four
            url="{{ route('admin.user.all') }}?date={{ now()->subDays(7)->toDateString() }}to{{ now()->toDateString() }}"
            variant="success" title="Waste Collector Joined Last Week" :value="$widget['week']" icon="las la-calendar"
            :currency=false />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four
            url="{{ route('admin.user.all') }}?date={{ now()->subDays(30)->toDateString() }}to{{ now()->toDateString() }}"
            variant="primary" title="Waste Collector Joined Last Month" :value="$widget['month']" icon="las la-calendar-plus"
            :currency=false />
    </div>
</div>
