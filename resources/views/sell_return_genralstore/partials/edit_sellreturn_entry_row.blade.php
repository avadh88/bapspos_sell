
@php
    $hide_tax = '';
    if( session()->get('business.enable_inline_tax') == 0){
        $hide_tax = 'hide';
    }
    $currency_precision = config('constants.currency_precision', 2);
    $quantity_precision = config('constants.quantity_precision', 2);

    $hide_price='';
    if(!auth()->user()->show_price)
    {
        $hide_price='hide';
    }
@endphp
<div class="table-responsive">
    <table class="table table-condensed table-bordered table-th-green text-center table-striped" 
    id="sellreturn_entry_table">
        <thead>
              <tr>
                <th>#</th>
                <th>@lang( 'product.product_name' )</th>
                <th>@lang( 'sellreturn.sellreturn_quantity' )</th>
                <th class="{{$hide_price}}">@lang( 'lang_v1.unit_cost_before_discount' )</th>
                <!-- <th>@lang( 'lang_v1.discount_percent' )</th>
                <th>@lang( 'sellreturn.unit_cost_before_tax' )</th> -->
                <th class="{{$hide_price}} {{$hide_tax}}">@lang( 'sellreturn.subtotal_before_tax' )</th>
                <th class="{{$hide_price}} {{$hide_tax}}">@lang( 'sellreturn.product_tax' )</th>
                <th class="{{$hide_price}} {{$hide_tax}}">@lang( 'sellreturn.net_cost' )</th>
                <th class="{{$hide_price}}">@lang( 'sellreturn.line_total' )</th>
                <th class="@if(!session('business.enable_editing_product_from_sellreturn')) hide @endif">
                    @lang( 'lang_v1.profit_margin' )
                </th>
                <!-- <th>@lang( 'sellreturn.unit_selling_price')</th> -->
                @if(session('business.enable_lot_number'))
                    <th>
                        @lang('lang_v1.lot_number')
                    </th>
                @endif
                @if(session('business.enable_product_expiry'))
                    <th>@lang('product.mfg_date') / @lang('product.exp_date')</th>
                @endif
                <th>
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </th>
              </tr>
        </thead>
        <tbody>
    <?php $row_count = 0; ?>
    @foreach($sellreturn->sellreturngenralstore_lines as $sellreturn_line)
        <tr>
            <td><span class="sr_number"></span></td>
            <td>
                {{ $sellreturn_line->product->name }} ({{$sellreturn_line->variations->sub_sku}})
                @if( $sellreturn_line->product->type == 'variable') 
                    <br/>(<b>{{ $sellreturn_line->variations->product_variation->name}}</b> : {{ $sellreturn_line->variations->name}})
                @endif
            </td>

            <td>
                {!! Form::hidden('products[' . $loop->index . '][product_id]', $sellreturn_line->product_id ); !!}
                {!! Form::hidden('products[' . $loop->index . '][variation_id]', $sellreturn_line->variation_id,['class'=>'hidden_variation_id'] ); !!}
                {!! Form::hidden('products[' . $loop->index . '][sellreturn_line_id]',
                $sellreturn_line->id); !!}

                @php
                    $check_decimal = 'false';
                    if($sellreturn_line->product->unit->allow_decimal == 0){
                        $check_decimal = 'true';
                    }
                @endphp
            
                {!! Form::text('products[' . $loop->index . '][quantity]', 
                number_format($sellreturn_line->quantity, $quantity_precision, $currency_details->decimal_separator, $currency_details->thousand_separator),
                ['class' => 'form-control input-sm sellreturn_quantity input_number mousetrap', 'required', 'data-rule-abs_digit' => $check_decimal, 'data-msg-abs_digit' => __('lang_v1.decimal_value_not_allowed')]); !!} 

                <input type="hidden" class="base_unit_cost" value="{{$sellreturn_line->variations->default_sellreturn_price}}">
                @if(count($sellreturn_line->product->unit->sub_units) > 0)
                    <br>
                    <select name="products[{{$loop->index}}][sub_unit_id]" value="{{$sellreturn_line->sub_unit_id}}" class="form-control input-sm sub_unit">
                            <option value="{{$sellreturn_line->product->unit->id}}" data-multiplier="1">{{$sellreturn_line->product->unit->short_name}}</option>
                        @foreach($sellreturn_line->product->unit->sub_units as $sub_unit)
                            <option value="{{$sub_unit->id}}" data-multiplier="{{$sub_unit->base_unit_multiplier}}" @if($sub_unit->id == $sellreturn_line->sub_unit_id) selected @endif >
                                {{$sub_unit->short_name}}
                            </option>
                        @endforeach
                    </select>
                @else 
                    {{ $sellreturn_line->product->unit->short_name }}
                @endif

                <input type="hidden" name="products[{{$loop->index}}][product_unit_id]" value="{{$sellreturn_line->product->unit->id}}">

                <input type="hidden" class="base_unit_selling_price" value="{{$sellreturn_line->variations->default_sell_price}}">
            </td>
            <!-- <td>
                {!! Form::text('sellreturn[' . $loop->index . '][pp_without_discount]', number_format($sellreturn_line->pp_without_discount/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm sellreturn_unit_cost_without_discount input_number', 'required']); !!}
            </td> -->
            <!-- <td>
                {!! Form::text('sellreturn[' . $loop->index . '][discount_percent]', number_format($sellreturn_line->discount_percent, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm inline_discounts input_number', 'required']); !!} <b>%</b>
            </td> -->
            <td class="{{$hide_price}}">
                {!! Form::text('products[' . $loop->index . '][purchase_price]', 
                number_format($sellreturn_line->unit_price/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm sellreturn_unit_cost input_number', 'required']); !!}
            </td>
            <td class="{{$hide_tax}}">
                <span class="row_subtotal_before_tax">
                    {{number_format($sellreturn_line->quantity * $sellreturn_line->unit_price/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                </span>
                <input type="hidden" class="row_subtotal_before_tax_hidden" value="{{number_format($sellreturn_line->quantity * $sellreturn_line->unit_price/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}">
            </td>

            <td class="{{$hide_tax}}">
                <div class="input-group">
                    <select name="products[{{ $loop->index }}][sellreturn_line_tax_id]" class="form-control input-sm sellreturn_line_tax_id" placeholder="'Please Select'">
                        <option value="" data-tax_amount="0" @if( empty( $sellreturn_line->tax_id ) )
                        selected @endif >@lang('lang_v1.none')</option>
                        @foreach($taxes as $tax)
                            <option value="{{ $tax->id }}" data-tax_amount="{{ $tax->amount }}" @if( $sellreturn_line->tax_id == $tax->id) selected @endif >{{ $tax->name }}</option>
                        @endforeach
                    </select>
                    <span class="input-group-addon sellreturn_product_unit_tax_text">
                        {{number_format($sellreturn_line->item_tax/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                    </span>
                    {!! Form::hidden('sellreturn[' . $loop->index . '][item_tax]', number_format($sellreturn_line->item_tax/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'sellreturn_product_unit_tax']); !!}
                </div>
            </td>
            <td class="{{$hide_tax}}">
                {!! Form::text('products[' . $loop->index . '][sellreturn_price_inc_tax]', number_format($sellreturn_line->sellreturn_price_inc_tax/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm sellreturn_unit_cost_after_tax input_number', 'required']); !!}
            </td>
            <td class="{{$hide_price}}">
                <span class="row_subtotal_after_tax">
                {{number_format($sellreturn_line->unit_price_inc_tax * $sellreturn_line->quantity/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                </span>
                <input type="hidden" class="row_subtotal_after_tax_hidden" value="{{number_format($sellreturn_line->unit_price_inc_tax * $sellreturn_line->quantity/$sellreturn->exchange_rate, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}">
            </td>

            <td class="@if(!session('business.enable_editing_product_from_sellreturn')) hide @endif">
                @php
                    $pp = $sellreturn_line->sellreturn_price;
                    $sp = $sellreturn_line->variations->default_sell_price;
                    if(!empty($sellreturn_line->sub_unit->base_unit_multiplier)) {
                        $sp = $sp * $sellreturn_line->sub_unit->base_unit_multiplier;
                    }
                    if($pp == 0){
                        $profit_percent = 100;
                    } else {
                        $profit_percent = (($sp - $pp) * 100 / $pp);
                    }
                @endphp
                
                {!! Form::text('products[' . $loop->index . '][profit_percent]', 
                number_format($profit_percent, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), 
                ['class' => 'form-control input-sm input_number profit_percent', 'required']); !!}
            </td>

            <!-- <td>
                @if(session('business.enable_editing_product_from_sellreturn'))
                    {!! Form::text('sellreturn[' . $loop->index . '][default_sell_price]', number_format($sp, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator), ['class' => 'form-control input-sm input_number default_sell_price', 'required']); !!}
                @else
                    {{number_format($sp, $currency_precision, $currency_details->decimal_separator, $currency_details->thousand_separator)}}
                @endif

            </td> -->
            @if(session('business.enable_lot_number'))
                <td>
                    {!! Form::text('products[' . $loop->index . '][lot_number]', $sellreturn_line->lot_number, ['class' => 'form-control input-sm']); !!}
                </td>
            @endif

            @if(session('business.enable_product_expiry'))
                <td style="text-align: left;">
                    @php
                        $expiry_period_type = !empty($sellreturn_line->product->expiry_period_type) ? $sellreturn_line->product->expiry_period_type : 'month';
                    @endphp
                    @if(!empty($expiry_period_type))
                    <input type="hidden" class="row_product_expiry" value="{{ $sellreturn_line->product->expiry_period }}">
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
                    @php
                        $mfg_date = null;
                        $exp_date = null;
                        if(!empty($sellreturn_line->mfg_date)){
                            $mfg_date = $sellreturn_line->mfg_date;
                        }
                        if(!empty($sellreturn_line->exp_date)){
                            $exp_date = $sellreturn_line->exp_date;
                        }
                    @endphp
                    <div class="input-group @if($hide_mfg) hide @endif">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('products[' . $loop->index . '][mfg_date]', !empty($mfg_date) ? @format_date($mfg_date) : null, ['class' => 'form-control input-sm expiry_datepicker mfg_date', 'readonly']); !!}
                    </div>
                    <b><small>@lang('product.exp_date'):</small></b>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        {!! Form::text('products[' . $loop->index . '][exp_date]', !empty($exp_date) ? @format_date($exp_date) : null, ['class' => 'form-control input-sm expiry_datepicker exp_date', 'readonly']); !!}
                    </div>
                    @else
                    <div class="text-center">
                        @lang('product.not_applicable')
                    </div>
                    @endif
                </td>
            @endif

            <td><i class="fa fa-times remove_sellreturn_entry_row text-danger" title="Remove" style="cursor:pointer;"></i></td>
        </tr>
        <?php $row_count = $loop->index + 1 ; ?>
    @endforeach
        </tbody>
    </table>
</div>
<input type="hidden" id="row_count" value="{{ $row_count }}">