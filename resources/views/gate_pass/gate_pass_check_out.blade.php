@extends('layouts.app')
@section('title', __('gate_pass.checkout'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('gate_pass.checkout')
        <small></small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="box box-solid">
        {!! Form::open(['url' => action('GatePassController@getCheckOutDetail'), 'method' => 'get', 'id' => 'checkout_gate_pass_form']) !!}

        <div class="box-body" style="padding: 30px;">
            @can('gate_pass.verify')

            <div class="row">

                <div class="form-group">
                    {!! Form::label('type', __('gate_pass.serial_no') . ':*' ) !!}
                    <div class="input-group">
                        <span class="input-group-addon" id="gp_prefix">
                        </span>

                        {!! Form::number('serial_no', null, ['class' => 'form-control','id'=> 'serial_no' ,'placeholder' => __('gate_pass.serial_no'), 'required']); !!}<span class="input-group-btn">
                        </span>
                    </div>
                </div>


                <div class="col-sm-12 form-inline">
                    <button type="button" id="submit_checkout_form" class="btn btn-primary btn-flat btn-modal"> @lang( 'Next' )</button>
                </div>
                @endcan
            </div>
        </div>
        {!! Form::close() !!}

        <div class="modal fade" tabindex="-1" id="checkout-form" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"></h5>
                    </div>
                    <div class="modal-body" id="checkout-details">
                    </div>
                </div>
            </div>
        </div>

</section>
<!-- /.content -->

@endsection
@section('javascript')
<script src="{{ asset('js/gate_pass.js?v=' . $asset_v) }}"></script>
@endsection