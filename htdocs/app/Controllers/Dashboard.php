<?php namespace App\Controllers;

use App\Models\ActivityModel;
use App\Models\WorkoutSetModel;
use App\Models\StrengthWorkoutModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();
        
        $activityModel = new ActivityModel();
        $strengthModel = new WorkoutSetModel();
        $workoutModel  = new StrengthWorkoutModel();

        $userSettings = $db->table('user_settings')->where('user_id', $userId)->get()->getRowArray();
        
        if (!$userSettings) {
            $userSettings = [
                'strava_access_token' => null,
                'goal_ride_km' => 300,
                'goal_run_km' => 60,
                'goal_swim_km' => 20,
                'goal_walk_km' => 20,
            ];
        }

        $recentActivities = $activityModel->getRecent($userId, 100); // On récupère plus d'historique pour le graphe filtrable
        
        $activitiesBySport = [];
        $recentWorkouts = []; 
        
        $tonnageData = $strengthModel->getTotalVolumePerUser($userId); 
        $tonnageByDate = [];
        foreach ($tonnageData as $t) {
            $tonnageByDate[$t['workout_date']] = $t['total_volume_kg'];
        }

        $localWorkouts = $workoutModel->where('user_id', $userId)->findAll();
        $localWorkoutIds = [];
        foreach($localWorkouts as $lw) {
             $localWorkoutIds[date('Y-m-d', strtotime($lw['date']))] = $lw['id'];
        }
        
        // Tableau brut de toutes les activités pour alimenter le filtre JS du graphique
        $rawActivitiesForChart = [];

        foreach ($recentActivities as $act) {
            $sport = $act['sport'] ?: 'Autre';
            $act['premium'] = json_decode($act['raw_data'], true);
            
            if ($sport === 'WeightTraining') {
                $dateKey = date('Y-m-d', strtotime($act['started_at']));
                $act['local_volume'] = $tonnageByDate[$dateKey] ?? null;
                $act['local_workout_id'] = $localWorkoutIds[$dateKey] ?? null; 
                $recentWorkouts[] = $act;
                continue; 
            } 
            
            // Normalisation des noms de sports
            if ($sport === 'Ride' || $sport === 'VirtualRide') $sport = 'Vélo';
            elseif ($sport === 'Run') $sport = 'Course à pied';
            elseif ($sport === 'Swim') $sport = 'Natation';
            elseif ($sport === 'Walk') $sport = 'Marche';

            $activitiesBySport[$sport][] = $act;

            // Données formatées pour le graphique dynamique
            $rawActivitiesForChart[] = [
                'sport'    => $sport,
                'date'     => date('Y-m', strtotime($act['started_at'])), // format Mois (ex: 2026-10)
                'distance' => (float) $act['distance_meters']
            ];
        }

        // Calcul des distances mensuelles globales pour l'option "Tous les sports"
        $monthlyDistance = $activityModel->getMonthlyDistance($userId, 6);

        $data = [
            'title'                  => 'Tableau de bord Global',
            'userSettings'           => $userSettings,
            'statsBySport'           => $activityModel->getStatsBySport($userId),
            'currentMonthStats'      => $activityModel->getCurrentMonthStats($userId),
            'activitiesBySport'      => $activitiesBySport,
            'monthlyDistance'        => $monthlyDistance,
            'rawActivitiesForChart' => $rawActivitiesForChart, // Transmis à la vue
            'tonnageData'            => $tonnageData,
            'recentWorkouts'         => $recentWorkouts, 
        ];

        return view('dashboard/index', $data);
    }
}