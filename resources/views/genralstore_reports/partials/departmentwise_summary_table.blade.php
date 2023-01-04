<div class="table-responsive">
    <table class="table table-bordered table-striped" id="departmentwise_summary_report_table" >
        <thead>
            
            <tr>
                <th>@lang('report.productname')</th>
                <th>@lang('report.sku')</th>
                <th>@lang('report.demand')</th>
                <th>@lang('report.total_demand_cost')</th>
                <th>@lang('report.refundable')</th>
                <th>@lang('report.usageamount')</th>
                <th>@lang('report.nonrefundable')</th>
                <th>@lang('report.nonrefundableamount')</th>
                <th>@lang('report.difference')</th>
                <th>@lang('report.totalusage')</th>
                <th>@lang('report.return')</th>
                <th>@lang('report.totalreturncost')</th>
                <th>@lang('report.pending')</th>
                <th>@lang('report.nuksani')</th>
                <th>@lang('report.damage')</th>
                <th>@lang('report.damage_value')</th>
                <th>@lang('report.disposableqty')</th>
                <th>@lang('report.disposablevalue')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="2"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_demand"></td>
                <td id="footer_total_demand_cost" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_refundable"></td>
                <td id="footer_total_refundable_cost" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_non_refundable"></td>
                <td id="footer_total_non_refundable_cost" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_difference"></td>
                <td id="footer_total_usage" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_returned"></td>
                <td id="footer_total_returned_cost" class="display_currency" data-currency_symbol=true></td>
                <td id="footer_total_pending"></td>
                <td id="footer_total_nuksani"class="display_currency" data-currency_symbol=true ></td>
                <td id="footer_total_damage"></td>
                <td id="footer_total_damage_value"class="display_currency" data-currency_symbol=true ></td>
                <td id="footer_total_disposable_qty" ></td>
                <td id="footer_total_disposable_value" class="display_currency" data-currency_symbol=true></td>
            </tr>
        </tfoot>
    </table>
</div>