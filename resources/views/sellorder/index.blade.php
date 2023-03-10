@extends('layouts.app')
@section('title', __('sellorder.sellorder'))

@section('content')
@php
    $show_price=1;
    if(!auth()->user()->show_price)
    {
        $show_price=0;
    }
@endphp
<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('sellorder.sellorder')
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
                {!! Form::label('sellorder_list_filter_location_id',  __('sellorder.business_location') . ':') !!}
                {!! Form::select('sellorder_list_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sellorder_list_filter_customer_id',  __('sellorder.customer') . ':') !!}
                {!! Form::select('sellorder_list_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sellorder_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sellorder_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-primary', 'title' => __('sellorder.all_sellorder')])
        @can('sellorder.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action('SellOrderController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @can('sellorder.view')
            <div class="table-responsive">
                <input type="hidden" id="show_price" value="{{$show_price}}" name="show_price" />
                @if($show_price==1)
                <table class="table table-bordered table-striped ajax_view" id="sellorder_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sellorder.ref_no')</th>
                            <th>@lang('sellorder.location')</th>
                            <th>@lang('sellorder.customer')</th>
                            <th>@lang('sellorder.sellorder_status')</th>
                            <th>@lang('sellorder.payment_status')</th>
                            <th>@lang('sellorder.grand_total')</th>
                            <th>@lang('sellorder.payment_due') &nbsp;&nbsp;<i class="fa fa-info-circle text-info" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="{{ __('messages.purchase_due_tooltip')}}" aria-hidden="true"></i></th>
                            <th>Notes</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_status_count"></td>
                            <td id="footer_payment_status_count"></td>
                            <td><span class="display_currency" id="footer_sellorder_total" data-currency_symbol ="true"></span></td>
                            <td class="text-left"><small>@lang('report.purchase_due') - <span class="display_currency" id="footer_total_due" data-currency_symbol ="true"></span><br>
                            @lang('lang_v1.purchase_return') - <span class="display_currency" id="footer_total_purchase_return_due" data-currency_symbol ="true"></span>
                            </small></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                @else
                <table class="table table-bordered table-striped ajax_view" id="sellorder_table_without_price">
                    <thead>
                        <tr>
                            <th>@lang('messages.date')</th>
                            <th>@lang('sellorder.ref_no')</th>
                            <th>@lang('sellorder.location')</th>
                            <th>@lang('sellorder.customer')</th>
                            <th>@lang('sellorder.sellorder_status')</th>
                            <th>@lang('sellorder.payment_status')</th>
                            <th>Notes</th>
                            <th>@lang('messages.action')</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr class="bg-gray font-17 text-center footer-total">
                            <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                            <td id="footer_status_count"></td>
                            <td id="footer_payment_status_count"></td>
                            <td></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
                @endif
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
<script src="{{ asset('js/sellorder.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
<script>
        //Date range as a button
    $('#sellorder_list_filter_date_range').daterangepicker(
        dateRangeSettings,
        function (start, end) {
            $('#sellorder_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
            sellorder_table.ajax.reload();
        }
    );
    $('#sellorder_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
        sellorder_table.ajax.reload();
        $('#sellorder_list_filter_date_range').val('');
    });
</script>
	
@endsection