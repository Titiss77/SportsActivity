<?php 
$this->setData([
    'title' => 'Progression : ' . $exercise['name'],
    'backUrl' => 'javascript:history.back()',
    'backText' => 'Retour à la séance',
    'hideNavLinks' => true
]);
?>

<?= $this->extend('layout/main') ?>

<?= $this->section('content') ?>
<div class="container py-4" style="max-width: 800px; margin: 0 auto;">
    <div class="mb-4">
        <h1 class="h3">Progression : <span class="text-primary"><?= esc($exercise['name']) ?></span></h1>
        <span class="badge bg-secondary"><?= esc($exercise['muscle_group']) ?></span>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h6 class="text-muted fw-bold mb-3" style="font-size: 0.9rem;">Évolution de la charge & 1-Rep Max (1RM)
                Estimé</h6>

            <?php if (empty($evolutionData)): ?>
            <p class="text-muted text-center py-4">Pas assez de données pour afficher une progression.</p>
            <?php else: ?>
            <canvas id="exerciseChart" height="120"></canvas>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (!empty($evolutionData)): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('exerciseChart');
    if (ctx) {
        ExerciseCharts.init(
            ctx,
            <?= json_encode(array_column($evolutionData, 'workout_date')) ?>,
            <?= json_encode(array_column($evolutionData, 'total_weight_lifted')) ?>,
            <?= json_encode(array_column($evolutionData, 'weight_used')) ?>,
            <?= json_encode(array_column($evolutionData, 'total_reps')) ?>,
            <?= json_encode(array_column($evolutionData, 'estimated_1rm')) ?>
        );
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>