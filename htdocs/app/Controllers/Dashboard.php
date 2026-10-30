<?php namespace App\Controllers;

use App\Models\ActivityModel;
use App\Models\WorkoutSetModel;
use App\Models\StrengthWorkoutModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $activityModel = new ActivityModel();
        $strengthModel = new WorkoutSetModel();
        $workoutModel  = new StrengthWorkoutModel();

        // On récupère les 30 dernières activités Strava pour bien les répartir
        $recentActivities = $activityModel->getRecent(30);
        
        $activitiesBySport = [];
        foreach ($recentActivities as $act) {
            $sport = $act['sport'] ?: 'Autre';
            $activitiesBySport[$sport][] = $act;
        }

        $data = [
            'title'             => 'Tableau de bord Global',
            'stats'             => $activityModel->getStats(),
            'statsBySport'      => $activityModel->getStatsBySport(),
            'activitiesBySport' => $activitiesBySport,
            'monthlyDistance'   => $activityModel->getMonthlyDistance(6),
            'tonnageData'       => $strengthModel->getTotalVolumePerWorkout(),
            'recentWorkouts'    => $workoutModel->getRecent(5),
        ];

        return view('dashboard/index', $data);
    }

    public function activities()
    {
        $model = new ActivityModel();
        return view('dashboard/activities', [
            'title'      => 'Historique Strava',
            'activities' => $model->getRecent(100),
        ]);
    }
}