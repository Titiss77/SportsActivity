<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-semibold" href="<?= site_url('/') ?>">← Retour au tableau de bord</a>
    </div>
</nav>

<main class="container" style="max-width: 600px;">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h1 class="h5 mb-0 fw-bold text-success"><?= esc($title) ?></h1>
            <p class="text-muted small mb-0">Date de la séance : <?= date('d/m/Y', strtotime($date)) ?></p>
        </div>
        <div class="card-body">
            <form action="<?= site_url('strength/save') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= esc($date) ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold">Exercice</label>
                    <select name="exercise_id" class="form-select" required>
                        <option value="">-- Choisir un exercice --</option>
                        <?php foreach ($exercises as $ex): ?>
                            <option value="<?= esc($ex['id']) ?>"><?= esc($ex['name']) ?> (<?= esc($ex['muscle_group']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row mb-3">
                    <div class="col-4">
                        <label class="form-label fw-semibold">Séries</label>
                        <input type="number" name="sets" class="form-control" value="4" min="1" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Répétitions</label>
                        <input type="number" name="reps" class="form-control" value="10" min="1" required>
                    </div>
                    <div class="col-4">
                        <label class="form-label fw-semibold">Poids (kg)</label>
                        <input type="number" step="0.5" name="weight" class="form-control" placeholder="ex: 60" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold">Notes (Optionnel)</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Sensations, variantes utilisées..."></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-success">Enregistrer la charge</button>
                </div>
            </form>
        </div>
    </div>
</main>

</body>
</html>