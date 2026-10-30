<?php
if (!function_exists('formatDist')) {
    function formatDist($meters) {
        if ($meters < 1000) {
            return number_format($meters, 0, ',', ' ') . ' m';
        }
        return number_format($meters / 1000, 2, ',', ' ') . ' km';
    }
}
if (!function_exists('formatTime')) {
    function formatTime($seconds) {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        return $h > 0 ? sprintf('%dh %02d', $h, $m) : sprintf('%d min', $m);
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f4f6f8; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }
        .card { border-radius: 8px; border: 1px solid #e9ecef; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { background-color: #fff; border-bottom: 1px solid #e9ecef; }
        .text-strava { color: #fc4c02; }
        .bg-strava { background-color: #fc4c02; color: white; border-color: #fc4c02; }
        .bg-strava:hover { background-color: #e34301; color: white; }
        .list-group-item { border-color: #f1f3f5; }
        .section-title { color: #198754; font-weight: 700; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= site_url('/') ?>">Strava & Musculation</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('activites') ?>">Historique Complet</a>
            <?php if (!session()->has('strava_access_token')): ?>
                <a class="btn bg-strava btn-sm" href="<?= site_url('strava/connect') ?>">Connecter Strava</a>
            <?php else: ?>
                <span class="btn btn-outline-secondary btn-sm disabled">Strava Connecté</span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-warning alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif ?>

    <?php if (session()->has('strava_access_token')): ?>
    <div class="card mb-4">
        <div class="card-body py-3">
            <form method="post" action="<?= site_url('strava/sync') ?>" class="d-flex flex-wrap gap-3 align-items-end">
                <?= csrf_field() ?>
                <div>
                    <label for="start_date" class="form-label mb-1 fw-semibold small text-muted">Synchroniser du :</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" required max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="end_date" class="form-label mb-1 fw-semibold small text-muted">Au :</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <button class="btn btn-sm bg-strava" type="submit">Importer</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECTION 1 : CARDIO & ENDURANCE (STRAVA) -->
    <div class="mb-5">
        <h2 class="h4 border-bottom pb-2 text-strava fw-bold">🏃‍♂️ Cardio & Endurance</h2>
        
        <div class="row g-3 mb-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">🎯 Objectifs du mois en cours</h3>
                        <div class="row g-4">
                            <?php foreach ($currentMonthStats as $monthStat): ?>
                                <?php 
                                    if ($monthStat['sport'] === 'WeightTraining') continue;

                                    $sportName = $monthStat['sport'];
                                    if ($sportName === 'Ride' || $sportName === 'VirtualRide') $sportName = 'Vélo';
                                    elseif ($sportName === 'Run') $sportName = 'Course à pied';
                                    elseif ($sportName === 'Swim') $sportName = 'Natation';

                                    $goalDist = $monthStat['sport'] === 'Ride' ? 300000 : ($monthStat['sport'] === 'Run' ? 60000 : 20000); 
                                    $progress = min(100, ($monthStat['total_distance'] / $goalDist) * 100);
                                ?>
                                <div class="col-12 col-md-4">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-semibold small text-uppercase"><?= esc($sportName) ?></span>
                                        <span class="small fw-bold"><?= formatDist($monthStat['total_distance']) ?> / <?= formatDist($goalDist) ?></span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-strava" role="progressbar" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="text-muted small mt-1"><?= formatTime($monthStat['total_time']) ?> d'effort cumulé</div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-7">
                <div class="row g-3">
                    <?php if (empty($activitiesBySport)): ?>
                        <div class="col-12"><p class="text-muted">Aucune activité cardio enregistrée.</p></div>
                    <?php else: ?>
                        <?php foreach ($activitiesBySport as $sport => $acts): ?>
                            <div class="col-12 col-md-6">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-header bg-white fw-bold d-flex justify-content-between">
                                        <span><?= esc($sport) ?></span>
                                        <span class="badge bg-light text-dark border"><?= count($acts) ?> récentes</span>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($acts, 0, 4) as $activity): ?>
                                            <div class="list-group-item py-2">
                                                <div class="fw-semibold text-truncate" style="max-width: 100%;"><?= esc($activity['name']) ?></div>
                                                <div class="d-flex justify-content-between align-items-center small mt-1">
                                                    <span class="text-muted"><?= date('d/m/Y', strtotime($activity['started_at'])) ?></span>
                                                    <span class="fw-bold text-dark"><?= formatDist($activity['distance_meters']) ?></span>
                                                </div>
                                                <div class="d-flex gap-2 mt-2">
                                                    <?php if (isset($activity['premium']['suffer_score'])): ?>
                                                        <span class="badge bg-danger text-white rounded-pill" title="Effort Relatif">🔥 <?= esc($activity['premium']['suffer_score']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (isset($activity['premium']['average_watts']) && $activity['premium']['average_watts'] > 0): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill">⚡ <?= round($activity['premium']['average_watts']) ?> W</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($activity['elevation_gain_m']) && $activity['elevation_gain_m'] > 0): ?>
                                                        <span class="badge bg-secondary text-white rounded-pill">⛰️ <?= round($activity['elevation_gain_m']) ?> m</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-4 text-muted">Distance mensuelle globale (km)</h3>
                        <div style="height: 250px;">
                            <canvas id="distanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Musculation -->
    <div class="mb-5">
        <h2 class="h4 border-bottom pb-2 mb-4 section-title">🏋️‍♂️ Renforcement & Musculation</h2>
        
        <div class="row g-4">
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">Évolution du Tonnage par Séance</h3>
                        <?php if (empty($tonnageData)): ?>
                            <p class="text-muted small">Ajoutez des séries pour générer la courbe de tonnage.</p>
                        <?php else: ?>
                            <canvas id="tonnageChart" height="150"></canvas>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="p-3 pb-2">
                            <h3 class="h6 fw-bold mb-0 text-muted">Dernières Séances</h3>
                        </div>
                        <?php if (empty($recentWorkouts)): ?>
                            <p class="text-muted small px-3">Aucune séance enregistrée.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentWorkouts as $workout): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div style="width: 100%;">
                                            <div class="fw-semibold text-success text-truncate"><?= esc($workout['name']) ?></div>
                                            <div class="d-flex justify-content-between small text-muted mt-1">
                                                <span><?= date('d/m/Y', strtotime($workout['started_at'])) ?></span>
                                                <span class="fw-bold"><?= formatTime($workout['duration_seconds']) ?></span>
                                            </div>
                                            <div class="d-flex gap-2 mt-2">
                                                <?php if (isset($workout['premium']['suffer_score'])): ?>
                                                    <span class="badge bg-danger text-white rounded-pill" title="Effort Relatif">🔥 <?= esc($workout['premium']['suffer_score']) ?></span>
                                                <?php endif; ?>
                                                <?php if (isset($workout['local_volume'])): ?>
                                                    <span class="badge bg-success text-white rounded-pill" title="Tonnage Local">🏋️‍♂️ <?= number_format($workout['local_volume'], 0, ',', ' ') ?> kg</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-secondary border rounded-pill">0 kg (à renseigner)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    Chart.defaults.maintainAspectRatio = false;

    <?php 
    $filteredDistance = array_filter($monthlyDistance ?? [], function($d) {
        return true; 
    });
    
    if (!empty($filteredDistance)): ?>
        const monthlyLabels = <?= json_encode(array_column($filteredDistance, 'month_key')) ?>;
        const monthlyValues = <?= json_encode(array_map(static fn($row) => round(((float) $row['distance_meters']) / 1000, 2), $filteredDistance)) ?>;
        new Chart(document.getElementById('distanceChart'), {
            type: 'bar',
            data: { 
                labels: monthlyLabels, 
                datasets: [{ 
                    label: 'Distance (km)',
                    data: monthlyValues, 
                    backgroundColor: '#fc4c02',
                    maxBarThickness: 40 
                }] 
            },
            options: { 
                responsive: true, 
                scales: { 
                    y: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false } }
                }, 
                plugins: { legend: { display: false } } 
            }
        });
    <?php endif ?>

    <?php if (!empty($tonnageData)): ?>
        const tonnageLabels = <?= json_encode(array_column($tonnageData, 'workout_date')) ?>;
        const tonnageValues = <?= json_encode(array_column($tonnageData, 'total_volume_kg')) ?>;
        new Chart(document.getElementById('tonnageChart'), {
            type: 'line',
            data: { 
                labels: tonnageLabels, 
                datasets: [{ 
                    data: tonnageValues, 
                    borderColor: '#198754', 
                    backgroundColor: 'rgba(25, 135, 84, 0.1)', 
                    fill: true, 
                    tension: 0.3 
                }] 
            },
            options: { 
                responsive: true, 
                scales: { y: { beginAtZero: true, grid: { color: '#f1f3f5' } }, x: { grid: { display: false } } }, 
                plugins: { legend: { display: false } } 
            }
        });
    <?php endif ?>
</script>
</body>
</html>