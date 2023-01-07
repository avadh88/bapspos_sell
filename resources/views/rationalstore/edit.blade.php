@extends('layouts.app')
@section('title', __('rationalstore.edit_rationalstore'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
  <h1>@lang('rationalstore.edit_rationalstore') <i class="fa fa-keyboard-o hover-q text-muted" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="bottom" data-content="@include('rationalstore.partials.keyboard_shortcuts_details')" data-html="true" data-trigger="hover" data-original-title="" title=""></i></h1>
</section>

<!-- Main content -->
<section class="content">

  <!-- Page level currency setting -->
  <input type="hidden" id="p_code" value="{{$currency_details->code}}">
  <input type="hidden" id="p_symbol" value="{{$currency_details->symbol}}">
  <input type="hidden" id="p_thousand" value="{{$currency_details->thousand_separator}}">
  <input type="hidden" id="p_decimal" value="{{$currency_details->decimal_separator}}">

  @include('layouts.partials.error')

  {!! Form::open(['url' => action('RationalStoreController@update' , [$rationalstore->id] ), 'method' => 'PUT', 'id' => 'add_rationalstore_form', 'files' => true ]) !!}

  @php
  $currency_precision = config('constants.currency_precision', 2);
  @endphp

  <input type="hidden" id="rationalstore_id" value="{{ $rationalstore->id }}">

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="@if(!empty($default_rationalstore_status)) col-sm-4 @else col-sm-3 @endif">
      <div class="form-group">
        {!! Form::label('ref_no', __('rationalstore.ref_no') . '*') !!}
        {!! Form::text('ref_no', $rationalstore->ref_no, ['class' => 'form-control', 'required']); !!}
      </div>
    </div>

    <div class="@if(!empty($default_rationalstore_status)) col-sm-4 @else col-sm-3 @endif">
      <div class="form-group">
        {!! Form::label('transaction_date', __('rationalstore.rationing_date') . ':*') !!}
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-calendar"></i>
          </span>
          {!! Form::text('transaction_date', @format_datetime($rationalstore->transaction_date), ['class' => 'form-control', 'readonly', 'required']); !!}
        </div>
      </div>
    </div>

    <div class="col-sm-3 @if(!empty($default_rationalstore_status)) hide @endif">
      <div class="form-group">
        {!! Form::label('status', __('rationalstore.rationalstore_status') . ':*') !!}
        @show_tooltip(__('tooltip.order_status'))
        {!! Form::select('status', $orderStatuses, $rationalstore->status, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select') , 'required']); !!}
      </div>
    </div>

    <div class="clearfix"></div>

    <div class="col-sm-3">
      <div class="form-group">
        {!! Form::label('location_id', __('rationalstore.business_location').':*') !!}
        @show_tooltip(__('tooltip.rationalstore_location'))
        {!! Form::select('location_id', $business_locations, $rationalstore->location_id, ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'disabled']); !!}
      </div>
    </div>

    <!-- Currency Exchange Rate -->
    <div class="col-sm-3 @if(!$currency_details->rationalstore_in_diff_currency) hide @endif">
      <div class="form-group">
        {!! Form::label('exchange_rate', __('rationalstore.p_exchange_rate') . ':*') !!}
        @show_tooltip(__('tooltip.currency_exchange_factor'))
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-info"></i>
          </span>
          {!! Form::number('exchange_rate', $rationalstore->exchange_rate, ['class' => 'form-control', 'required', 'step' => 0.001]); !!}
        </div>
        <span class="help-block text-danger">
          @lang('rationalstore.diff_rationalstore_currency_help', ['currency' => $currency_details->name])
        </span>
      </div>
    </div>
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="col-sm-4">
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
          {!! Form::text('product_qty','', ['class' => 'form-control input_number', 'placeholder' => __('rationalstore.qty'),
          'id' => 'product_qty']); !!}
        </div>
      </div>
    </div>
    <div class="col-sm-2">
      <div class="form-group">
        <div class="input-group">
          <span class="input-group-addon">
            <i class="fa fa-calendar"></i>
          </span>
          {!! Form::text('rationalstoredate', @format_date('now'), ['class' => 'form-control', 'required','id'=>'rationalstoredate']); !!}
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
        <button tabindex="-1" type="button" class="btn btn-link btn-modal" data-href="{{action('ProductController@quickAdd')}}" data-container=".quick_add_product_modal"><i class="fa fa-plus"></i> @lang( 'product.add_new_product' ) </button>
      </div>
    </div>

  </div>

  <div class="row">
    <div class="col-sm-12">
      @include('rationalstore.partials.edit_rationalstore_entry_row')

      <hr />
      <div class="pull-right col-md-5">
        <table class="pull-right col-md-12">
          <tr class="hide">
            <th class="col-md-7 text-right">@lang( 'rationalstore.total_before_tax' ):</th>
            <td class="col-md-5 text-left">
              <span id="total_st_before_tax" class="display_currency"></span>
              <input type="hidden" id="st_before_tax_input" value=0>
            </td>
          </tr>
          <tr>
            <th class="col-md-7 text-right">@lang( 'rationalstore.net_total_amount' ):</th>
            <td class="col-md-5 text-left">
              <span id="total_subtotal" class="display_currency">{{$rationalstore->total_before_tax/$rationalstore->exchange_rate}}</span>
              <!-- This is total before rationalstore tax-->
              <input type="hidden" id="total_subtotal_input" value="{{$rationalstore->total_before_tax/$rationalstore->exchange_rate}}" name="total_before_tax">
            </td>
          </tr>
        </table>
      </div>

    </div>
  </div>
  @endcomponent

  @component('components.widget', ['class' => 'box-primary'])
  <div class="row">
    <div class="col-sm-12">
      <table class="table">
        <tr>
          <th class="col-md-10 text-right">@lang('rationalstore.rationalstore_total'):</th>
          <td class="col-md-2 text-left">
            {!! Form::hidden('final_total', $rationalstore->final_total , ['id' => 'grand_total_hidden']); !!}
            <span id="grand_total" class="display_currency" data-currency_symbol='true'>{{$rationalstore->final_total}}</span>
          </td>
        </tr>
        <tr>
          <td colspan="4">
            <div class="form-group">
              {!! Form::label('additional_notes',__('rationalstore.additional_notes')) !!}
              {!! Form::textarea('additional_notes', $rationalstore->additional_notes, ['class' => 'form-control', 'rows' => 3]); !!}
            </div>
          </td>
        </tr>

      </table>
    </div>
  </div>
  @endcomponent

  <div class="row">
    <div class="col-sm-12">
      <button type="button" id="submit_rational_form" class="btn btn-primary pull-right btn-flat">@lang('messages.update')</button>
    </div>
  </div>
  {!! Form::close() !!}
</section>
<!-- /.content -->
<!-- quick product modal -->
<div class="modal fade quick_add_product_modal" tabindex="-1" role="dialog" aria-labelledby="modalTitle"></div>
<div class="modal fade contact_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
  @include('contact.create', ['quick_add' => true])
</div>

@endsection

@section('javascript')
<script src="{{ asset('js/rationing.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/product.js?v=' . $asset_v) }}"></script>
<script type="text/javascript">
  $(document).ready(function() {
    update_table_total();
    update_grand_total();
  });
</script>
@include('rationalstore.partials.keyboard_shortcuts')
@endsection