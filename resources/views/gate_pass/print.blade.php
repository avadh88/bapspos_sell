<div class="row">
    <div class="col-xs-12 text-center">
        <h2 style="margin-top:0px;" class="text-center">
            @lang('lang_v1.gate_pass')
        </h2>
        <h3>{{$gatePassData->duplicate}}</h3>
        <p>--------------------------------------------</p>
        <p style="width: 100% !important" class="word-wrap">
            <span class="pull-left text-left">
                <p></p>
            </span>
            <br>
            <span class="pull-left text-left">
                <p><b>@lang('gate_pass.serial_no'):</b> {{ $gatePassData->serial_no }} | {{ @format_datetime($gatePassData->date) }}</p>
                <p><b>@lang('gate_pass.vibhag_name'):</b> {{ $gatePassData->vibhag_name }}</p>
                <p><b>@lang('gate_pass.driver_name'):</b> {{ $gatePassData->driver_name }}</p>
                <p><b>@lang('gate_pass.vehicle_number'):</b> {{ $gatePassData->vehicle_number }}</p>
                <p><b>@lang('gate_pass.driver_number'):</b> {{ $gatePassData->driver_mobile_number }}</p>
                <p><b>@lang('gate_pass.deliver_to'):</b> {{ $gatePassData->deliever_to }}</p>
            </span>
        </p>
    </div>
</div>

<p>--------------------------------------------</p>
<div class="row">
    <div class="col-xs-12">
        <table class="table table-responsive" style="padding:0px; margin:0px;">
            <thead>
                <tr>
                    <th style="padding:0px; margin:0px;">Sr.no.</th>
                    <th style="padding:0px; margin:0px;">Item Name</th>
                </tr>
            </thead>
            <tbody>
                @foreach($gatePassData->values as $attr)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{!! $attr->name !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
<p>--------------------------------------------</p>
<div class="row" style="">
    <div class="col-xs-12 text-center">
        <span class="pull-left text-left" style="margin-top: 5px;">
            <p><b>@lang('gate_pass.sign_of_gate_pass_approval'):</b> {{ $gatePassData->sign_of_gate_pass_approval }}</p>
            @if($gatePassData->sign_of_secutiry_person)

            <p><b>@lang('gate_pass.sign_of_secutiry_person'):</b> {{ $gatePassData->sign_of_secutiry_person }}</p>
            @endif
        </span>
    </div>
</div>

<div class="row">
    <div class="col-xs-12 text-center">
        <p>--------------------------------------------</p>
    </div>
</div>
<!-- <div class="row">
    <div class="col-xs-12 text-center">
        <p style="width: 100% !important" class="word-wrap">
            <span class="pull-left text-left">
                <p></p>
            </span>
            <br>
            <span class="pull-left text-left">
                <p><b>@lang('gate_pass.sign_of_gate_pass_approval'):</b> {{ $gatePassData->sign_of_gate_pass_approval }}</p>
                @if($gatePassData->sign_of_secutiry_person)

                <p><b>@lang('gate_pass.sign_of_secutiry_person'):</b> {{ $gatePassData->sign_of_secutiry_person }}</p>
                @endif
            </span>
        </p>
    </div>
</div> -->