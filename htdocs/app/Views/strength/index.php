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

    <div class="container" style="max-width: 600px;">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h1 class="h4 mb-0 fw-bold text-success">📈 Progression par Exercice</h1>
            </div>
            <div class="card-body py-4">
                <p class="text-muted small mb-4">Sélectionnez un exercice pour visualiser l'évolution de vos charges
                    maximales au fil des séances de musculation.</p>

                <form onsubmit="event.preventDefault(); goToExercise();">
                    <div class="mb-3">
                        <select id="exerciseSelectGraph" class="form-select form-select-lg fw-semibold" required>
                            <option value="">Sélectionner un exercice...</option>
                            <?php foreach ($exercises as $ex): ?>
                            <option value="<?= esc($ex['id']) ?>"><?= esc($ex['name']) ?>
                                (<?= esc($ex['muscle_group']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold rounded-pill">Afficher le
                        graphique</button>
                </form>
            </div>
        </div>
    </div>

    <script>
    function goToExercise() {
        const id = document.getElementById('exerciseSelectGraph').value;
        if (id) {
            window.location.href = "<?= site_url('exercise/') ?>" + id;
        }
    }
    </script>
</body>

</html>