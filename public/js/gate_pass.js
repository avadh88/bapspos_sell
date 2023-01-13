$(document).ready(function () {
    $("#gp_prefix").text(`GP${new Date().getFullYear()}/`)
    $('input.gate_pass_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
    });

    gate_pass_table = $('#gate_pass_table').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: '/gate-pass',
            data: function (d) {
                if ($('#gate_pass_filter_serial_no').length) {
                    d.serial_no = $('#gate_pass_filter_serial_no').val();
                }

                var start = '';
                var end = '';
                if ($('#gate_pass_filter_date_range').val()) {
                    start = $('input#gate_pass_filter_date_range')
                        .data('daterangepicker')
                        .startDate.format('YYYY-MM-DD');
                    end = $('input#gate_pass_filter_date_range')
                        .data('daterangepicker')
                        .endDate.format('YYYY-MM-DD');
                }
                d.start_date = start;
                d.end_date = end;
            },
        },
        columnDefs: [
            {
                orderable: false,
                searchable: false,
            },
        ],
        columns: [
            { data: 'serial_no', name: 'serial_no' },
            { data: 'date', name: 'date' },
            { data: 'vibhag_name', name: 'vibhag_name' },
            { data: 'driver_name', name: 'driver_name' },
            { data: 'driver_mobile_number', name: 'driver_mobile_number' },
            { data: 'vehicle_number', name: 'vehicle_number' },
            { data: 'deliever_to', name: 'deliever_to' },
            { data: 'check_in', name: 'check_in' },
            { data: 'check_out', name: 'check_out' },
            { data: 'action', name: 'action' },
        ],
        fnDrawCallback: function (oSettings) {
        },
    });

    $('table#gate_pass_table tbody').on('click', 'a.delete-gate_pass', function (e) {
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
                            gate_pass_table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            }
        });
    });

    $(document).on('click', '#add_items', function () {
        var html = '<div class="row">'
        html += '<div class="col-sm-4" style="padding-left: 0">'
        html += '<div class="form-group">'
        html += '<input type="text" name="items[]" class="form-control">'
        html += '</div>'
        html += '</div>'
        // html += '<div class="col-sm-4" style="padding-left: 10px; padding-right: 10px">'
        // html += '<div class="form-group">'
        // html += '<input type="text" name="qtys[]" class="form-control" required>'
        // html += '</div>'
        // html += '</div>'
        html += '<div class="col-sm-4" style="padding-left:22px">'
        html += '<div class="form-group">'
        html += '<button type="button" class="btn btn-danger delete_items">-</button>'
        html += '</div>'
        html += '</div>'
        html += '</div>';
        $('#items_data').append(html);
    });
    $(document).on('click', '.delete_items', function () {
        $(this)
            .closest('.row')
            .remove();
    });
    $(document).on('click', 'button#submit_gate_pass_form', function (e) {
        e.preventDefault();
        $('form#add_gate_pass_form').validate({
        });
        if ($('form#add_gate_pass_form').valid()) {
            //     $('form#add_gate_pass_form').submit();
            var data = $("#add_gate_pass_form").serialize();
            var url = $("#add_gate_pass_form").attr('action');
            $.ajax({
                method: 'POST',
                url: url,
                data: data,
                dataType: 'json',
                success: function (result) {
                    if (result.success == 1) {
                        toastr.success(result.msg);

                        setTimeout(function () {
                            window.location.reload()
                        }, 4000);

                        gatepass_print(result.receipt);

                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });

    $(document).on('click', 'button#submit_edit_gate_pass_form', function (e) {
        $('form#add_gate_pass_form').validate({
        });
        $('form#add_gate_pass_form').valid()

    });

    $(document).on('click', 'button#submit_checkout_form', function (e) {
        e.preventDefault()
        $('form#checkout_gate_pass_form').validate({
        });
        if ($('form#checkout_gate_pass_form').valid()) {
            // let data = $("#checkout_gate_pass_form").serialize();
            let serial_no = $("#serial_no").val();
            let prefix = `GP${new Date().getFullYear()}/`;
            let data = {
                serial_no, prefix
            }
            console.log("data", data);
            $("#checkout-details").html("")
            $.ajax({
                url: '/gate-pass/checkout-details',
                type: 'get',
                data: data,
                success: function (response) {
                    if (response.success) {
                        toastr.success(response.msg);
                    } else if (response.success == 0) {
                        toastr.error(response.msg);
                    } else {
                        $("#checkout-details").html(response);
                        $('#checkout-form').modal('show');

                    }
                },
            })
        }
    });

    $('#gate_pass_filter_serial_no').on('keyup', function (e) {
        let key = e.which;
        let serialNo = $("#gate_pass_filter_serial_no").val().length;
        if (key == 13 || serialNo > 3 || serialNo == 0) {
            gate_pass_table.ajax.reload();
        }
    });

});
function gatepass_print(receipt) {

    if (receipt.html_content != '') {
        //If printer type browser then print content
        $('#receipt_section').html(receipt.html_content);
        __currency_convert_recursively($('#receipt_section'));
        __print_receipt('receipt_section');
    }
}

