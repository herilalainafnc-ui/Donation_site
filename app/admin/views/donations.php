<?php
/** Dons en ligne : paiements et dons réguliers. */
$fmtTotals = function (array $sums): string {
    $sums = array_filter(array_map('floatval', $sums));
    if (!$sums) {
        return '0';
    }
    return implode(' + ', array_map(fn ($c, $v) => money($v, currency_symbol($c)), array_keys($sums), $sums));
};
$providers = payment_providers();
$statusPill = ['paid' => 'pill-ok', 'active' => 'pill-ok', 'pending' => 'pill-ongoing', 'failed' => 'pill-danger', 'cancelled' => '', 'expired' => ''];
?>
<?php if (!online_payments_enabled()): ?>
    <div class="panel notice">
        <strong>Le paiement en ligne n'est pas encore actif sur le site.</strong>
        Il s'active dès que les clés d'un prestataire (Stripe, MVola ou Orange Money) sont ajoutées dans <code>app/config.php</code> sur le serveur
        et que ce moyen est coché dans <a href="<?= admin_url('settings', ['tab' => 'payments']) ?>">Paramètres du site → Paiement en ligne</a>.
    </div>
<?php endif; ?>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon"><?= icon('heart') ?></span><div><strong><?= e($fmtTotals($totalMonth)) ?></strong><small>Reçus ce mois-ci</small></div></div>
    <div class="kpi"><span class="kpi-icon"><?= icon('chart') ?></span><div><strong><?= e($fmtTotals($totalAll)) ?></strong><small>Total reçu en ligne</small></div></div>
    <div class="kpi"><span class="kpi-icon"><?= icon('calendar') ?></span><div><strong><?= $activePlans ?></strong><small>Dons réguliers actifs</small></div></div>
    <div class="kpi"><span class="kpi-icon"><?= icon('target') ?></span><div><strong><?= e($fmtTotals($monthlyPlanned)) ?></strong><small>Équivalent mensuel des dons réguliers</small></div></div>
</div>

<nav class="tabs">
    <a href="<?= admin_url('donations') ?>" class="<?= $donView === 'payments' ? 'active' : '' ?>">Paiements</a>
    <a href="<?= admin_url('donations', ['view' => 'plans']) ?>" class="<?= $donView === 'plans' ? 'active' : '' ?>">Dons réguliers</a>
</nav>

<form method="get" action="<?= url('admin/') ?>" class="toolbar search">
    <input type="hidden" name="p" value="donations">
    <?php if ($donView === 'plans'): ?><input type="hidden" name="view" value="plans"><?php endif; ?>
    <select name="s" onchange="this.form.submit()">
        <option value="">Tous les statuts</option>
        <?php foreach (($donView === 'plans' ? ['active', 'pending', 'cancelled', 'expired'] : ['paid', 'pending', 'failed', 'cancelled', 'expired']) as $s): ?>
            <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($donView === 'plans' ? plan_status_label($s) : donation_status_label($s)) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="m" onchange="this.form.submit()">
        <option value="">Tous les moyens</option>
        <?php foreach ($providers as $id => $p): ?><option value="<?= e($id) ?>" <?= $provider === $id ? 'selected' : '' ?>><?= e($p->label()) ?></option><?php endforeach; ?>
    </select>
</form>

<div class="panel table-panel">
    <?php if (!$rows): ?>
        <div class="empty"><?= $donView === 'plans' ? 'Aucun don régulier pour le moment.' : 'Aucun paiement en ligne pour le moment.' ?></div>
    <?php elseif ($donView === 'payments'): ?>
        <table class="table">
            <thead><tr><th>Date</th><th>Référence</th><th>Donateur</th><th>Projet</th><th>Montant</th><th>Moyen</th><th>Fréquence</th><th>Statut</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $d): ?>
                <tr>
                    <td data-label="Date"><?= e(date('d/m/Y H:i', strtotime($d['paid_at'] ?: $d['created_at']))) ?></td>
                    <td data-label="Référence"><code><?= e($d['reference']) ?></code></td>
                    <td data-label="Donateur"><strong><?= e($d['donor_name']) ?></strong><br><small><?= e($d['email']) ?><?= $d['phone'] ? ' · ' . e($d['phone']) : '' ?></small></td>
                    <td data-label="Projet"><?= e($d['project_title'] ?? 'Besoin le plus grand') ?></td>
                    <td data-label="Montant"><strong><?= e(money($d['amount'], currency_symbol($d['currency']))) ?></strong></td>
                    <td data-label="Moyen"><?= e(($providers[$d['provider']] ?? null)?->label() ?? $d['provider']) ?></td>
                    <td data-label="Fréquence"><?= e(frequency_label($d['frequency'])) ?></td>
                    <td data-label="Statut"><span class="pill <?= $statusPill[$d['status']] ?? '' ?>"><?= e(donation_status_label($d['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <table class="table">
            <thead><tr><th>Créé le</th><th>Référence</th><th>Donateur</th><th>Projet</th><th>Montant</th><th>Moyen</th><th>Prochaine échéance</th><th>Paiements</th><th>Statut</th><th class="actions-col">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $pl): $prov = $providers[$pl['provider']] ?? null; ?>
                <tr>
                    <td data-label="Créé le"><?= e(date('d/m/Y', strtotime($pl['created_at']))) ?></td>
                    <td data-label="Référence"><code><?= e($pl['reference']) ?></code></td>
                    <td data-label="Donateur"><strong><?= e($pl['donor_name']) ?></strong><br><small><?= e($pl['email']) ?></small></td>
                    <td data-label="Projet"><?= e($pl['project_title'] ?? 'Besoin le plus grand') ?></td>
                    <td data-label="Montant"><strong><?= e(money($pl['amount'], currency_symbol($pl['currency']))) ?></strong><br><small><?= e(frequency_label($pl['frequency'])) ?></small></td>
                    <td data-label="Moyen"><?= e($prov?->label() ?? $pl['provider']) ?><br><small><?= $prov && $prov->supportsAutoRecurring() ? 'prélèvement automatique' : 'rappel par e-mail' ?></small></td>
                    <td data-label="Prochaine échéance"><?= $pl['status'] === 'active' && $pl['next_due_date'] ? e(format_date($pl['next_due_date'])) : '—' ?></td>
                    <td data-label="Paiements"><?= (int) $pl['payments_count'] ?></td>
                    <td data-label="Statut"><span class="pill <?= $statusPill[$pl['status']] ?? '' ?>"><?= e(plan_status_label($pl['status'])) ?></span></td>
                    <td class="actions-col">
                        <?php if (in_array($pl['status'], ['active', 'pending'], true)): ?>
                            <form method="post" action="<?= admin_url('donations', ['view' => 'plans']) ?>" data-confirm="Arrêter ce don régulier ? Aucun nouveau paiement ne sera demandé au donateur.">
                                <?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $pl['id'] ?>">
                                <button class="btn btn-danger btn-sm" name="action" value="cancel">Arrêter</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
