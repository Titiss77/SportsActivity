<?php 
$this->setData([
    'title' => 'Renseigner : ' . ($name ?? 'Nouvelle séance'),
    'backUrl' => site_url('/'),
    'backText' => 'Retour',
    'hideNavLinks' => true
]);
?>

<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container mb-5" style="max-width: 500px; margin: 0 auto;">
    <h1 class="h4 mb-1 fw-bold"><?= esc($title) ?></h1>
    <p class="text-muted small mb-4">Date : <?= date('d/m/Y', strtotime($date)) ?></p>

    <form action="<?= site_url('strength/save') ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="date" value="<?= esc($date) ?>">
        <input type="hidden" name="strava_id" value="<?= esc($stravaId ?? '') ?>">
        <input type="hidden" name="workout_id" value="<?= esc($workoutId ?? '') ?>">

        <div class="mb-4">
            <!-- Appel direct géré par attribut onchange laissé car il dépend d'un seul élément racine, mais JS centralisé -->
            <select name="exercise_id" id="exerciseSelect" class="form-select dynamic-input fw-semibold" required
                onchange="toggleNewExercise()">
                <option value="">Sélectionner un exercice...</option>
                <?php foreach ($exercises as $ex): ?>
                <option value="<?= esc($ex['id']) ?>"><?= esc($ex['name']) ?>
                    (<?= esc($ex['muscle_group'] ?? 'Autre') ?>)</option>
                <?php endforeach; ?>
                <option value="new" class="text-primary fw-bold">+ Créer un nouvel exercice...</option>
            </select>
        </div>

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

        <div class="d-flex justify-content-between px-2 mb-3 set-header">
            <div style="width: 15%;">Série</div>
            <div style="width: 35%;">Répétitions</div>
            <div style="width: 35%;">Poids (kg)</div>
            <div style="width: 10%;"></div>
        </div>

        <div id="setsContainer">
            <div class="set-row d-flex align-items-center mb-3 gap-2">
                <div class="fw-bold text-center set-number" style="width: 15%;">1</div>
                <div style="width: 35%;">
                    <input type="number" name="reps[]" class="form-control dynamic-input text-center" value="12" min="1"
                        required>
                </div>
                <div style="width: 35%;">
                    <input type="number" step="0.25" name="weight[]" class="form-control dynamic-input text-center"
                        value="40" required>
                </div>
                <div style="width: 10%; text-align: center;">
                    <!-- Supression de onClick inline, géré par le listener sur #setsContainer -->
                    <button type="button" class="delete-btn" title="Supprimer">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Appel direct gardé pour simplicité -->
        <button type="button" class="btn w-100 btn-add-set d-flex justify-content-center align-items-center gap-2 mb-4"
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

        <button type="submit" class="btn btn-dark w-100 py-3 fw-bold rounded-pill">Sauvegarder l'entraînement</button>
    </form>
</div>
<?= $this->endSection() ?>