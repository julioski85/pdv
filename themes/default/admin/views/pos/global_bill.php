<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
            </button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('global_bill'); ?></h4>
        </div>
        <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('pos/global_bill/', $attrib); ?>
        <div class="modal-body">
            <p><?= lang('global_bill_info'); ?></p>
            <div class="row">
                           <div class="col-md-10 col-sm-9">
                            <div class="form-group">
                                <?= lang('periodicity', 'periodicity'); ?>
                                <?=form_dropdown("periodicity",$this->factura->getPeriodicity(),"",'class="form-control" id="periodicity" required="required"');?>
                            </div>
                         
                </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <?= lang('date', 'date'); ?>
                             <input type="date" id="date" name="date">
                    </input>
                   <div class="col-sm-6" style="display: none;" id="date-finish-container">
                        <div class="form-group">
                            <?= lang('date_finish', 'date'); ?>
                            <?= form_input('date-finish',  '', 'class="form-control datetime" id="date-finish"'); ?>
                        </div>
                    </div>
            </div>

        </div>
        <div class="modal-footer">
            <?php echo form_submit('facturar','facturar', 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close();?>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
    $.fn.datetimepicker.dates['sma'] = <?=$dp_lang?>;
</script>
<script type="text/javascript" src="<?= $assets ?>pos/js/parse-track-data.js"></script>
<?= $modal_js ?>
<script type="text/javascript" charset="UTF-8">
    $(document).ready(function () {
            });
</script>
