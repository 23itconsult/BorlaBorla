@extends('errors.app')

@section('content')
    <div class="error-content__footer">
        <p class="error-content__message">
            <span class="title" style="font-size: 4rem">@lang('Unauthorised')</span>
            <span class="text" style="font-size: 2rem">
                @lang('You are not authorised to view this page')
            </span>
        </p>
     <a href="javascript:void(0)" onclick="history.back()" class="btn btn-outline--primary error-btn">
    <span class="btn--icon"><i class="fa-solid fa-house"></i></span>
    <span class="text">@lang('Back to Home')</span>
</a>
    </div>
@endsection
