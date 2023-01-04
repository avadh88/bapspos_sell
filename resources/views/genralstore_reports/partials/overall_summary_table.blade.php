<div class="table-responsive">
    <table class="table table-bordered table-striped" id="overall_summary_report_table">
        <thead>
            <tr>
                <th>@lang('report.department')</th>
                <th>@lang('report.demand')</th>
                <th>@lang('report.issue')</th>
                <th>@lang('report.difference')</th>
                <th>@lang('report.refundable_qty')</th>
                <th>@lang('report.non_refundable_qty')</th>
                <th>@lang('report.disposableqty')</th>
                <th>@lang('report.disposable_return_qty')</th>
                <th>@lang('report.return_qty')</th>
                <th>@lang('report.damage_qty')</th>
                <th>@lang('report.demand_value')</th>
                <th>@lang('report.issue_value')</th>
                <th>@lang('report.refundable_value')</th>
                <th>@lang('report.non_refundable_value')</th>
                <th>@lang('report.disposable_value')</th>
                <th>@lang('report.disposable_return_value')</th>
                <th>@lang('report.return_value')</th>
                <th>@lang('report.damage_value')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_demand_qty"></td>
                <td id="footer_total_issue_qty"></td>
                <td id="footer_total_difference_qty"></td>
                <td id="footer_total_qty_refunbable"></td>
                <td id="footer_total_qty_non_refunbable"></td>
                <td id="footer_total_qty_sold_disposable"></td>
                <td id="footer_total_qty_return_disposable"></td>
                <td id="footer_total_qty_return"></td>
                <td id="footer_total_qty_damage"></td>
                <td id="footer_total_demand_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_issue_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_refundabale_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_non_refundable_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_disposable_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_disposable_return_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_return_value" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_damage_value" class="display_currency" data-currency_symbol=true></td>
                
            </tr>
        </tfoot>
    </table>
</div>