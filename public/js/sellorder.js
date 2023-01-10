$(document).ready(function () {
    if ($('input#iraqi_selling_price_adjustment').length > 0) {
        iraqi_selling_price_adjustment = true;
    } else {
        iraqi_selling_price_adjustment = false;
    }

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    
    $('#sellorderdate').datepicker({
        autoclose: true,
        format: datepicker_date_format
    });

    //get customer
    $('#customer_id').select2({
        ajax: {
            url: '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            return data.text + "<br>" + LANG.mobile + ": " + data.mobile; 
        },
        minimumInputLength: 1,
        language: {
            noResults: function() {
                var name = $('#customer_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });

    //Quick add supplier
    $(document).on('click', '.add_new_customer', function () {
        $('#supplier_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });

    $('form#quick_add_contact')
        .submit(function (e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contact-id',
                        type: 'post',
                        data: {
                            contact_id: function () {
                                return $('#contact_id').val();
                            },
                            hidden_id: function () {
                                if ($('#hidden_id').length) {
                                    return $('#hidden_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
            },
            messages: {
                contact_id: {
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function (form) {
                $(form)
                    .find('button[type="submit"]')
                    .attr('disabled', true);
                var data = $(form).serialize();
                $.ajax({
                    method: 'POST',
                    url: $(form).attr('action'),
                    dataType: 'json',
                    data: data,
                    success: function (result) {
                        if (result.success == true) {
                            $('select#supplier_id').append(
                                $('<option>', { value: result.data.id, text: result.data.name })
                            );
                            $('select#supplier_id')
                                .val(result.data.id)
                                .trigger('change');
                            $('div.contact_modal').modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            },
        });
    $('.contact_modal').on('hidden.bs.modal', function () {
        $('form#quick_add_contact')
            .find('button[type="submit"]')
            .removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });

    //Add products
    if ($('#search_product').length > 0) {
        $('#search_product')
            .autocomplete({
                source: '/sellorder/get_products',
                minLength: 1,
                response: function (event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        var term = $(this).data('ui-autocomplete').term;
                        swal({
                            title: LANG.no_products_found,
                            text: __translate('add_name_as_new_product', { term: term }),
                            buttons: [LANG.cancel, LANG.ok],
                        }).then(value => {
                            if (value) {
                                var container = $('.quick_add_product_modal');
                                $.ajax({
                                    url: '/products/quick_add?product_name=' + term,
                                    dataType: 'html',
                                    success: function (result) {
                                        $(container)
                                            .html(result)
                                            .modal('show');
                                    },
                                });
                            }
                        });
                    }
                },
                select: function (event, ui) {
                    //$(this).val(null);
                    //get_sellorder_entry_row(ui.item.product_id, ui.item.variation_id);
                    document.getElementById('search_product').value = ui.item.text;
                    document.getElementById('variation_id').value   = ui.item.variation_id;
                    document.getElementById('product_id').value     = ui.item.product_id;
                    
                    return false;
                },
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
                return $('<li>')
                    .append('<div>' + item.text + '</div>')
                    .appendTo(ul);
            };
    }

    $(document).on('click', '.remove_sellorder_entry_row', function () {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(value => {
            if (value) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
                update_grand_total();
                update_table_sr_number();
            }
        });
    });

    //On Change of quantity
    $(document).on('change', '.sellorder_quantity', function () {
        var row = $(this).closest('tr');
        var quantity = __read_number($(this), true);
        var sellorder_before_tax = __read_number(row.find('input.sellorder_unit_cost'), true);
        var sellorder_after_tax = __read_number(
            row.find('input.sellorder_unit_cost_after_tax'),
            true
        );

        //Calculate sub totals
        var sub_total_before_tax = quantity * sellorder_before_tax;
        var sub_total_after_tax = quantity * sellorder_before_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.sellorder_unit_cost_without_discount', function () {
        var sellorder_before_discount = __read_number($(this), true);

        var row = $(this).closest('tr');
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var quantity = __read_number(row.find('input.sellorder_quantity'), true);

        //Calculations.
        var sellorder_before_tax =
            parseFloat(sellorder_before_discount) -
            __calculate_amount('percentage', discount_percent, sellorder_before_discount);

        __write_number(row.find('input.sellorder_unit_cost'), sellorder_before_tax, true);

        var sub_total_before_tax = quantity * sellorder_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellorder_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellorder_before_tax);

        var sellorder_after_tax = sellorder_before_tax + tax;
        var sub_total_after_tax = quantity * sellorder_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.sellorder_unit_cost_after_tax'), sellorder_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        row.find('.sellorder_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellorder_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.inline_discounts', function () {
        var row = $(this).closest('tr');

        var discount_percent = __read_number($(this), true);

        var quantity = __read_number(row.find('input.sellorder_quantity'), true);
        var sellorder_before_discount = __read_number(
            row.find('input.sellorder_unit_cost_without_discount'),
            true
        );

        //Calculations.
        var sellorder_before_tax =
            parseFloat(sellorder_before_discount) -
            __calculate_amount('percentage', discount_percent, sellorder_before_discount);

        __write_number(row.find('input.sellorder_unit_cost'), sellorder_before_tax, true);

        var sub_total_before_tax = quantity * sellorder_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellorder_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellorder_before_tax);

        var sellorder_after_tax = sellorder_before_tax + tax;
        var sub_total_after_tax = quantity * sellorder_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.sellorder_unit_cost_after_tax'), sellorder_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);
        row.find('.sellorder_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellorder_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.sellorder_unit_cost', function () {
        var row = $(this).closest('tr');
        var quantity = __read_number(row.find('input.sellorder_quantity'), true);
        var sellorder_before_tax = __read_number($(this), true);

        var sub_total_before_tax = quantity * sellorder_before_tax;

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var sellorder_before_discount = __get_principle(sellorder_before_tax, discount_percent, true);
        __write_number(
            row.find('input.sellorder_unit_cost_without_discount'),
            sellorder_before_discount,
            true
        );

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellorder_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellorder_before_tax);

        var sellorder_after_tax = sellorder_before_tax + tax;
        var sub_total_after_tax = quantity * sellorder_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.sellorder_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellorder_product_unit_tax'), tax, true);

        //row.find('.sellorder_product_unit_tax_text').text( tax );
        __write_number(row.find('input.sellorder_unit_cost_after_tax'), sellorder_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', 'select.sellorder_line_tax_id', function () {
        var row = $(this).closest('tr');
        var sellorder_before_tax = __read_number(row.find('.sellorder_unit_cost'), true);
        var quantity = __read_number(row.find('input.sellorder_quantity'), true);

        //Tax
        var tax_rate = parseFloat(
            $(this)
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellorder_before_tax);

        //Purchase price
        var sellorder_after_tax = sellorder_before_tax + tax;
        var sub_total_after_tax = quantity * sellorder_after_tax;

        row.find('.sellorder_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellorder_product_unit_tax'), tax, true);

        __write_number(row.find('input.sellorder_unit_cost_after_tax'), sellorder_after_tax, true);

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.sellorder_unit_cost_after_tax', function () {
        var row = $(this).closest('tr');
        var sellorder_after_tax = __read_number($(this), true);
        var quantity = __read_number(row.find('input.sellorder_quantity'), true);

        var sub_total_after_tax = sellorder_after_tax * quantity;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellorder_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var sellorder_before_tax = __get_principle(sellorder_after_tax, tax_rate);
        var sub_total_before_tax = quantity * sellorder_before_tax;
        var tax = __calculate_amount('percentage', tax_rate, sellorder_before_tax);

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var sellorder_before_discount = __get_principle(sellorder_before_tax, discount_percent, true);
        __write_number(
            row.find('input.sellorder_unit_cost_without_discount'),
            sellorder_before_discount,
            true
        );

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        __write_number(row.find('.sellorder_unit_cost'), sellorder_before_tax, true);

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.sellorder_product_unit_tax_text').text(__currency_trans_from_en(tax, true, true));
        __write_number(row.find('input.sellorder_product_unit_tax'), tax);

        update_table_total();
        update_grand_total();
    });

    $('#tax_id, #discount_type, #discount_amount, input#shipping_charges').change(function () {
        update_grand_total();
    });

    //Purchase table
    sellorder_table = $('#sellorder_table').DataTable({
        processing: true,
        serverSide: false,
        aaSorting: [[0, 'desc']],
        ajax: {
            url: '/sellorder',
            data: function (d) {
                if ($('#sellorder_list_filter_location_id').length) {
                    d.location_id = $('#sellorder_list_filter_location_id').val();
                }
                if ($('#sellorder_list_filter_customer_id').length) {
                    d.supplier_id = $('#sellorder_list_filter_customer_id').val();
                }
                if ($('#sellorder_list_filter_payment_status').length) {
                    d.payment_status = $('#sellorder_list_filter_payment_status').val();
                }
                if ($('#sellorder_list_filter_status').length) {
                    d.status = $('#sellorder_list_filter_status').val();
                }

                var start = '';
                var end = '';
                if ($('#sellorder_list_filter_date_range').val()) {
                    start = $('input#sellorder_list_filter_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#sellorder_list_filter_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
            },
        },
        columnDefs: [
            {
                targets: [7, 8],
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_name', name: 'BS.name' },
            { data: 'name', name: 'contacts.name' },
            { data: 'status', name: 'status' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'final_total', name: 'final_total' },
            { data: 'payment_due', name: 'payment_due' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
            var total_sellorder = sum_table_col($('#sellorder_table'), 'final_total');
            $('#footer_sellorder_total').text(total_sellorder);

            var total_due = sum_table_col($('#sellorder_table'), 'payment_due');
            $('#footer_total_due').text(total_due);

            var total_sellorder_return_due = sum_table_col($('#sellorder_table'), 'sellorder_return');
            $('#footer_total_sellorder_return_due').text(total_sellorder_return_due);

            $('#footer_status_count').html(__sum_status_html($('#sellorder_table'), 'status-label'));

            $('#footer_payment_status_count').html(
                __sum_status_html($('#sellorder_table'), 'payment-status-label')
            );

            __currency_convert_recursively($('#sellorder_table'));
        },
        createdRow: function (row, data, dataIndex) {
            $(row)
                .find('td:eq(5)')
                .attr('class', 'clickable_td');
        },
    });

    $(document).on(
        'change',
        '#sellorder_list_filter_location_id, \
                    #sellorder_list_filter_customer_id, #sellorder_list_filter_payment_status,\
                     #sellorder_list_filter_status',
        function () {
            sellorder_table.ajax.reload();
        }
    );

    update_table_sr_number();

    $(document).on('change', '.mfg_date', function () {
        var this_date = $(this).val();
        var this_moment = moment(this_date, moment_date_format);
        var expiry_period = parseFloat(
            $(this)
                .closest('td')
                .find('.row_product_expiry')
                .val()
        );
        var expiry_period_type = $(this)
            .closest('td')
            .find('.row_product_expiry_type')
            .val();
        if (this_date) {
            if (expiry_period && expiry_period_type) {
                exp_date = this_moment
                    .add(expiry_period, expiry_period_type)
                    .format(moment_date_format);
                $(this)
                    .closest('td')
                    .find('.exp_date')
                    .datepicker('update', exp_date);
            } else {
                $(this)
                    .closest('td')
                    .find('.exp_date')
                    .datepicker('update', '');
            }
        } else {
            $(this)
                .closest('td')
                .find('.exp_date')
                .datepicker('update', '');
        }
    });

    $('#sellorder_entry_table tbody')
        .find('.expiry_datepicker')
        .each(function () {
            $(this).datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
        });

    $(document).on('change', '.profit_percent', function () {
        var row = $(this).closest('tr');
        var profit_percent = __read_number($(this), true);

        var sellorder_unit_cost = __read_number(row.find('input.sellorder_unit_cost'), true);
        var default_sell_price =
            parseFloat(sellorder_unit_cost) +
            __calculate_amount('percentage', profit_percent, sellorder_unit_cost);
        var exchange_rate = $('input#exchange_rate').val();
        __write_number(
            row.find('input.default_sell_price'),
            default_sell_price * exchange_rate,
            true
        );
    });

    $(document).on('change', '.default_sell_price', function () {
        var row = $(this).closest('tr');
        update_inline_profit_percentage(row);
    });

    $('table#sellorder_table tbody').on('click', 'a.delete-sellorder', function (e) {
        e.preventDefault();
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).attr('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function (result) {
                        if (result.success == true) {
                            toastr.success(result.msg);
                            sellorder_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $('table#sellorder_entry_table').on('change', 'select.sub_unit', function () {
        var tr = $(this).closest('tr');
        var base_unit_cost = tr.find('input.base_unit_cost').val();
        var base_unit_selling_price = tr.find('input.base_unit_selling_price').val();

        var multiplier = parseFloat(
            $(this)
                .find(':selected')
                .data('multiplier')
        );

        var unit_sp = base_unit_selling_price * multiplier;
        var unit_cost = base_unit_cost * multiplier;

        var sp_element = tr.find('input.default_sell_price');
        __write_number(sp_element, unit_sp);

        var cp_element = tr.find('input.sellorder_unit_cost_without_discount');
        __write_number(cp_element, unit_cost);
        cp_element.change();
    });
    
    // $('table#sellorder_entry_table tbody').on('change', 'input.sellorder_quantity', function() {
    //     $('table#sellorder_entry_table tbody').on('change', '.sellorder_quantity', function () {
    //     var qty_element = $(this).closest('tr');
    //     if ($(this).val()) {
    //         var default_qty = $(this).data('qty_available');
    //         var default_err_msg = $(this).data('msg_max_default');
    //         console.log("default_qty",default_qty,default_err_msg,qty_element);
    //         qty_element.attr('data-rule-max-value', default_qty);
    //         qty_element.attr('data-msg-max-value', default_err_msg);
    
    //         qty_element.rules('add', {
    //             'max-value': default_qty,
    //             messages: {
    //                 'max-value': default_err_msg,
    //             },
    //         });
    //     }
    //     qty_element.trigger('change');
    // });
});

function get_sellorder_entry_row(product_id, variation_id,product_qty=1,date) {
    if (product_id) {

        var add_via_ajax = true;
        var is_added     = false;
        var row_count    = $('#row_count').val();

        $('#sellorder_entry_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.hidden_variation_id')
                    .val();
                var sellOrderdate = $(this)
                    .find('.sell_order_date')
                    .val();
                    
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (row_v_id == variation_id && !is_added && sellOrderdate == date
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.sellorder_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, (Number(qty) + Number(product_qty)));
                    qty_element.change();

                    //round_row_to_iraqi_dinnar($(this));
                    update_table_total();
                    update_grand_total();
                    update_table_sr_number();

                    $('input#search_product')
                        .focus()
                        .select();
                }
            });
        if(add_via_ajax)
        {
            $.ajax({
                method: 'POST',
                url: '/sellorder/get_sellorder_entry_row',
                dataType: 'html',
                data: { product_id: product_id, row_count: row_count, variation_id: variation_id,product_qty:product_qty,date:date },
                success: function (result) {
                    $(result)
                        .find('.sellorder_quantity')
                        .each(function () {
                            row = $(this).closest('tr');

                            $('#sellorder_entry_table tbody').prepend(
                                update_sellorder_entry_row_values(row)
                            );
                            update_row_price_for_exchange_rate(row);

                            update_inline_profit_percentage(row);

                            update_table_total();
                            update_grand_total();
                            update_table_sr_number();
                        });
                    if ($(result).find('.sellorder_quantity').length) {
                        $('#row_count').val(
                            $(result).find('.sellorder_quantity').length + parseInt(row_count)
                        );
                    }
                },
            });
        }
        document.getElementById('search_product').value = '';
        document.getElementById('product_qty').value = '';
        $('input#search_product').focus()
    }
}

function update_sellorder_entry_row_values(row) {
    if (typeof row != 'undefined') {
        var quantity = __read_number(row.find('.sellorder_quantity'), true);
        var unit_cost_price = __read_number(row.find('.sellorder_unit_cost'), true);
        var row_subtotal_before_tax = quantity * unit_cost_price;

        var tax_rate = parseFloat(
            $('option:selected', row.find('.sellorder_line_tax_id')).attr('data-tax_amount')
        );

        var unit_product_tax = __calculate_amount('percentage', tax_rate, unit_cost_price);

        var unit_cost_price_after_tax = unit_cost_price + unit_product_tax;
        var row_subtotal_after_tax = quantity * unit_cost_price_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(row_subtotal_before_tax, false, true)
        );
        __write_number(row.find('.row_subtotal_before_tax_hidden'), row_subtotal_before_tax, true);
        __write_number(row.find('.sellorder_product_unit_tax'), unit_product_tax, true);
        row.find('.sellorder_product_unit_tax_text').text(
            __currency_trans_from_en(unit_product_tax, false, true)
        );
        row.find('.sellorder_unit_cost_after_tax').text(
            __currency_trans_from_en(unit_cost_price_after_tax, true)
        );
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(row_subtotal_after_tax, false, true)
        );
        __write_number(row.find('.row_subtotal_after_tax_hidden'), row_subtotal_after_tax, true);

        row.find('.expiry_datepicker').each(function () {
            $(this).datepicker({
                autoclose: true,
                format: datepicker_date_format,
            });
        });

        
        return row;
    }
}

function update_row_price_for_exchange_rate(row) {
    var exchange_rate = $('input#exchange_rate').val();

    if (exchange_rate == 1) {
        return true;
    }

    var sellorder_unit_cost_without_discount =
        __read_number(row.find('.sellorder_unit_cost_without_discount'), true) / exchange_rate;
    __write_number(
        row.find('.sellorder_unit_cost_without_discount'),
        sellorder_unit_cost_without_discount,
        true
    );

    var sellorder_unit_cost = __read_number(row.find('.sellorder_unit_cost'), true) / exchange_rate;
    __write_number(row.find('.sellorder_unit_cost'), sellorder_unit_cost, true);

    var row_subtotal_before_tax_hidden =
        __read_number(row.find('.row_subtotal_before_tax_hidden'), true) / exchange_rate;
    row.find('.row_subtotal_before_tax').text(
        __currency_trans_from_en(row_subtotal_before_tax_hidden, false, true)
    );
    __write_number(
        row.find('input.row_subtotal_before_tax_hidden'),
        row_subtotal_before_tax_hidden,
        true
    );

    var sellorder_product_unit_tax =
        __read_number(row.find('.sellorder_product_unit_tax'), true) / exchange_rate;
    __write_number(row.find('input.sellorder_product_unit_tax'), sellorder_product_unit_tax, true);
    row.find('.sellorder_product_unit_tax_text').text(
        __currency_trans_from_en(sellorder_product_unit_tax, false, true)
    );

    var sellorder_unit_cost_after_tax =
        __read_number(row.find('.sellorder_unit_cost_after_tax'), true) / exchange_rate;
    __write_number(
        row.find('input.sellorder_unit_cost_after_tax'),
        sellorder_unit_cost_after_tax,
        true
    );

    var row_subtotal_after_tax_hidden =
        __read_number(row.find('.row_subtotal_after_tax_hidden'), true) / exchange_rate;
    __write_number(
        row.find('input.row_subtotal_after_tax_hidden'),
        row_subtotal_after_tax_hidden,
        true
    );
    row.find('.row_subtotal_after_tax').text(
        __currency_trans_from_en(row_subtotal_after_tax_hidden, false, true)
    );
}

function iraqi_dinnar_selling_price_adjustment(row) {
    var default_sell_price = __read_number(row.find('input.default_sell_price'), true);

    //Adjsustment
    var remaining = default_sell_price % 250;
    if (remaining >= 125) {
        default_sell_price += 250 - remaining;
    } else {
        default_sell_price -= remaining;
    }

    __write_number(row.find('input.default_sell_price'), default_sell_price, true);

    update_inline_profit_percentage(row);
}

function update_inline_profit_percentage(row) {
    //Update Profit percentage
    var default_sell_price = __read_number(row.find('input.default_sell_price'), true);
    var exchange_rate = $('input#exchange_rate').val();
    default_sell_price_in_base_currency = default_sell_price / parseFloat(exchange_rate);

    var sellorder_before_tax = __read_number(row.find('input.sellorder_unit_cost'), true);
    var profit_percent = __get_rate(sellorder_before_tax, default_sell_price_in_base_currency);
    __write_number(row.find('input.profit_percent'), profit_percent, true);
}

function update_table_total() {
    var total_quantity = 0;
    var total_st_before_tax = 0;
    var total_subtotal = 0;

    $('#sellorder_entry_table tbody')
        .find('tr')
        .each(function () {
            total_quantity += __read_number($(this).find('.sellorder_quantity'), true);
            total_st_before_tax += __read_number(
                $(this).find('.row_subtotal_before_tax_hidden'),
                true
            );
            total_subtotal += __read_number($(this).find('.row_subtotal_after_tax_hidden'), true);
        });

    $('#total_quantity').text(__number_f(total_quantity, true));
    $('#total_st_before_tax').text(__currency_trans_from_en(total_st_before_tax, true, true));
    __write_number($('input#st_before_tax_input'), total_st_before_tax, true);

    $('#total_subtotal').text(__currency_trans_from_en(total_subtotal, true, true));
    __write_number($('input#total_subtotal_input'), total_subtotal, true);
}

function update_grand_total() {
    var st_before_tax = __read_number($('input#st_before_tax_input'), true);
    var total_subtotal = __read_number($('input#total_subtotal_input'), true);

    //Calculate Discount
    var discount_type = $('select#discount_type').val();
    var discount_amount = __read_number($('input#discount_amount'), true);
    var discount = __calculate_amount(discount_type, discount_amount, total_subtotal);
    $('#discount_calculated_amount').text(__currency_trans_from_en(discount, true, true));

    //Calculate Tax
    var tax_rate = parseFloat($('option:selected', $('#tax_id')).data('tax_amount'));
    var tax = __calculate_amount('percentage', tax_rate, total_subtotal - discount);
    __write_number($('input#tax_amount'), tax);
    $('#tax_calculated_amount').text(__currency_trans_from_en(tax, true, true));

    //Calculate shipping
    var shipping_charges = __read_number($('input#shipping_charges'), true);

    //Calculate Final total
    grand_total = total_subtotal - discount + tax + shipping_charges;

    __write_number($('input#grand_total_hidden'), grand_total, true);

    var payment = __read_number($('input.payment-amount'), true);

    var due = grand_total - payment;
    // __write_number($('input.payment-amount'), grand_total, true);

    $('#grand_total').text(__currency_trans_from_en(grand_total, true, true));

    $('#payment_due').text(__currency_trans_from_en(due, true, true));

    //__currency_convert_recursively($(document));
}
$(document).on('change', 'input.payment-amount', function () {
    var payment = __read_number($(this), true);
    var grand_total = __read_number($('input#grand_total_hidden'), true);
    var bal = grand_total - payment;
    $('#payment_due').text(__currency_trans_from_en(bal, true, true));
});

function update_table_sr_number() {
    var sr_number = 1;
    $('table#sellorder_entry_table tbody')
        .find('.sr_number')
        .each(function () {
            $(this).text(sr_number);
            sr_number++;
        });
}

$(document).on('click', 'button#submit_sellorder_form', function (e) {
    e.preventDefault();

    //Check if product is present or not.
    if ($('table#sellorder_entry_table tbody tr').length <= 0) {
        toastr.warning(LANG.no_products_added);
        $('input#search_product').select();
        return false;
    }

    $('form#add_sellorder_form').validate({
        rules: {
            ref_no: {
                remote: {
                    url: '/sellorder/check_ref_number',
                    type: 'post',
                    data: {
                        ref_no: function () {
                            return $('#ref_no').val();
                        },
                        contact_id: function () {
                            return $('#supplier_id').val();
                        },
                        sellorder_id: function () {
                            if ($('#sellorder_id').length > 0) {
                                return $('#sellorder_id').val();
                            } else {
                                return '';
                            }
                        },
                    },
                },
            },
        },
        messages: {
            ref_no: {
                remote: LANG.ref_no_already_exists,
            },
        },
    });

    if ($('form#add_sellorder_form').valid()) {
        $('form#add_sellorder_form').submit();
    }
});

$('#add-product').click(function(){
    var product_id   = document.getElementById('product_id').value;
    var variation_id = document.getElementById('variation_id').value;
    var product_qty  = document.getElementById('product_qty').value;
    var date         = document.getElementById('sellorderdate').value;
    get_sellorder_entry_row(product_id, variation_id,product_qty,date);
})

