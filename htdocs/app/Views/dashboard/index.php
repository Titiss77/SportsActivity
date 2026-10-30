<?php
// Helpers de formatage pour un affichage intelligent (mètres vs kilomètres)
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
        body { background: #f8f9fa; }
        .stat-card { border: 0; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,.04); transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-value { font-size: clamp(1.2rem, 2.5vw, 1.8rem); font-weight: 700; }
        .muted { color: #6c757d; }
        .text-strava { color: #fc4c02; }
        .bg-strava { background-color: #fc4c02; color: white; }
    </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('/') ?>">Tableau de bord Global</a>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-light btn-sm" href="<?= site_url('activites') ?>">Historique Complet</a>
            <?php if (!session()->has('strava_access_token')): ?>
                <a class="btn btn-warning btn-sm" href="<?= site_url('strava/connect') ?>">Connecter Strava</a>
            <?php else: ?>
                <span class="btn btn-secondary btn-sm disabled">Strava Connecté</span>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container py-4">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-warning alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif ?>

    <!-- SYNCHRONISATION STRAVA -->
    <?php if (session()->has('strava_access_token')): ?>
    <div class="card border-0 shadow-sm mb-5">
        <div class="card-body">
            <form method="post" action="<?= site_url('strava/sync') ?>" class="d-flex flex-wrap gap-3 align-items-end">
                <?= csrf_field() ?>
                <div>
                    <label for="start_date" class="form-label mb-1 fw-semibold small text-strava">Synchroniser du :</label>
                    <input type="date" name="start_date" id="start_date" class="form-control form-control-sm" required max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="end_date" class="form-label mb-1 fw-semibold small text-strava">Au :</label>
                    <input type="date" name="end_date" id="end_date" class="form-control form-control-sm" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <button class="btn btn-sm bg-strava text-nowrap" type="submit">Importer les activités</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>


    <!-- SECTION 1 : CARDIO & ENDURANCE (STRAVA) -->
    <div class="mb-5">
        <h2 class="h4 border-bottom pb-2 text-strava fw-bold">🏃‍♂️ Cardio & Endurance</h2>
        
        <!-- Totaux par Sport -->
        <div class="row g-3 mb-4 mt-2">
            <?php foreach ($statsBySport as $sportStat): ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="card stat-card h-100 border-start border-4 border-warning">
                        <div class="card-body py-3">
                            <div class="fw-bold text-uppercase small text-muted mb-1"><?= esc($sportStat['sport']) ?></div>
                            <div class="fs-4 fw-bold"><?= formatDist($sportStat['total_distance_meters']) ?></div>
                            <div class="small text-muted"><?= formatTime($sportStat['total_duration_seconds']) ?> • <?= esc($sportStat['total_activities']) ?> séances</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-4">
            <!-- Activités par Sport -->
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
                                                <div class="d-flex justify-content-between small muted mt-1">
                                                    <span><?= date('d/m/Y', strtotime($activity['started_at'])) ?></span>
                                                    <span class="fw-bold text-dark"><?= formatDist($activity['distance_meters']) ?></span>
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

            <!-- Graphique Cardio -->
            <div class="col-12 col-xl-5">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">Distance mensuelle globale (km)</h3>
                        <canvas id="distanceChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- SECTION 2 : MUSCULATION -->
    <div class="mb-5">
        <h2 class="h4 border-bottom pb-2 text-success fw-bold">🏋️‍♂️ Renforcement & Musculation</h2>
        
        <div class="row g-4 mt-2">
            <!-- Graphique Tonnage -->
            <div class="col-12 col-xl-7">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">Évolution du Tonnage par Séance</h3>
                        <?php if (empty($tonnageData)): ?>
                            <p class="text-muted">Ajoutez des séries pour générer la courbe de tonnage.</p>
                        <?php else: ?>
                            <canvas id="tonnageChart" height="150"></canvas>
                        <?php endif ?>
                    </div>
                </div>
            </div>
            
            <!-- Liste des Séances -->
            <div class="col-12 col-xl-5">
                <div class="card stat-card h-100">
                    <div class="card-body">
                        <h3 class="h6 fw-bold mb-3 text-muted">Dernières Séances</h3>
                        <?php if (empty($recentWorkouts)): ?>
                            <p class="text-muted">Aucune séance enregistrée.</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recentWorkouts as $workout): ?>
                                    <a href="<?= site_url('workout/' . $workout['id']) ?>" class="list-group-item list-group-item-action px-0 d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-semibold text-success">Séance du <?= date('d/m/Y', strtotime($workout['date'])) ?></div>
                                            <div class="small muted text-truncate" style="max-width: 280px;"><?= esc($workout['notes']) ?></div>
                                        </div>
                                        <span class="badge bg-light text-dark border">Voir ➔</span>
                                    </a>
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
    // Graphique Distance Mensuelle (Strava)
    <?php if (!empty($monthlyDistance)): ?>
        const monthlyLabels = <?= json_encode(array_column($monthlyDistance, 'month_key')) ?>;
        const monthlyValues = <?= json_encode(array_map(static fn($row) => round(((float) $row['distance_meters']) / 1000, 2), $monthlyDistance)) ?>;
        new Chart(document.getElementById('distanceChart'), {
            type: 'bar',
            data: { labels: monthlyLabels, datasets: [{ label: 'Distance (km)', data: monthlyValues, backgroundColor: '#fc4c02', borderRadius: 4 }] },
            options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
    <?php endif ?>

    // Graphique Tonnage (Musculation)
    <?php if (!empty($tonnageData)): ?>
        const tonnageLabels = <?= json_encode(array_column($tonnageData, 'workout_date')) ?>;
        const tonnageValues = <?= json_encode(array_column($tonnageData, 'total_volume_kg')) ?>;
        new Chart(document.getElementById('tonnageChart'), {
            type: 'line',
            data: { 
                labels: tonnageLabels, 
                datasets: [{ 
                    label: 'Tonnage Total (kg)', 
                    data: tonnageValues, 
                    borderColor: '#198754', 
                    backgroundColor: 'rgba(25, 135, 84, 0.1)', 
                    fill: true, 
                    tension: 0.3 
                }] 
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } }, plugins: { legend: { display: false } } }
        });
    <?php endif ?>
</script>
</body>
</html>