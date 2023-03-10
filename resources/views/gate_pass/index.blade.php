@extends('layouts.app')
@section('title', __('gate_pass.gate_pass'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('gate_pass.gate_pass')
        <small></small>
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('gate_pass_filter_serial_no', __('gate_pass.serial_no') . ':') !!}
            {!! Form::text('gate_pass_filter_serial_no', null, ['class' => 'form-control']); !!}
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('gate_pass_filter_date_range', __('report.date_range') . ':') !!}
            {!! Form::text('gate_pass_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
        </div>
    </div>

    <div class="col-md-3">
        <div class="form-group">
            {!! Form::label('type', __('gate_pass.type') . ':') !!}
            {!! Form::select('type',[''=>'All','1'=>'Mandir','0'=>'Haribhakta'],'',['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control']); !!}
        </div>
    </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('gate_pass.gate_pass')])
    @slot('tool')
    <div class="box-tools">
        <a class="btn btn-block btn-primary" href="{{action('GatePassController@create')}}">
            <i class="fa fa-plus"></i> @lang('messages.add')</a>
    </div>
    @endslot
    <div class="table-responsive">
        <table class="table table-bordered table-striped" id="gate_pass_table">
            <thead>
                <tr>
                    <th>@lang('gate_pass.serial_no')</th>
                    <th>@lang('messages.date')</th>
                    <th>@lang('gate_pass.vibhag_name')</th>
                    <th>@lang('gate_pass.driver_name')</th>
                    <th>@lang('gate_pass.driver_number')</th>
                    <th>@lang('gate_pass.vehicle_number')</th>
                    <th>@lang('gate_pass.deliever_to')</th>
                    <th>@lang('gate_pass.check_in')</th>
                    <th>@lang('gate_pass.checkout')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    </div>
    @endcomponent

</section>

<!-- <section id="receipt_section" class="print_section"></section> -->

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/gate_pass.js?v=' . $asset_v) }}"></script>
<script>
    //Date range as a button
    $('#gate_pass_filter_date_range').daterangepicker(
        dateRangeSettings,
        function(start, end) {
            $('#gate_pass_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            gate_pass_table.ajax.reload();
        }
    );
    $('#gate_pass_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        gate_pass_table.ajax.reload();
        $('#gate_pass_filter_date_range').val('');
    });
</script>
@endsection