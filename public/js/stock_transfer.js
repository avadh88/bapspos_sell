$(document).ready(function() {
    //Add products
    if ($('#search_product_for_srock_adjustment').length > 0) {
        //Add Product
        $('#search_product_for_srock_adjustment')
            .autocomplete({
                source: function(request, response) {
                    $.getJSON(
                        '/products/list',
                        { location_id:  (typeof($('#location_id').val()) != 'undefined') ? $('#location_id').val() : (typeof($('#location_id_all').val()) != 'undefined' ? $('#location_id_all').val(): ''), 
                        term: request.term },
                        response
                    );
                },
                minLength: 1,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        if (ui.item.qty_available > 0 && ui.item.enable_stock == 1) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                focus: function(event, ui) {
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    if (ui.item.qty_available > 0) {
                        //$(this).val(null);
                        //stock_transfer_product_row(ui.item.variation_id);
                        document.getElementById('search_product_for_srock_adjustment').value = ui.item.name;
                        document.getElementById('variation_id').value   = ui.item.variation_id;
                        document.getElementById('product_id').value     = ui.item.product_id;
                        document.getElementById('product_current_qty').value     = ui.item.qty_available;
                    
                    return false;
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            if (item.qty_available <= 0) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else if (item.enable_stock != 1) {
                return ul;
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                string += ' (' + item.sub_sku + ') </div>';
                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };
    }

    $('select#location_id').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
    });

    $('select#location_id_all').change(function() {
        if ($(this).val()) {
            $('#search_product_for_srock_adjustment').removeAttr('disabled');
        } else {
            $('#search_product_for_srock_adjustment').attr('disabled', 'disabled');
        }
        $('table#stock_adjustment_product_table tbody').html('');
        $('#product_row_index').val(0);
    });
    

    $(document).on('change', 'input.product_quantity', function() {
        update_table_row($(this).closest('tr'));
    });
    $(document).on('change', 'input.product_unit_price', function() {
        update_table_row($(this).closest('tr'));
    });

    $(document).on('click', '.remove_product_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('tr')
                    .remove();
                update_table_total();
            }
        });
    });

    //Date picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    jQuery.validator.addMethod(
        'notEqual',
        function(value, element, param) {
            return this.optional(element) || value != param;
        },
        'Please select different location'
    );

    $('form#stock_transfer_form').validate({
        rules: {
            transfer_location_id: {
                notEqual: function() {
                    return $('select#location_id').val();
                },
            },
        },
    });
    $('#save_stock_transfer').click(function(e) {
        e.preventDefault();

        if ($('table#stock_adjustment_product_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }
        if ($('form#stock_transfer_form').valid()) {
            $('form#stock_transfer_form').submit();
        } else {
            return false;
        }
    });

    stock_transfer_table = $('#stock_transfer_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: '/stock-transfers',
        columnDefs: [
            {
                targets: 7,
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'transaction_date', name: 'transaction_date' },
            { data: 'ref_no', name: 'ref_no' },
            { data: 'location_from', name: 'l1.name' },
            { data: 'location_to', name: 'l2.name' },
            { data: 'shipping_charges', name: 'shipping_charges' },
            { data: 'final_total', name: 'final_total' },
            { data: 'additional_notes', name: 'additional_notes' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function(oSettings) {
            __currency_convert_recursively($('#stock_transfer_table'));
        },
    });
    var detailRows = [];

    $('#stock_transfer_table tbody').on('click', '.view_stock_transfer', function() {
        var tr = $(this).closest('tr');
        var row = stock_transfer_table.row(tr);
        
        var idx = $.inArray(tr.attr('id'), detailRows);
        if (row.child.isShown()) {
            $(this)
                .find('i')
                .removeClass('fa-eye')
                .addClass('fa-eye-slash');
            row.child.hide();

            // Remove from the 'open' array
            detailRows.splice(idx, 1);
        } else {
            $(this)
                .find('i')
                .removeClass('fa-eye-slash')
                .addClass('fa-eye');

            row.child(get_stock_transfer_details(row.data())).show();

            // Add to the 'open' array
            if (idx === -1) {
                detailRows.push(tr.attr('id'));
            }
        }
    });

    // On each draw, loop over the `detailRows` array and show any child rows
    stock_transfer_table.on('draw', function() {
        $.each(detailRows, function(i, id) {
            $('#' + id + ' .view_stock_transfer').trigger('click');
        });
    });

    //Delete Stock Transfer
    $(document).on('click', 'button.delete_stock_transfer', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var href = $(this).data('href');
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            toastr.success(result.msg);
                            stock_transfer_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $('#location_id_all').on('change',function() {
        $.ajax({
            type: 'get',
            url: '/products/list',
            data: { 
                location_id: $(this).val(),
                category_id:(typeof($('#category_id').val()) != 'undefined') ? $('#category_id').val() :''
            },
            dataType: 'json',
            success: function(result) {
                createProductRow(result);
                update_table_total();
                // if (result.success) {
                //     toastr.success(result.msg);
                //     stock_transfer_table.ajax.reload();
                // } else {
                //     toastr.error(result.msg);
                // }
            },
        });
        
    })

    $('#fetch-po-details').on('click',function() {
        
        $.ajax({
            type: 'get',
            url: '/po/products/list',
            data: { 
                po_id: $('#po').val(), 
            },
            dataType: 'json',
            success: function(result) {
                if(result.status)
                {
                    $('table#stock_adjustment_product_table tbody').html('');
                    createProductRow(result.data);
                    update_table_total();
                   
                    
                }
                
                // if (result.success) {
                //     toastr.success(result.msg);
                //     stock_transfer_table.ajax.reload();
                // } else {
                //     toastr.error(result.msg);
                // }
            },
        });
        
    })

    
});

function stock_transfer_product_row(variation_id,product_qty=1) {
    var row_index = parseInt($('#product_row_index').val());
    var location_id = $('select#location_id').val();
    $.ajax({
        method: 'POST',
        url: '/stock-adjustments/get_product_row',
        data: { row_index: row_index, variation_id: variation_id, location_id: location_id,product_qty:product_qty },
        dataType: 'html',
        success: function(result) {
            $('table#stock_adjustment_product_table tbody').prepend(result);
            update_table_total();
            $('#product_row_index').val(row_index + 1);
        },
    });
    document.getElementById('search_product_for_srock_adjustment').value = '';
    document.getElementById('product_qty').value = '';
    document.getElementById('product_current_qty').value = '';
    $('input#search_product_for_srock_adjustment').focus()
}

function update_table_total() {
    var table_total = 0;
    $('table#stock_adjustment_product_table tbody tr').each(function() {
        var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
        if (this_total) {
            table_total += this_total;
        }
    });
    $('input#total_amount').val(table_total);
    $('span#total_adjustment').text(__number_f(table_total));
}

function update_table_row(tr) {
    var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
    var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
    var row_total = 0;
    if (quantity && unit_price) {
        row_total = quantity * unit_price;
    }
    tr.find('input.product_line_total').val(__number_f(row_total));
    update_table_total();
}

function get_stock_transfer_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');
    $.ajax({
        url: '/stock-transfers/' + rowData.DT_RowId,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}
$('#add-product').click(function(){
    var product_id   = document.getElementById('product_id').value;
    var variation_id = document.getElementById('variation_id').value;
    var product_qty  = document.getElementById('product_qty').value;
    var element      = document.getElementById(product_id);
    if(element)
    {
        element.remove();
    }
    stock_transfer_product_row(variation_id,product_qty);
})


function createProductRow(productArr)
{
    //$('table#stock_adjustment_product_table tbody').html('');
    let i=0;
    productArr.forEach(element => {
        // if(i==100)
        // {
        //     return false;
        // }
        if(element.qty_available > 0)
        {
            let productRow = '';
            productRow+='<tr class="product_row" id="'+element.product_id+'">';
                productRow+="<td>";
                    productRow+=element.name
                    productRow+='<br>';
                    productRow+=element.sub_sku
                productRow+="</td>";
                productRow+="<td>";
                    productRow+='<input type="hidden" name="products['+i+'][product_id]" class="form-control product_id" value="'+element.product_id+'"/>'
                    productRow+='<input type="hidden" name="products['+i+'][variation_id]" class="form-control product_id" value="'+element.product_id+'"/>'
                    productRow+='<input type="hidden" name="products['+i+'][enable_stock]" class="form-control product_id" value="'+element.enable_stock+'"/>'
                    productRow+=(!element.qty_available ? 0 : element.qty_available)
                productRow+='</td>';
                productRow+="<td>";
                    productRow+='<input type="text" class="form-control product_quantity input_number input_quantity" value="'+(!element.qty_available ? 0 : element.qty_available)+'" name="products['+i+'][quantity]" data-rule-required="true" data-msg-required="This field is required" data-rule-max-value="'+element.qty_available+'" data-msg-max-value="Only '+element.qty_available+' '+element.unit+' available" data-qty_available="'+element.qty_available+'" data-msg_max_default="Only '+element.qty_available+' '+element.unit+' available"/>'+element.unit
                productRow+="</td>";
                productRow+='<td>';
                    productRow+='<input type="text" name="products['+i+'][unit_price]" class="form-control product_unit_price input_number" value="'+element.selling_price+'"/>';
                productRow+='</td>';
                productRow+='<td>';
                    productRow+='<input type="text" readonly="" name="products['+i+'][price]" class="form-control product_line_total" value="'+element.qty_available*element.selling_price+'"/>';
                productRow+='</td>';
                productRow+='<td class="text-center">';
                    productRow+='<i class="fa fa-trash remove_product_row cursor-pointer" aria-hidden="true"></i>';
                productRow+='</td>';
            productRow+="</tr>";
            $('table#stock_adjustment_product_table tbody').append(productRow);
            i++;
        }
    });
}