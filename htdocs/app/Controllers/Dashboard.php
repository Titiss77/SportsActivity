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
        
        $userSettings = $db->table('user_settings')->where('user_id', $userId)->get()->getRowArray();
        
        if (!$userSettings) {
            $userSettings = [
                'strava_access_token' => null,
                'goal_ride_km' => 300,
                'goal_run_km' => 60,
                'goal_swim_km' => 20,
                'goal_walk_km' => 20,
            ];
        } else {
            // Vérification et rafraîchissement automatique du token Strava
            if (!empty($userSettings['strava_refresh_token'])) {
                if (empty($userSettings['strava_expires_at']) || $userSettings['strava_expires_at'] < (time() + 300)) {
                    $client = \Config\Services::curlrequest();
                    try {
                        $response = $client->post('https://www.strava.com/oauth/token', [
                            'form_params' => [
                                'client_id'     => env('STRAVA_CLIENT_ID'),
                                'client_secret' => env('STRAVA_CLIENT_SECRET'),
                                'grant_type'    => 'refresh_token',
                                'refresh_token' => $userSettings['strava_refresh_token'],
                            ],
                            'http_errors' => false
                        ]);

                        if ($response->getStatusCode() === 200) {
                            $data = json_decode($response->getBody(), true);
                            $updateData = [
                                'strava_access_token'  => $data['access_token'],
                                'strava_refresh_token' => $data['refresh_token'],
                                'strava_expires_at'    => $data['expires_at']
                            ];
                            $db->table('user_settings')->where('user_id', $userId)->update($updateData);
                            $userSettings = array_merge($userSettings, $updateData);
                        } else {
                            $clearData = [
                                'strava_access_token'  => null,
                                'strava_refresh_token' => null,
                                'strava_expires_at'    => null
                            ];
                            $db->table('user_settings')->where('user_id', $userId)->update($clearData);
                            $userSettings = array_merge($userSettings, $clearData);
                        }
                    } catch (\Exception $e) {
                        $userSettings['strava_access_token'] = null;
                    }
                }
            }
        }
        
        $activityModel = new ActivityModel();
        $strengthModel = new WorkoutSetModel();
        $workoutModel  = new StrengthWorkoutModel();
        $tradModel     = new TradModel();
        
        // CHARGEMENT OPTIMISÉ : 1 seule requête pour toutes les traductions (Correction N+1)
        $allTrads = $tradModel->getAllTrads();
        
        // OPTIMISATION FRONT-END : On limite aux 6 derniers mois pour correspondre au graphique et alléger le JSON JavaScript
        $dateLimit = date('Y-m-d H:i:s', strtotime('-6 months'));
        $recentActivities = $activityModel->where('user_id', $userId)
                                          ->where('started_at >=', $dateLimit)
                                          ->orderBy('started_at', 'DESC')
                                          ->findAll();
        
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
            
            $sportTraduit = $allTrads[$sport] ?? $sport;
            
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
            $monthStat['sport_trad'] = $allTrads[$sport] ?? $sport;
        }
        unset($monthStat);

        $muscleHeatmap = $strengthModel->getMuscleHeatmap($userId, 15);
        $streakStats = $activityModel->getStreakStats($userId);

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
            'muscleHeatmap'         => $muscleHeatmap,
            'streakStats'           => $streakStats,
        ];
        
        return view('dashboard/index', $data);
    }

    public function activities()
    {
        $userId = auth()->id();
        $activityModel = new ActivityModel();
        $tradModel = new TradModel();
        
        $allTrads = $tradModel->getAllTrads();
        
        $activities = $activityModel->getActivitiesSince($userId);
        $globalStats = $activityModel->getStatsBySport($userId);
        $AllStats = $activityModel->getAllStats($userId);

        if (empty($activities)) {
            $activities = [];
        }
        
        if ($activities) {
            foreach ($activities as &$activity) {
                $sport = $activity['sport'] ?: 'Autre';
                $activity['sport'] = $allTrads[$sport] ?? "$sport (non traduit)";
            }
            unset($activity);
        }

        if ($globalStats) {
            foreach ($globalStats as &$global) {
                $sport = $global['sport'] ?: 'Autre';
                $global['sport'] = $allTrads[$sport] ?? "$sport (non traduit)";
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