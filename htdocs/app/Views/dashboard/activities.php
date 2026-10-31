<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Implémentation de Simple DataTables pour trier et paginer les utilisateurs (JS) -->
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
</head>

<body class="bg-light">
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Activités</h1>
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">Retour au tableau de bord</a>
        </div>

        <div style="display: flex; justify-content: center;">
            <table class="table table-striped table-hover shadow-sm"
                style="width: 90%; max-width: 1200px; margin-top: 20px;">
                <style>
                th,
                td {
                    padding: 0.75rem;
                    text-align: center;
                    border-bottom: 1px solid #dee2e6;
                }
                </style>
                <thead>
                    <tr>
                        <th>Sport</th>
                        <th>Activité Totales</th>
                        <th>Distance (km)</th>
                        <th>Durée (s)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">Aucune activité enregistrée. </td>
                    </tr>
                    <?php else: ?>
                    <tr style="font-weight: bold; background-color: #f8f9fa;">
                        <?php $countAll=(int) ($allGlobal['total_activities'] ?? 0);?>
                        <?php $distanceAll=(int) ($allGlobal['total_distance'] ?? 0);?>
                        <?php $durationAll=(int) ($allGlobal['total_duration'] ?? 0);?>
                        <td>Toutes les activités</td>
                        <td><?= $countAll ?></td>
                        <td><?= $distanceAll ?></td>
                        <td><?= $durationAll ?></td>
                    </tr>
                    <?php foreach ($globalStats as $global): ?>
                    <?php $count=(int) ($global['total_activities'] ?? 0);?>
                    <?php $distance=(int) ($global['total_distance'] ?? 0);?>
                    <?php $duration=(int) ($global['total_duration'] ?? 0);?>
                    <tr>
                        <td><?=esc($global['sport'] ?: '—') ?></td>
                        <td><?=$count ?></td>
                        <td><?=$distance ?></td>
                        <td><?=$duration ?></td>
                    </tr><?php endforeach;

            endif ?>
                </tbody>
            </table>
        </div>

        <div class="container fade-in">
            <?php if (session()->has('message')) { ?>
            <div class="alert alert-success"><?php echo esc(session('message')); ?></div>
            <?php } ?>
            <?php if (session()->has('error')) { ?>
            <div class="alert alert-danger"><?php echo esc(session('error')); ?></div>
            <?php } ?>

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
                                <td colspan="7" class="text-center py-5 text-secondary">Aucune activité enregistrée.
                                </td>
                            </tr>
                            <?php else:
    foreach ($activities as $activity): ?>
                            <?php $seconds = (int) ($activity['duration_seconds'] ?? 0); ?>
                            <tr>
                                <td><?= esc($activity['started_at'] ?: '—') ?></td>
                                <td><a href="https://www.strava.com/activities/<?= esc($activity['strava_activity_id']) ?>"
                                        target="_blank"
                                        class="fw-semibold text-truncate condensed-text text-dark text-decoration-none d-block">
                                        <?= esc($activity['name']) ?>
                                    </a>
                                </td>
                                <td><?= esc($activity['sport'] ?: '—') ?></td>
                                <td><?= number_format(((float) ($activity['distance_meters'] ?? 0)) / 1000, 2, ',', ' ') ?>
                                    km</td>
                                <td><?= sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds % 3600, 60), $seconds % 60) ?>
                                </td>
                                <td><?= !empty($activity['avg_heart_rate']) ? (int) $activity['avg_heart_rate'] . ' bpm' : '—' ?>
                                </td>
                                <td><?= isset($activity['elevation_gain_m']) ? number_format((float) $activity['elevation_gain_m'], 0, ',', ' ') . ' m' : '—' ?>
                                </td>
                            </tr>
                            <?php endforeach;
endif ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
            // Initialisation et traduction de Simple DataTables
            const table = document.getElementById("myAdminTable");

            if (table) {
                new simpleDatatables.DataTable(table, {

                        searchable: true,
                        fixedHeight: false,
                        labels: {
                            placeholder: "Rechercher une activité",
                            perPage: "activités par page",
                            noRows: "Aucune activité trouvée",
                            info: "Affichage de {start} à {end} sur {rows} activités",
                        }
                    }

                );
            }
        }

    );
    </script>
</body>

</html>