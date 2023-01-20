@php
	$show_price='';
	if(!auth()->user()->show_price)
	{
		$show_price= 'hide';
	}
@endphp
<div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('sellorder.sellorder_details') (<b>@lang('sellorder.ref_no'):</b> #{{ $sellorder->ref_no }})
    </h4>
</div>
<div class="modal-body">
  <div class="row">
    <div class="col-sm-12">
      <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($sellorder->transaction_date) }}</p>
    </div>
  </div>
  <div class="row invoice-info">
    <div class="col-sm-4 invoice-col">
      @lang('sellorder.customer'):
      <address>
        <strong>{{ $sellorder->contact->supplier_business_name }}</strong>
        {{ $sellorder->contact->name }}
        @if(!empty($sellorder->contact->landmark))
          <br>{{$sellorder->contact->landmark}}
        @endif
        @if(!empty($sellorder->contact->city) || !empty($sellorder->contact->state) || !empty($sellorder->contact->country))
          <br>{{implode(',', array_filter([$sellorder->contact->city, $sellorder->contact->state, $sellorder->contact->country]))}}
        @endif
        @if(!empty($sellorder->contact->tax_number))
          <br>@lang('contact.tax_no'): {{$sellorder->contact->tax_number}}
        @endif
        @if(!empty($sellorder->contact->mobile))
          <br>@lang('contact.mobile'): {{$sellorder->contact->mobile}}
        @endif
        @if(!empty($sellorder->contact->email))
          <br>Email: {{$sellorder->contact->email}}
        @endif
      </address>
      @if($sellorder->document_path)
        
        <a href="{{$sellorder->document_path}}" 
        download="{{$sellorder->document_name}}" class="btn btn-sm btn-success pull-left no-print">
          <i class="fa fa-download"></i> 
            &nbsp;{{ __('sellorder.download_document') }}
        </a>
      @endif
    </div>

    <div class="col-sm-4 invoice-col">
      @lang('business.business'):
      <address>
        <strong>{{ $sellorder->business->name }}</strong>
        {{ $sellorder->location->name }}
        @if(!empty($sellorder->location->landmark))
          <br>{{$sellorder->location->landmark}}
        @endif
        @if(!empty($sellorder->location->city) || !empty($sellorder->location->state) || !empty($sellorder->location->country))
          <br>{{implode(',', array_filter([$sellorder->location->city, $sellorder->location->state, $sellorder->location->country]))}}
        @endif
        
        @if(!empty($sellorder->business->tax_number_1))
          <br>{{$sellorder->business->tax_label_1}}: {{$sellorder->business->tax_number_1}}
        @endif

        @if(!empty($sellorder->business->tax_number_2))
          <br>{{$sellorder->business->tax_label_2}}: {{$sellorder->business->tax_number_2}}
        @endif

        @if(!empty($sellorder->location->mobile))
          <br>@lang('contact.mobile'): {{$sellorder->location->mobile}}
        @endif
        @if(!empty($sellorder->location->email))
          <br>@lang('business.email'): {{$sellorder->location->email}}
        @endif
      </address>
    </div>

    <div class="col-sm-4 invoice-col">
      <b>@lang('sellorder.ref_no'):</b> #{{ $sellorder->ref_no }}<br/>
      <b>@lang('messages.date'):</b> {{ @format_date($sellorder->transaction_date) }}<br/>
      <b>@lang('sellorder.sellorder_status'):</b> {{ ucfirst( $sellorder->status ) }}<br>
      <b>@lang('sellorder.payment_status'):</b> {{ ucfirst( $sellorder->payment_status ) }}<br>
    </div>
  </div>

  <br>
  <div class="row">
    <div class="col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table bg-gray">
          <thead>
            <tr class="bg-green">
              <th>#</th>
              <th>@lang('product.product_name')</th>
              <th>@lang('sellorder.sellorder_quantity')</th>
              <th class="{{$show_price}}">@lang( 'lang_v1.unit_cost_before_discount' )</th>
              <!-- <th>@lang( 'lang_v1.discount_percent' )</th> -->
              <!-- <th class="no-print">@lang('sellorder.unit_cost_before_tax')</th> -->
              <!-- <th class="no-print">@lang('sellorder.subtotal_before_tax')</th> -->
              <!-- <th>@lang('sale.tax')</th> -->
              <!-- <th>@lang('sellorder.unit_cost_after_tax')</th> -->
              <!-- <th>@lang('sellorder.unit_selling_price')</th> -->
              @if(session('business.enable_lot_number'))
                <th>@lang('lang_v1.lot_number')</th>
              @endif
              @if(session('business.enable_product_expiry'))
                <th>@lang('product.mfg_date')</th>
                <th>@lang('product.exp_date')</th>
              @endif
              <th class="{{$show_price}}">@lang('sale.subtotal')</th>
              <th>@lang( 'sale.sale_order_date' )</th>
            </tr>
          </thead>
          @php 
            $total_before_tax = 0.00;
          @endphp
          @foreach($sellorder->sellorder_lines as $sellorder_line)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>
                {{ $sellorder_line->product->name }}
                 @if( $sellorder_line->product->type == 'variable')
                  - {{ $sellorder_line->variations->product_variation->name}}
                  - {{ $sellorder_line->variations->name}}
                 @endif
              </td>
              <td><span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $sellorder_line->quantity }}</span> @if(!empty($sellorder_line->sub_unit)) {{$sellorder_line->sub_unit->short_name}} @else {{$sellorder_line->product->unit->short_name}} @endif</td>
              <td class="{{$show_price}}"><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->pp_without_discount}}</span></td>
              <!-- <td><span class="display_currency">{{ $sellorder_line->discount_percent}}</span> %</td> -->
              <!-- <td class="no-print"><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->sellorder_price }}</span></td> -->
              <!-- <td class="no-print"><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->quantity * $sellorder_line->sellorder_price }}</span></td> -->
              <!-- <td><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->item_tax }} </span> <br/><small>@if(!empty($taxes[$sellorder_line->tax_id])) ( {{ $taxes[$sellorder_line->tax_id]}} ) </small>@endif</td> -->
              <!-- <td><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->sellorder_price_inc_tax }}</span></td> -->
              @php
                $sp = $sellorder_line->variations->default_sell_price;
                if(!empty($sellorder_line->sub_unit->base_unit_multiplier)) {
                  $sp = $sp * $sellorder_line->sub_unit->base_unit_multiplier;
                }
              @endphp
              <!-- <td><span class="display_currency" data-currency_symbol="true">{{$sp}}</span></td> -->

              @if(session('business.enable_lot_number'))
                <td>{{$sellorder_line->lot_number}}</td>
              @endif

              @if(session('business.enable_product_expiry'))
              <td>
                @if( !empty($sellorder_line->product->expiry_period_type) )
                  @if(!empty($sellorder_line->mfg_date))
                    {{ @format_date($sellorder_line->mfg_date) }}
                  @endif
                @else
                  @lang('product.not_applicable')
                @endif
              </td>
              <td>
                @if( !empty($sellorder_line->product->expiry_period_type) )
                  @if(!empty($sellorder_line->exp_date))
                    {{ @format_date($sellorder_line->exp_date) }}
                  @endif
                @else
                  @lang('product.not_applicable')
                @endif
              </td>
              @endif
              
              <td class="{{$show_price}}"><span class="display_currency" data-currency_symbol="true">{{ $sellorder_line->pp_without_discount * $sellorder_line->quantity }}</span></td>
              <td>
                @if( !empty($sellorder_line->sell_order_date) )
                  @if(!empty($sellorder_line->sell_order_date))
                    {{ @format_date($sellorder_line->sell_order_date) }}
                  @endif
                @else
                  @lang('product.not_applicable')
                @endif
              </td>
            </tr>
            @php 
              $total_before_tax += ($sellorder_line->quantity * $sellorder_line->pp_without_discount);
            @endphp
          @endforeach
        </table>
      </div>
    </div>
  </div>
  <br>
  <div class="row {{$show_price}}">
    <div class="col-sm-12 col-xs-12">
      <h4>{{ __('sale.payment_info') }}:</h4>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table">
          <tr class="bg-green">
            <th>#</th>
            <th>{{ __('messages.date') }}</th>
            <th>{{ __('sellorder.ref_no') }}</th>
            <th>{{ __('sale.amount') }}</th>
            <th>{{ __('sale.payment_mode') }}</th>
            <th>{{ __('sale.payment_note') }}</th>
          </tr>
          @php
            $total_paid = 0;
          @endphp
          @forelse($sellorder->payment_lines as $payment_line)
            @php
              $total_paid += $payment_line->amount;
            @endphp
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ @format_date($payment_line->paid_on) }}</td>
              <td>{{ $payment_line->payment_ref_no }}</td>
              <td><span class="display_currency" data-currency_symbol="true">{{ $payment_line->amount }}</span></td>
              <td>{{ $payment_methods[$payment_line->method] }}</td>
              <td>@if($payment_line->note) 
                {{ ucfirst($payment_line->note) }}
                @else
                --
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="5" class="text-center">
                @lang('sellorder.no_payments')
              </td>
            </tr>
          @endforelse
        </table>
      </div>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table">
          <!-- <tr class="hide">
            <th>@lang('sellorder.total_before_tax'): </th>
            <td></td>
            <td><span class="display_currency pull-right">{{ $total_before_tax }}</span></td>
          </tr> -->
          <tr>
            <th>@lang('sellorder.net_total_amount'): </th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
          </tr>
          <tr>
            <th>@lang('sellorder.discount'):</th>
            <td>
              <b>(-)</b>
              @if($sellorder->discount_type == 'percentage')
                ({{$sellorder->discount_amount}} %)
              @endif
            </td>
            <td>
              <span class="display_currency pull-right" data-currency_symbol="true">
                @if($sellorder->discount_type == 'percentage')
                  {{$sellorder->discount_amount * $total_before_tax / 100}}
                @else
                  {{$sellorder->discount_amount}}
                @endif                  
              </span>
            </td>
          </tr>
          <tr>
            <th>@lang('sellorder.sellorder_tax'):</th>
            <td><b>(+)</b></td>
            <td class="text-right">
                @if(!empty($sellorder_taxes))
                  @foreach($sellorder_taxes as $k => $v)
                    <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
                  @endforeach
                @else
                0.00
                @endif
              </td>
          </tr>
          @if( !empty( $sellorder->shipping_charges ) )
            <tr>
              <th>@lang('sellorder.additional_shipping_charges'):</th>
              <td><b>(+)</b></td>
              <td><span class="display_currency pull-right" >{{ $sellorder->shipping_charges }}</span></td>
            </tr>
          @endif
          <tr>
            <th>@lang('sellorder.sellorder_total'):</th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true" >{{ $sellorder->final_total }}</span></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6">
      <strong>@lang('sellorder.shipping_details'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        @if($sellorder->shipping_details)
          {{ $sellorder->shipping_details }}
        @else
          --
        @endif
      </p>
    </div>
    <div class="col-sm-6">
      <strong>@lang('sellorder.additional_notes'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        @if($sellorder->additional_notes)
          {{ $sellorder->additional_notes }}
        @else
          --
        @endif
      </p>
    </div>
  </div>

  {{-- Barcode --}}
  <div class="row print_section">
    <div class="col-xs-12">
      <img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($sellorder->ref_no, 'C128', 2,30,array(39, 48, 54), true)}}">
    </div>
  </div>
</div>