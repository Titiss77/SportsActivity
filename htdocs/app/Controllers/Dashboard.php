<?php namespace App\Controllers;

use App\Models\ActivityModel;
use App\Models\WorkoutSetModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $activityModel = new ActivityModel();
        $strengthModel = new WorkoutSetModel();

        // Récupérer les 30 dernières activités pour Strava
        $recentActivities = $activityModel->getRecent(30);
        
        $activitiesBySport = [];
        $recentWorkouts = []; // Séances de musculation Strava (WeightTraining)
        $tonnageData = $strengthModel->getTotalVolumePerWorkout(); // Graphique de tonnage (géré en interne)
        
        foreach ($recentActivities as $act) {
            $sport = $act['sport'] ?: 'Autre';
            
            // Extraction des données Premium
            $act['premium'] = json_decode($act['raw_data'], true);
            
            // Traduction et séparation de la Musculation
            if ($sport === 'WeightTraining') {
                $recentWorkouts[] = $act;
                continue; // Ne pas afficher dans la section "Cardio"
            } 
            
            if ($sport === 'Ride' || $sport === 'VirtualRide') {
                $sport = 'Vélo';
            } elseif ($sport === 'Run') {
                $sport = 'Course à pied';
            } elseif ($sport === 'Swim') {
                $sport = 'Natation';
            }

            $activitiesBySport[$sport][] = $act;
        }

        $data = [
            'title'             => 'Tableau de bord Global',
            'stats'             => $activityModel->getStats(),
            'statsBySport'      => $activityModel->getStatsBySport(),
            'currentMonthStats' => $activityModel->getCurrentMonthStats(),
            'activitiesBySport' => $activitiesBySport,
            'monthlyDistance'   => $activityModel->getMonthlyDistance(6), // Historique cardio
            'tonnageData'       => $tonnageData,
            'recentWorkouts'    => $recentWorkouts, // Les séances Strava 'WeightTraining' avec les vraies dates
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