<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<script>
    $(document).ready(function () {
         $('#periodicity').change(function() {
    var selectedOption = $(this).val();
    if(selectedOption!="01"){
      $("#date-finish-container").show();
    }else{
      $("#date-finish-container").hide();
} 
  }); 
       
$("#chek_global_bill").click(function(){
  if ($("#global_bill").is(":visible")) {
    $("#global_bill").css("display", "none");
  } else {
    $("#global_bill").css("display", "block");
  }
});
        function factura(x) {
            if (x == "") {
                return '';
            } else {
                return '<div class="text-center"><span class="payment_status label label-success">' + "Facturado" + '</span></div>';
            }   
}
                  
        function balance(x, number) {
            if (!x) {
                return '0.00';
            }
            var b = x.split('__');
            var total = parseFloat(b[0]);
            var rounding = parseFloat(b[1]);
            var paid = parseFloat(b[2]);
            if (number == 'number') {
                return formatDecimals(total+rounding-paid);
            }
            return currencyFormat(total+rounding-paid);
        }
        oTable = $('#POSData').dataTable({
            "aaSorting": [[1, "desc"], [2, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
            'sAjaxSource': '<?= admin_url('pos/getSales' . ($warehouse_id ? '/' . $warehouse_id : '')) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                var oSettings = oTable.fnSettings();
                nRow.id = aData[0];
                nRow.className = "receipt_link";
                return nRow;
            },
            "aoColumns": [{
                "bSortable": false,
                "mRender": checkbox
            }, {"mRender": fld}, null, null, null,null, {"mRender": currencyFormat}, {"mRender": currencyFormat}, {"mRender": balance}, {"mRender": row_status}, {"mRender": pay_status},{"mRender": factura},{"mRender": paid_by}, {"bSortable": false}],
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
                var gtotal = 0, paid = 0, bal = 0;
                for (var i = 0; i < aaData.length; i++) {
                    gtotal += parseFloat(aaData[aiDisplay[i]][6]);
                    paid += parseFloat(aaData[aiDisplay[i]][7]);
                    bal += parseFloat(balance(aaData[aiDisplay[i]][8], 'number'));
                }
                var nCells = nRow.getElementsByTagName('th');
                nCells[6].innerHTML = currencyFormat(parseFloat(gtotal));
                nCells[7].innerHTML = currencyFormat(parseFloat(paid));
                nCells[8].innerHTML = currencyFormat(parseFloat(bal));
            }
        }).fnSetFilteringDelay().dtFilter([
            {column_number: 1, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text", data: []},
            {column_number: 2, filter_default_label: "[<?=lang('reference_no');?>]", filter_type: "text", data: []},
            {column_number: 3, filter_default_label: "[<?=lang('biller');?>]", filter_type: "text", data: []},
            {column_number: 4, filter_default_label: "[<?=lang('customer');?>]", filter_type: "text"},
            {column_number: 5, filter_default_label: "[<?=lang('products');?>]", filter_type: "text"},
            {column_number: 9, filter_default_label: "[<?=lang('sale_status');?>]", filter_type: "text", data: []},
            {column_number: 10, filter_default_label: "[<?=lang('payment_status');?>]", filter_type: "text", data: []},
            {column_number: 11, filter_default_label: "[Factura]", filter_type: "text", data: []},
            {column_number: 12, filter_default_label: "[Metodo de pago]", filter_type: "text", data: []},
        ], "footer");

        $(document).on('click', '.duplicate_pos', function (e) {
            e.preventDefault();
            var link = $(this).attr('href');
            if (localStorage.getItem('positems')) {
                bootbox.confirm("<?= $this->lang->line('leave_alert') ?>", function (gotit) {
                    if (gotit == false) {
                        return true;
                    } else {
                        window.location.href = link;
                    }
                });
            } else {
                window.location.href = link;
            }
        });
        $(document).on('click', '.email_receipt', function (e) {
            e.preventDefault();
            var sid = $(this).attr('data-id');
            var ea = $(this).attr('data-email-address');
            var email = prompt("<?= lang('email_address'); ?>", ea);
            if (email != null) {
                $.ajax({
                    type: "post",
                    url: "<?= admin_url('pos/email_receipt') ?>/" + sid,
                    data: { <?= $this->security->get_csrf_token_name(); ?>: "<?= $this->security->get_csrf_hash(); ?>", email: email, id: sid },
                    dataType: "json",
                        success: function (data) {
                        bootbox.alert(data.msg);
                    },
                    error: function () {
                        bootbox.alert('<?= lang('ajax_request_failed'); ?>');
                        return false;
                    }
                });
            }
        });
    });

</script>

<?php if ($Owner || ($GP && $GP['bulk_actions'])) {
    echo admin_form_open('sales/sale_actions', 'id="action-form"');
} ?>
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i
                class="fa-fw fa fa-barcode"></i><?= lang('pos_sales') . ' (' . ($warehouse_id ? $warehouse->name : lang('all_warehouses')) . ')'; ?>
        </h2>

        <div class="box-icon">
            <ul class="btn-tasks">
                <li class="dropdown">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fa fa-tasks tip"  data-placement="left" title="<?= lang('actions') ?>"></i></a>
                    <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
                        <li><a href="<?= admin_url('pos') ?>"><i class="fa fa-plus-circle"></i> <?= lang('add_sale') ?></a></li>
                        <li><a href="#" id="excel" data-action="global_invoice"><i class="fa fa-file-excel-o"></i> <?= lang('global_bill') ?></a></li>
                        <li><a href="#" id="excel" data-action="export_excel"><i class="fa fa-file-excel-o"></i> <?= lang('export_to_excel') ?></a></li>
                        <li class="divider"></li>
                        <li><a href="#" class="bpo" title="<b><?= $this->lang->line('delete_sales') ?></b>" data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>" data-html="true" data-placement="left"><i class="fa fa-trash-o"></i> <?= lang('delete_sales') ?></a></li>
                    </ul>
                </li>
                <?php if (!empty($warehouses)) {
                    ?>
                    <li class="dropdown">
                        <a data-toggle="dropdown" class="dropdown-toggle" href="#"><i class="icon fa fa-building-o tip" data-placement="left" title="<?= lang('warehouses') ?>"></i></a>
                        <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
                            <li><a href="<?= admin_url('pos/sales') ?>"><i class="fa fa-building-o"></i> <?= lang('all_warehouses') ?></a></li>
                            <li class="divider"></li>
                            <?php
                            foreach ($warehouses as $warehouse) {
                                echo '<li><a href="' . admin_url('pos/sales/' . $warehouse->id) . '"><i class="fa fa-building"></i>' . $warehouse->name . '</a></li>';
                            } ?>
                        </ul>
                    </li>
                    <?php
                } ?>
                <li><i id="chek_global_bill" class="icon fa fa-file tip" data-placement="left" style="cursor: pointer;" title="<?= lang('global_bill') ?>" ></i></li>
            </ul>
        </div>
    </div>
<div class="box-content" id="global_bill" style="display: none;">
    <div class="row">
        <div class="col-md-10 col-sm-9">
            <div class="form-group">
                <?= lang('periodicity', 'periodicity'); ?>
                <?=form_dropdown("periodicity",$this->factura->getPeriodicity(),"",'class="form-control" id="periodicity" required="required"');?>
            </div>
        </div>
        <div class="col-md-10 col-sm-9">
            <div class="form-group">
                <?= lang('months', 'months'); ?>
                <?=form_dropdown("months",$this->factura->getMonths(),"",'class="form-control" id="months" required="required"');?>
            </div>
        </div>

       <div class="col-md-10 col-sm-9">
            <div class="form-group">
                <label for="year">Año</label>
                <?php
                $start_year = 1980;
                $end_year = 2080;
                $current_year = date("Y");
                $year_options = array();
                for ($i = $end_year; $i >= $start_year; $i--)
                    $year_options[$i] = $i;
                echo form_dropdown('year',$year_options,$current_year , 'class="form-control" id="year" required="required"'); ?>
            </div>
        </div>
       <div class="col-md-10 col-sm-9">
            <div class="form-group">
                <label for="payment_form">Forma de pago</label>
                <?=form_dropdown("payment_form",$this->factura->getFormaDePago(),"",'class="form-control" id="payment_form" required="required"');?>
            </div>
        </div>

        <div class="col-md-10 col-sm-9">
            <div class="form-group">
                <?=lang('biller', 'biller'); ?>
                <?php
                foreach ($billers as $biller) {
                    $btest           = ($biller->company && $biller->company != '-' ? $biller->company : $biller->name);
                    $bl[$biller->id] = $btest;
                    $posbillers[]    = ['logo' => $biller->logo, 'company' => $btest];
                    if ($biller->id == $pos_settings->default_biller) {
                        $posbiller = ['logo' => $biller->logo, 'company' => $btest];
                    }
                }
                echo form_dropdown('biller', $bl, ($_POST['biller'] ?? $pos_settings->default_biller), 'class="form-control" id="posbiller" required="required"'); ?>
            </div>
        </div>

        <div class="col-md-10 col-sm- ">
            <a class="btn btn-primary" href="#" id="excel" data-action="global_invoice">Facturar</a>
        </div>
    </div>
</div>
        <div class="box-content">
        <div class="row">
            <div class="col-lg-12">
                <p class="introtext"><?= lang('list_results'); ?></p>

                <div class="table-responsive">
                    <table id="POSData" class="table table-bordered table-hover table-striped">
                        <thead>
                        <tr>
                            <th style="min-width:30px; width: 30px; text-align: center;">
                                <input class="checkbox checkft" type="checkbox" name="check"/>
                            </th>
                            <th><?= lang('date'); ?></th>
                            <th><?= lang('reference_no'); ?></th>
                            <th><?= lang('biller'); ?></th>
                            <th><?= lang('customer'); ?></th>
                            <th><?= lang('products'); ?></th>
                            <th><?= lang('grand_total'); ?></th>
                            <th><?= lang('paid'); ?></th>
                            <th><?= lang('balance'); ?></th>
                            <th><?= lang('sale_status'); ?></th>
                            <th><?= lang('payment_status'); ?></th>
                            <th>Factura</th>
                            <th>Meotodo de pago</th>
                            <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="11" class="dataTables_empty"><?= lang('loading_data'); ?></td>
                        </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                        <tr class="active">
                            <th style="min-width:30px; width: 30px; text-align: center;">
                                <input class="checkbox checkft" type="checkbox" name="check"/>
                            </th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th><?= lang('grand_total'); ?></th>
                            <th><?= lang('paid'); ?></th>
                            <th><?= lang('balance'); ?></th>
                            <th class="defaul-color"></th>
                            <th class="defaul-color"></th>
                            <th class="defaul-color"></th>
                            <th class="defaul-color"></th>
                            <th style="width:80px; text-align:center;"><?= lang('actions'); ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php if ($Owner || ($GP && $GP['bulk_actions'])) {
    ?>
    <div style="display: none;">
        <input type="hidden" name="form_action" value="" id="form_action"/>
        <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
    </div>
    <?= form_close() ?>
    <?php
} ?>
