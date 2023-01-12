<div class="row text-center">
    <div class="col-xs-12">
        <h2 style="font-size: 24px;" class="page-header"><b>@lang('lang_v1.gate_pass')</b><span style="font-size: 18px;"> ( {{$gatePassData->duplicate}} )</span></b></h2>
    </div>
</div>
<div class="row">
    <div class="col-xs-12">
        <h5 class="page-header" style="font-size: 16px;">
            <b>@lang('gate_pass.serial_no'):</b> #{{ $gatePassData->serial_no }} ({{ @format_datetime($gatePassData->date) }})
            <!-- <small class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($gatePassData->date) }}</small> -->
        </h5>
    </div>
</div>

<div class="row invoice-info" style="font-size: 14px;">
    <div class="col-sm-6 invoice-col" style="width: 50%;">
        <p><b>@lang('gate_pass.vibhag_name'):</b> {{ $gatePassData->vibhag_name }}</p>
        <p><b>@lang('gate_pass.driver_name'):</b> {{ $gatePassData->driver_name }}</p>
        <p><b>@lang('gate_pass.vehicle_number'):</b> {{ $gatePassData->vehicle_number }}</p>
    </div>

    <div class="col-sm-6 invoice-col pull-right" style="width: 50%;">
        <p><b>@lang('gate_pass.driver_number'):</b> {{ $gatePassData->driver_mobile_number }}</p>
        <p><b>@lang('gate_pass.deliever_to'):</b> {{ $gatePassData->deliever_to }}</p>
    </div>
</div>
<!-- <br> -->
<div class="modal-body">
    <div class="row">
        <div class="col-xs-12">
            <div class="table-responsive">
                <table class="table bg-gray">
                    <tr class="bg-green">
                        <th>Sr.no.</th>
                        <th>Item Name</th>
                        <th>Qty</th>
                    </tr>
                    @foreach( $gatePassData->values as $attr)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{!! $attr->name !!}</td>
                        <td>{!! $attr->qty !!}</td>
                    </tr>
                    @endforeach
                </table>
            </div>
        </div>
    </div>
</div>
<div class="row invoice-info" style="font-size: 14px;">
    <div class="col-sm-4 invoice-col">
        <p class="no-shadow bg-gray">
            {{ $gatePassData->sign_of_gate_pass_approval }}
        </p>
        <strong>@lang('gate_pass.sign_of_gate_pass_approval'):</strong><br>
    </div>

    <div class="col-sm-4 invoice-col">
    </div>

    <div class="col-sm-4 invoice-col">
        <p class="no-shadow bg-gray">
            {{ $gatePassData->sign_of_secutiry_person }}
        </p>
        <strong>@lang('gate_pass.sign_of_secutiry_person'):</strong><br>
    </div>
</div>