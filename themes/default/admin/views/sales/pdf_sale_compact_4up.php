<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="facturadas-compact-card">
    <div class="facturadas-compact-head">
        <div class="facturadas-compact-title"><?= lang('sale'); ?></div>
        <div class="facturadas-compact-ref"><?= lang('ref'); ?>: <?= htmlspecialchars((string) $inv->reference_no, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>

    <table class="facturadas-compact-grid">
        <tr>
            <td class="facturadas-compact-label"><?= lang('customer'); ?>:</td>
            <td><?= htmlspecialchars((string) (($customer && $customer->company && $customer->company !== '-') ? $customer->company : ($customer->name ?? '-')), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="facturadas-compact-label"><?= lang('date'); ?>:</td>
            <td><?= $this->sma->hrld((string) $inv->date); ?></td>
        </tr>
        <tr>
            <td class="facturadas-compact-label"><?= lang('warehouse'); ?>:</td>
            <td><?= htmlspecialchars((string) ($warehouse->name ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
        <tr>
            <td class="facturadas-compact-label"><?= lang('payment_method'); ?>:</td>
            <td><?= htmlspecialchars((string) ($payment_method ?: '-'), ENT_QUOTES, 'UTF-8'); ?></td>
        </tr>
    </table>

    <div class="facturadas-compact-products">
        <div class="facturadas-compact-products-title"><?= lang('products'); ?></div>
        <ul>
            <?php foreach ($product_lines as $line): ?>
                <li>
                    <?= htmlspecialchars((string) $line['name'], ENT_QUOTES, 'UTF-8'); ?>
                    <?php if (!empty($line['quantity'])): ?>
                        (<?= $this->sma->formatDecimal((float) $line['quantity']); ?>)
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if ($extra_products > 0): ?>
            <div class="facturadas-compact-extra">+ <?= (int) $extra_products; ?> productos más</div>
        <?php endif; ?>
    </div>

    <div class="facturadas-compact-footer">
        <div class="facturadas-compact-total"><?= lang('total'); ?>: <?= $compact_total; ?></div>
        <?php if (!empty($show_qr) && !empty($inv->hash)): ?>
            <div class="facturadas-compact-qr">
                <?= $this->sma->qrcode('link', urlencode(site_url('view/sale/' . $inv->hash)), 2); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
