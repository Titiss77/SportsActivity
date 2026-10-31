<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-light bg-white border-bottom mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= site_url('/') ?>">← Retour Tableau de bord</a>
        </div>
    </nav>

    <div class="container" style="max-width: 700px;">

        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold text-primary">🔐 Sécurité du Compte</div>
            <div class="card-body">
                <form action="<?= site_url('profile/updateAccount') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nom d'utilisateur</label>
                        <input type="text" class="form-control bg-light" value="<?= esc($user->username) ?>" disabled>
                        <small class="text-muted">Pour changer l'email ou l'identifiant, veuillez contacter
                            l'administrateur.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nouveau mot de passe</label>
                        <input type="password" name="password" class="form-control"
                            placeholder="Laissez vide si inchangé">
                    </div>
                    <button class="btn btn-primary" type="submit">Mettre à jour le mot de passe</button>
                </form>
            </div>
        </div>

        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-white py-3 fw-bold text-strava" style="color: #fc4c02;">🎯 Objectifs Mensuels
                Strava (km)</div>
            <div class="card-body">
                <form action="<?= site_url('profile/updateGoals') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Vélo (km)</label>
                            <input type="number" name="goal_ride_km" class="form-control"
                                value="<?= esc($settings['goal_ride_km'] ?? 300) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Course à pied (km)</label>
                            <input type="number" name="goal_run_km" class="form-control"
                                value="<?= esc($settings['goal_run_km'] ?? 60) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Natation (km)</label>
                            <input type="number" name="goal_swim_km" class="form-control"
                                value="<?= esc($settings['goal_swim_km'] ?? 20) ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Marche (km)</label>
                            <input type="number" name="goal_walk_km" class="form-control"
                                value="<?= esc($settings['goal_walk_km'] ?? 20) ?>" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button class="btn btn-warning text-dark fw-bold" type="submit">Sauvegarder les
                            objectifs</button>
                        <?php if (!empty($settings['strava_access_token'])): ?>
                        <form action="<?= site_url('strava/disconnect') ?>" method="post" class="m-0 p-0">
                            <?= csrf_field() ?>
                            <button class="btn btn-outline-danger" type="submit">Déconnecter Strava</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>