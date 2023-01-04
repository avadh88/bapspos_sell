<!-- business information here -->
<style>
.with_20{
    width:10%;
}
.font10{
    font-size:10px;
}
.font8{
	font-size: 8px;
}
hr{
	margin-top:0px !important; 
}
</style>
<div class="row font10">
    <?php
    // echo "<pre>";
    // print_r($receipt_details);
    // exit;
    ?>
    <!-- Logo -->
    <div class="col-xs-3">
	@if(!empty($receipt_details->logo))
		<img src="{{$receipt_details->logo}}" class="img img-responsive center-block">
	@endif
    </div>
	<!-- Header text -->
	@if(!empty($receipt_details->header_text))
		<div class="col-xs-3 font10">
			{!! $receipt_details->header_text !!}
		</div>
	@endif

	<!-- business information here -->
	<div class="col-xs-4 text-center font10">
		<h6 class="text-center">
			<!-- Shop & Location Name  -->
			@if(!empty($receipt_details->display_name))
				{{$receipt_details->display_name}}
			@endif
		</h6>

		<!-- Address -->
		<p>
		@if(!empty($receipt_details->address))
				<small class="text-center">
				{!! $receipt_details->address !!}
				</small>
		@endif
		@if(!empty($receipt_details->contact))
			<br/>{{ $receipt_details->contact }}
		@endif	
		@if(!empty($receipt_details->contact) && !empty($receipt_details->website))
			, 
		@endif
		</p>

		<!-- Title of receipt -->
		@if(!empty($receipt_details->invoice_heading))
			<h5 class="text-center">
				Return {!! $receipt_details->invoice_heading !!}
            </h5>
		@endif

		<!-- Invoice  number, Date  -->
		
	</div>
	<div class="col-xs-5">
		<p style="width: 100% !important" class="word-wrap">
			<span class="pull-left text-left word-wrap">
				@if(!empty($receipt_details->invoice_no_prefix))
					<b>{!! $receipt_details->invoice_no_prefix !!}</b>
				@endif
				<span class="font8">{{$receipt_details->ref_no}}</span>

				<!-- Table information-->
		        @if(!empty($receipt_details->table_label) || !empty($receipt_details->table))
		        	<br/>
					<span class="pull-left text-left">
						@if(!empty($receipt_details->table_label))
							<b>{!! $receipt_details->table_label !!}</b>
						@endif
						{{$receipt_details->table}}

						<!-- Waiter info -->
					</span>
		        @endif	
			</span>

			<span class="pull-left text-left">
                <b>{{$receipt_details->date_label}}</b> {{$receipt_details->invoice_date}}
            </span>
        </p>
	</div>
</div>
<div class="row font10">
    <div class="col-xs-12">
    <p style="width: 100% !important" class="word-wrap">
        <span class="pull-left text-left" style="text-align: left !important">
            <!-- customer info -->
            @if(!empty($receipt_details->customer_name))
                <br/>
                <b>{{ $receipt_details->customer_label }}</b> {{ $receipt_details->customer_name }} 
                ({{ $receipt_details->customer_mobile }})
            @endif
        </span>
    </p>
    </div>
	@if(!empty($receipt_details->defects_label) || !empty($receipt_details->repair_defects))
		<div class="col-xs-12">
			<br>
			@if(!empty($receipt_details->defects_label))
				<b>{!! $receipt_details->defects_label !!}</b>
			@endif
			{{$receipt_details->repair_defects}}
		</div>
    @endif
	<!-- /.col -->
</div>


<div class="row font10">
	<div class="col-xs-12">
		<table class="table table-responsive">
			<thead>
				<tr>
					<th>{{$receipt_details->table_product_label}}</th>
                    <th>{{$receipt_details->table_qty_label}}</th>
                    @if(!$receipt_details->hide_product_price)
                        <th>{{$receipt_details->table_unit_price_label}}</th>
                    @endif
                    @if(!$receipt_details->hide_row_sub_total)
                        <th>{{$receipt_details->table_subtotal_label}}</th>
                    @endif
				</tr>
			</thead>
			<tbody>
				@forelse($receipt_details->lines as $line)
					<tr>
						<td style="word-break: break-all;">
							@if(!empty($line['image']))
								<img src="{{$line['image']}}" alt="Image" width="50" style="float: left; margin-right: 8px;">
							@endif
                            {{$line['name']}} {{$line['variation']}} 
                            @if(!empty($line['sub_sku'])) ({{$line['sub_sku']}}) @endif @if(!empty($line['brand'])), {{$line['brand']}} @endif @if(!empty($line['cat_code'])), {{$line['cat_code']}}@endif
                            @if(!empty($line['product_custom_fields'])), {{$line['product_custom_fields']}} @endif
                            @if(!empty($line['sell_line_note']))({{$line['sell_line_note']}}) @endif 
                            @if(!empty($line['lot_number']))<br> {{$line['lot_number_label']}}:  {{$line['lot_number']}} @endif 
                            @if(!empty($line['product_expiry'])), {{$line['product_expiry_label']}}:  {{$line['product_expiry']}} @endif 
                        </td>
                        <td>{{$line['quantity']}} {{$line['units']}} </td>
                        @if(!$receipt_details->hide_product_price)
                            <td>{{$line['unit_price_inc_tax']}}</td>
                        @endif
                        @if(!$receipt_details->hide_row_sub_total)
                            <td>{{$line['line_total']}}</td>
                        @endif
					</tr>
				@empty
					
				@endforelse
			</tbody>
		</table>
	</div>
</div>

@if(!$receipt_details->hide_row_sub_total)
<div class="row font10">
	<div class="col-md-12"><hr/></div>
	<div class="col-xs-6">

		<table class="table table-condensed">

			@if(!empty($receipt_details->payments))
				@foreach($receipt_details->payments as $payment)
					<tr>
						<td>{{$payment['method']}}</td>
						<td>{{$payment['amount']}}</td>
						<td>{{$payment['date']}}</td>
					</tr>
				@endforeach
			@endif

			<!-- Total Paid-->
			@if(!empty($receipt_details->total_paid))
				<tr>
					<th>
						{!! $receipt_details->total_paid_label !!}
					</th>
					<td>
						{{$receipt_details->total_paid}}
					</td>
				</tr>
			@endif

			<!-- Total Due-->
			@if(!empty($receipt_details->total_due))
			<tr>
				<th>
					{!! $receipt_details->total_due_label !!}
				</th>
				<td>
					{{$receipt_details->total_due}}
				</td>
			</tr>
			@endif

			@if(!empty($receipt_details->all_due))
			<tr>
				<th>
					{!! $receipt_details->all_bal_label !!}
				</th>
				<td>
					{{$receipt_details->all_due}}
				</td>
			</tr>
			@endif
		</table>

		{{$receipt_details->additional_notes}} 
		<br>
		
	</div>

	<div class="col-xs-6">
        <div class="table-responsive">
          	<table class="table">
				<tbody>
					<tr>
						<th style="width:70%">
							{!! $receipt_details->subtotal_label !!}
						</th>
						<td>
							{{$receipt_details->subtotal}}
						</td>
					</tr>
					
					<!-- Shipping Charges -->
					@if(!empty($receipt_details->shipping_charges))
						<tr>
							<th style="width:70%">
								{!! $receipt_details->shipping_charges_label !!}
							</th>
							<td>
								{{$receipt_details->shipping_charges}}
							</td>
						</tr>
					@endif

					<!-- Discount -->
					@if( !empty($receipt_details->discount) )
						<tr>
							<th>
								{!! $receipt_details->discount_label !!}
							</th>

							<td>
								(-) {{$receipt_details->discount}}
							</td>
						</tr>
					@endif

					<!-- Tax -->
					@if( !empty($receipt_details->tax) )
						<tr>
							<th>
								{!! $receipt_details->tax_label !!}
							</th>
							<td>
								(+) {{$receipt_details->tax}}
							</td>
						</tr>
					@endif

					<!-- Total -->
					<tr>
						<th>
							{!! $receipt_details->total_label !!}
						</th>
						<td>
							{{$receipt_details->total}}
						</td>
					</tr>
				</tbody>
        	</table>
        </div>
    </div>
</div>
@else

<div class="row font10">
    <div class="col-xs-12">
    <hr/>
		Return By: {{$receipt_details->additional_notes}}
		<br>
		Receive By: {{$receipt_details->service_staff}} 
    </div>
</div>
@endif
