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
                source: '/sellreturngenralstore/getProducts',
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
                    
                    document.getElementById('search_product').value = ui.item.text;
                    document.getElementById('variation_id').value   = ui.item.variation_id;
                    document.getElementById('product_id').value     = ui.item.product_id;
                    //document.getElementById('qty_available').value   = ui.item.qty_available;
                    //document.getElementById('enable_stock').value   = ui.item.enable_stock;
                    // $("#ui-autocomplete").hide()
                    return false;
                    $(this).val(null);
                    
                },
            })
            .autocomplete('instance')._renderItem = function (ul, item) {
                return $('<li>')
                    .append('<div>' + item.text + '</div>')
                    .appendTo(ul);
            };
    }

    $(document).on('click', '.remove_sellreturn_entry_row', function () {
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
    $(document).on('change', '.sellreturn_quantity', function () {
        var row = $(this).closest('tr');
        var quantity = __read_number($(this), true);
        var sellreturn_before_tax = __read_number(row.find('input.sellreturn_unit_cost'), true);
        var sellreturn_after_tax = __read_number(
            row.find('input.sellreturn_unit_cost_after_tax'),
            true
        );

        //Calculate sub totals
        var sub_total_before_tax = quantity * sellreturn_before_tax;
        var sub_total_after_tax = quantity * sellreturn_before_tax;

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

    $(document).on('change', '.sellreturn_unit_cost_without_discount', function () {
        var sellreturn_before_discount = __read_number($(this), true);

        var row = $(this).closest('tr');
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var quantity = __read_number(row.find('input.sellreturn_quantity'), true);

        //Calculations.
        var sellreturn_before_tax =
            parseFloat(sellreturn_before_discount) -
            __calculate_amount('percentage', discount_percent, sellreturn_before_discount);

        __write_number(row.find('input.sellreturn_unit_cost'), sellreturn_before_tax, true);

        var sub_total_before_tax = quantity * sellreturn_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellreturn_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellreturn_before_tax);

        var sellreturn_after_tax = sellreturn_before_tax + tax;
        var sub_total_after_tax = quantity * sellreturn_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.sellreturn_unit_cost_after_tax'), sellreturn_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        row.find('.sellreturn_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellreturn_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.inline_discounts', function () {
        var row = $(this).closest('tr');

        var discount_percent = __read_number($(this), true);

        var quantity = __read_number(row.find('input.sellreturn_quantity'), true);
        var sellreturn_before_discount = __read_number(
            row.find('input.sellreturn_unit_cost_without_discount'),
            true
        );

        //Calculations.
        var sellreturn_before_tax =
            parseFloat(sellreturn_before_discount) -
            __calculate_amount('percentage', discount_percent, sellreturn_before_discount);

        __write_number(row.find('input.sellreturn_unit_cost'), sellreturn_before_tax, true);

        var sub_total_before_tax = quantity * sellreturn_before_tax;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellreturn_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellreturn_before_tax);

        var sellreturn_after_tax = sellreturn_before_tax + tax;
        var sub_total_after_tax = quantity * sellreturn_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        __write_number(row.find('input.sellreturn_unit_cost_after_tax'), sellreturn_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);
        row.find('.sellreturn_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellreturn_product_unit_tax'), tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.sellreturn_unit_cost', function () {
        var row = $(this).closest('tr');
        var quantity = __read_number(row.find('input.sellreturn_quantity'), true);
        var sellreturn_before_tax = __read_number($(this), true);

        var sub_total_before_tax = quantity * sellreturn_before_tax;

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var sellreturn_before_discount = __get_principle(sellreturn_before_tax, discount_percent, true);
        __write_number(
            row.find('input.sellreturn_unit_cost_without_discount'),
            sellreturn_before_discount,
            true
        );

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellreturn_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellreturn_before_tax);

        var sellreturn_after_tax = sellreturn_before_tax + tax;
        var sub_total_after_tax = quantity * sellreturn_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.sellreturn_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellreturn_product_unit_tax'), tax, true);

        //row.find('.sellreturn_product_unit_tax_text').text( tax );
        __write_number(row.find('input.sellreturn_unit_cost_after_tax'), sellreturn_after_tax, true);
        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_inline_profit_percentage(row);
        update_table_total();
        update_grand_total();
    });

    $(document).on('change', 'select.sellreturn_line_tax_id', function () {
        var row = $(this).closest('tr');
        var sellreturn_before_tax = __read_number(row.find('.sellreturn_unit_cost'), true);
        var quantity = __read_number(row.find('input.sellreturn_quantity'), true);

        //Tax
        var tax_rate = parseFloat(
            $(this)
                .find(':selected')
                .data('tax_amount')
        );
        var tax = __calculate_amount('percentage', tax_rate, sellreturn_before_tax);

        //Purchase price
        var sellreturn_after_tax = sellreturn_before_tax + tax;
        var sub_total_after_tax = quantity * sellreturn_after_tax;

        row.find('.sellreturn_product_unit_tax_text').text(
            __currency_trans_from_en(tax, false, true)
        );
        __write_number(row.find('input.sellreturn_product_unit_tax'), tax, true);

        __write_number(row.find('input.sellreturn_unit_cost_after_tax'), sellreturn_after_tax, true);

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        update_table_total();
        update_grand_total();
    });

    $(document).on('change', '.sellreturn_unit_cost_after_tax', function () {
        var row = $(this).closest('tr');
        var sellreturn_after_tax = __read_number($(this), true);
        var quantity = __read_number(row.find('input.sellreturn_quantity'), true);

        var sub_total_after_tax = sellreturn_after_tax * quantity;

        //Tax
        var tax_rate = parseFloat(
            row
                .find('select.sellreturn_line_tax_id')
                .find(':selected')
                .data('tax_amount')
        );
        var sellreturn_before_tax = __get_principle(sellreturn_after_tax, tax_rate);
        var sub_total_before_tax = quantity * sellreturn_before_tax;
        var tax = __calculate_amount('percentage', tax_rate, sellreturn_before_tax);

        //Update unit cost price before discount
        var discount_percent = __read_number(row.find('input.inline_discounts'), true);
        var sellreturn_before_discount = __get_principle(sellreturn_before_tax, discount_percent, true);
        __write_number(
            row.find('input.sellreturn_unit_cost_without_discount'),
            sellreturn_before_discount,
            true
        );

        row.find('.row_subtotal_after_tax').text(
            __currency_trans_from_en(sub_total_after_tax, false, true)
        );
        __write_number(row.find('input.row_subtotal_after_tax_hidden'), sub_total_after_tax, true);

        __write_number(row.find('.sellreturn_unit_cost'), sellreturn_before_tax, true);

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(sub_total_before_tax, false, true)
        );
        __write_number(
            row.find('input.row_subtotal_before_tax_hidden'),
            sub_total_before_tax,
            true
        );

        row.find('.sellreturn_product_unit_tax_text').text(__currency_trans_from_en(tax, true, true));
        __write_number(row.find('input.sellreturn_product_unit_tax'), tax);

        update_table_total();
        update_grand_total();
    });

    $('#tax_id, #discount_type, #discount_amount, input#shipping_charges').change(function () {
        update_grand_total();
    });

    //Purchase table
    // 

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

    $('#sellreturn_entry_table tbody')
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

        var sellreturn_unit_cost = __read_number(row.find('input.sellreturn_unit_cost'), true);
        var default_sell_price =
            parseFloat(sellreturn_unit_cost) +
            __calculate_amount('percentage', profit_percent, sellreturn_unit_cost);
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

    $('table#sell_return_table tbody').on('click', 'a.delete-sellreturn', function (e) {
        
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
                            sell_return_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $('table#sellreturn_entry_table').on('change', 'select.sub_unit', function () {
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

        var cp_element = tr.find('input.sellreturn_unit_cost_without_discount');
        __write_number(cp_element, unit_cost);
        cp_element.change();
    });
});

function get_sellreturn_entry_row() {
    var product_id   = document.getElementById('product_id').value;
    var variation_id = document.getElementById('variation_id').value;
    var product_qty  = document.getElementById('product_qty').value;
    if (product_id) {

        var add_via_ajax = true;
        var is_added     = false;
        var row_count = $('#row_count').val();

        $('#sellreturn_entry_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.hidden_variation_id')
                    .val();
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                if (row_v_id == variation_id && !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity
                    qty_element = $(this).find('.sellreturn_quantity');
                    var qty = __read_number(qty_element);
                    __write_number(qty_element, (Number(qty) + Number(product_qty)));
                    qty_element.change();

                    //round_row_to_iraqi_dinnar($(this));
                    
                    $('input#search_product')
                        .focus()
                        .select();
                }
            });
            if(add_via_ajax)
            {
                $.ajax({
                    method: 'POST',
                    url: '/sellreturngenralstore/get_sellreturn_entry_row',
                    dataType: 'html',
                    data: { product_id: product_id, row_count: row_count, variation_id: variation_id,product_qty:product_qty },
                    success: function (result) {
                        $(result)
                            .find('.sellreturn_quantity')
                            .each(function () {
                                row = $(this).closest('tr');

                                $('#sellreturn_entry_table tbody').append(
                                    update_sellreturn_entry_row_values(row)
                                );
                                update_row_price_for_exchange_rate(row);

                                update_inline_profit_percentage(row);

                                update_table_total();
                                update_grand_total();
                                update_table_sr_number();
                            });
                        if ($(result).find('.sellreturn_quantity').length) {
                            $('#row_count').val(
                                $(result).find('.sellreturn_quantity').length + parseInt(row_count)
                            );
                        }
                    },
                });
            }
    }
    document.getElementById('search_product').value = '';
    document.getElementById('product_qty').value = '';
    $('input#search_product').focus()
}

function update_sellreturn_entry_row_values(row) {
    if (typeof row != 'undefined') {
        var quantity = __read_number(row.find('.sellreturn_quantity'), true);
        var unit_cost_price = __read_number(row.find('.sellreturn_unit_cost'), true);
        var row_subtotal_before_tax = quantity * unit_cost_price;

        var tax_rate = parseFloat(
            $('option:selected', row.find('.sellreturn_line_tax_id')).attr('data-tax_amount')
        );

        var unit_product_tax = __calculate_amount('percentage', tax_rate, unit_cost_price);

        var unit_cost_price_after_tax = unit_cost_price + unit_product_tax;
        var row_subtotal_after_tax = quantity * unit_cost_price_after_tax;

        row.find('.row_subtotal_before_tax').text(
            __currency_trans_from_en(row_subtotal_before_tax, false, true)
        );
        __write_number(row.find('.row_subtotal_before_tax_hidden'), row_subtotal_before_tax, true);
        __write_number(row.find('.sellreturn_product_unit_tax'), unit_product_tax, true);
        row.find('.sellreturn_product_unit_tax_text').text(
            __currency_trans_from_en(unit_product_tax, false, true)
        );
        row.find('.sellreturn_unit_cost_after_tax').text(
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

    var sellreturn_unit_cost_without_discount =
        __read_number(row.find('.sellreturn_unit_cost_without_discount'), true) / exchange_rate;
    __write_number(
        row.find('.sellreturn_unit_cost_without_discount'),
        sellreturn_unit_cost_without_discount,
        true
    );

    var sellreturn_unit_cost = __read_number(row.find('.sellreturn_unit_cost'), true) / exchange_rate;
    __write_number(row.find('.sellreturn_unit_cost'), sellreturn_unit_cost, true);

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

    var sellreturn_product_unit_tax =
        __read_number(row.find('.sellreturn_product_unit_tax'), true) / exchange_rate;
    __write_number(row.find('input.sellreturn_product_unit_tax'), sellreturn_product_unit_tax, true);
    row.find('.sellreturn_product_unit_tax_text').text(
        __currency_trans_from_en(sellreturn_product_unit_tax, false, true)
    );

    var sellreturn_unit_cost_after_tax =
        __read_number(row.find('.sellreturn_unit_cost_after_tax'), true) / exchange_rate;
    __write_number(
        row.find('input.sellreturn_unit_cost_after_tax'),
        sellreturn_unit_cost_after_tax,
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

    var sellreturn_before_tax = __read_number(row.find('input.sellreturn_unit_cost'), true);
    var profit_percent = __get_rate(sellreturn_before_tax, default_sell_price_in_base_currency);
    __write_number(row.find('input.profit_percent'), profit_percent, true);
}

function update_table_total() {
    var total_quantity = 0;
    var total_st_before_tax = 0;
    var total_subtotal = 0;

    $('#sellreturn_entry_table tbody')
        .find('tr')
        .each(function () {
            total_quantity += __read_number($(this).find('.sellreturn_quantity'), true);
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
    $('table#sellreturn_entry_table tbody')
        .find('.sr_number')
        .each(function () {
            $(this).text(sr_number);
            sr_number++;
        });
}

$(document).on('click', 'button#submit_sellreturn_form', function (e) {
    e.preventDefault();

    //Check if product is present or not.
    if ($('table#sellreturn_entry_table tbody tr').length <= 0) {
        toastr.warning(LANG.no_products_added);
        $('input#search_product').select();
        return false;
    }

    $('form#add_sellreturn_form').validate({
        rules: {
            ref_no: {
                remote: {
                    url: '/sellreturngenralstore/check_ref_number',
                    type: 'post',
                    data: {
                        ref_no: function () {
                            return $('#ref_no').val();
                        },
                        contact_id: function () {
                            return $('#supplier_id').val();
                        },
                        sellreturn_id: function () {
                            if ($('#sellreturn_id').length > 0) {
                                return $('#sellreturn_id').val();
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

    if ($('form#add_sellreturn_form').valid()) {
        $('#submit_sellreturn_form').prop('disabled', true);
        submit_sell_return_form()
        //$('form#add_sellreturn_form').submit();
    }
    function submit_sell_return_form()
    {
        var data = $("#add_sellreturn_form").serialize();
        data = data + '&status=final';
        var url = $("#add_sellreturn_form").attr('action');
        
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                if (result.success == 1) {
                    
                    toastr.success(result.msg);

                    setTimeout(function() {
                        window.location.reload()
                    }, 4000);

                    //Check if enabled or not
                    if (result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }

                    //get_recent_transactions('final', $('div#tab_final'));
                } else {
                    toastr.error(result.msg);
                }

                $('div.pos-processing').hide();
                $('#submit_sellreturn_form').removeAttr('disabled');
            },
        });
    }

    function pos_print(receipt) {
        //If printer type then connect with websocket
        if (receipt.print_type == 'printer') {
            var content = receipt;
            content.type = 'print-receipt';
    
            //Check if ready or not, then print.
            if (socket != null && socket.readyState == 1) {
                socket.send(JSON.stringify(content));
            } else {
                initializeSocket();
                setTimeout(function() {
                    socket.send(JSON.stringify(content));
                }, 700);
            }
    
        } else if (receipt.html_content != '') {
            //If printer type browser then print content
            $('#receipt_section').html(receipt.html_content);
            __currency_convert_recursively($('#receipt_section'));
            __print_receipt('receipt_section');
        }
    }

    
});

$('#add-product').click(function(){
    get_sellreturn_entry_row();
})

$('#product_qty').on('keypress', function(e) {
    var key = e.which;
    if (key == 13) {
        // the enter key code
        get_sellreturn_entry_row();
    }
});