@extends('admin.layouts.app')
@section('panel')
    <div class="row responsive-row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                 <!-- Button trigger modal -->

<div class="d-flex justify-content-between">
<div class="col-8">
<form action="">
    <div class="row">
<div class="select col-6">
        <div class="input-group input--group">
            <select name="role" class="form-select">
                <option value="" disabled selected>@lang('Select a role')</option>

                    @forelse ($Roles as $role)
<option value="{{ $role->id }}">{{ $role->name }}</option>
@empty
<option disabled>@lang('No roles found')</option>
@endforelse
                
            </select>
        </div>
    </div>

      <div class="col-6">
                <button type="button" class="btn btn--danger" data-bs-toggle="modal" data-bs-target="#cronModal">
                    Assign permissionsto role
                </button>
            </div>
    </div>

              
                </form>
         
                </div>
          

                  <div>
               <h6 align="left">
                 <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#cronModal">
                    Add system permission
                </button>
               </h6>
            </div>
             </div>
              
               
          
            
<x-admin.ui.card class="table-has-filter">
    <x-admin.ui.card.body :paddingZero="true">
        <x-admin.ui.table.layout searchPlaceholder="Search rider" filterBoxLocation="collector.filter">
            <x-admin.ui.table>
                <x-admin.ui.table.header>
                    <tr>
                        <!-- ✅ Check All Checkbox -->
                        <th>
                            <input type="checkbox" id="checkAll">
                        </th>
                        <th>@lang('url')</th>
                        <th>@lang('route name')</th>
                        <th>@lang('description')</th>
                        <th>@lang('Created by')</th>
                        <th>@lang('created at')</th>
                        <th>@lang('Action')</th>
                    </tr>
                </x-admin.ui.table.header>

                <x-admin.ui.table.body>
                    @forelse($Allpermissions as $user)
                        <tr>
                            <!-- ✅ Individual Checkbox -->
                            <td>
                                <input type="checkbox" name="permission_ids[]" value="{{ $user->id }}" class="checkItem">
                            </td>

                            <td>
                                <strong class="d-block">{{ $user->name }}</strong>
                            </td>

                            <td>
                                <strong class="d-block">{{ $user->route_name }}</strong>
                            </td>

                            <td>
                                <strong class="d-block">{{ $user->permission_human_text }}</strong>
                            </td>

                            <td>
                                <strong class="d-block">{{ $user->created_by }}</strong>
                            </td>

                            <td>
                                <strong class="d-block ">{{ showDateTime($user->created_at) }}</strong>
                                <small class="d-block"> {{ diffForHumans($user->created_at) }}</small>
                            </td>

                            <td>
                                <x-admin.ui.btn.edit 
                                    user_id="{{ $user->id }}" 
                                    name="{{ $user->name }}" 
                                    class="button" 
                                    data-bs-toggle="modal"  
                                    data-bs-target="#cronModal"/>
                            </td>
                        </tr>
                    @empty
                        <x-admin.ui.table.empty_message />
                    @endforelse
                </x-admin.ui.table.body>
            </x-admin.ui.table>

            @if ($Allpermissions->hasPages())
                <x-admin.ui.table.footer>
                    {{ paginateLinks($Allpermissions) }}
                </x-admin.ui.table.footer>
            @endif
        </x-admin.ui.table.layout>
    </x-admin.ui.card.body>
</x-admin.ui.card>












    <!-- Modal -->
<x-admin.ui.modal id="cronModal" data-bs-backdrop="static" data-bs-keyboard="false">
    <x-admin.ui.modal.header class="p-3 p-md-4">
        <div>
            <h1 class="modal-title">@lang('Create system role')</h1>
        </div>
        <button type="button" class="btn-close close" data-bs-dismiss="modal" aria-label="Close">
            <i class="las la-times"></i>
        </button>
    </x-admin.ui.modal.header>

    <x-admin.ui.modal.body class="p-3 p-md-4">
        {{-- Wrap inputs inside a form {{ route('admin.add_adminuser') }}--}}
        <form action="{{route('admin.add_adminuser') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="fs-14">@lang('url')</label>
                    <div class="input-group input--group">
                        <input type="text" name="name" class="form-control" placeholder="@lang('Enter url')">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="fs-14">@lang('route name')</label>
                    <div class="input-group input--group">
                        <input type="text" name="route_name" class="form-control" placeholder="@lang('Enter email')">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="fs-14">@lang('user name')</label>
                    <div class="input-group input--group">
                        <input type="text" name="username" class="form-control" placeholder="@lang('Enter user name')">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="fs-14">@lang('Password')</label>
                    <div class="input-group input--group">
                        <input type="password" name="password" class="form-control" placeholder="@lang('Enter password')">
                    </div>
                </div>
                </div>

                <input type="hidden" name="role_id" class="form-control role_id" placeholder="@lang('Role name')">
           

     <div class="form-group mt-4 d-flex gap-2 flex-wrap justify-content-end">
    <button type="submit" class="btn btn--success btn-large fs-14">
        <i class="fas fa-save"></i>
        @lang('Save system role')
    </button>
    {{-- <a href="{{ route('cron') }}?target=all" class="btn btn--primary btn-large fs-14">
        <i class="fas fa-bolt"></i>
        @lang('Run Manually')
    </a> --}}
</div>

        </form>
    </x-admin.ui.modal.body>
</x-admin.ui.modal>

                </div>
            </div>
        </div>

@endsection

@push('breadcrumb-plugins')
    {{-- <div class=" d-flex gap-2  flex-wrap">
        @if ($user->status == Status::USER_ACTIVE)
            <button type="button" class="flex-fill btn  btn--warning" data-bs-toggle="modal"
                data-bs-target="#userStatusModal">
                <i class="las la-ban me-1"></i>@lang('Ban User')
            </button>
        @else
            <button type="button" class="flex-fill btn  btn--info" data-bs-toggle="modal"
                data-bs-target="#userStatusModal">
                <i class="las la-ban me-1"></i>@lang('Unban User')
            </button>
        @endif
        <a href="{{ route('admin.report.user.notification.history') }}?user_id={{ $user->id }}"
            class="flex-fill btn  btn--secondary">
            <i class="las la-bell me-1"></i>@lang('Notifications Logs')
        </a>
    </div> --}}
@endpush

@push('script')
    <script>
        "use strict";
        (function($) {


            const inputValues = {};
            const $formElements = $('.user-form input, .user-form select').not("[name=_token]");
            const $submitButton = $(".user-form").find('button[type=submit]');

            $(".button").click(function(){
                let id=$(this).attr("user_id");
                let role_name=$(this).attr("name");
                $('.role_name').val(role_name);
                 $('.role_id').val(id);
            })

        $(".close").click(function(){
                // let id=$(this).attr("user_id");
                // let role_name=$(this).attr("name");
                $('.role_name').val(null);
                $('.role_id').val(null);
                
                 
            })
// For "Check All"
$('#checkAll').on('change', function () {
    $('.checkItem').prop('checked', this.checked);

    updateCheckedArray();
});

// For individual checkboxes
$('.checkItem').on('change', function () {
    // If any checkbox is unchecked, uncheck "Check All"
    if (!$('.checkItem:checked').length) {
        $('#checkAll').prop('checked', false);
    }

    // If all are checked, tick "Check All"
    if ($('.checkItem:checked').length === $('.checkItem').length) {
        $('#checkAll').prop('checked', true);
    }

    updateCheckedArray();
});

// Helper function to collect checked values/ids
function updateCheckedArray() {
    let checkedArray = $('.checkItem:checked').map(function () {
        return $(this).val(); // or .attr('id')
    }).get();

    console.log(checkedArray); // You can send this array to backend or store in hidden input
}

            

            $formElements.each(function(i, element) {
                const $element = $(element);
                const name = $element.attr('name');
                const type = $element.attr('type');
                var value = $element.val();

                if (type == 'checkbox') {
                    value = $element.is(":checked");
                }
                const inputValue = {
                    inittial_value: value,
                    new_value: value,
                }
                inputValues[name] = inputValue;
            });

            $(".user-form").on('input change', 'input,select', function(e) {
                const name = $(this).attr('name');
                const type = $(this).attr('type');
                var value = $(this).val();

                if (type == 'checkbox') {
                    value = $(this).is(":checked");
                }

                const oldInputValue = inputValues[name];
                const newInputValue = {
                    inittial_value: oldInputValue.inittial_value,
                    new_value: value,
                };
                inputValues[name] = newInputValue;

                btnEnableDisable();
            });

            // submit btn disable/enable depend on input values
            function btnEnableDisable() {
                var isDisabled = true;
                $.each(inputValues, function(i, element) {
                    if (element.inittial_value != element.new_value) {
                        isDisabled = false;
                        return;
                    }
                });
                if (isDisabled) {
                    $submitButton.addClass("disabled").attr('disabled', true);
                } else {
                    $submitButton.removeClass("disabled").attr('disabled', false);
                }
            }

            let mobileElement = $('.mobile-code');
            $('select[name=country]').on('change', function() {
                mobileElement.text(`+${$('select[name=country] :selected').data('mobile_code')}`);
            });
        })(jQuery);
    </script>
@endpush


@push('style')
    <style>
        .verification-switch {
            grid-template-columns: repeat(2, 1fr);
        }
    </style>
@endpush
