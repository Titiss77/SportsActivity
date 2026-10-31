<?php namespace App\Controllers;

use App\Models\ActivityModel;
use App\Models\WorkoutSetModel;
use App\Models\TradModel;
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
        $tradModel     = new TradModel();
        
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

            if ($sport === 'Workout' && (float) $act['distance_meters'] <= 0) {
                continue;
            }
            
            $sportTraduit = $tradModel->getTrad($sport) ?? $sport;
            
            $activitiesBySport[$sportTraduit][] = $act;
            $rawActivitiesForChart[] = [
                'sport'    => $sportTraduit,
                'date'     => date('Y-m', strtotime($act['started_at'])),
                'distance' => (float) $act['distance_meters']
            ];
        }

        $monthlyDistance = $activityModel->getMonthlyDistance($userId, 6);
        $currentMonthStats = $activityModel->getCurrentMonthStats($userId);
        
        foreach ($currentMonthStats as &$monthStat) {
            $sport = $monthStat['sport'] ?: 'Autre';
            $monthStat['sport_trad'] = $tradModel->getTrad($sport) ?? $sport;
        }
        unset($monthStat);

        $data = [
            'title'                 => 'Tableau de bord Global',
            'userSettings'          => $userSettings,
            'statsBySport'          => $activityModel->getStatsBySport($userId),
            'currentMonthStats'     => $currentMonthStats,
            'activitiesBySport'     => $activitiesBySport,
            'monthlyDistance'       => $monthlyDistance,
            'rawActivitiesForChart' => $rawActivitiesForChart,
            'tonnageData'           => $tonnageData,
            'recentWorkouts'        => array_slice($recentWorkouts, 0, 5),
        ];

        return view('dashboard/index', $data);
    }

    public function activities()
    {
        $userId = auth()->id();
        $activityModel = new ActivityModel();
        $tradModel = new TradModel();
        
        $activities = $activityModel->getActivitiesSince($userId);
        $globalStats = $activityModel->getStatsBySport($userId);
        $AllStats = $activityModel->getAllStats($userId);

        if (empty($activities)) {
            $activities = [];
        }
        
        if ($activities) {
            foreach ($activities as &$activity) {
                $sport = $activity['sport'] ?: 'Autre';
                $activity['sport'] = $tradModel->getTrad($sport) ?? "$sport (non traduit)";
            }
            unset($activity);
        }

        if ($globalStats) {
            foreach ($globalStats as &$global) {
                $sport = $global['sport'] ?: 'Autre';
                $global['sport'] = $tradModel->getTrad($sport) ?? "$sport (non traduit)";
            }
            unset($global);
        }
        
        $data = [
            'title'      => 'Historique des Activités',
            'allGlobal'  => $AllStats,
            'globalStats' => $globalStats,
            'activities' => $activities,
        ];
        
        return view('dashboard/activities', $data);
    }
}