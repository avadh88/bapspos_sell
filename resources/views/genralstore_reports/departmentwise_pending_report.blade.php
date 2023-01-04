@extends('layouts.app')
@section('title', __('report.departmentwisependingreport'))

@php

    $show_price=1;
    if(!auth()->user()->show_price)
    {
        $show_price=0;
    }
@endphp

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('report.departmentwisependingreport')}}</h1>
</section>

<!-- Main content -->
<section class="content">
    
            @component('components.filters', ['title' => __('report.filters')])
              {!! Form::open(['url' => action('GenralstoreReportController@getDepartmentWisePendingReport'), 'method' => 'get', 'id' => 'departwise_pending_report_filter_form' ]) !!}
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('location_id',  __('purchase.business_location') . ':') !!}
                                {!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('customer_id', __('contact.customer') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-user"></i>
                                    </span>
                                    {!! Form::select('customer_id', $customers, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
                                </div>
                                <label id="customer_id-error" class="error" for="customer_id"></label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                            {!! Form::label('search_product', __('lang_v1.search_product') . ':') !!}
                                <div class="input-group">
                                    <span class="input-group-addon">
                                        <i class="fa fa-search"></i>
                                    </span>
                                    <input type="hidden" value="" id="variation_id">
                                    {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('cg_date_range', __('report.date_range') . ':') !!}
                                {!! Form::text('date_range', @format_date('first day of this month') . ' ~ ' . @format_date('last day of this month'), ['placeholder' => __('lang_v1.select_a_date_range'), 'class' => 'form-control', 'id' => 'cg_date_range']); !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::label('show_disposable_product', __('Show Disposable Product') . ':') !!}
                                
                                <select name="show_disposable_product" id="show_disposable_product" class='form-control select2'>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::label('show_disposable_product', __('Show Only Pending Product') . ':') !!}
                                
                                <select name="show_only_pending_product" id="show_only_pending_product" class='form-control select2'>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group">
                            {!! Form::label('send_message', __('Send Message') . ':') !!}
                                
                                <select name="send_sms" id="send_sms" class='form-control select2'>
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                {!! Form::close() !!}
            @endcomponent
        
    <div class="row">
        <div class="col-md-12">
            <input type="hidden" name="show_price" id="show_price" value="{{$show_price}}"/>
            @component('components.widget', ['class' => 'box-primary'])
                @include('genralstore_reports.partials.departmentwise_pending_table')
            @endcomponent
        </div>
    </div>
</section>
<!-- /.content -->

@endsection

@section('javascript')
    <script src="{{ asset('js/genralstorereport.js?v=' . $asset_v) }}"></script>
    <script type="text/javascript">
        $(document).ready(function(){
            if($('#cg_date_range').length == 1){
                $('#cg_date_range').daterangepicker({
                    ranges: ranges,
                    autoUpdateInput: false,
                    locale: {
                        format: moment_date_format
                    }
                });
                
            }
        })
    </script>
    <style>
    
    </style>
@endsection