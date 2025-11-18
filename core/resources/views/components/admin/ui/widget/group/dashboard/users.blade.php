@props(['widget'])
<div class="row responsive-row">
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.two :url="route('admin.user.all')" variant="primary" title="Total User" :value="$widget['total_users']"
            icon="las la-users" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.two :url="route('admin.user.active')" variant="success" title="Active User" :value="$widget['active_users']"
            icon="las la-user-check" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.two :url="route('admin.user.email.unverified')" variant="warning" title="Email Unverified User" :value="$widget['email_unverified_users']"
            icon="lar la-envelope" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.two :url="route('admin.user.mobile.unverified')" variant="danger" title="Mobile Unverified User" :value="$widget['mobile_unverified_users']"
            icon="las la-comment-slash" />
    </div>
</div>
