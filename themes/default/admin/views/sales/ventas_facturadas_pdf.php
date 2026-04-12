<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>

<div class="box">
    <div class="box-header">
        <h2 class="blue">
            <i class="fa-fw fa fa-file-pdf-o"></i>Ventas facturadas PDF
        </h2>
    </div>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">
                <p class="introtext">Genera un ZIP mensual con PDFs combinados por día, almacén y método de pago (solo ventas con factura).</p>

                <?php echo admin_form_open('sales/generar_ventas_facturadas_pdf'); ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="year">Año</label>
                            <select name="year" id="year" class="form-control" required>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?= $year; ?>"<?= (int) $selected_year === (int) $year ? ' selected' : ''; ?>>
                                        <?= $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="month">Mes</label>
                            <select name="month" id="month" class="form-control" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                    <option value="<?= $m; ?>"<?= (int) $selected_month === $m ? ' selected' : ''; ?>>
                                        <?= sprintf('%02d', $m); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-download"></i> Generar ZIP
                    </button>
                    <a href="<?= admin_url('sales'); ?>" class="btn btn-default">Volver</a>
                </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>
