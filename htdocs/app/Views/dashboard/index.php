<?php
if (!function_exists('formatDist')) {
    function formatDist($meters)
    {
        if ($meters < 1000)
            return number_format($meters, 0, ',', ' ') . ' m';
        return number_format($meters / 1000, 2, ',', ' ') . ' km';
    }
}

if (!function_exists('formatTime')) {
    function formatTime($seconds)
    {
        $h = intdiv((int)$seconds, 3600);
        $m = intdiv((int)$seconds % 3600, 60);
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    body {
        background: #f4f6f8;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .card {
        border-radius: 12px;
        border: 1px solid #e9ecef;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #e9ecef;
    }

    .text-strava {
        color: #fc4c02;
    }

    .bg-strava {
        background-color: #fc4c02;
        color: white;
        border-color: #fc4c02;
    }

    .bg-strava:hover {
        background-color: #e34301;
        color: white;
    }

    .list-group-item {
        border-color: #f1f3f5;
        padding: 0.5rem 1rem;
    }

    .section-title {
        color: #198754;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .workout-link {
        color: #198754;
        text-decoration: none;
        transition: color 0.2s;
    }

    .workout-link:hover {
        color: #146c43;
        text-decoration: underline;
    }

    .condensed-text {
        font-size: 0.85rem;
    }

    /* Styles pour la modal des records et zones */
    .zone-bar-container {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
    }

    .zone-label {
        width: 30px;
        font-weight: bold;
    }

    .zone-bar-wrapper {
        flex-grow: 1;
        background: transparent;
        height: 24px;
        margin: 0 10px;
        display: flex;
        align-items: center;
    }

    .zone-bar {
        height: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        padding-left: 8px;
        color: white;
        font-weight: bold;
        font-size: 0.8rem;
    }

    .zone-percent {
        width: 45px;
        text-align: right;
        font-weight: bold;
    }

    .zone-range {
        font-size: 0.75rem;
        color: #6c757d;
        text-align: right;
        margin-top: -5px;
        margin-bottom: 10px;
    }

    .record-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #eee;
    }

    .record-item:last-child {
        border-bottom: none;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-white border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= site_url('/') ?>">Tableau de Bord</a>
            <div class="d-flex flex-wrap gap-2">
                <?php if (empty($userSettings['strava_access_token'])): ?>
                <a href="<?= site_url('strava/connect') ?>" class="d-inline-block text-decoration-none"
                    title="Connect with Strava">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 150 48" width="200" height="32"
                        style="border-radius: 4px; display: block;">
                        <rect width="230" height="48" rx="4" fill="#fc4c02" />
                        <path fill="#ffffff" d="M25.7 11.2l-7.8 15.4h4.6l3.2-6.4 3.2 6.4h4.6l-7.8-15.4z" />
                        <path fill="#fda580" d="M31.4 26.6l-3.2 6.4-3.2-6.4h-3.5l6.7 13.2 6.7-13.2h-3.5z" />
                        <text x="50" y="29" fill="#ffffff"
                            font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif"
                            font-size="13" font-weight="700" letter-spacing="0.4">CONNECT WITH STRAVA</text>
                    </svg>
                </a>
                <?php endif; ?>
                <a class="btn btn-outline-success btn-sm" href="<?= site_url('activites') ?>">Historique</a>
                <a class="btn btn-outline-secondary btn-sm" href="<?= site_url('profile') ?>">Mon Profil
                    (<?= esc(auth()->user()->username ?? 'User') ?>)</a>
                <a class="btn btn-danger btn-sm" href="<?= site_url('logout') ?>">Déconnexion</a>
            </div>
        </div>
    </nav>
    <main class="container">
        <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?>
        </div>
        <?php endif ?>
        <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-warning alert-dismissible fade show"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif ?>

        <?php if (!empty($userSettings['strava_access_token'])): ?>
        <div class="card mb-4 border-0 bg-light">
            <div class="card-body py-2 d-flex flex-wrap justify-content-between align-items-center">
                <form method="post" action="<?= site_url('strava/sync') ?>"
                    class="d-flex flex-wrap gap-2 align-items-end">
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
            <strong>Strava n'est pas connecté.</strong> Cliquez sur "Connecter Strava" en haut à droite pour lier
            votre compte.
        </div>
        <?php endif; ?>

        <?php if (empty($activitiesBySport) && empty($recentWorkouts)): ?>
        <div class="alert alert-info border-0 shadow-sm">
            <strong>Aucune activité récente.</strong> Connectez Strava et importez vos activités pour voir vos
            statistiques ici.
        </div>
        <?php else: ?>

        <?php if (!empty($activitiesBySport)): ?>
        <!-- SECTION 1 : CARDIO & ENDURANCE (STRAVA) -->
        <div class="mb-5">
            <h2 class="h4 border-bottom pb-2 text-strava fw-bold">🔥 Cardio & Endurance</h2>

            <div class="row g-4">
                <div class="col-12 col-xl-6">
                    <div class="row g-2">
                        <?php foreach ($activitiesBySport as $sport => $acts): ?>
                        <?php $modalId = "modal" . md5($sport); ?>
                        <div class="col-12 col-md-6">
                            <div class="card h-100 shadow-sm border-0">
                                <div
                                    class="card-header bg-white fw-bold d-flex justify-content-between align-items-center py-2">
                                    <span class="condensed-text"><?= esc($sport) ?></span>
                                    <!-- Modification : Bouton cliquable pour ouvrir la modale -->
                                    <button class="btn btn-sm btn-light border condensed-text" data-bs-toggle="modal"
                                        data-bs-target="#<?= $modalId ?>">
                                        <?= count($acts) ?> réc.
                                    </button>
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($acts, 0, 5) as $activity): ?>
                                    <div class="list-group-item">
                                        <a href="https://www.strava.com/activities/<?= esc($activity['strava_activity_id']) ?>"
                                            target="_blank"
                                            class="fw-semibold text-truncate condensed-text text-dark text-decoration-none d-block">
                                            <?= esc($activity['name']) ?>
                                        </a>
                                        <div
                                            class="d-flex justify-content-between align-items-center condensed-text mt-1">
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

                        <!-- MODAL STATISTIQUES POUR LE SPORT -->
                        <div class="modal fade" id="<?= $modalId ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow">
                                    <div class="modal-header border-0 pb-0">
                                        <h5 class="modal-title fw-bold">Statistiques : <?= esc($sport) ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body pt-3">
                                        <?php if (isset($sportDetails[$sport])): ?>
                                        <!-- Section Zones Cardiaques -->
                                        <h6 class="fw-bold text-muted mb-3 text-uppercase" style="font-size:0.8rem;">
                                            Fréquence Cardiaque (Temps dans les zones)</h6>
                                        <div class="mb-4">
                                            <?php 
                                                    // Inverser pour afficher Z5 en haut
                                                    $zones = $sportDetails[$sport]['zones'];
                                                    foreach ($zones as $zoneName => $zoneData): 
                                                ?>
                                            <div class="zone-bar-container">
                                                <div class="zone-label"><?= $zoneName ?></div>
                                                <div class="zone-bar-wrapper">
                                                    <div class="zone-bar"
                                                        style="width: <?= max(2, $zoneData['percent']) ?>%; background-color: <?= $zoneData['color'] ?>;">
                                                        <?= $zoneData['percent'] > 5 ? $zoneData['percent'] . '%' : '' ?>
                                                    </div>
                                                </div>
                                                <div class="zone-percent"><?= $zoneData['percent'] ?>%</div>
                                            </div>
                                            <div class="zone-range"><?= $zoneData['range'] ?></div>
                                            <?php endforeach; ?>
                                        </div>

                                        <!-- Section Records -->
                                        <h6 class="fw-bold text-muted mb-2 mt-4 text-uppercase"
                                            style="font-size:0.8rem;">Meilleurs temps (Records)</h6>
                                        <div>
                                            <?php foreach ($sportDetails[$sport]['records'] as $distance => $time): ?>
                                            <div class="record-item">
                                                <span class="text-dark"><?= esc($distance) ?></span>
                                                <span class="fw-bold text-dark"><?= esc($time) ?></span>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <p class="text-muted text-center py-4">Pas de données détaillées disponibles
                                            pour ce sport.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- FIN MODAL -->
                        <?php endforeach ?>
                    </div>
                </div>

                <div class="col-12 col-xl-6">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h3 class="h6 fw-bold mb-0 text-muted">Progression des Activités</h3>
                                <select id="sportFilter"
                                    class="form-select form-select-sm w-auto fw-semibold text-strava border-strava"
                                    onchange="updateStravaChart()">
                                    <option value="all">Tous les sports</option>
                                    <?php foreach (array_keys($activitiesBySport) as $sName): ?>
                                    <option value="<?= esc($sName) ?>"><?= esc($sName) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="height: 250px; position: relative;">
                                <canvas id="stravaStyleChart"></canvas>
                            </div>
                            <div class="text-end mt-1">
                                <span class="small fw-bold" style="color: #fc4c02; font-size: 0.75rem;">POWERED BY
                                    STRAVA</span>
                            </div>
                            <!-- NOUVEAU : Bloc Série d'activités (Strava Streak) -->
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
                            <h3 class="h6 fw-bold mb-3 text-muted">📈 Évolution du Tonnage</h3>
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
                                        <span class="badge bg-success text-white rounded-pill"
                                            style="font-size:0.75rem;">
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
    </main>

    <script>
    Chart.defaults.maintainAspectRatio = false;

    // --- 1. Graphique Strava Original ---
    const allActivities = <?= json_encode($rawActivitiesForChart ?? []) ?>;
    const defaultLabels = <?= json_encode(array_column($monthlyDistance ?? [], 'month_key')) ?>;
    const defaultValues =
        <?= json_encode(array_map(static fn($row) => round(((float) $row['distance_meters']) / 1000, 2), $monthlyDistance ?? [])) ?>;

    const ctx = document.getElementById('stravaStyleChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 250);
    gradient.addColorStop(0, 'rgba(252, 76, 2, 0.4)');
    gradient.addColorStop(1, 'rgba(252, 76, 2, 0.0)');

    let stravaChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: defaultLabels,
            datasets: [{
                data: defaultValues,
                borderColor: '#fc4c02',
                backgroundColor: gradient,
                borderWidth: 3,
                fill: true,
                tension: 0.35,
                pointBackgroundColor: '#fc4c02',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: '#f1f3f5'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y + ' km';
                        }
                    }
                }
            }
        }
    });

    function updateStravaChart() {
        const selectedSport = document.getElementById('sportFilter').value;
        let filteredDataByMonth = {};

        defaultLabels.forEach(label => {
            filteredDataByMonth[label] = 0;
        });

        allActivities.forEach(act => {
            if (selectedSport === 'all' || act.sport === selectedSport) {
                if (filteredDataByMonth.hasOwnProperty(act.date)) filteredDataByMonth[act.date] += (act
                    .distance / 1000);
            }
        });

        const newValues = defaultLabels.map(label => Math.round(filteredDataByMonth[label] * 100) / 100);
        stravaChart.data.datasets[0].data = newValues;
        stravaChart.update();
    }

    // --- 2. Graphique Tonnage Original ---
    <?php if (!empty($tonnageData)): ?>
    const tonnageLabels = <?= json_encode(array_column($tonnageData, 'workout_date')) ?>;
    const tonnageValues = <?= json_encode(array_column($tonnageData, 'total_volume_kg')) ?>;
    const maxTon = Math.max(...tonnageValues);
    const suggestedMaxTon = maxTon > 0 ? maxTon * 1.2 : 100;

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
                pointBackgroundColor: '#198754',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: suggestedMaxTon,
                    grid: {
                        color: '#f1f3f5'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
    <?php endif ?>

    // --- 3. Heatmap Radar Musculaire ---
    <?php if (!empty($muscleHeatmap)): ?>
    new Chart(document.getElementById('muscleRadarChart').getContext('2d'), {
        type: 'radar',
        data: {
            labels: <?= json_encode(array_column($muscleHeatmap, 'muscle_group')) ?>,
            datasets: [{
                label: 'Volume (kg)',
                data: <?= json_encode(array_column($muscleHeatmap, 'volume')) ?>,
                backgroundColor: 'rgba(25, 135, 84, 0.2)',
                borderColor: '#198754',
                pointBackgroundColor: '#198754',
                borderWidth: 2
            }]
        },
        options: {
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                r: {
                    ticks: {
                        display: false
                    },
                    grid: {
                        color: '#e9ecef'
                    },
                    angleLines: {
                        color: '#e9ecef'
                    }
                }
            }
        }
    });
    <?php endif; ?>
    </script>
</body>

</html>