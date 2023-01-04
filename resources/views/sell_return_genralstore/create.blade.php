@extends('layouts.app')
@section('title', __('sellreturn.add_sell_return'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('sellreturn.add_sell_return') <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('sell_return_genralstore.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i></h1>
</section>

<!-- Main content -->
<section class="content no-print">

	<!-- Page level currency setting -->
	<input type="hidden" id="p_code" value="{{$currency_details->code}}">
	<input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
	<input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
	<input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

	@include('layouts.partials.error')

	{!! Form::open(['url' => action('SellReturnGenralstoreController@store'), 'method' => 'post', 'id' => 'add_sellreturn_form', 'files' => true ]) !!}
	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<div class="@if(!empty($default_sellreturn_status)) col-sm-7 @else col-sm-6 @endif">
				<div class="form-group">
					{!! Form::label('supplier_id', __('contact.customer') . ':*') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-user"></i>
						</span>
						{!! Form::select('contact_id', [], null, ['class' => 'form-control', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'customer_id']); !!}
						<span class="input-group-btn">
							<button type="button" class="btn btn-default bg-white btn-flat add_new_customer" data-name=""><i class="fa fa-plus-circle text-primary fa-lg"></i></button>
						</span>
					</div>
				</div>
			</div>
			<div class="@if(!empty($default_sellreturn_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('ref_no', __('sellreturn.ref_no').':') !!}
					{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
				</div>
			</div>
			<div class="@if(!empty($default_sellreturn_status)) col-sm-4 @else col-sm-3 @endif">
				<div class="form-group">
					{!! Form::label('transaction_date', __('sellreturn.sell_return_date') . ':*') !!}
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-calendar"></i>
						</span>
						{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
					</div>
				</div>
			</div>
			<!-- <div class="col-sm-3 @if(!empty($default_sellreturn_status)) hide @endif">
				<div class="form-group">
					{!! Form::label('status', __('sellreturn.sellreturn_status') . ':*') !!} @show_tooltip(__('tooltip.order_status'))
					{!! Form::select('status', $orderStatuses, $default_sellreturn_status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div> -->

			
			<div class="clearfix"></div>
			@if(count($business_locations) == 1)
				@php 
					$default_location = current(array_keys($business_locations->toArray())) 
				@endphp
			@else
				@php $default_location = null; @endphp
			@endif
			<div class="col-sm-3">
				<div class="form-group">
					{!! Form::label('location_id', __('sellreturn.business_location').':*') !!}
					@show_tooltip(__('tooltip.sellreturn_location'))
					{!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required']); !!}
				</div>
			</div>
			
			<!-- Currency Exchange Rate -->
			<div class="col-sm-3 @if(!$currency_details->sellreturn_in_diff_currency) hide @endif">
				<div class="form-group">
					{!! Form::label('exchange_rate', __('sellreturn.p_exchange_rate') . ':*') !!}
					@show_tooltip(__('tooltip.currency_exchange_factor'))
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-info"></i>
						</span>
						{!! Form::number('exchange_rate', $currency_details->p_exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); !!}
					</div>
					<span class="help-block text-danger">
						@lang('sellreturn.diff_sellreturn_currency_help', ['currency' => $currency_details->name])
					</span>
				</div>
			</div>

			<div class="col-sm-3">
                <div class="form-group">
                    {!! Form::label('document', __('sellreturn.attach_document') . ':') !!}
                    {!! Form::file('document', ['id' => 'upload_document']); !!}
                    <p class="help-block">@lang('sellreturn.max_file_size', ['size' => (config('constants.document_size_limit') / 1000000)])</p>
                </div>
            </div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<div class="col-sm-6">
				<div class="form-group">
					<div class="input-group">
						<span class="input-group-addon">
							<i class="fa fa-search"></i>
						</span>
						{!! Form::text('search_product', null, ['class' => 'form-control mousetrap', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autofocus']); !!}
						<input type="hidden" name="variation_id" id="variation_id" value="0">
						<input type="hidden" name="product_id" id="product_id" value="0">
					</div>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<div class="input-group">
						{!! Form::text('product_qty','', ['class' => 'form-control input_number', 'placeholder' => __('sale.qty'),
						'id' => 'product_qty']); !!}
					</div>
				</div>
			</div>
						
			<div class="col-sm-2">
				<div class="form-group">
					<button type="button" id="add-product" class="btn btn-primary pull-right btn-flat">@lang('messages.add')</button>
				</div>
			</div>
			<div class="col-sm-2">
				<div class="form-group">
					<button tabindex="-1" type="button" class="btn btn-link btn-modal"data-href="{{action('ProductController@quickAdd')}}" 
            	data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
				</div>
			</div>
		</div>
		@php
			$hide_tax = '';
			$hide_price='';
			if( session()->get('business.enable_inline_tax') == 0){
				$hide_tax = 'hide';
			}
			if(!auth()->user()->show_price)
			{
				$hide_price='hide';
			}
		@endphp
		<div class="row">
			<div class="col-sm-12">
				<div class="table-responsive">
					<table class="table table-condensed table-bordered table-th-green text-center table-striped" id="sellreturn_entry_table">
						<thead>
							<tr>
								<th>#</th>
								<th>@lang( 'product.product_name' )</th>
								<th>@lang( 'sale.qty' )</th>
								<th class="{{$hide_price}}">@lang( 'sellreturn.unit_cost_before_tax' )</th>
								<th class="{{$hide_tax}} {{$hide_price}}">@lang( 'sellreturn.subtotal_before_tax' )</th>
								<th class="{{$hide_tax}} {{$hide_price}}">@lang( 'sellreturn.product_tax' )</th>
								<th class="{{$hide_tax}} {{$hide_price}}">@lang( 'sellreturn.net_cost' )</th>
								<th class="{{$hide_price}}">@lang( 'sellreturn.line_total' )</th>
								
								<th><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
				<hr/>
				<div class="pull-right col-md-5">
					<table class="pull-right col-md-12">
						<tr class="hide">
							<th class="col-md-7 text-right">@lang( 'sellreturn.total_before_tax' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_st_before_tax" class="display_currency"></span>
								<input type="hidden" id="st_before_tax_input" value=0>
							</td>
						</tr>
						<tr class="{{$hide_price}}">
							<th class="col-md-7 text-right {{$hide_price}}">@lang( 'sellreturn.net_total_amount' ):</th>
							<td class="col-md-5 text-left">
								<span id="total_subtotal" class="display_currency"></span>
								<!-- This is total before sellreturn tax-->
								<input type="hidden" id="total_subtotal_input" value=0  name="total_before_tax">
							</td>
						</tr>
					</table>
				</div>

				<input type="hidden" id="row_count" value="0">
			</div>
		</div>
	@endcomponent

	@component('components.widget', ['class' => 'box-primary'])
		<div class="row">
			<div class="col-sm-12">
				<table class="table">
					<tr class="{{$hide_price}}">
						<th class="col-md-10 text-right"><b>@lang('sellreturn.sellreturn_total'): </b></th>
						<td class="col-md-2 text-left">
							{!! Form::hidden('final_total', 0 , ['id' => 'grand_total_hidden']); !!}
							<span id="grand_total" class="display_currency" data-currency_symbol='true'>0</span>
						</td>
					</tr>
					
					<tr>
						<td colspan="4">
							<div class="form-group">
								{!! Form::label('additional_notes',__('sellreturn.additional_notes')) !!}
								{!! Form::textarea('additional_notes', null, ['class' => 'form-control required', 'rows' => 3]); !!}
							</div>
						</td>
					</tr>

				</table>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<button type="button" id="submit_sellreturn_form" class="btn btn-primary pull-right btn-flat">@lang('messages.save')</button>
			</div>
		</div>
	@endcomponent

	

{!! Form::close() !!}
</section>
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
	@include('contact.create', ['quick_add' => true])
</div>
<!-- /.content -->
@endsection

@section('javascript')
	<script src="{{ asset('js/sellreturngenralstore.js?v=' . $asset_v) }}"></script>
	<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
	@include('sell_return_genralstore.partials.keyboard_shortcuts')
@endsection
