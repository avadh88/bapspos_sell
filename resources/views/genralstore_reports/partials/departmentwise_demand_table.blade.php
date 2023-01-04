<div class="table-responsive">
    <table class="table table-bordered table-striped" id="departmentwise_demand_report_table">
        <thead>
            <tr>
                <th>@lang('report.billnumber')</th>
                <th>@lang('report.date')</th>
                <th>@lang('report.department')</th>
                <th>@lang('report.productname')</th>
                <th>@lang('report.quantity')</th>
                <th>@lang('report.price')</th>
                <th>@lang('report.subtotal')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_quantity" ></td>
                <td></td>
                <td id="footer_total_price" class="display_currency" data-currency_symbol=true></td>
            </tr>
        </tfoot>
    </table>
</div>