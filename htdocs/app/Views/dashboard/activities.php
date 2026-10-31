<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container"><a class="navbar-brand" href="<?= site_url('/') ?>">COROS · Entraînements</a></div>
    </nav>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3 mb-0">Activités</h1>
            <a href="<?= site_url('/') ?>" class="btn btn-outline-secondary">Retour au tableau de bord</a>
        </div>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
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
                            <td><?= esc($activity['started_at'] ?: '—') ?></td>
                            <td><?= esc($activity['name'] ?: '—') ?></td>
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
                        <?php endforeach; endif ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>