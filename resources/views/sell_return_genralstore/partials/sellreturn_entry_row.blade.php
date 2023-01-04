@php
$hide_price='';
if(!auth()->user()->show_price)
{
    $hide_price='hide';
}
@endphp
@foreach( $variations as $variation)
    <tr>
        <td><span class="sr_number"></span></td>
        <td>
            {{ $product->name }} ({{$variation->sub_sku}})
            @if( $product->type == 'variable' )
                <br/>
                (<b>{{ $variation->product_variation->name }}</b> : {{ $variation->name }})
            @endif
        </td>@php

@endphp
        <td style="font-size: 15px;">
            {!! Form::hidden('products[' . $row_count . '][product_id]', $product->id ); !!}
            {!! Form::hidden('products[' . $row_count . '][variation_id]', $variation->id , ['class' => 'hidden_variation_id']); !!}

            @php
                $check_decimal = 'false';
                if($product->unit->allow_decimal == 0){
                    $check_decimal = 'true';
                }
                $currency_precision = config('constants.currency_precision', 2);
                $quantity_precision = config('constants.quantity_precision', 2);
            @endphp
            {!! Form::text('products[' . $row_count . '][quantity]', number_format($product_qty, $quantity_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm sellreturn_quantity input_number mousetrap', 'required','data-rule-more_then_zero'=>'true','data-msg-more_then_zero'=>__('lang_v1.more_then_zero'), 'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' => __('lang_v1.decimal_value_not_allowed')]); !!}
            <input type="hidden" class="base_unit_cost" value="{{$variation->default_sellreturn_price}}">
            <input type="hidden" class="base_unit_selling_price" value="{{$variation->default_sell_price}}">

            <input type="hidden" name="products[{{$row_count}}][product_unit_id]" value="{{$product->unit->id}}">
            @if(!empty($sub_units))
                <br>
                <select name="products[{{$row_count}}][sub_unit_id]" class="form-control input-sm sub_unit">
                    @foreach($sub_units as $key => $value)
                        <option value="{{$key}}" data-multiplier="{{$value['multiplier']}}">
                            {{$value['name']}}
                        </option>
                    @endforeach
                </select>
            @else 
                {{ $product->unit->short_name }}
            @endif
        </td>
        
        <td class="{{$hide_price}}">
            {!! Form::text('products[' . $row_count . '][purchase_price]',
            number_format($variation->default_purchase_price, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm sellreturn_unit_cost input_number', 'required']); !!}
        </td>

        <td class="{{$hide_tax}} {{$hide_price}}">
            <span class="row_subtotal_before_tax display_currency">0</span>
            <input type="hidden" class="row_subtotal_before_tax_hidden" value=0>
        </td>

        <td class="{{$hide_tax}} {{$hide_price}}">
            <div class="input-group">
                <select name="products[{{ $row_count }}][sellreturn_line_tax_id]" class="form-control select2 input-sm sellreturn_line_tax_id" placeholder="'Please Select'">
                    <option value="" data-tax_amount="0" @if( $hide_tax == 'hide' )
                    selected @endif >@lang('lang_v1.none')</option>
                    @foreach($taxes as $tax)
                        <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" @if( $product->tax == $tax->id && $hide_tax != 'hide') selected @endif >{{ $tax->name }}</option>
                    @endforeach
                </select>
                {!! Form::hidden('products[' . $row_count . '][item_tax]', 0, ['class' => 'sellreturn_product_unit_tax']); !!}
                <span class="input-group-addon sellreturn_product_unit_tax_text">
                    0.00</span>
            </div>
        </td>

        <td class="{{$hide_tax}} {{$hide_price}}">
            @php
                $dpp_inc_tax = number_format($variation->dpp_inc_tax, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator);
                if($hide_tax == 'hide'){
                    $dpp_inc_tax = number_format($variation->default_sellreturn_price, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator);
                }

            @endphp
            {!! Form::text('sellreturn[' . $row_count . '][sellreturn_price_inc_tax]', $dpp_inc_tax, ['class' => 'form-control input-sm sellreturn_unit_cost_after_tax input_number', 'required']); !!}
        </td>
        
        <td class="{{$hide_price}}">
            <span class="row_subtotal_after_tax display_currency">0</span>
            <input type="hidden" class="row_subtotal_after_tax_hidden" value=0>
        </td>
        
        
        @if(session('business.enable_lot_number'))
            <td>
                {!! Form::text('sellreturn[' . $row_count . '][lot_number]', null, ['class' => 'form-control input-sm']); !!}
            </td>
        @endif
        @if(session('business.enable_product_expiry'))
            <td style="text-align: left;">

                {{-- Maybe this condition for checkin expiry date need to be removed --}}
                @php
                    $expiry_period_type = !empty($product->expiry_period_type) ? $product->expiry_period_type : 'month';
                @endphp
                @if(!empty($expiry_period_type))
                <input type="hidden" class="row_product_expiry" value="{{ $product->expiry_period }}">
                <input type="hidden" class="row_product_expiry_type" value="{{ $expiry_period_type }}">

                @if(session('business.expiry_type') == 'add_manufacturing')
                    @php
                        $hide_mfg = false;
                    @endphp
                @else
                    @php
                        $hide_mfg = true;
                    @endphp
                @endif

                <b class="@if($hide_mfg) hide @endif"><small>@lang('product.mfg_date'):</small></b>
                <div class="input-group @if($hide_mfg) hide @endif">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    {!! Form::text('products[' . $row_count . '][mfg_date]', null, ['class' => 'form-control input-sm expiry_datepicker mfg_date', 'readonly']); !!}
                </div>
                <b><small>@lang('product.exp_date'):</small></b>
                <div class="input-group">
                    <span class="input-group-addon">
                        <i class="fa fa-calendar"></i>
                    </span>
                    {!! Form::text('products[' . $row_count . '][exp_date]', null, ['class' => 'form-control input-sm expiry_datepicker exp_date', 'readonly']); !!}
                </div>
                @else
                <div class="text-center">
                    @lang('product.not_applicable')
                </div>
                @endif
            </td>
        @endif
        <?php $row_count++ ;?>

        <td><i class="fa fa-times remove_sellreturn_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
    </tr>
@endforeach

<input type="hidden" id="row_count" value="{{ $row_count }}">