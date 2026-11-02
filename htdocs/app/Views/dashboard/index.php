<?php if (!function_exists('formatDist')) {
    function formatDist($meters) {
        if ($meters < 1000) return number_format($meters, 0, ',', ' ') . ' m';
        return number_format($meters / 1000, 2, ',', ' ') . ' km';
    }
}
if (!function_exists('formatTime')) {
    function formatTime($seconds) {
        $h = intdiv((int)$seconds, 3600);
        $m = intdiv((int)$seconds % 3600, 60);
        return $h > 0 ? sprintf('%dh %02d', $h, $m) : sprintf('%d min', $m);
    }
}
?>

<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>

<?php if (!empty($userSettings['strava_access_token'])): ?>
<div class="card mb-4 border-0 bg-light">
    <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center">
        <form method="post" action="<?= site_url('strava/sync') ?>" class="d-flex flex-wrap gap-2 align-items-end">
            <?= csrf_field() ?>
            <div>
                <label class="form-label mb-0 fw-semibold condensed-text text-muted">Du :</label>
                <input type="date" name="start_date" class="form-control form-control-sm" required
                    max="<?= date('Y-m-d') ?>">
            </div>
            <div>
                <label class="form-label mb-0 fw-semibold condensed-text text-muted">Au :</label>
                <input type="date" name="end_date" class="form-control form-control-sm" required
                    value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div><button class="btn btn-sm bg-strava" type="submit">Importer</button></div>
        </form>
    </div>
</div>
<?php else: ?>
<div class="alert alert-info border-0 shadow-sm">
    <strong>Strava n'est pas connecté.</strong> Cliquez sur "Connecter Strava" en haut à droite pour lier votre compte.
</div>
<?php endif; ?>

<?php if (empty($activitiesBySport) && empty($recentWorkouts)): ?>
<div class="alert alert-info border-0 shadow-sm">
    <strong>Aucune activité récente.</strong> Connectez Strava et importez vos activités pour voir vos statistiques ici.
</div>
<?php else: ?>
<?php if (!empty($activitiesBySport)): ?>
<div class="mb-5">
    <h2 class="h4 border-bottom pb-2 text-strava fw-bold">🚀 Cardio & Endurance</h2>
    <div class="row g-4">
        <div class="col-12 col-xl-6">
            <div class="row g-2">
                <?php foreach ($activitiesBySport as $sport => $acts): ?>
                <div class="col-12 col-md-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div
                            class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-2">
                            <span class="condensed-text"><?= esc($sport) ?></span>
                            <span class="badge bg-light text-dark border condensed-text"><?= count($acts) ?> réc.</span>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($acts, 0, 5) as $activity): ?>
                            <div class="list-group-item">
                                <a href="https://www.strava.com/activities/<?= esc($activity['strava_activity_id']) ?>"
                                    target="_blank"
                                    class="fw-semibold text-truncate condensed-text text-dark text-decoration-none d-block">
                                    <?= esc($activity['name']) ?>
                                </a>
                                <div class="d-flex justify-content-between align-items-center condensed-text mt-1">
                                    <span
                                        class="text-muted"><?= date('d/m/y', strtotime($activity['started_at'])) ?></span>
                                    <span
                                        class="fw-bold text-dark"><?= formatDist($activity['distance_meters']) ?></span>
                                </div>
                            </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <div class="col-12 col-xl-6">
            <div class="card h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h6 fw-bold mb-0 text-muted">Progression des Activités</h3>
                        <select id="sportFilter"
                            class="form-select form-select-sm w-auto fw-semibold text-strava border-strava">
                            <option value="all">Tous les sports</option>
                            <?php foreach (array_keys($activitiesBySport) as $sName): ?>
                            <option value="<?= esc($sName) ?>"><?= esc($sName) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="stravaStyleChart"></canvas>
                    </div>

                    <div class="card mt-4 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <h3 class="h6 fw-bold mb-0 text-muted">🔥 Série d'activités</h3>
                            </div>
                            <div class="row text-center">
                                <div class="col-6 border-end">
                                    <span
                                        class="d-block fs-3 fw-bold text-dark"><?= esc($streakStats['streak_weeks'] ?? 0) ?>
                                        Semaines</span>
                                    <small class="text-muted">Votre série</small>
                                </div>
                                <div class="col-6">
                                    <span
                                        class="d-block fs-3 fw-bold text-dark"><?= esc($streakStats['total_in_streak'] ?? 0) ?></span>
                                    <small class="text-muted">Activités en série</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($recentWorkouts)): ?>
<div class="mb-5">
    <div style="display: flex; justify-content: space-between; align-items: flex-start;">
        <h2 class="h4 pb-2 mb-4 section-title">💪 Renforcement & Musculation</h2>
        <a class="btn btn-outline-success btn-sm" href="<?= site_url('strength') ?>">Progression Muscu</a>
    </div>
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-3 text-muted">Évolution du Tonnage</h3>
                    <?php if (empty($tonnageData)): ?>
                    <p class="text-muted small">Aucune donnée de tonnage.</p>
                    <?php else: ?>
                    <div style="height: 250px;"><canvas id="tonnageChart"></canvas></div>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="h6 fw-bold mb-3 text-muted">🎯 Heatmap Musculaire (15j)</h3>
                    <?php if (empty($muscleHeatmap)): ?>
                    <p class="text-muted small">Aucune donnée musculaire récente.</p>
                    <?php else: ?>
                    <div style="height: 250px; display:flex; justify-content:center;"><canvas
                            id="muscleRadarChart"></canvas></div>
                    <?php endif ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-xl-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="p-3 pb-2 border-bottom">
                        <h3 class="h6 fw-bold mb-0 text-muted">Séances de force récentes</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentWorkouts as $workout): ?>
                        <?php 
                                    $stravaId = $workout['strava_activity_id'];
                                    $workoutId = $workout['local_workout_id'] ?? $stravaId;
                                ?>
                        <div class="list-group-item py-2">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <a href="<?= site_url('workout/' . $workoutId) ?>"
                                    class="fw-semibold workout-link text-truncate condensed-text pe-2"
                                    style="max-width: 80%;">
                                    <?= esc($workout['name']) ?>
                                </a>
                                <span
                                    class="condensed-text fw-bold text-dark text-nowrap"><?= formatTime($workout['duration_seconds']) ?></span>
                            </div>
                            <div class="d-flex align-items-center gap-2 condensed-text">
                                <span
                                    class="text-muted pe-2 border-end"><?= date('d/m', strtotime($workout['started_at'])) ?></span>
                                <?php if (isset($workout['local_volume'])): ?>
                                <span class="badge bg-success text-white rounded-pill" style="font-size:0.75rem;">
                                    <?= number_format($workout['local_volume'], 0, ',', ' ') ?> kg
                                </span>
                                <?php else: ?>
                                <span class="badge bg-light text-secondary border rounded-pill"
                                    style="font-size:0.75rem;">0 kg</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif ?>
<?php endif; ?>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    Chart.defaults.maintainAspectRatio = false;

    const ctxStrava = document.getElementById('stravaStyleChart')?.getContext('2d');
    if (ctxStrava) {
        DashboardCharts.initStrava(
            ctxStrava,
            <?= json_encode($rawActivitiesForChart ?? []) ?>,
            <?= json_encode(array_column($monthlyDistance ?? [], 'month_key')) ?>,
            <?= json_encode(array_map(static fn($row) => round(((float) $row['distance_meters']) / 1000, 2), $monthlyDistance ?? [])) ?>
        );
    }

    <?php if (!empty($tonnageData)): ?>
    const ctxTonnage = document.getElementById('tonnageChart')?.getContext('2d');
    if (ctxTonnage) {
        DashboardCharts.initTonnage(
            ctxTonnage,
            <?= json_encode(array_column($tonnageData, 'workout_date')) ?>,
            <?= json_encode(array_column($tonnageData, 'total_volume_kg')) ?>
        );
    }
    <?php endif; ?>

    <?php if (!empty($muscleHeatmap)): ?>
    const ctxRadar = document.getElementById('muscleRadarChart')?.getContext('2d');
    if (ctxRadar) {
        DashboardCharts.initRadar(
            ctxRadar,
            <?= json_encode(array_column($muscleHeatmap, 'muscle_group')) ?>,
            <?= json_encode(array_column($muscleHeatmap, 'volume')) ?>
        );
    }
    <?php endif; ?>
});
</script>
<?= $this->endSection() ?>