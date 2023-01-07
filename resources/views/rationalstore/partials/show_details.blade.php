<div class="modal-header">
  <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
  <h4 class="modal-title" id="modalTitle"> @lang('rationalstore.rationalstore_details') (<b>@lang('rationalstore.ref_no'):</b> #{{ $rationalstore->ref_no }})
  </h4>
</div>
<div class="modal-body">
  <div class="row">
    <div class="col-sm-12">
      <p class="pull-right"><b>@lang('messages.date'):</b> {{ @format_date($rationalstore->transaction_date) }}</p>
    </div>
  </div>
  <div class="row invoice-info">
    <div class="col-sm-4 invoice-col">
      @lang('rationalstore.customer'):
      <address>
        <strong>{{ $rationalstore->contact->supplier_business_name }}</strong>
        {{ $rationalstore->contact->name }}
        @if(!empty($rationalstore->contact->landmark))
        <br>{{$rationalstore->contact->landmark}}
        @endif
        @if(!empty($rationalstore->contact->city) || !empty($rationalstore->contact->state) || !empty($rationalstore->contact->country))
        <br>{{implode(',', array_filter([$rationalstore->contact->city, $rationalstore->contact->state, $rationalstore->contact->country]))}}
        @endif
        @if(!empty($rationalstore->contact->tax_number))
        <br>@lang('contact.tax_no'): {{$rationalstore->contact->tax_number}}
        @endif
        @if(!empty($rationalstore->contact->mobile))
        <br>@lang('contact.mobile'): {{$rationalstore->contact->mobile}}
        @endif
        @if(!empty($rationalstore->contact->email))
        <br>Email: {{$rationalstore->contact->email}}
        @endif
      </address>
    </div>

    <div class="col-sm-4 invoice-col">
      @lang('business.business'):
      <address>
        <strong>{{ $rationalstore->business->name }}</strong>
        {{ $rationalstore->location->name }}
        @if(!empty($rationalstore->location->landmark))
        <br>{{$rationalstore->location->landmark}}
        @endif
        @if(!empty($rationalstore->location->city) || !empty($rationalstore->location->state) || !empty($rationalstore->location->country))
        <br>{{implode(',', array_filter([$rationalstore->location->city, $rationalstore->location->state, $rationalstore->location->country]))}}
        @endif

        @if(!empty($rationalstore->business->tax_number_1))
        <br>{{$rationalstore->business->tax_label_1}}: {{$rationalstore->business->tax_number_1}}
        @endif

        @if(!empty($rationalstore->business->tax_number_2))
        <br>{{$rationalstore->business->tax_label_2}}: {{$rationalstore->business->tax_number_2}}
        @endif

        @if(!empty($rationalstore->location->mobile))
        <br>@lang('contact.mobile'): {{$rationalstore->location->mobile}}
        @endif
        @if(!empty($rationalstore->location->email))
        <br>@lang('business.email'): {{$rationalstore->location->email}}
        @endif
      </address>
    </div>

    <div class="col-sm-4 invoice-col">
      <b>@lang('rationalstore.ref_no'):</b> #{{ $rationalstore->ref_no }}<br />
      <b>@lang('messages.date'):</b> {{ @format_date($rationalstore->transaction_date) }}<br />
      <b>@lang('rationalstore.rationalstore_status'):</b> {{ ucfirst( $rationalstore->status ) }}<br>
      <b>@lang('rationalstore.payment_status'):</b> {{ ucfirst( $rationalstore->payment_status ) }}<br>
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
              <th>@lang('rationalstore.rationalstore_quantity')</th>
              <th>@lang( 'lang_v1.unit_cost_before_discount' )</th>
              @if(session('business.enable_lot_number'))
              <th>@lang('lang_v1.lot_number')</th>
              @endif
              @if(session('business.enable_product_expiry'))
              <th>@lang('product.mfg_date')</th>
              <th>@lang('product.exp_date')</th>
              @endif
              <th>@lang('rationalstore.subtotal')</th>
            </tr>
          </thead>
          @php
          $total_before_tax = 0.00;
          @endphp
          @foreach($rationalstore->rationalstore_lines as $rationalstore_line)
          <tr>
            <td>{{ $loop->iteration }}</td>
            <td>
              {{ $rationalstore_line->product->name }}
              @if( $rationalstore_line->product->type == 'variable')
              - {{ $rationalstore_line->variations->product_variation->name}}
              - {{ $rationalstore_line->variations->name}}
              @endif
            </td>
            <td><span class="display_currency" data-is_quantity="true" data-currency_symbol="false">{{ $rationalstore_line->quantity }}</span> @if(!empty($rationalstore_line->sub_unit)) {{$rationalstore_line->sub_unit->short_name}} @else {{$rationalstore_line->product->unit->short_name}} @endif</td>
            <td><span class="display_currency" data-currency_symbol="true">{{ $rationalstore_line->pp_without_discount}}</span></td>
            @php
            $sp = $rationalstore_line->variations->default_sell_price;
            if(!empty($rationalstore_line->sub_unit->base_unit_multiplier)) {
            $sp = $sp * $rationalstore_line->sub_unit->base_unit_multiplier;
            }
            @endphp

            @if(session('business.enable_lot_number'))
            <td>{{$rationalstore_line->lot_number}}</td>
            @endif

            <td><span class="display_currency" data-currency_symbol="true">{{ $rationalstore_line->pp_without_discount * $rationalstore_line->quantity }}</span></td>
          </tr>
          @php
          $total_before_tax += ($rationalstore_line->quantity * $rationalstore_line->pp_without_discount);
          @endphp
          @endforeach
        </table>
      </div>
    </div>
  </div>
  <br>
  <div class="row">
    <div class="col-sm-12 col-xs-12">
      <h4>{{ __('rationalstore.payment_info') }}:</h4>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table">
          <tr class="bg-green">
            <th>#</th>
            <th>{{ __('messages.date') }}</th>
            <th>{{ __('rationalstore.ref_no') }}</th>
            <th>{{ __('rationalstore.amount') }}</th>
            <th>{{ __('rationalstore.payment_mode') }}</th>
            <th>{{ __('rationalstore.payment_note') }}</th>
          </tr>
          @php
          $total_paid = 0;
          @endphp
          @forelse($rationalstore->payment_lines as $payment_line)
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
              @lang('rationalstore.no_payments')
            </td>
          </tr>
          @endforelse
        </table>
      </div>
    </div>
    <div class="col-md-6 col-sm-12 col-xs-12">
      <div class="table-responsive">
        <table class="table">
          <tr>
            <th>@lang('rationalstore.net_total_amount'): </th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $total_before_tax }}</span></td>
          </tr>
          <tr>
            <th>@lang('rationalstore.discount'):</th>
            <td>
              <b>(-)</b>
              @if($rationalstore->discount_type == 'percentage')
              ({{$rationalstore->discount_amount}} %)
              @endif
            </td>
            <td>
              <span class="display_currency pull-right" data-currency_symbol="true">
                @if($rationalstore->discount_type == 'percentage')
                {{$rationalstore->discount_amount * $total_before_tax / 100}}
                @else
                {{$rationalstore->discount_amount}}
                @endif
              </span>
            </td>
          </tr>
          <tr>
            <th>@lang('rationalstore.rationalstore_tax'):</th>
            <td><b>(+)</b></td>
            <td class="text-right">
              @if(!empty($rationalstore_taxes))
              @foreach($rationalstore_taxes as $k => $v)
              <strong><small>{{$k}}</small></strong> - <span class="display_currency pull-right" data-currency_symbol="true">{{ $v }}</span><br>
              @endforeach
              @else
              0.00
              @endif
            </td>
          </tr>
          @if( !empty( $rationalstore->shipping_charges ) )
          <tr>
            <th>@lang('rationalstore.additional_shipping_charges'):</th>
            <td><b>(+)</b></td>
            <td><span class="display_currency pull-right">{{ $rationalstore->shipping_charges }}</span></td>
          </tr>
          @endif
          <tr>
            <th>@lang('rationalstore.rationalstore_total'):</th>
            <td></td>
            <td><span class="display_currency pull-right" data-currency_symbol="true">{{ $rationalstore->final_total }}</span></td>
          </tr>
        </table>
      </div>
    </div>
  </div>
  <div class="row">
    <div class="col-sm-6">
      <strong>@lang('rationalstore.shipping_details'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        @if($rationalstore->shipping_details)
        {{ $rationalstore->shipping_details }}
        @else
        --
        @endif
      </p>
    </div>
    <div class="col-sm-6">
      <strong>@lang('rationalstore.additional_notes'):</strong><br>
      <p class="well well-sm no-shadow bg-gray">
        @if($rationalstore->additional_notes)
        {{ $rationalstore->additional_notes }}
        @else
        --
        @endif
      </p>
    </div>
  </div>

  {{-- Barcode --}}
  <div class="row print_section">
    <div class="col-xs-12">
      <img class="center-block" src="data:image/png;base64,{{DNS1D::getBarcodePNG($rationalstore->ref_no, 'C128', 2,30,array(39, 48, 54), true)}}">
    </div>
  </div>
</div>