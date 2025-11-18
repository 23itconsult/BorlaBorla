@extends('admin.layouts.app')
@section('panel')
    <div class="row responsive-row">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-body">
                 <!-- Button trigger modal -->

             
                <h6 align="right">
                <button type="button" class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#cronModal">
                    Add new user
                </button>
                </h6>
          
            
  <x-admin.ui.card class="table-has-filter">
        <x-admin.ui.card.body :paddingZero="true">
            <x-admin.ui.table.layout searchPlaceholder="Search rider" filterBoxLocation="collector.filter">
                <x-admin.ui.table>
                    <x-admin.ui.table.header>
                        <tr>
                            <th>@lang('User name')</th>
                            <th>@lang('Email')</th>
                            <th>@lang('name')</th>
                             <th>@lang('user role')</th>
                            <th>@lang('Joined At')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </x-admin.ui.table.header>
                    <x-admin.ui.table.body>
                        @forelse($riders as $user)
                            <tr>
                                <td>
                                    <x-admin.other.user_info :user="$user" />
                                </td>
                                <td>
                                    <div>
                                        <strong class="d-block">
                                            {{ $user->email }}
                                        </strong>
                                       
                                    </div>
                                </td>
                                   <td>
                                    <div>
                                        <strong class="d-block">
                                            {{ $user->name}}
                                        </strong>
                                       
                                    </div>
                                </td>
                                     <td>
                                    <div>
                                        <strong class="d-block">
                                            {{ $user->role_name}}
                                        </strong>
                                       
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        <strong class="d-block ">{{ showDateTime($user->created_at) }}</strong>
                                        <small class="d-block"> {{ diffForHumans($user->created_at) }}</small>
                                    </div>
                                </td>
                             
                                <td>
                                    <x-admin.ui.btn.details :href="route('admin.adminprofile', $user->id)" />
                                </td>
                            </tr>
                        @empty
                            <x-admin.ui.table.empty_message />
                        @endforelse
                    </x-admin.ui.table.body>
                </x-admin.ui.table>
                @if ($riders->hasPages())
                    <x-admin.ui.table.footer>
                        {{ paginateLinks($riders) }}
                    </x-admin.ui.table.footer>
                @endif
            </x-admin.ui.table.layout>
        </x-admin.ui.card.body>
    </x-admin.ui.card>







<!-- Modal -->
<x-admin.ui.modal id="cronModal">
    <x-admin.ui.modal.header class="p-3 p-md-4">
        <div>
            <h1 class="modal-title">@lang('Create new user')</h1>
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
                    <label class="fs-14">@lang('Full name')</label>
                    <div class="input-group input--group">
                        <input type="text" name="name" class="form-control" placeholder="@lang('Enter full name')">
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="fs-14">@lang('Email')</label>
                    <div class="input-group input--group">
                        <input type="email" name="email" class="form-control" placeholder="@lang('Enter email')">
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
                     {{-- ✅ User Role Selection --}}
                <div class="col-md-12">
                    <label class="fs-14">@lang('User Role')</label>
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
            </div>

     <div class="form-group mt-4 d-flex gap-2 flex-wrap justify-content-end">
    <button type="submit" class="btn btn--success btn-large fs-14">
        <i class="fas fa-save"></i>
        @lang('Save User')
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
