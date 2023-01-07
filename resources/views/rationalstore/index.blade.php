@extends('layouts.app')
@section('title', __('rationalstore.rationalstore'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('rationalstore.rationalstore')
        <small></small>
    </h1>
    <!-- <ol class="breadcrumb">
        <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
        <li class="active">Here</li>
    </ol> -->
</section>

<!-- Main content -->
<section class="content no-print">
    @component('components.filters', ['title' => __('report.filters')])
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('rationalstore_list_filter_location_id',  __('rationalstore.business_location') . ':') !!}
                {!! Form::select('rationalstore_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('rationalstore_list_filter_customer_id',  __('rationalstore.customer') . ':') !!}
                {!! Form::select('rationalstore_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('rationalstore_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('rationalstore_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('rationalstore.all_rationalstore')])
        @can('rationalstore.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action('RationalStoreController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @can('rationalstore.view')
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="rationalstore_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('rationalstore.ref_no')</th>
                            <th>@lang('rationalstore.location')</th>
                            <th>@lang('rationalstore.customer')</th>
                            <th>@lang('rationalstore.grand_total')</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td><span class="display_currency" id="footer_rationalstore_total" data-currency_symbol ="true"></span></td>
                            <td id="footer_status_count"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endcan
    @endcomponent

    <div class="modal fade product_modal" tabindex="-1" role="dialog" 
    	aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

</section>

<section id="receipt_section" class="print_section"></section>

<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/rationing.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
        //Date range as a button
    $('#rationalstore_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#rationalstore_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            rationalstore_table.ajax.reload();
        }
    );
    $('#rationalstore_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        rationalstore_table.ajax.reload();
        $('#rationalstore_list_filter_date_range').val('');
    });
</script>
	
@endsection