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

        // Récupérer les paramètres et les objectifs de l'utilisateur
        $userSettings = $db->table('user_settings')->where('user_id', $userId)->get()->getRowArray();
        
        // S'il n'a pas de paramètres, on définit des défauts pour éviter les erreurs
        if (!$userSettings) {
            $userSettings = [
                'strava_access_token' => null,
                'goal_ride_km' => 300,
                'goal_run_km' => 60,
                'goal_swim_km' => 20,
                'goal_walk_km' => 20,
            ];
        }

        $recentActivities = $activityModel->getRecent($userId, 30);
        
        $activitiesBySport = [];
        $recentWorkouts = []; 
        
        // On récupère le tonnage de l'utilisateur via une requête join (filtré par userId)
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
            
            if ($sport === 'Ride' || $sport === 'VirtualRide') $sport = 'Vélo';
            elseif ($sport === 'Run') $sport = 'Course à pied';
            elseif ($sport === 'Swim') $sport = 'Natation';
            elseif ($sport === 'Walk') $sport = 'Marche';

            $activitiesBySport[$sport][] = $act;
        }

        $data = [
            'title'             => 'Tableau de bord Global',
            'userSettings'      => $userSettings,
            'statsBySport'      => $activityModel->getStatsBySport($userId),
            'currentMonthStats' => $activityModel->getCurrentMonthStats($userId),
            'activitiesBySport' => $activitiesBySport,
            'monthlyDistance'   => $activityModel->getMonthlyDistance($userId, 6), 
            'tonnageData'       => $tonnageData,
            'recentWorkouts'    => $recentWorkouts, 
        ];

        return view('dashboard/index', $data);
    }
}