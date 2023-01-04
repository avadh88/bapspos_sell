@extends('layouts.app')
@section('title', __('lang_v1.sell_return'))

@php

    $hide_price='';
    if(!auth()->user()->show_price)
    {
        $hide_price='hide';
    }
@endphp

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header no-print">
    <h1>@lang('lang_v1.sell_return')
    </h1>
</section>

<!-- Main content -->
<section class="content no-print">
    <input type="hidden" id="show_price" name="show_price" value="{{auth()->user()->show_price}}"/>
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.sell_return')])
        
        @can('sellreturn.view')
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_return_filter_location_id',  __('purchase.business_location') . ':') !!}

                {!! Form::select('sell_return_filter_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all') ]); !!}
            </div>
        </div>
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_return_filter_customer_id',  __('contact.customer') . ':') !!}
                @if($departmentUser)
                    {!! Form::select('sell_return_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                @else
                    {!! Form::select('sell_return_filter_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!} 
                @endif
                
            </div>
        </div>
        @if(!$departmentUser)
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_return_filter_payment_status',  __('purchase.payment_status') . ':') !!}
                {!! Form::select('sell_return_filter_payment_status', ['paid' => __('lang_v1.paid'), 'due' => __('lang_v1.due'), 'partial' => __('lang_v1.partial')], null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')]); !!}
            </div>
        </div>
        @endif
        <div class="col-md-3">
            <div class="form-group">
                {!! Form::label('sell_return_list_filter_date_range', __('report.date_range') . ':') !!}
                {!! Form::text('sell_return_list_filter_date_range', null, ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'readonly']); !!}
            </div>
        </div>
        @if(!$departmentUser)
            @if($userId)
            <div class="col-md-4 hide">
                <div class="form-group">
                    {!! Form::label('created_by',  __('report.user') . ':') !!}
                    {!! Form::select('created_by', $users, $userId, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                </div>
            </div>
            @else
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('created_by',  __('report.user') . ':') !!}
                    {!! Form::select('created_by', $users, $userId, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('report.all_users')]); !!}
                </div>
            </div>
            @endif
        @endif
        @endcan
    @endcomponent
    @component('components.widget', ['class' => 'box-primary', 'title' => __( 'lang_v1.all_sell_return')])
        @can('sellreturngenralstore.create')
            @slot('tool')
                <div class="box-tools">
                    <a class="btn btn-block btn-primary" href="{{action('SellReturnGenralstoreController@create')}}">
                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                </div>
            @endslot
        @endcan
        @include('sell_return_genralstore.partials.sell_return_list')
    @endcomponent
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>

    <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
        aria-labelledby="gridSystemModalLabel">
    </div>
</section>
<section class="invoice print_section" id="receipt_section"></section>
<!-- /.content -->
@stop
@section('javascript')
<script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>

<script>
    $(document).ready(function(){
        let show_price = document.getElementById("show_price").value;
        //Date range as a button
        $('#sell_return_list_filter_date_range').daterangepicker(
        dateRangeSettings,
            function (start, end) {
                $('#sell_return_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format));
                sell_return_table.ajax.reload();
            }
        );
        $('#sell_return_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#sell_return_list_filter_date_range').val('');
            sell_return_table.ajax.reload();
        });

        if(show_price==1)
        {
            sell_return_table = $('#sell_return_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                "ajax": {
                    "url": "/sellreturngenralstore",
                    "data": function ( d ) {
                        var start = $('#sell_return_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#sell_return_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                        d.location_id = $('#sell_return_filter_location_id').val();
                        d.customer_id = $('#sell_return_filter_customer_id').val();
                        d.payment_status = $('#sell_return_filter_payment_status').val();
                        d.created_by     = $("#created_by").val();
                    }
                },
                columnDefs: [ {
                    "targets": [7, 8],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                    { data: 'transaction_date', name: 'transaction_date'  },
                    { data: 'invoice_no', name: 'invoice_no'},
                    { data: 'parent_sale', name: 'T1.invoice_no'},
                    { data: 'name', name: 'contacts.name'},
                    { data: 'business_location', name: 'bl.name'},
                    { data: 'payment_status', name: 'payment_status'},
                    { data: 'final_total', name: 'final_total'},
                    { data: 'payment_due', name: 'payment_due'},
                    { data: 'action', name: 'action'}
                ],
                "fnDrawCallback": function (oSettings) {
                    var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                    $('#footer_sell_return_total').text(total_sell);
                    
                    $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'), 'payment-status-label'));

                    var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                    $('#footer_total_due_sr').text(total_due);

                    __currency_convert_recursively($('#sell_return_table'));
                },
                createdRow: function( row, data, dataIndex ) {
                    $( row ).find('td:eq(2)').attr('class', 'clickable_td');
                }
            });
        }
        else
        {
            sell_return_table = $('#sell_return_table').DataTable({
                processing: true,
                serverSide: true,
                aaSorting: [[0, 'desc']],
                "ajax": {
                    "url": "/sellreturngenralstore",
                    "data": function ( d ) {
                        var start = $('#sell_return_list_filter_date_range').data('daterangepicker').startDate.format('YYYY-MM-DD');
                        var end = $('#sell_return_list_filter_date_range').data('daterangepicker').endDate.format('YYYY-MM-DD');
                        d.start_date = start;
                        d.end_date = end;
                        d.location_id = $('#sell_return_filter_location_id').val();
                        d.customer_id = $('#sell_return_filter_customer_id').val();
                        d.payment_status = $('#sell_return_filter_payment_status').val();
                        d.created_by     = $("#created_by").val();
                    }
                },
                columnDefs: [ {
                    // "targets": [7, 8],
                    "orderable": false,
                    "searchable": false
                } ],
                columns: [
                    { data: 'transaction_date', name: 'transaction_date'  },
                    { data: 'invoice_no', name: 'invoice_no'},
                    { data: 'parent_sale', name: 'T1.invoice_no'},
                    { data: 'name', name: 'contacts.name'},
                    { data: 'business_location', name: 'bl.name'},
                    { data: 'action', name: 'action'}
                ],
                "fnDrawCallback": function (oSettings) {
                    var total_sell = sum_table_col($('#sell_return_table'), 'final_total');
                    $('#footer_sell_return_total').text(total_sell);
                    
                    $('#footer_payment_status_count_sr').html(__sum_status_html($('#sell_return_table'), 'payment-status-label'));

                    var total_due = sum_table_col($('#sell_return_table'), 'payment_due');
                    $('#footer_total_due_sr').text(total_due);

                    __currency_convert_recursively($('#sell_return_table'));
                },
                createdRow: function( row, data, dataIndex ) {
                    $( row ).find('td:eq(2)').attr('class', 'clickable_td');
                }
            });
        }

        $(document).on('change', '#sell_return_filter_location_id, #sell_return_filter_customer_id, #sell_return_filter_payment_status,#created_by',  function() {
            sell_return_table.ajax.reload();
        });
    })
</script>
<script src="{{ asset('js/sellreturngenralstore.js?v=' . $asset_v) }}"></script>	
@endsection

