<div class="row">
	<div class="col-xs-12 col-sm-10 col-sm-offset-1">
		<div class="table-responsive">
			<table class="table table-condensed bg-gray">
				<tr>
					<th>@lang('report.date')</th>
					<th>@lang('report.issue_qty')</th>
					<th>@lang('report.sale_invoice')</th>
					<th>@lang('report.return_qty')</th>
                    <th>@lang('report.return_invoice')</th>
                    <th>@lang('report.notes')</th>
				</tr>
				@foreach($products as $product)
					<tr>
						<td>
							{{ @format_datetime($product->transaction_date) }}
						</td>
						<td>
							{{ $saleQty = $product->sale_qty != '' ? $product->sale_qty:'0' }}
						</td>
						<td>
							{{ $invoice_no = $product->invoice_no != '' ? $product->invoice_no:'-' }}
						</td>
						<td>
							{{ $returnQty = $product->return_qty != '' ? $product->return_qty:'0' }}	
						</td>
						<td>
							{{ $ref_no = $product->ref_no != '' ? $product->ref_no:'-' }}
						</td>
						<td>
							{{$product->additional_notes}}
						</td>
					</tr>
				@endforeach
			</table>
		</div>
	</div>
</div>