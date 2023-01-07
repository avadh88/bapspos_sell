<?php
//dd($sellorder->sellorder_lines);
?>
@foreach($sellorder->sellorder_lines as $key =>$sellOrderDetails)

<tr class="product_row" data-row_index="{{$key}}">
    <td>

        <div data-toggle="tooltip" data-placement="bottom" title="Edit product Unit Price and Tax">
            <span class="text-link text-info cursor-pointer font_size_15" data-toggle="modal" data-target="#row_edit_product_price_modal_1">
                {{$sellOrderDetails->product['name']}}<i class="fa fa-info-circle"></i>
            </span>
        </div>
        <input type="hidden" class="enable_sr_no" value="0">

    </td>

    <td>

        <input type="hidden" name="products[{{$key}}][unit_price]" class="form-control pos_unit_price input_number mousetrap" value="{{@num_format($sellOrderDetails['purchase_price'])}}">
        <input type="hidden" name="products[{{$key}}][line_discount_type]" class="form-control pos_unit_price input_number mousetrap" value="fixed">
        <input type="hidden" name="products[{{$key}}][line_discount_amount]" class="form-control pos_unit_price input_number mousetrap" value="0.00">
        <input type="hidden" name="products[{{$key}}][item_tax]" class="form-control pos_unit_price input_number mousetrap" value="0.00">
        <input type="hidden" name="products[{{$key}}][tax_id]" class="form-control pos_unit_price input_number mousetrap" value="">
        <input type="hidden" name="products[{{$key}}][sell_line_note]" class="form-control pos_unit_price input_number mousetrap" value="">
        
        <input type="hidden" name="products[{{$key}}][product_id]" class="form-control product_id" value="{{$sellOrderDetails['product_id']}}">

        <input type="hidden" value="{{$sellOrderDetails['product_id']}}" name="products[{{$key}}][variation_id]" class="row_variation_id">
        <input type="hidden" value="{{$sellOrderDetails->product['category_id']}}" name="products[{{$key}}][category_id]" class="row_category_id">

        <input type="hidden" value="{{$sellOrderDetails->product['enable_stock']}}" name="products[{{$key}}][enable_stock]">


        <div class="input-group input-number">
            <!-- <span class="input-group-btn"><button type="button" class="btn btn-default btn-flat quantity-down"><i class="fa fa-minus text-danger"></i></button></span> -->

            <input type="text" data-min="1" class="form-control pos_quantity input_number mousetrap input_quantity valid" value="{{$sellOrderDetails['quantity']}}" name="products[{{$key}}][quantity]" readonly=true data-allow-overselling="false" data-decimal="1" data-rule-required="true" data-msg-required="This field is required"  data-rule-more_then_zero="true" data-msg-more_then_zero="Product qty should be more then zero" aria-required="true" aria-invalid="false">

            <!-- <span class="input-group-btn"><button type="button" class="btn btn-default btn-flat quantity-up"><i class="fa fa-plus text-success"></i></button></span> -->
        </div>

        <input type="hidden" name="products[{{$key}}][product_unit_id]" value="{{$sellOrderDetails->product['unit_id']}}">
        {{$sellOrderDetails->product->unit['actual_name']}}

        <input type="hidden" class="base_unit_multiplier" name="products[{{$key}}][base_unit_multiplier]" value="1">

        <input type="hidden" class="hidden_base_unit_sell_price" value="{{$sellOrderDetails['purchase_price']}}">

    </td>
    <td class="hide">
        <input type="text" name="products[{{$key}}][unit_price_inc_tax]" class="form-control pos_unit_price_inc_tax input_number" value="{{$sellOrderDetails['purchase_price']*$sellOrderDetails['quantity']}}">
    </td>
    <td class="text-center v-center hide">
        <input type="hidden" class="form-control pos_line_total " value="{{$sellOrderDetails['purchase_price']*$sellOrderDetails['quantity']}}">
        <span class="display_currency pos_line_total_text " data-currency_symbol="true">{{$sellOrderDetails['purchase_price']*$sellOrderDetails['quantity']}}</span>
    </td>
    <td class="text-center">
        <i class="fa fa-close text-danger pos_remove_row cursor-pointer" aria-hidden="true"></i>
    </td>
</tr>

@endforeach

<?php 
//dd($sellOrderDetails->product['name']); 
?>