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
                    borderWidth: 3,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#fc4c02',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
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

        // Écouteur d'événement pour le filtre par sport
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

        allActivities.forEach(act => {
            if (selectedSport === 'all' || act.sport === selectedSport) {
                if (filteredDataByMonth.hasOwnProperty(act.date)) {
                    filteredDataByMonth[act.date] += (act.distance / 1000);
                }
            }
        });

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
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
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
            labels: {
                placeholder: "Rechercher une activité...",
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
function toggleNewExercise() {
    const select = document.getElementById('exerciseSelect');
    const div = document.getElementById('newExerciseDiv');
    const inputName = document.getElementById('newExerciseName');
    
    if (select && div && inputName) {
        if (select.value === 'new') {
            div.style.display = 'block';
            inputName.required = true;
        } else {
            div.style.display = 'none';
            inputName.required = false;
        }
    }
}

function updateSetNumbers() {
    const rows = document.querySelectorAll('.set-row');
    rows.forEach((row, index) => {
        const numberEl = row.querySelector('.set-number');
        if (numberEl) numberEl.innerText = index + 1;
    });
}

function addSet() {
    const container = document.getElementById('setsContainer');
    const rows = document.querySelectorAll('.set-row');
    
    let lastReps = "";
    let lastWeight = "";
    if (rows.length > 0) {
        const lastRow = rows[rows.length - 1];
        const repsInput = lastRow.querySelector('input[name="reps[]"]');
        const weightInput = lastRow.querySelector('input[name="weight[]"]');
        
        if (repsInput) lastReps = repsInput.value;
        if (weightInput) lastWeight = weightInput.value;
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
    
    if (container) {
        container.appendChild(newRow);
        updateSetNumbers();
    }
}

function deleteSet(btn) {
    const rows = document.querySelectorAll('.set-row');
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

// Navigation vers la page d'exercice (depuis index musculation)
function goToExercise(baseUrl) {
    const selectEl = document.getElementById('exerciseSelectGraph');
    if (selectEl && selectEl.value) {
        window.location.href = baseUrl + selectEl.value;
    }
}