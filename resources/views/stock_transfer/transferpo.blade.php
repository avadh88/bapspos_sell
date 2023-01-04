@extends('layouts.app')
@section('title', __('lang_v1.add_stock_transfer'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>@lang('lang_v1.add_stock_transfer')</h1>
</section>

<section class="content-header">
	<div class="row">
    <div class="col-sm-2">
        <label>Purchase Order No
    </div>
    <div class="col-sm-2">
        <div class="form-group">
        <div class="input-group">
            <input class="form-control" placeholder="PO" name="po" type="text" value="" id="po">
        </div>
        </div>
    </div>
    <div class="col-sm-1">
        <div class="form-group">
            <button type="button" id="fetch-po-details" class="btn btn-primary pull-right btn-flat">Fetch Details</button>
        </div>
    </div>
		
	</div>
</section>

<!-- Main content -->
<section class="content no-print">
	{!! Form::open(['url' => action('StockTransferController@store'), 'method' => 'post', 'id' => 'stock_transfer_form' ]) !!}
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transaction_date', __('messages.date') . ':*') !!}
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-calendar"></i>
							</span>
							{!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']); !!}
						</div>
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('ref_no', __('purchase.ref_no').':') !!}
						{!! Form::text('ref_no', null, ['class' => 'form-control']); !!}
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('location_id', __('lang_v1.location_from').':*') !!}
						{!! Form::select('location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => '']); !!}
					</div>
				</div>
				<div class="col-sm-3">
					<div class="form-group">
						{!! Form::label('transfer_location_id', __('lang_v1.location_to').':*') !!}
						{!! Form::select('transfer_location_id', $business_locations, null, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required', 'id' => 'transfer_location_id']); !!}
					</div>
				</div>
				
			</div>
		</div>
	</div> <!--box end-->
	<div class="box box-solid">
		<div class="box-header">
        	<h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
       	</div>
		<div class="box-body">
			<div class="row">
				<div class="col-sm-6 col-sm-offset-1">
					<div class="form-group">
						<div class="input-group">
							<span class="input-group-addon">
								<i class="fa fa-search"></i>
							</span>
							{!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product_for_srock_adjustment', 'placeholder' => __('stock_adjustment.search_product'), 'disabled']); !!}
							<input type="hidden" name="variation_id" id="variation_id" value="0">
						    <input type="hidden" name="product_id" id="product_id" value="0">
						</div>
					</div>
				</div>
				<div class="col-sm-2">
				  <div class="form-group">
					<div class="input-group">
						{!! Form::text('product_current_qty','', ['class' => 'form-control input_number', 'placeholder' => __('Current Stock'),
						'id' => 'product_current_qty','readonly'=>'readonly']); !!}
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
						
				<div class="col-sm-1">
					<div class="form-group">
						<button type="button" id="add-product" class="btn btn-primary pull-right btn-flat">@lang('messages.add')</button>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-10 col-sm-offset-1">
					<input type="hidden" id="product_row_index" value="0">
					<input type="hidden" id="total_amount" name="final_total" value="0">
					<div class="table-responsive">
					<table class="table table-bordered table-striped table-condensed" 
					id="stock_adjustment_product_table">
						<thead>
							<tr>
								<th class="col-sm-4 text-center">	
									@lang('sale.product')
								</th>
								<th class="col-sm-4 text-center">	
									Current Stock
								</th>
								<th class="col-sm-2 text-center">
									Transfer Qty
								</th>
								<th class="col-sm-2 text-center">
									@lang('sale.unit_price')
								</th>
								<th class="col-sm-2 text-center">
									@lang('sale.subtotal')
								</th>
								<th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
							</tr>
						</thead>
						<tbody>
						</tbody>
						<tfoot>
							<tr class="text-center"><td colspan="location_id"></td><td></td><td></td><td></td><td><div class="pull-right"><b>@lang('stock_adjustment.total_amount'):</b> <span id="total_adjustment">0.00</span></div></td></tr>
						</tfoot>
					</table>
					</div>
				</div>
			</div>
		</div>
	</div> <!--box end-->
	<div class="box box-solid">
		<div class="box-body">
			<div class="row">
				<div class="col-sm-4">
					<div class="form-group">
							{!! Form::label('shipping_charges', __('lang_v1.shipping_charges') . ':') !!}
							{!! Form::text('shipping_charges', 0, ['class' => 'form-control input_number', 'placeholder' => __('lang_v1.shipping_charges')]); !!}
					</div>
				</div>
				<div class="col-sm-4">
					<div class="form-group">
						{!! Form::label('additional_notes',__('purchase.additional_notes')) !!}
						{!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-12">
					<button type="submit" id="save_stock_transfer" class="btn btn-primary pull-right">@lang('messages.save')</button>
				</div>
			</div>

		</div>
	</div> <!--box end-->
	{!! Form::close() !!}
</section>
@stop
@section('javascript')
	<script src="{{ asset('js/stock_transfer.js?v=' . $asset_v) }}"></script>
@endsection
