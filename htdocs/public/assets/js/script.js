// ==========================================
// GRAPHIQUES DU TABLEAU DE BORD (DASHBOARD)
// ==========================================
const DashboardCharts = {
    stravaChartInstance: null,

    initStrava: function (ctx, allActivities, labels, values) {
        if (!ctx) return;
        const gradient = ctx.createLinearGradient(0, 0, 0, 250);
        gradient.addColorStop(0, 'rgba(252, 76, 2, 0.4)');
        gradient.addColorStop(1, 'rgba(252, 76, 2, 0.0)');

        this.stravaChartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: '#fc4c02',
                    backgroundColor: gradient,
                    borderWidth: 2, // Réduit pour un rendu plus léger
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#fc4c02',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointRadius: 3, // Réduit
                    pointHoverRadius: 5 // Réduit
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false, // Désactive l'animation pour un chargement immédiat
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false } }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function(context) { return context.parsed.y + ' km'; } } }
                }
            }
        });

        const filter = document.getElementById('sportFilter');
        if (filter) {
            filter.addEventListener('change', (e) => {
                this.updateStrava(e.target.value, allActivities, labels);
            });
        }
    },

    updateStrava: function (selectedSport, allActivities, defaultLabels) {
        let filteredDataByMonth = {};
        defaultLabels.forEach(label => { filteredDataByMonth[label] = 0; });

        // Optimisation de la boucle
        const len = allActivities.length;
        for (let i = 0; i < len; i++) {
            const act = allActivities[i];
            if (selectedSport === 'all' || act.sport === selectedSport) {
                if (filteredDataByMonth[act.date] !== undefined) {
                    filteredDataByMonth[act.date] += (act.distance / 1000);
                }
            }
        }

        const newValues = defaultLabels.map(label => Math.round(filteredDataByMonth[label] * 100) / 100);
        this.stravaChartInstance.data.datasets[0].data = newValues;
        this.stravaChartInstance.update();
    },

    initTonnage: function (ctx, labels, values) {
        if (!ctx) return;
        const maxTon = Math.max(...values);
        const suggestedMaxTon = maxTon > 0 ? maxTon * 1.2 : 100;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#198754',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 1,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                animation: false, // Désactivé
                scales: {
                    y: { beginAtZero: true, suggestedMax: suggestedMaxTon, grid: { color: '#f1f3f5' } },
                    x: { grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    },

    initRadar: function (ctx, labels, values) {
        if (!ctx) return;
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Volume (kg)',
                    data: values,
                    backgroundColor: 'rgba(25, 135, 84, 0.2)',
                    borderColor: '#198754',
                    pointBackgroundColor: '#198754',
                    borderWidth: 2
                }]
            },
            options: {
                animation: false, // Désactivé
                plugins: { legend: { display: false } },
                scales: {
                    r: { ticks: { display: false }, grid: { color: '#e9ecef' }, angleLines: { color: '#e9ecef' } }
                }
            }
        });
    }
};

// ==========================================
// GRAPHIQUE D'ÉVOLUTION D'UN EXERCICE
// ==========================================
const ExerciseCharts = {
    init: function (ctx, labels, totalWeights, weights, reps, oneRm) {
        if (!ctx) return;
        new Chart(ctx, {
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
                        pointRadius: 3,
                    },
                    {
                        label: 'Charge Réelle (kg)',
                        data: totalWeights,
                        borderColor: '#dd4814',
                        backgroundColor: 'rgba(221, 72, 20, 0.1)',
                        fill: true,
                        tension: 0.3,
                        pointRadius: 4,
                        pointHoverRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                animation: false, // Désactivé
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
                    y: { beginAtZero: false, title: { display: true, text: 'Poids (kg)' } }
                }
            }
        });
    }
};

// ==========================================
// DATATABLES (HISTORIQUE)
// ==========================================
document.addEventListener("DOMContentLoaded", function() {
    const table = document.getElementById("myAdminTable");
    if (table && typeof simpleDatatables !== 'undefined') {
        new simpleDatatables.DataTable(table, {
            searchable: true,
            fixedHeight: false,
            perPage: 15, // Optimisation: affiche moins de lignes d'un coup
            labels: {
                placeholder: "Rechercher...",
                perPage: "activités par page",
                noRows: "Aucune activité trouvée",
                info: "Affichage de {start} à {end} sur {rows} activités"
            }
        });
    }
});

// ==========================================
// FORMULAIRE DE MUSCULATION (LOGIQUE DYNAMIQUE)
// ==========================================

// Variables globales pour le formulaire afin d'éviter les recherches DOM multiples
let formState = {
    container: null,
    exerciseSelect: null,
    newExerciseDiv: null,
    newExerciseName: null
};

document.addEventListener("DOMContentLoaded", function() {
    formState.container = document.getElementById('setsContainer');
    formState.exerciseSelect = document.getElementById('exerciseSelect');
    formState.newExerciseDiv = document.getElementById('newExerciseDiv');
    formState.newExerciseName = document.getElementById('newExerciseName');
    
    // Délégation d'événements pour le conteneur des séries (meilleure performance que d'ajouter des onClick inline)
    if (formState.container) {
        formState.container.addEventListener('click', function(e) {
            const btn = e.target.closest('.delete-btn');
            if (btn) {
                deleteSet(btn);
            }
        });
    }
});

function toggleNewExercise() {
    if (formState.exerciseSelect && formState.newExerciseDiv && formState.newExerciseName) {
        if (formState.exerciseSelect.value === 'new') {
            formState.newExerciseDiv.style.display = 'block';
            formState.newExerciseName.required = true;
        } else {
            formState.newExerciseDiv.style.display = 'none';
            formState.newExerciseName.required = false;
        }
    }
}

function updateSetNumbers() {
    if (!formState.container) return;
    const rows = formState.container.getElementsByClassName('set-row');
    const len = rows.length;
    for (let i = 0; i < len; i++) {
        const numberEl = rows[i].querySelector('.set-number');
        if (numberEl) numberEl.innerText = i + 1;
    }
}

function addSet() {
    if (!formState.container) return;
    
    // Utiliser getElementsByClassName car c'est plus rapide que querySelectorAll
    const rows = formState.container.getElementsByClassName('set-row');
    
    let lastReps = "";
    let lastWeight = "";
    
    if (rows.length > 0) {
        const lastRow = rows[rows.length - 1];
        // Optimisation : recherche ciblée
        const inputs = lastRow.getElementsByTagName('input');
        if (inputs.length >= 2) {
             lastReps = inputs[0].value;
             lastWeight = inputs[1].value;
        }
    }
    
    const newRow = document.createElement('div');
    newRow.className = 'set-row d-flex align-items-center mb-3 gap-2';
    // Éviter l'événement inline onclick="deleteSet(this)" géré via délégation
    newRow.innerHTML = `
        <div class="fw-bold text-center set-number" style="width: 15%;"></div>
        <div style="width: 35%;">
            <input type="number" name="reps[]" class="form-control dynamic-input text-center" value="${lastReps}" min="1" required>
        </div>
        <div style="width: 35%;">
            <input type="number" step="0.25" name="weight[]" class="form-control dynamic-input text-center" value="${lastWeight}" required>
        </div>
        <div style="width: 10%; text-align: center;">
            <button type="button" class="delete-btn" title="Supprimer">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </button>
        </div>
    `;
    
    formState.container.appendChild(newRow);
    updateSetNumbers();
}

function deleteSet(btn) {
    if (!formState.container) return;
    const rows = formState.container.getElementsByClassName('set-row');
    if (rows.length > 1) {
        const row = btn.closest('.set-row');
        if (row) {
            row.remove();
            updateSetNumbers();
        }
    } else {
        alert("Vous devez avoir au moins une série.");
    }
}

function goToExercise(baseUrl) {
    const selectEl = document.getElementById('exerciseSelectGraph');
    if (selectEl && selectEl.value) {
        window.location.href = baseUrl + selectEl.value;
    }
}