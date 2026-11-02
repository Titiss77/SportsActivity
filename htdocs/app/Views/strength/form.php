<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    body {
        background-color: #f8f9fa;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }

    .dynamic-input {
        border-radius: 12px;
        height: 50px;
        font-size: 1.1rem;
    }

    .btn-add-set {
        border-radius: 25px;
        border: 1px solid #ced4da;
        color: #495057;
        background: white;
        font-weight: 600;
        padding: 10px;
        transition: 0.2s;
    }

    .btn-add-set:hover {
        background: #e9ecef;
    }

    .delete-btn {
        cursor: pointer;
        color: #6c757d;
        transition: color 0.2s;
        background: none;
        border: none;
        padding: 5px;
    }

    .delete-btn:hover {
        color: #dc3545;
    }

    .set-header {
        font-size: 0.95rem;
        font-weight: 700;
        color: #000;
    }

    .set-number {
        font-size: 1.1rem;
        color: #6c757d;
    }
    </style>
</head>

<body>
    <nav class="navbar navbar-light bg-white border-bottom mb-4">
        <div class="container">
            <a class="text-decoration-none fw-semibold text-dark" href="<?= site_url('/') ?>">
                <span class="fs-5">←</span> Retour
            </a>
        </div>
    </nav>
    <main class="container mb-5" style="max-width: 500px;">
        <h1 class="h4 mb-1 fw-bold"><?= esc($title) ?></h1>
        <p class="text-muted small mb-4">Date : <?= date('d/m/Y', strtotime($date)) ?></p>

        <form action="<?= site_url('strength/save') ?>" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="date" value="<?= esc($date) ?>">
            <input type="hidden" name="strava_id" value="<?= esc($stravaId ?? '') ?>">
            <input type="hidden" name="workout_id" value="<?= esc($workoutId ?? '') ?>">

            <!-- Sélection Exercice -->
            <div class="mb-4">
                <select name="exercise_id" id="exerciseSelect" class="form-select dynamic-input fw-semibold" required
                    onchange="toggleNewExercise()">
                    <option value="">Sélectionner un exercice...</option>
                    <?php foreach ($exercises as $ex): ?>
                    <option value="<?= esc($ex['id']) ?>"><?= esc($ex['name']) ?>
                        (<?= esc($ex['muscle_group'] ?? 'Autre') ?>)
                    </option>
                    <?php endforeach; ?>
                    <option value="new" class="text-primary fw-bold">+ Créer un nouvel exercice...</option>
                </select>
            </div>

            <!-- Bloc Nouvel Exercice (Masqué par défaut) -->
            <div id="newExerciseDiv" class="mb-4 p-3 bg-white border rounded shadow-sm" style="display: none;">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nom de l'exercice</label>
                    <input type="text" name="new_exercise_name" id="newExerciseName" class="form-control"
                        placeholder="ex: Soulevé de terre roumain">
                </div>
                <div>
                    <label class="form-label small fw-bold">Groupe musculaire</label>
                    <select name="new_exercise_group" class="form-select">
                        <option value="">Sélectionner un groupe...</option>
                        <?php foreach ($muscleGroups as $mg): ?>
                        <option value="<?= esc($mg['libelle']) ?>"><?= esc($mg['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- En-têtes Colonnes -->
            <div class="d-flex justify-content-between px-2 mb-3 set-header">
                <div style="width: 15%;">Série</div>
                <div style="width: 35%;">Répétitions</div>
                <div style="width: 35%;">Poids (kg)</div>
                <div style="width: 10%;"></div>
            </div>

            <!-- Conteneur des Séries Dynamiques -->
            <div id="setsContainer">
                <div class="set-row d-flex align-items-center mb-3 gap-2">
                    <div class="fw-bold text-center set-number" style="width: 15%;">1</div>
                    <div style="width: 35%;">
                        <input type="number" name="reps[]" class="form-control dynamic-input text-center" value="12"
                            min="1" required>
                    </div>
                    <div style="width: 35%;">
                        <input type="number" step="0.25" name="weight[]" class="form-control dynamic-input text-center"
                            value="40" required>
                    </div>
                    <div style="width: 10%; text-align: center;">
                        <button type="button" class="delete-btn" onclick="deleteSet(this)" title="Supprimer">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path
                                    d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                                </path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Bouton Ajouter Série -->
            <button type="button"
                class="btn w-100 btn-add-set d-flex justify-content-center align-items-center gap-2 mb-4"
                onclick="addSet()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Ajouter une série
            </button>

            <div class="mb-4">
                <textarea name="notes" class="form-control bg-white" rows="2"
                    placeholder="Notes optionnelles (sensations...)"></textarea>
            </div>

            <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-pill">Sauvegarder
                l'entraînement</button>
        </form>
    </main>

    <script>
    // Afficher ou masquer les champs de création d'exercice
    function toggleNewExercise() {
        const select = document.getElementById('exerciseSelect');
        const div = document.getElementById('newExerciseDiv');
        const inputName = document.getElementById('newExerciseName');

        if (select.value === 'new') {
            div.style.display = 'block';
            inputName.required = true;
        } else {
            div.style.display = 'none';
            inputName.required = false;
        }
    }

    // Mettre à jour les numéros de ligne (1, 2, 3...)
    function updateSetNumbers() {
        const rows = document.querySelectorAll('.set-row');
        rows.forEach((row, index) => {
            row.querySelector('.set-number').innerText = index + 1;
        });
    }

    // Ajouter une nouvelle ligne de série
    function addSet() {
        const container = document.getElementById('setsContainer');
        const rows = document.querySelectorAll('.set-row');

        // Récupérer les valeurs de la dernière ligne pour les copier (UX)
        let lastReps = "";
        let lastWeight = "";
        if (rows.length > 0) {
            const lastRow = rows[rows.length - 1];
            lastReps = lastRow.querySelector('input[name="reps[]"]').value;
            lastWeight = lastRow.querySelector('input[name="weight[]"]').value;
        }

        const newRow = document.createElement('div');
        newRow.className = 'set-row d-flex align-items-center mb-3 gap-2';
        newRow.innerHTML = `
            <div class="fw-bold text-center set-number" style="width: 15%;"></div>
            <div style="width: 35%;">
                <input type="number" name="reps[]" class="form-control dynamic-input text-center" value="${lastReps}" min="1" required>
            </div>
            <div style="width: 35%;">
                <input type="number" step="0.25" name="weight[]" class="form-control dynamic-input text-center" value="${lastWeight}" required>
            </div>
            <div style="width: 10%; text-align: center;">
                <button type="button" class="delete-btn" onclick="deleteSet(this)" title="Supprimer">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                </button>
            </div>
        `;
        container.appendChild(newRow);
        updateSetNumbers();
    }

    // Supprimer une série
    function deleteSet(btn) {
        const rows = document.querySelectorAll('.set-row');
        if (rows.length > 1) {
            btn.closest('.set-row').remove();
            updateSetNumbers();
        } else {
            alert("Vous devez avoir au moins une série.");
        }
    }
    </script>
</body>

</html>