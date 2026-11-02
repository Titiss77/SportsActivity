<?php 
$this->setData([
    'title' => 'Historique des Activités',
    'backUrl' => site_url('/'),
    'backText' => 'Retour au tableau de bord',
    'hideNavLinks' => true
]);
?>

<?= $this->extend('layout/main') ?>

<?= $this->section('styles') ?>
<link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
<style>
th,
td {
    padding: 0.75rem;
    text-align: center;
    border-bottom: 1px solid #dee2e6;
}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Activités</h1>
</div>

<div style="display: flex; justify-content: center;">
    <table class="table table-striped table-hover shadow-sm" style="width: 90%; max-width: 1200px; margin-top: 20px;">
        <thead>
            <tr>
                <th>Sport</th>
                <th>Activités Totales</th>
                <th>Distance (km)</th>
                <th>Durée</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($activities)): ?>
            <tr>
                <td colspan="4" class="text-center py-5 text-secondary">Aucune activité enregistrée.</td>
            </tr>
            <?php else: ?>
            <tr style="font-weight: bold; background-color: #f8f9fa;">
                <?php $countAll = (int) ($allGlobal['total_activities'] ?? 0); ?>
                <?php $distanceAll = (float) ($allGlobal['total_distance'] ?? 0); ?>
                <?php $durationAll = $allGlobal['total_duration'] ?? '0h 0min 0s'; ?>
                <td>Toutes les activités</td>
                <td><?= $countAll ?></td>
                <td><?= $distanceAll ?></td>
                <td><?= esc($durationAll) ?></td>
            </tr>
            <?php foreach ($globalStats as $global): ?>
            <?php $count = (int) ($global['total_activities'] ?? 0); ?>
            <?php $distance = (float) ($global['total_distance'] ?? 0); ?>
            <?php $duration = $global['total_duration'] ?? '0h 0min 0s'; ?>
            <tr>
                <td><?= esc($global['sport'] ?: 'Autre') ?></td>
                <td><?= $count ?></td>
                <td><?= $distance ?></td>
                <td><?= esc($duration) ?></td>
            </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<div class="container fade-in mt-4">
    <div class="card shadow-card">
        <div class="card-body" style="padding: 1.5rem;">
            <table id="myAdminTable" class="dataTable-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activité</th>
                        <th>Sport</th>
                        <th>Distance</th>
                        <th>Durée</th>
                        <th>FC moy.</th>
                        <th>Dénivelé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">Aucune activité enregistrée.</td>
                    </tr>
                    <?php else: foreach ($activities as $activity): ?>
                    <?php $seconds = (int) ($activity['duration_seconds'] ?? 0); ?>
                    <tr>
                        <td><?= esc($activity['started_at'] ?: 'N/A') ?></td>
                        <td>
                            <a href="https://www.strava.com/activities/<?= esc($activity['strava_activity_id']) ?>"
                                target="_blank"
                                class="fw-semibold text-truncate condensed-text text-dark text-decoration-none d-block">
                                <?= esc($activity['name']) ?>
                            </a>
                        </td>
                        <td><?= esc($activity['sport'] ?: 'N/A') ?></td>
                        <td><?= number_format(((float) ($activity['distance_meters'] ?? 0)) / 1000, 2, ',', ' ') ?> km
                        </td>
                        <td><?= sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) ?>
                        </td>
                        <td><?= !empty($activity['avg_heart_rate']) ? (int) $activity['avg_heart_rate'] . ' bpm' : 'N/A' ?>
                        </td>
                        <td><?= isset($activity['elevation_gain_m']) ? number_format((float) $activity['elevation_gain_m'], 0, ',', ' ') . ' m' : 'N/A' ?>
                        </td>
                    </tr>
                    <?php endforeach; endif ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- La logique d'initialisation de datatables est prise en charge de base par script.js -->
<script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
<?= $this->endSection() ?>