@extends('admin.layouts.app')
@section('panel')
    <div class="row">
        <div class="col-12">
            <x-admin.ui.card>
                <x-admin.ui.card.body :paddingZero=true>
                    <x-admin.ui.table.layout searchPlaceholder="Search service" :renderExportButton="false">
                        <x-admin.ui.table>
                            <x-admin.ui.table.header>
                                <tr>
                                    <th>@lang('Name')</th>
                                    <th>@lang('Waste Per Bag')</th>
                                    <th>@lang('Waste Per Weight')</th>
                                    <th>@lang('Commission')</th>
                                    <th>@lang('Status')</th>
                                    <th>@lang('Action')</th>
                                </tr>
                            </x-admin.ui.table.header>
                            <x-admin.ui.table.body>
                                @forelse($wasteServices as $service)
                                    <tr>
                                        <td>
                                            <div class="flex-thumb-wrapper gap-1">
                                                <div class="thumb">
                                                    <img src="{{ imageGet('waste_service', $service->image) }}">
                                                </div>
                                                <span>{{ __($service->name) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="d-block">{{ showAmount($service->price_per_bag) }}</span>
                                                
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="d-block">{{ showAmount($service->price_per_kg) }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <span class="d-block">{{ getAmount($service->commission) }}%</span>
                                            </div>
                                        </td>
                                        <td>
                                            <x-admin.other.status_switch :status="$service->status" :action="route('admin.service.status', $service->id)" title="waste_service" />
                                        </td>
                                        <td>
                                            <x-admin.ui.btn.edit tag="button" :data-image="imageGet('waste_service', $service->image)" :data-resource="$service" />
                                        </td>
                                    </tr>
                                @empty
                                    <x-admin.ui.table.empty_message />
                                @endforelse
                            </x-admin.ui.table.body>
                        </x-admin.ui.table>
                        @if ($wasteServices->hasPages())
                            <x-admin.ui.table.footer>
                                {{ paginateLinks($wasteServices) }}
                            </x-admin.ui.table.footer>
                        @endif
                    </x-admin.ui.table.layout>
                </x-admin.ui.card.body>
            </x-admin.ui.card>
        </div>
    </div>

    <x-admin.ui.modal id="modal" class="modal-xl">
        <x-admin.ui.modal.header>
            <h4 class="modal-title"></h4>
            <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close">
                <i class="las la-times"></i>
            </button>
        </x-admin.ui.modal.header>
        <x-admin.ui.modal.body>
            <form action="{{ route('admin.service.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>@lang('Image')</label>
                    <x-image-uploader type="waste_service" />
                </div>
                <div class="form-group">
                    <label>@lang('Name')</label>
                    <input class="form-control" name="name" type="text" required value="{{ old('name') }}">
                </div>
            
                <!-- Household Waste Pricing -->
                <div class="row mb-3">
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>@lang('Price Per Bag')</label>
                            <input class="form-control" name="price_per_bag" type="number" step="any" value="{{ old('household_waste_price_per_bag') }}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>@lang('Price Per Kg')</label>
                            <input class="form-control" name="price_per_kg" type="number" step="any" value="{{ old('household_waste_price_per_kg') }}">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>@lang('Waste Commission')</label>
                            <input class="form-control" name="commission" type="number" step="any" required value="{{ old('household_waste_commission') }}">
                        </div>
                    </div>
                </div>
            
                <div class="form-group">
                    <x-admin.ui.btn.modal />
                </div>
            </form>
        </x-admin.ui.modal.body>
    </x-admin.ui.modal>
@endsection

@push('script')
    <script>
        (function($) {
            "use strict";
            const $modal = $("#modal");

            $(".edit-btn").on('click', function (e) {
    const data = $(this).data('resource');
    const imagePath = $(this).data('image');
    const action = "{{ route('admin.service.store', ':id') }}";

    // Set the form action
    $('form').attr('action', action.replace(':id', data.id));

    // Set the name field
    $("input[name='name']").val(data.name);

    // Set the pricing fields
    $("input[name='price_per_bag']").val(data.price_per_bag);
    $("input[name='price_per_kg']").val(data.price_per_kg);
    $("input[name='commission']").val(data.commission);

    // Update the image
    $modal.find(".image-upload img").attr('src', imagePath);
    $modal.find(".image-upload [type=file]").attr('required', false);

    // Update the modal title and show modal
    $modal.find(".modal-title").text("@lang('Edit Service')");
    $modal.modal("show");
});


            $(".add-btn").on('click', function(e) {
                const action = "{{ route('admin.service.store') }}";
                $modal.find(".modal-title").text("@lang('Add Service')");
                $modal.find('form').trigger('reset');
                $modal.find('form').attr('action', action);
                $modal.find(".image-upload img").attr('src', "{{ asset('assets/images/drag-and-drop.png') }}");
                $modal.find(".image-upload [type=file]").attr('required', true);
                $modal.modal("show");
            });
        })(jQuery);
    </script>
@endpush


@push('modal')
    <x-confirmation-modal />
@endpush

@push('breadcrumb-plugins')
    <x-admin.ui.btn.add tag="button" />
@endpush


@push('style')
    <style>
        .divider-title {
            position: relative;
            text-align: center;
            width: max-content;
            margin: 0 auto;
        }

        .divider-title::before {
            position: absolute;
            content: '';
            top: 14px;
            left: -90px;
            background: #6b6b6b65;
            height: 2px;
            width: 80px;
        }

        .divider-title::after {
            position: absolute;
            content: '';
            top: 14px;
            right: -90px;
            background: #6b6b6b65;
            height: 2px;
            width: 80px;
        }
    </style>
@endpush
