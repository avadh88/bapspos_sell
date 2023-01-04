@php

    $hide_price='';
    if(!auth()->user()->show_price)
    {
        $hide_price='hide';
    }
@endphp
<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('lang_v1.sell_return') (<b>@lang('sale.invoice_no'):</b> {{ $sell_return->ref_no }})
    </h4>
</div>
<div class="modal-body">
   <div class="row">
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_return_details'):</h4>
        <strong>@lang('lang_v1.return_date'):</strong> {{@format_date($sell_return->transaction_date)}}<br>
        <strong>@lang('contact.customer'):</strong> {{ $sell_return->contact->name }} <br>
        <strong>@lang('purchase.business_location'):</strong> {{ $sell_return->location->name }}
      </div>
      <div class="col-sm-6 col-xs-6">
        <h4>@lang('lang_v1.sell_details'):</h4>
        <strong>@lang('sale.ref_no'):</strong> {{ $sell_return->ref_no }} <br>
        <strong>@lang('messages.date'):</strong> {{@format_date($sell_return->transaction_date)}}
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-sm-12">
        
      </div>
      <div class="col-sm-4">
        
      </div>
      <div class="col-sm-12">
        <br>
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
                <th>#</th>
                <th>@lang('product.product_name')</th>
                <th class="{{$hide_price}}">@lang('sale.unit_price')</th>
                <th>@lang('lang_v1.return_quantity')</th>
                <th class="{{$hide_price}}">@lang('lang_v1.return_subtotal')</th>
            </tr>
        </thead>
      
        <tbody>
            @php
              $total_before_tax = 0;
            @endphp
            @foreach($sell_return->sellreturngenralstore_lines as $sell_return_line)

            @if($sell_return_line->quantity_returned == 0)
                @continue
            @endif

            @php
              $unit_name = $sell_return_line->product->unit->short_name;

              if(!empty($sell_return_line->sub_unit)) {
                $unit_name = $sell_return_line->sub_unit->short_name;
              }
            @endphp

            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>
                  {{ $sell_return_line->product->name }}
                  @if( $sell_return_line->product->type == 'variable')
                    - {{ $sell_return_line->variations->product_variation->name}}
                    - {{ $sell_return_line->variations->name}}
                  @endif
                </td>
                <td class="{{$hide_price}}"><span class="display_currency" data-currency_symbol="true">{{ $sell_return_line->unit_price_inc_tax }}</span></td>
                <td>{{@format_quantity($sell_return_line->quantity_returned)}} {{$unit_name}}</td>
                <td class="{{$hide_price}}">
                  @php
                    $line_total = $sell_return_line->unit_price_inc_tax * $sell_return_line->quantity_returned;
                    $total_before_tax += $line_total ;
                  @endphp
                  <span class="display_currency" data-currency_symbol="true">{{$line_total}}</span>
                </td>
            </tr>
            @endforeach
          </tbody>
      </table>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6 col-sm-offset-6 col-xs-6 col-xs-offset-6">
      <table class="table">
        <tr class="{{$hide_price}}">
          <th>@lang('purchase.net_total_amount'): </th>
          <td></td>
          <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
        </tr>

        
        
        <tr>
          <th>@lang('lang_v1.total_return_tax'):</th>
          <td><b>(+)</b></td>
          <td class="text-right">
              @if(!empty($sell_return_taxes))
                @foreach($sell_return_taxes as $k => $v)
                  <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                @endforeach
              @else
              0.00
              @endif
            </td>
        </tr>
       
      </table>
    </div>
</div>
<div class="modal-footer">
    <a href="#" class="print-invoice btn btn-primary" data-href="{{action('SellReturnGenralstoreController@printInvoice', [$sell_return->id])}}"><i class="fa fa-print" aria-hidden="true"></i> @lang("messages.print")</a>
      <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
  });
</script>