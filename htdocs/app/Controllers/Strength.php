<?php namespace App\Controllers;

use App\Models\StrengthWorkoutModel;
use App\Models\WorkoutSetModel;
use App\Models\ActivityModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class Strength extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();
        $exercises = $db->table('exercises')->orderBy('name', 'ASC')->get()->getResultArray();
        
        return view('strength/index', [
            'title' => 'Progression Musculation',
            'exercises' => $exercises
        ]);
    }

    public function workout($id = null)
    {
        if ($id === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId = auth()->id();
        $workoutModel = new StrengthWorkoutModel();
        
        // Vérification renforcée pour s'assurer que la séance appartient bien à l'utilisateur
        $workout = $workoutModel->where('strava_activity_id', $id)->where('user_id', $userId)->first();
        if (!$workout && is_numeric($id)) {
            $workout = $workoutModel->where('id', (int)$id)->where('user_id', $userId)->first();
        }

        if (!$workout) {
            $activityModel = new ActivityModel();
            $stravaActivity = $activityModel->where('strava_activity_id', $id)->where('user_id', $userId)->first();

            if (!$stravaActivity) {
                throw PageNotFoundException::forPageNotFound();
            }

            $workout = [
                'id'                 => null, 
                'strava_activity_id' => $stravaActivity['strava_activity_id'],
                'date'               => $stravaActivity['started_at'],
                'notes'              => ''
            ];
        }

        $setModel = new WorkoutSetModel();
        $rawSets = [];
        
        if (!empty($workout['id'])) {
            $rawSets = $setModel->getWorkoutDetails((int)$workout['id']);
        }

        $exercises = [];
        $totalVolume = 0;
        
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
            $totalVolume += ($set['reps'] * $set['weight']);
        }

        return view('strength/workout', [
            'title'       => 'Détail de la séance',
            'workout'     => $workout,
            'exercises'   => $exercises,
            'totalVolume' => $totalVolume
        ]);
    }

    public function exercise($id = null)
    {
        if ($id === null || !is_numeric($id)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $userId = auth()->id();
        $db = \Config\Database::connect();
        $exercise = $db->table('exercises')->where('id', (int)$id)->get()->getRowArray();

        if (!$exercise) {
            throw PageNotFoundException::forPageNotFound();
        }

        $setModel = new WorkoutSetModel();
        // Modification ICI : On filtre sur l'utilisateur
        $evolution = $setModel->getExerciseEvolution($userId, (int)$id);

        return view('strength/exercise', [
            'title'         => 'Progression : ' . $exercise['name'],
            'exercise'      => $exercise,
            'evolutionData' => $evolution
        ]);
    }

    public function form()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');
        $name = $this->request->getGet('name') ?? 'Nouvelle séance';
        $stravaId = $this->request->getGet('strava_id') ?? '';
        $workoutId = $this->request->getGet('workout_id') ?? '';

        $db = \Config\Database::connect();
        $exercises = $db->table('exercises')->orderBy('name', 'ASC')->get()->getResultArray();

        return view('strength/form', [
            'title'     => 'Renseigner : ' . $name,
            'date'      => $date,
            'stravaId'  => $stravaId,
            'workoutId' => $workoutId,
            'exercises' => $exercises
        ]);
    }

    public function save()
    {
        $userId = auth()->id();
        $date = $this->request->getPost('date');
        $notes = $this->request->getPost('notes');
        $stravaId = $this->request->getPost('strava_id');
        $workoutId = $this->request->getPost('workout_id');
        
        $workoutModel = new StrengthWorkoutModel();
        
        // CORRECTION ICI : On insère l'ID de l'utilisateur connecté
        if (empty($workoutId)) {
            $workoutId = $workoutModel->insert([
                'user_id'            => $userId,
                'strava_activity_id' => $stravaId ?: null,
                'date'               => $date . ' 12:00:00', 
                'notes'              => $notes ?: 'Séance saisie manuellement'
            ]);
        }
        
        $exerciseId = $this->request->getPost('exercise_id');
        $newExerciseName = $this->request->getPost('new_exercise_name');
        $newExerciseGroup = $this->request->getPost('new_exercise_group') ?: 'Autre';

        $db = \Config\Database::connect();

        if ($exerciseId === 'new' && !empty($newExerciseName)) {
            $db->table('exercises')->insert([
                'name'         => trim($newExerciseName),
                'muscle_group' => trim($newExerciseGroup)
            ]);
            $exerciseId = $db->insertID();
        }

        $repsArray = $this->request->getPost('reps');
        $weightArray = $this->request->getPost('weight');

        if ($workoutId && $exerciseId && is_array($repsArray) && is_array($weightArray)) {
            $setModel = new WorkoutSetModel();
            
            $existingSetsCount = $setModel->where('workout_id', $workoutId)->where('exercise_id', $exerciseId)->countAllResults();
            $setNumber = $existingSetsCount + 1;
            
            foreach ($repsArray as $index => $reps) {
                $weight = $weightArray[$index] ?? 0;
                
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

        $redirectId = $stravaId ?: $workoutId;
        return redirect()->to('/workout/' . $redirectId)->with('success', 'Séries enregistrées avec succès !');
    }
}