<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-semibold" href="javascript:history.back()"> Retour à la séance</a>
        </div>
    </nav>
    <main class="container py-4" style="max-width: 800px;">
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
    </main>
    <?php if (!empty($evolutionData)): ?>
    <script>
    const labels = <?= json_encode(array_column($evolutionData, 'workout_date')) ?>;
    const totalWeights = <?= json_encode(array_column($evolutionData, 'total_weight_lifted')) ?>;
    const weights = <?= json_encode(array_column($evolutionData, 'weight_used')) ?>;
    const reps = <?= json_encode(array_column($evolutionData, 'total_reps')) ?>;

    // NOUVEAU : 1RM Estimé
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
                    borderDash: [5, 5], // Tirets pour différencier
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
</body>

</html>