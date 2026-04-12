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
                <p class="introtext">Genera un ZIP por día con PDFs combinados por almacén y método de pago (solo ventas POS con factura).</p>

                <?php echo admin_form_open('sales/generar_ventas_facturadas_pdf_dia'); ?>
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
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="day">Día</label>
                            <select name="day" id="day" class="form-control" required>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d; ?>"<?= (int) $selected_day === $d ? ' selected' : ''; ?>>
                                        <?= sprintf('%02d', $d); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-download"></i> Generar ZIP diario
                    </button>
                    <a href="<?= admin_url('sales'); ?>" class="btn btn-default">Volver</a>
                </div>
                <?= form_close(); ?>

                <hr>
                <p class="text-muted">Exportación mensual (versión anterior):</p>
                <?php echo admin_form_open('sales/generar_ventas_facturadas_pdf', ['id' => 'facturadas-monthly-form']); ?>
                <input type="hidden" name="year" id="legacy-year" value="<?= (int) $selected_year; ?>">
                <input type="hidden" name="month" id="legacy-month" value="<?= (int) $selected_month; ?>">
                <button type="submit" class="btn btn-default">
                    <i class="fa fa-clock-o"></i> Ejecutar mensual (legacy)
                </button>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var monthlyForm = document.getElementById('facturadas-monthly-form');
        if (!monthlyForm) {
            return;
        }
        monthlyForm.addEventListener('submit', function () {
            var yearField = document.getElementById('year');
            var monthField = document.getElementById('month');
            var legacyYear = document.getElementById('legacy-year');
            var legacyMonth = document.getElementById('legacy-month');
            if (yearField && legacyYear) {
                legacyYear.value = yearField.value;
            }
            if (monthField && legacyMonth) {
                legacyMonth.value = monthField.value;
            }
        });
    })();
</script>
