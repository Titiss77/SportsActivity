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
const labels = <?= json_encode(array_column($evolutionData, 'workout_date')) ?>;
const totalWeights = <?= json_encode(array_column($evolutionData, 'total_weight_lifted')) ?>;
const weights = <?= json_encode(array_column($evolutionData, 'weight_used')) ?>;
const reps = <?= json_encode(array_column($evolutionData, 'total_reps')) ?>;
const oneRm = <?= json_encode(array_column($evolutionData, 'estimated_1rm')) ?>;

new Chart(document.getElementById('exerciseChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
                label: '1RM Estimé (kg)',
                data: oneRm,
                borderColor: '#198754',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                borderDash: [5, 5],
                fill: false,
                tension: 0.3,
                pointRadius: 4,
            },
            {
                label: 'Charge Réelle (kg)',
                data: totalWeights,
                borderColor: '#dd4814',
                backgroundColor: 'rgba(221, 72, 20, 0.1)',
                fill: true,
                tension: 0.3,
                pointRadius: 5,
                pointHoverRadius: 8
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let index = context.dataIndex;
                        if (context.datasetIndex === 0) {
                            return ` 1RM Estimé : ${oneRm[index]} kg`;
                        } else {
                            return ` Charge Vol. : ${totalWeights[index]} kg (${weights[index]} kg x ${reps[index]} reps)`;
                        }
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: false,
                title: {
                    display: true,
                    text: 'Poids (kg)'
                }
            }
        }
    }
});
</script>
<?php endif; ?>
<?= $this->endSection() ?>