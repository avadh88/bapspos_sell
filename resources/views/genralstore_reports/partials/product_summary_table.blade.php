<div class="table-responsive">
    <table class="table table-bordered table-striped" id="total_product_summary_report_table">
        <thead>
            <tr>
                <th>@lang('report.product')</th>
                <th>@lang('report.sku')</th>
                <th>@lang('report.price')</th>
                <th>@lang('report.total_purchase')</th>
                <th>@lang('report.total_seva')</th>
                <th>@lang('report.total_transfer_from')</th>
                <th>@lang('report.total_transfer_to')</th>
                <th>@lang('report.total_permanent_sell')</th>
                <th>@lang('report.current_stock')</th>
                
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_price"></td>
                <td id="footer_total_purchase" ></td>
                <td id="footer_total_seva"></td>
                <td id="footer_total_transfer_from"></td>
                <td id="footer_total_transfer_to"></td>
                <td id="footer_total_permanent_sell"></td>
                <td id="footer_current_stock"></td>
                
            </tr>
        </tfoot>
    </table>
</div>