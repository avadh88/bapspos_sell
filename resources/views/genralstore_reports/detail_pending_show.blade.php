<div class="modal-dialog modal-xl no-print" role="document">
  <div class="modal-content">
    <div class="modal-header">
    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    <h4 class="modal-title" id="modalTitle"> @lang('report.totalpendingreport')
    </h4>
</div>
<div class="modal-body">
    <br>
    <div class="row">
      <div class="col-sm-12 col-xs-12">
        <h4>{{ __('sale.products') }}:</h4>
      </div>
    </div>

    <div class="row">
      
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="table-responsive">
          <table class="table bg-gray" id="totalpending_detail_report">
            <tr class="bg-green">
              <th>#</th>
              <th>@lang('report.department')</th>
              <th>@lang('report.quantity')</th>
            </tr>
            @foreach($product_data as $product_line)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>
                {{ $product_line->name }}
              </td>
              <td>
              <span class="quantity" data-orig-value="{{ $product_line->remain }}">{{ $product_line->remain }}</span>
              </td>
            </tr> 
            @endforeach
            
            <tfoot>
                <tr class="bg-gray font-17 footer-total text-center">
                    <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                    <td id="footer_total_quantity_detail"></td>
                </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    
      <button type="button" class="btn btn-default no-print" data-dismiss="modal">@lang( 'messages.close' )</button>
    </div>
  </div>
</div>

<script type="text/javascript">
  $(document).ready(function(){
    var element = $('div.modal-xl');
    __currency_convert_recursively(element);
    $('#footer_total_quantity_detail').html(
        __sum_data($('#totalpending_detail_report'), 'quantity')
    );
  });
</script>
