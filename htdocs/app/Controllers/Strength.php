<?php namespace App\Controllers;

use App\Models\StrengthWorkoutModel;
use App\Models\WorkoutSetModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Strength extends BaseController
{
    // Affiche le détail d'une séance (liste des exos et séries)
    public function workout($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $workoutModel = new StrengthWorkoutModel();
        $workout = $workoutModel->find((int)$id);

        if (!$workout) {
            throw PageNotFoundException::forPageNotFound();
        }

        $setModel = new WorkoutSetModel();
        $rawSets = $setModel->getWorkoutDetails((int)$id);

        // Grouper les séries par exercice pour un affichage propre
        $exercises = [];
        foreach ($rawSets as $set) {
            $exId = $set['exercise_id'];
            if (!isset($exercises[$exId])) {
                $exercises[$exId] = [
                    'id'           => $exId,
                    'name'         => $set['exercise_name'],
                    'muscle_group' => $set['muscle_group'],
                    'sets'         => []
                ];
            }
            $exercises[$exId]['sets'][] = $set;
        }

        return view('strength/workout', [
            'title'     => 'Séance du ' . date('d/m/Y', strtotime($workout['date'])),
            'workout'   => $workout,
            'exercises' => $exercises
        ]);
    }

    // Affiche le graphique de progression d'un exercice précis
    public function exercise($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $db = \Config\Database::connect();
        $exercise = $db->table('exercises')->where('id', (int)$id)->get()->getRowArray();

        if (!$exercise) {
            throw PageNotFoundException::forPageNotFound();
        }

        $setModel = new WorkoutSetModel();
        $evolution = $setModel->getExerciseEvolution((int)$id);

        return view('strength/exercise', [
            'title'         => 'Progression : ' . $exercise['name'],
            'exercise'      => $exercise,
            'evolutionData' => $evolution
        ]);
    }

    // Affiche le formulaire pour ajouter du poids
    public function form()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $name = $this->request->getGet('name') ?? 'Nouvelle séance';

        $db = \Config\Database::connect();
        // Trier les exercices par ordre alphabétique pour faciliter la recherche
        $exercises = $db->table('exercises')->orderBy('name', 'ASC')->get()->getResultArray();

        return view('strength/form', [
            'title' => 'Renseigner : ' . $name,
            'date' => $date,
            'exercises' => $exercises
        ]);
    }

    // Sauvegarde les données du formulaire dynamique
    public function save()
    {
        $date = $this->request->getPost('date');
        $notes = $this->request->getPost('notes');
        
        $workoutModel = new StrengthWorkoutModel();
        
        // On crée la séance globale
        $workoutId = $workoutModel->insert([
            'date' => $date . ' 12:00:00', 
            'notes' => $notes ?: 'Séance saisie manuellement'
        ]);
        
        $exerciseId = $this->request->getPost('exercise_id');
        $newExerciseName = $this->request->getPost('new_exercise_name');
        $newExerciseGroup = $this->request->getPost('new_exercise_group') ?: 'Autre';

        $db = \Config\Database::connect();

        // Si l'utilisateur a choisi de créer un nouvel exercice
        if ($exerciseId === 'new' && !empty($newExerciseName)) {
            $db->table('exercises')->insert([
                'name' => trim($newExerciseName),
                'muscle_group' => trim($newExerciseGroup)
            ]);
            $exerciseId = $db->insertID();
        }

        // Récupération des tableaux envoyés par le formulaire
        $repsArray = $this->request->getPost('reps');
        $weightArray = $this->request->getPost('weight');

        if ($workoutId && $exerciseId && is_array($repsArray) && is_array($weightArray)) {
            $setModel = new WorkoutSetModel();
            
            $setNumber = 1;
            foreach ($repsArray as $index => $reps) {
                $weight = $weightArray[$index] ?? 0;
                
                // On ne sauvegarde que si les champs sont remplis
                if ($reps !== '' && $weight !== '') {
                    $setModel->insert([
                        'workout_id'  => $workoutId,
                        'exercise_id' => $exerciseId,
                        'set_number'  => $setNumber,
                        'reps'        => $reps,
                        'weight'      => $weight
                    ]);
                    $setNumber++;
                }
            }
        }

        return redirect()->to('/')->with('success', 'Séance de musculation enregistrée avec succès !');
    }
}