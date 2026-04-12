<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="modal-dialog">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-2x">&times;</i>
            </button>
            <h4 class="modal-title" id="myModalLabel"><?php echo lang('add_payment'); ?></h4>
        </div>
        <?php $attrib = ['data-toggle' => 'validator', 'role' => 'form'];
        echo admin_form_open_multipart('pos/facturar/' . $inv->id, $attrib); ?>
        <div class="modal-body">
            <p><?= lang('enter_info'); ?></p>

            <div class="row">
                <?php if ($Owner || $Admin) {
            ?>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <?= lang('date', 'date'); ?>
                            <?= form_input('date', (isset($_POST['date']) ? $_POST['date'] : ''), 'class="form-control datetime" id="date" required="required"'); ?>
                        </div>
                    </div>
                <?php
        } ?>
                <div class="col-sm-6">
                    <div class="form-group">
                        <?= lang('reference_no', 'reference_no'); ?>
                        <?= form_input('reference_no', (isset($_POST['reference_no']) ? $_POST['reference_no'] : $payment_ref), 'class="form-control tip" id="reference_no" required="required"'); ?>
                    </div>
                </div>

                <div class="col-md-10 col-sm-9">
                            <div class="form-group">
                                <label for="formas_de_pago">Forma De Pago</label>
                                <?=form_dropdown("formasdepago",$this->factura->getFormaDePago(),"",'class="form-control" id="formasdepago" required="required"');?>
                            </div>
                            <div class="form-group">
                                <label for="metodo_de_pago">Metodo de pago</label>
                                <?=form_dropdown("metododepago",$this->factura->getMetodoDePago(),"",'class="form-control" id="metododepago" required="required"');?>
                            </div>
                            <div class="form-group">
                                <label for="uso_de_cfdi">Uso de CFDI</label>
                                <?=form_dropdown("usodecfdi",$this->factura->getUsoDeCfdi(),"",'class="form-control" id="usodecfdi" required="required"');?>
                            </div>
                </div>

                <input type="hidden" value="<?php echo $inv->id; ?>" name="sale_id"/>
            </div>

        </div>
        <div class="modal-footer">
            <?php echo form_submit('facturar','facturar', 'class="btn btn-primary"'); ?>
        </div>
    </div>
    <?php echo form_close(); ?>
</div>
<script type="text/javascript" src="<?= $assets ?>js/custom.js"></script>
<script type="text/javascript" charset="UTF-8">
    $.fn.datetimepicker.dates['sma'] = <?=$dp_lang?>;
</script>
<script type="text/javascript" src="<?= $assets ?>pos/js/parse-track-data.js"></script>
<?= $modal_js ?>
<script type="text/javascript" charset="UTF-8">
    $(document).ready(function () {
        $(document).on('change', '#gift_card_no', function () {
            var cn = $(this).val() ? $(this).val() : '';
            if (cn != '') {
                $.ajax({
                    type: "get", async: false,
                    url: site.base_url + "sales/validate_gift_card/" + cn,
                    dataType: "json",
                    success: function (data) {
                        if (data === false) {
                            $('#gift_card_no').parent('.form-group').addClass('has-error');
                            bootbox.alert('<?=lang('incorrect_gift_card')?>');
                        } else if (data.customer_id !== null && data.customer_id != <?=$inv->customer_id?>) {
                            $('#gift_card_no').parent('.form-group').addClass('has-error');
                            bootbox.alert('<?=lang('gift_card_not_for_customer')?>');

                        } else {
                            var due = <?=$inv->grand_total - $inv->paid?>;
                            if (due > data.balance) {
                                $('#amount_1').val(formatDecimal(data.balance));
                            }
                            $('#gc_details').html('<small>Card No: <span style="max-width:60%;float:right;">' + data.card_no + '</span><br>Value: <span style="max-width:60%;float:right;">' + currencyFormat(data.value) + '</span><br>Balance: <span style="max-width:60%;float:right;">' + currencyFormat(data.balance) + '</span></small>');
                            $('#gift_card_no').parent('.form-group').removeClass('has-error');
                        }
                    }
                });
            }
        });
        $(document).on('change', '.paid_by', function () {
            var p_val = $(this).val();
            localStorage.setItem('paid_by', p_val);
            $('#rpaidby').val(p_val);
            if (p_val == 'cash') {
                $('.pcheque_1').hide();
                $('.pcc_1').hide();
                $('.pcash_1').show();
                $('#amount_1').focus();
            } else if (p_val == 'CC' || p_val == 'stripe' || p_val == 'ppp' || p_val == 'authorize') {
                if (p_val == 'CC') {
                    $('#ppp-stripe').hide();
                } else {
                    $('#ppp-stripe').show();
                }
                $('.pcheque_1').hide();
                $('.pcash_1').hide();
                $('.pcc_1').show();
                $('#swipe_1').focus();
            } else if (p_val == 'Cheque') {
                $('.pcc_1').hide();
                $('.pcash_1').hide();
                $('.pcheque_1').show();
                $('#cheque_no_1').focus();
            } else {
                $('.pcheque_1').hide();
                $('.pcc_1').hide();
                $('.pcash_1').hide();
            }
            if (p_val == 'gift_card') {
                $('.gc').show();
                $('#gift_card_no').focus();
            } else {
                $('.gc').hide();
            }
        });
        $('#pcc_no_1').change(function (e) {
            var pcc_no = $(this).val();
            localStorage.setItem('pcc_no_1', pcc_no);
            var CardType = null;
            var ccn1 = pcc_no.charAt(0);
            if (ccn1 == 4)
                CardType = 'Visa';
            else if (ccn1 == 5)
                CardType = 'MasterCard';
            else if (ccn1 == 3)
                CardType = 'Amex';
            else if (ccn1 == 6)
                CardType = 'Discover';
            else
                CardType = 'Visa';

            $('#pcc_type_1').select2("val", CardType);
        });

        $('.swipe').keypress(function (e) {
            var self = $(this);
            var id = 1;
            var TrackData = $(this).val();
            if (e.keyCode == 13) {
                e.preventDefault();

                var p = new SwipeParserObj(TrackData);

                if (p.hasTrack1) {
                    var CardType = null;
                    var ccn1 = p.account.charAt(0);
                    if (ccn1 == 4)
                        CardType = 'Visa';
                    else if (ccn1 == 5)
                        CardType = 'MasterCard';
                    else if (ccn1 == 3)
                        CardType = 'Amex';
                    else if (ccn1 == 6)
                        CardType = 'Discover';
                    else
                        CardType = 'Visa';

                    $('#pcc_no_' + id).val(p.account);
                    $('#pcc_holder_' + id).val(p.account_name);
                    $('#pcc_month_' + id).val(p.exp_month);
                    $('#pcc_year_' + id).val(p.exp_year);
                    $('#pcc_cvv2_' + id).val('');
                    $('#pcc_type_' + id).val(CardType);
                    self.val('');
                }
                else {
                    $('#pcc_no_' + id).val('');
                    $('#pcc_holder_' + id).val('');
                    $('#pcc_month_' + id).val('');
                    $('#pcc_year_' + id).val('');
                    $('#pcc_cvv2_' + id).val('');
                    $('#pcc_type_' + id).val('');
                }

                $('#pcc_cvv2_' + id).focus();
            }

        }).blur(function (e) {
            $(this).val('');
        }).focus(function (e) {
            $(this).val('');
        });
        $("#date").datetimepicker({
            format: site.dateFormats.js_ldate,
            fontAwesome: true,
            language: 'sma',
            weekStart: 1,
            todayBtn: 1,
            autoclose: 1,
            todayHighlight: 1,
            startView: 2,
            forceParse: 0
        }).datetimepicker('update', new Date());
    });
</script>
