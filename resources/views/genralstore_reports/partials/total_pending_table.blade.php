<div class="table-responsive">
    <table class="table table-bordered table-striped" id="total_pending_report_table">
        <thead>
            <tr>
                <th>@lang('report.productname')</th>
                <th>@lang('report.sku')</th>
                <th>@lang('report.disposible')</th>
                <th>@lang('report.sales')</th>
                <th>@lang('report.return')</th>
                <th>@lang('report.remain')</th>
                <th>@lang('report.price')</th>
                <th>@lang('report.subtotal')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="3"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_sales"></td>
                <td id="footer_total_return"></td>
                <td id="footer_total_remain" ></td>
                <td></td>
                <td id="footer_total_price" class="display_currency" data-currency_symbol=true></td>
            </tr>
        </tfoot>
    </table>
</div>