<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; }
    </style>
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom mb-4">
    <div class="container">
        <a class="text-decoration-none fw-semibold text-dark" href="<?= site_url('/') ?>">
            <span class="fs-5">←</span> Retour au tableau de bord
        </a>
    </div>
</nav>

<main class="container py-4" style="max-width: 800px;">
    
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="h3 fw-bold text-success mb-2"><?= esc($title) ?></h1>
            <div class="d-flex gap-2">
                <span class="badge bg-secondary">📅 <?= date('d/m/Y', strtotime($workout['date'])) ?></span>
                <span class="badge bg-success">🏋️‍♂️ Tonnage : <?= number_format($totalVolume, 0, ',', ' ') ?> kg</span>
            </div>
            <?php if (!empty($workout['notes'])): ?>
                <p class="text-muted fst-italic mt-3 mb-0">"<?= esc($workout['notes']) ?>"</p>
            <?php endif; ?>
        </div>
        
        <?php 
        // Gestion de l'URL pour le bouton d'ajout (ID local ou Strava ID)
        $addUrl = 'strength/form?date=' . date('Y-m-d', strtotime($workout['date']));
        if (!empty($workout['id'])) {
            $addUrl .= '&workout_id=' . esc($workout['id']);
        } elseif (!empty($workout['strava_activity_id'])) {
            $addUrl .= '&strava_id=' . esc($workout['strava_activity_id']);
        }
        ?>
        <a href="<?= site_url($addUrl) ?>" class="btn btn-outline-success fw-bold rounded-pill">
            ➕ Ajouter un exercice
        </a>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif ?>

    <?php if (empty($exercises)): ?>
        <div class="alert alert-info border-0 shadow-sm rounded-3">Aucun exercice n'a encore été renseigné pour cette séance.</div>
    <?php else: ?>
        <?php foreach ($exercises as $ex): ?>
            <div class="card border-0 shadow-sm mb-4 rounded-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom-0 rounded-top-4">
                    <h5 class="mb-0">
                        <a href="<?= site_url('exercise/' . $ex['id']) ?>" class="text-decoration-none text-dark fw-bold">
                            <?= esc($ex['name']) ?>
                        </a>
                    </h5>
                    <span class="badge bg-light text-secondary border"><?= esc($ex['muscle_group']) ?></span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th class="text-muted fw-semibold">Série</th>
                                <th class="text-muted fw-semibold">Répétitions</th>
                                <th class="text-muted fw-semibold">Charge (kg)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ex['sets'] as $set): ?>
                                <tr>
                                    <td class="text-muted fw-bold align-middle">#<?= esc($set['set_number']) ?></td>
                                    <td class="align-middle fs-5"><?= esc($set['reps']) ?></td>
                                    <td class="fw-bold align-middle fs-5 text-success"><?= esc($set['weight']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</main>

</body>
</html>