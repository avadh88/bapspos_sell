@php

    $show_price=1;
    if(!auth()->user()->show_price)
    {
        $show_price=0;
    }
@endphp
<div class="table-responsive">
    @if($show_price)
    <table class="table table-bordered table-striped" id="departmentwise_pending_report_table">
        <thead>
            <tr>
                <th>@lang('report.department')</th>
                <th>@lang('report.productname')</th>
                <th>@lang('report.sku')</th>
                <th>@lang('report.disposible')</th>
                <th>@lang('report.sales')</th>
                <th>@lang('report.return')</th>
                <th>@lang('report.remain')</th>
                <th>@lang('report.price')</th>
                <th>@lang('report.subtotal')</th>
                <th>@lang('report.action')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_sales"></td>
                <td id="footer_total_return"></td>
                <td id="footer_total_remain" ></td>
                <td></td>
                <td id="footer_total_price" class="display_currency" data-currency_symbol=true></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @else
    <table class="table table-bordered table-striped" id="departmentwise_pending_report_table_without_price">
        <thead>
            <tr>
                <th>@lang('report.department')</th>
                <th>@lang('report.productname')</th>
                <th>@lang('report.sku')</th>
                <th>@lang('report.disposible')</th>
                <th>@lang('report.sales')</th>
                <th>@lang('report.return')</th>
                <th>@lang('report.remain')</th>
                <th>@lang('report.action')</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_total_sales"></td>
                <td id="footer_total_return"></td>
                <td id="footer_total_remain" ></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
    @endif
</div>