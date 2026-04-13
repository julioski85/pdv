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
                <p class="introtext">Genera un ZIP por día con PDFs combinados por almacén y método de pago (solo ventas POS con factura; métodos: debit_card, CC y other).</p>

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
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="days">Días</label>
                            <select name="days[]" id="days" class="form-control" multiple size="8" required>
                                <?php for ($d = 1; $d <= 31; $d++): ?>
                                    <option value="<?= $d; ?>"<?= in_array($d, $selected_days, true) ? ' selected' : ''; ?>>
                                        <?= sprintf('%02d', $d); ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                            <small class="text-muted">Puedes seleccionar varios días con Ctrl/Cmd + clic.</small>
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
                <p class="text-muted">Exportación de efectivo en un solo PDF (paid_by = cash):</p>
                <?php echo admin_form_open('sales/generar_ventas_facturadas_efectivo_dia'); ?>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="cash-year">Año</label>
                            <select name="cash_year" id="cash-year" class="form-control" required>
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
                            <label for="cash-month">Mes</label>
                            <select name="cash_month" id="cash-month" class="form-control" required>
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
                            <label for="cash-day">Día</label>
                            <select name="cash_day" id="cash-day" class="form-control" required>
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
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-money"></i> Descargar efectivo del día
                    </button>
                </div>
                <?= form_close(); ?>

                <hr>
                <p class="text-muted">Exportación por referencias de venta en un solo PDF:</p>
                <?php echo admin_form_open('sales/generar_ventas_facturadas_pdf_referencias'); ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label for="sale-references">Referencias de venta</label>
                            <textarea
                                name="sale_references"
                                id="sale-references"
                                class="form-control"
                                rows="8"
                                placeholder="Pega aquí referencias desde Excel (una por línea o separadas por coma/;)"
                                required
                            ></textarea>
                            <small class="text-muted">Acepta referencias en líneas separadas, tabuladas o separadas por coma/;.</small>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-info">
                        <i class="fa fa-files-o"></i> Descargar PDF por referencias
                    </button>
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
