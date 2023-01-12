{!! Form::open(['url' => action('GatePassController@checkOut'), 'method' => 'get', 'id' => 'checkout_form' ]) !!}

<div class="modal-header">
    <h4 class="modal-title">@lang( 'gate_pass.checkout' )</h4>
</div>

<div class="modal-body">
    <div class="row invoice-info" style="font-size: 14px;">
        <div class="col-sm-6 invoice-col" style="width: 50%;">
            <div class="col-sm-3 hide">
                {!! Form::hidden('serial_no', $gatePassData->serial_no, ['class' => 'form-control']); !!}
            </div>
            <p><b>@lang('gate_pass.driver_name'):</b> {{ $gatePassData->driver_name }}</p>
            <p><b>@lang('gate_pass.driver_number'):</b> {{ $gatePassData->driver_mobile_number }}</p>
            <p><b>@lang('gate_pass.vehicle_number'):</b> {{ $gatePassData->vehicle_number }}</p>
        </div>
    </div>
</div>

<div class="modal-footer">
    <button type="submit" class="btn btn-primary">@lang( 'gate_pass.checkout' )</button>
</div>
{!! Form::close() !!}