<div class="table-responsive">
    <table class="table table-bordered table-striped" id="{{ $departmentUser == 1 || $show_price == 0  ? 'total_demand_report_table_department':'total_demand_report_table'}}">
        <thead>
            <tr>
            <th>@lang('report.productname')</th>
            <th>Sku</th>
                <th>@lang('Demand Qty')</th>
                <th>@lang('Delivered')</th>
                <th>@lang('report.pending')</th>
                <th>@lang('Outstanding')</th>
                @if(!$departmentUser && $show_price==1)
                <th>@lang('report.current_stock')</th>
                <th>@lang('Is Purchasable')</th>
                <th>@lang('Remaining Purchase')</th>
                <th>@lang('report.price')</th>
                <th>@lang('report.subtotal')</th>
                @endif
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan=2><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_quantity" ></td>
                <td></td>
                <td id="footer_total_quantity_pending" ></td>
                <td id="footer_total_quantity_outstanding" ></td>
                @if(!$departmentUser && $show_price==1)
                <td id="footer_total_quantity_current_stock"></td>
                <td></td>
                <td></td>
                <td></td>
                <td id="footer_total_price" class="display_currency text-left" data-currency_symbol=true></td>
                @endif
            </tr>
        </tfoot>
    </table>
</div>