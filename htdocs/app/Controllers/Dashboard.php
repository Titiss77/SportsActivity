<?php

namespace App\Controllers;

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

        // Récupère TOUTES les activités des 6 derniers mois complets (depuis le 1er avril)
        $recentActivities = $activityModel->getActivitiesSince($userId, 6);
        
        $activitiesBySport = [];
        $recentWorkouts = [];
         
        $tonnageData = $strengthModel->getTotalVolumePerUser($userId); 
        $tonnageByDate = [];
        foreach ($tonnageData as $t) {
            $tonnageByDate[$t['workout_date']] = (float) $t['total_volume_kg'];
        }

        $localWorkouts = $workoutModel->where('user_id', $userId)->findAll();
        $localWorkoutIdsByDate = [];
        $localWorkoutIdsByStrava = [];
        foreach ($localWorkouts as $lw) { 
            $localWorkoutIdsByDate[date('Y-m-d', strtotime($lw['date']))] = $lw['id'];
            if (!empty($lw['strava_activity_id'])) {
                $localWorkoutIdsByStrava[$lw['strava_activity_id']] = $lw['id'];
            }
        }
        
        $rawActivitiesForChart = [];

        foreach ($recentActivities as $act) {
            $sport = $act['sport'] ?: 'Autre';
            $act['premium'] = json_decode($act['raw_data'], true);
            
            if ($sport === 'WeightTraining') {
                $dateKey = date('Y-m-d', strtotime($act['started_at']));
                $stravaId = $act['strava_activity_id'];
                
                $act['local_volume'] = $tonnageByDate[$dateKey] ?? null;
                $act['local_workout_id'] = $localWorkoutIdsByStrava[$stravaId] ?? ($localWorkoutIdsByDate[$dateKey] ?? null); 
                $recentWorkouts[] = $act;
                continue; 
            }

            // Ignorer les activités génériques sans distance (ex: "Workout" soirée à 0 km)
            if ($sport === 'Workout' && (float) $act['distance_meters'] <= 0) {
                continue;
            }
             
            // Normalisation des noms de sports
            if ($sport === 'Ride' || $sport === 'VirtualRide' || $sport === 'Bike') $sport = 'Vélo';
            elseif (stripos($sport, 'Run') !== false) $sport = 'Course à pied';
            elseif (stripos($sport, 'Swim') !== false || $sport === 'Open Water') $sport = 'Natation';
            elseif ($sport === 'Walk' || $sport === 'Hike') $sport = 'Marche';

            $activitiesBySport[$sport][] = $act;

            $rawActivitiesForChart[] = [
                'sport'    => $sport,
                'date'     => date('Y-m', strtotime($act['started_at'])),
                'distance' => (float) $act['distance_meters']
            ];
        }

        $monthlyDistance = $activityModel->getMonthlyDistance($userId, 6);

        $data = [
            'title'                 => 'Tableau de bord Global',
            'userSettings'          => $userSettings,
            'statsBySport'          => $activityModel->getStatsBySport($userId),
            'currentMonthStats'     => $activityModel->getCurrentMonthStats($userId),
            'activitiesBySport'     => $activitiesBySport,
            'monthlyDistance'       => $monthlyDistance,
            'rawActivitiesForChart' => $rawActivitiesForChart,
            'tonnageData'           => $tonnageData,
            'recentWorkouts'        => array_slice($recentWorkouts, 0, 5), // Garde les 5 plus récentes à l'affichage
        ];

        return view('dashboard/index', $data);
    }
}