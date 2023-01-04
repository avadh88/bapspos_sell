@extends('layouts.auth')
@section('title', __('lang_v1.login'))

@section('content')

<div class="container">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
            <div class="panel panel-default">
                <div class="panel-heading">@lang('lang_v1.login')</div>
                <div class="panel-body">
                    <form class="form-horizontal" method="POST" action="{{ route('login_otp_verify') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('otp') ? ' has-error' : '' }}">
                            <label for="otp" class="col-md-4 control-label">OTP</label>

                            <div class="col-md-6">
                                <input id="otp" type="text" class="form-control" name="otp" value="" required autofocus>

                                @if ($errors->has('otp'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('otp') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-md-8 col-md-offset-4">
                                <button type="submit" class="btn btn-primary">
                                    @lang('lang_v1.login')
                                </button>
                                <a class="btn btn-link" href="{{ route('sendotp') }}">
                                    @lang('lang_v1.resend_otp')
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    
</div>
@stop
@section('javascript')

@endsection
