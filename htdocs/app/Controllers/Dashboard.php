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

        $recentActivities = $activityModel->getRecent(30);
        
        $activitiesBySport = [];
        $recentWorkouts = []; 
        $tonnageData = $strengthModel->getTotalVolumePerWorkout(); 
        
        $tonnageByDate = [];
        foreach ($tonnageData as $t) {
            $tonnageByDate[$t['workout_date']] = $t['total_volume_kg'];
        }

        // Pour faire la liaison entre Strava et les séances locales
        $localWorkouts = $workoutModel->findAll();
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
                $act['local_workout_id'] = $localWorkoutIds[$dateKey] ?? null; // Pour le lien
                
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
            'stats'             => $activityModel->getStats(),
            'statsBySport'      => $activityModel->getStatsBySport(),
            'currentMonthStats' => $activityModel->getCurrentMonthStats(),
            'activitiesBySport' => $activitiesBySport,
            'monthlyDistance'   => $activityModel->getMonthlyDistance(6), 
            'tonnageData'       => $tonnageData,
            'recentWorkouts'    => $recentWorkouts, 
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