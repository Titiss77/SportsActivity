<?php
if (!function_exists('formatDist')) {
    function formatDist($meters) {
        if ($meters < 1000) return number_format($meters, 0, ',', ' ') . ' m';
        return number_format($meters / 1000, 2, ',', ' ') . ' km';
    }
}
if (!function_exists('formatTime')) {
    function formatTime($seconds) {
        $h = intdiv($seconds, 3600); $m = intdiv($seconds % 3600, 60);
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
        .list-group-item { border-color: #f1f3f5; padding: 0.5rem 1rem; }
        .section-title { color: #198754; font-weight: 700; display: flex; align-items: center; gap: 8px; }
        .workout-link { color: #198754; text-decoration: none; transition: color 0.2s; }
        .workout-link:hover { color: #146c43; text-decoration: underline; }
        .condensed-text { font-size: 0.85rem; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= site_url('/') ?>">Tableau de Bord</a>
        <div class="d-flex flex-wrap gap-2">
            <?php if (empty($userSettings['strava_access_token'])): ?>
                <a class="btn bg-strava btn-sm text-white fw-bold" href="<?= site_url('strava/connect') ?>">Connecter Strava</a>
            <?php endif; ?>
            <a class="btn btn-outline-success btn-sm" href="<?= site_url('strength') ?>">Progression Muscu</a>
            <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('profile') ?>">Mon Profil (<?= esc(auth()->user()->username ?? 'User') ?>)</a>
            <a class="btn btn-danger btn-sm" href="<?= site_url('logout') ?>">Déconnexion</a>
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

    <?php if (!empty($userSettings['strava_access_token'])): ?>
    <div class="card mb-4 border-0 bg-light">
        <div class="card-body py-2">
            <form method="post" action="<?= site_url('strava/sync') ?>" class="d-flex flex-wrap gap-2 align-items-end">
                <?= csrf_field() ?>
                <div>
                    <label class="form-label mb-0 fw-semibold condensed-text text-muted">Du :</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" required max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label class="form-label mb-0 fw-semibold condensed-text text-muted">Au :</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div><button class="btn btn-sm bg-strava" type="submit">Importer</button></div>
            </form>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info border-0 shadow-sm">
        🔗 <strong>Strava n'est pas connecté.</strong> Cliquez sur "Connecter Strava" en haut à droite pour lier votre compte et importer vos activités.
    </div>
    <?php endif; ?>

    <!-- SECTION 1 : CARDIO & ENDURANCE (STRAVA) -->
    <div class="mb-5">
        <h2 class="h4 border-bottom pb-2 text-strava fw-bold">🏃‍♂️ Cardio & Endurance</h2>
        
        <div class="row g-3 mb-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body py-3">
                        <h3 class="h6 fw-bold mb-3 text-muted">🎯 Objectifs mensuels</h3>
                        <div class="row g-4">
                            <?php foreach ($currentMonthStats as $monthStat): ?>
                                <?php 
                                    if ($monthStat['sport'] === 'WeightTraining') continue;

                                    $sportName = $monthStat['sport'];
                                    $goalDist = 20000;
                                    
                                    if ($sportName === 'Ride' || $sportName === 'VirtualRide') {
                                        $sportName = 'Vélo';
                                        $goalDist = ($userSettings['goal_ride_km'] ?? 300) * 1000;
                                    } elseif ($sportName === 'Run') {
                                        $sportName = 'Course à pied';
                                        $goalDist = ($userSettings['goal_run_km'] ?? 60) * 1000;
                                    } elseif ($sportName === 'Swim') {
                                        $sportName = 'Natation';
                                        $goalDist = ($userSettings['goal_swim_km'] ?? 20) * 1000;
                                    } elseif ($sportName === 'Walk') {
                                        $sportName = 'Marche';
                                        $goalDist = ($userSettings['goal_walk_km'] ?? 20) * 1000;
                                    }

                                    $progress = min(100, ($monthStat['total_distance'] / max(1, $goalDist)) * 100);
                                ?>
                                <div class="col-12 col-md-6 col-xl-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-semibold condensed-text text-uppercase"><?= esc($sportName) ?></span>
                                        <span class="condensed-text fw-bold text-dark"><?= formatDist($monthStat['total_distance']) ?> / <?= formatDist($goalDist) ?></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-strava" style="width: <?= $progress ?>%"></div>
                                    </div>
                                    <div class="text-muted condensed-text mt-1"><?= formatTime($monthStat['total_time']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-6">
                <div class="row g-2">
                    <?php if (empty($activitiesBySport)): ?>
                        <div class="col-12"><p class="text-muted">Aucune activité cardio.</p></div>
                    <?php else: ?>
                        <?php foreach ($activitiesBySport as $sport => $acts): ?>
                            <div class="col-12 col-md-6">
                                <div class="card h-100 shadow-sm border-0">
                                    <div class="card-header bg-white fw-bold d-flex justify-content-between py-2">
                                        <span class="condensed-text"><?= esc($sport) ?></span>
                                        <span class="badge bg-light text-dark border condensed-text"><?= count($acts) ?> réc.</span>
                                    </div>
                                    <div class="list-group list-group-flush">
                                        <?php foreach (array_slice($acts, 0, 4) as $activity): ?>
                                            <div class="list-group-item">
                                                <div class="fw-semibold text-truncate condensed-text" style="max-width: 100%;"><?= esc($activity['name']) ?></div>
                                                <div class="d-flex justify-content-between align-items-center condensed-text mt-1">
                                                    <span class="text-muted"><?= date('d/m/y', strtotime($activity['started_at'])) ?></span>
                                                    <span class="fw-bold text-dark"><?= formatDist($activity['distance_meters']) ?></span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <?php if (isset($activity['premium']['suffer_score'])): ?>
                                                        <span class="badge bg-danger text-white rounded-pill condensed-text" style="font-size:0.7rem;">🔥 <?= esc($activity['premium']['suffer_score']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (isset($activity['premium']['average_watts']) && $activity['premium']['average_watts'] > 0): ?>
                                                        <span class="badge bg-warning text-dark rounded-pill condensed-text" style="font-size:0.7rem;">⚡ <?= round($activity['premium']['average_watts']) ?>W</span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($activity['elevation_gain_m']) && $activity['elevation_gain_m'] > 0): ?>
                                                        <span class="badge bg-secondary text-white rounded-pill condensed-text" style="font-size:0.7rem;">⛰️ <?= round($activity['elevation_gain_m']) ?>m</span>
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

            <!-- TABLEAU DES DISTANCES AU LIEU DU GRAPHE -->
            <div class="col-12 col-xl-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">Statistiques Globales</h3>
                        <table class="table table-sm table-striped">
                            <thead>
                                <tr>
                                    <th class="condensed-text text-muted">Mois</th>
                                    <th class="condensed-text text-muted text-end">Distance (km)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_filter($monthlyDistance ?? []) as $month): ?>
                                <tr>
                                    <td class="fw-semibold condensed-text"><?= esc($month['month_key']) ?></td>
                                    <td class="text-end fw-bold text-strava condensed-text"><?= number_format($month['distance_meters'] / 1000, 2, ',', ' ') ?> km</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
                        <h3 class="h6 fw-bold mb-3 text-muted">Évolution du Tonnage</h3>
                        <?php if (empty($tonnageData)): ?>
                            <p class="text-muted small">Aucune donnée de tonnage. Cliquez sur une séance pour ajouter vos charges et générer le graphique.</p>
                        <?php else: ?>
                            <div style="height: 250px;">
                                <canvas id="tonnageChart"></canvas>
                            </div>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-6">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div class="p-3 pb-2 border-bottom">
                            <h3 class="h6 fw-bold mb-0 text-muted">Séances de force récentes</h3>
                        </div>
                        <?php if (empty($recentWorkouts)): ?>
                            <p class="text-muted small p-3">Aucune séance.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentWorkouts as $workout): ?>
                                    <?php 
                                        $wDate = date('Y-m-d', strtotime($workout['started_at'])); 
                                        $stravaId = $workout['strava_activity_id'];
                                        $workoutId = $workout['local_workout_id'] ?? $stravaId; // Lien cliquable par défaut vers Strava ID si non lié
                                    ?>
                                    <div class="list-group-item py-2">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <a href="<?= site_url('workout/' . $workoutId) ?>" class="fw-semibold workout-link text-truncate condensed-text pe-2" style="max-width: 80%;">
                                                <?= esc($workout['name']) ?>
                                            </a>
                                            <span class="condensed-text fw-bold text-dark text-nowrap"><?= formatTime($workout['duration_seconds']) ?></span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 condensed-text">
                                            <span class="text-muted pe-2 border-end"><?= date('d/m', strtotime($workout['started_at'])) ?></span>
                                            
                                            <?php if (isset($workout['premium']['suffer_score'])): ?>
                                                <span class="badge bg-danger text-white rounded-pill" style="font-size:0.75rem;">🔥 <?= esc($workout['premium']['suffer_score']) ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($workout['local_volume'])): ?>
                                                <span class="badge bg-success text-white rounded-pill" style="font-size:0.75rem;">🏋️‍♂️ <?= number_format($workout['local_volume'], 0, ',', ' ') ?> kg</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border rounded-pill" style="font-size:0.75rem;">0 kg</span>
                                            <?php endif; ?>
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
    
    <?php if (!empty($tonnageData)): ?>
        const tonnageLabels = <?= json_encode(array_column($tonnageData, 'workout_date')) ?>;
        const tonnageValues = <?= json_encode(array_column($tonnageData, 'total_volume_kg')) ?>;
        
        // Calcul pour empêcher l'axe de s'étendre à l'infini
        const maxValue = Math.max(...tonnageValues);
        const suggestedMax = maxValue > 0 ? maxValue * 1.2 : 100;

        new Chart(document.getElementById('tonnageChart'), {
            type: 'line',
            data: { 
                labels: tonnageLabels, 
                datasets: [{ 
                    data: tonnageValues, 
                    borderColor: '#198754', 
                    backgroundColor: 'rgba(25, 135, 84, 0.1)', 
                    fill: true, 
                    tension: 0.3,
                    maxBarThickness: 40 // Sécurité supplémentaire
                }] 
            },
            options: { 
                responsive: true, 
                scales: { 
                    y: { 
                        beginAtZero: true,
                        suggestedMax: suggestedMax, // Empêche l'axe de croître indéfiniment
                        grid: { color: '#f1f3f5' }
                    }, 
                    x: { grid: { display: false } } 
                }, 
                plugins: { legend: { display: false } } 
            }
        });
    <?php endif ?>
</script>
</body>
</html>