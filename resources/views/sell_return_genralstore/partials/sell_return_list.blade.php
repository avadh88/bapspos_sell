@php
    $hide_price='';
    if(!auth()->user()->show_price)
    {
        $hide_price='hide';
    }
@endphp
<div class="table-responsive">
    <table class="table table-bordered table-striped ajax_view" id="sell_return_table">
        <thead>
            @if(!auth()->user()->show_price)
            <tr>
                <th>@lang('messages.date')</th>
                <th>@lang('sale.invoice_no')</th>
                <th>@lang('lang_v1.parent_sale')</th>
                <th>@lang('sale.customer_name')</th>
                <th>@lang('sale.location')</th>
                <th>@lang('messages.action')</th>
            </tr>
            @else
            <tr>
                <th>@lang('messages.date')</th>
                <th>@lang('sale.invoice_no')</th>
                <th>@lang('lang_v1.parent_sale')</th>
                <th>@lang('sale.customer_name')</th>
                <th>@lang('sale.location')</th>
                <th>@lang('purchase.payment_status')</th>
                <th>@lang('sale.total_amount')</th>
                <th>@lang('purchase.payment_due')</th>
                <th>@lang('messages.action')</th>
            </tr>
            @endif
        </thead>
        <tfoot>
            <tr class="bg-gray font-17 text-center footer-total">
            @if(!auth()->user()->show_price)
                <td colspan="4"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_payment_status_count_sr"></td>
                <td></td>
            @else
                <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                <td id="footer_payment_status_count_sr"></td>
                <td><span class="display_currency" id="footer_sell_return_total" data-currency_symbol ="true"></span></td>
                <td><span class="display_currency" id="footer_total_due_sr" data-currency_symbol ="true"></span></td>
                <td></td>
            @endif
                
            </tr>
        </tfoot>
    </table>
</div>