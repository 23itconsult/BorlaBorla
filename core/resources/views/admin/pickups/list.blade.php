@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table.layout searchPlaceholder="Search rides">
                        <x-admin.ui.table>
                            <x-admin.ui.table.header>
                                <tr>
                                    <th>@lang('User/Household')</th>
                                    <th class="text-start">@lang('Waste Collector')</th>
                                    <th>@lang('Pickup Location')</th>
                                    <th>@lang('Deposal Site')</th>
                                    <th>@lang('Cancel')</th>
                                    <th>@lang('Cancelled by')</th>
                                    <th>@lang('Waste Pickup Fee')</th>
                                    @if (request()->routeIS('admin.pickups.all'))
                                        <th>@lang('Status')</th>
                                    @endif
                                    <th>@lang('Action')</th>
                                </tr>
                            </x-admin.ui.table.header>
                            <x-admin.ui.table.body>
                                @forelse($rides as $ride)
                                    <tr>
                                        <td> <x-admin.other.user_info :user="$ride->user" /></td>
                                        <td>
                                            @if ($ride->driver)
                                                <x-admin.other.driver_info :driver="$ride->driver" />
                                            @else
                                                <span class="text-start w-100">@lang('No driver available')</span>
                                            @endif
                                        </td>
                                        <td>{{ __($ride->pickup_location) }} </td>
                                        <td>{{ __($ride->destination) }} </td>
                                        <td>
                                            @php echo $ride->cancel_reason @endphp
                                        </td>
                                        <td> 
                                            @if ($ride->canceled_user_type == 1)
                                                @php echo "User" @endphp
                                            @elseif ($ride->canceled_user_type == 2)
                                                @php echo "Waste Collector" @endphp
                                            @endif
                                        </td>
                                        <td>
                                            {{ showAmount($ride->amount) }}
                                        </td>
                                        @if (request()->routeIS('admin.pickups.all'))
                                            <td>
                                                @php echo $ride->statusBadge @endphp
                                            </td>
                                        @endif
                                        <td>
                                            <x-admin.ui.btn.details :href="route('admin.pickups.detail', $ride->id)" />
                                        </td>
                                    </tr>
                                @empty
                                    <x-admin.ui.table.empty_message />
                                @endforelse
                            </x-admin.ui.table.body>
                        </x-admin.ui.table>
                        @if ($rides->hasPages())
                            <x-admin.ui.table.footer>
                                {{ paginateLinks($rides) }}
                            </x-admin.ui.table.footer>
                        @endif
                    </x-admin.ui.table.layout>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>
@endsection
