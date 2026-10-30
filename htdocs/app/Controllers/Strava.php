<?php namespace App\Controllers;

use App\Models\ActivityModel;

class Strava extends BaseController
{
    public function connect()
    {
        $clientId = env('STRAVA_CLIENT_ID');
        $redirectUri = env('STRAVA_REDIRECT_URI');
        
        $scope = 'activity:read_all';
        $authUrl = "https://www.strava.com/oauth/authorize?client_id={$clientId}&response_type=code&redirect_uri={$redirectUri}&approval_prompt=force&scope={$scope}";
        
        return redirect()->to($authUrl);
    }

    public function callback()
    {
        $code = $this->request->getGet('code');
        $error = $this->request->getGet('error');

        if ($error === 'access_denied') {
            return redirect()->to('/')->with('error', 'Accès refusé par l\'utilisateur.');
        }

        if (!$code) {
            return redirect()->to('/')->with('error', 'Code d\'autorisation manquant.');
        }

        $client = \Config\Services::curlrequest();
        
        try {
            $response = $client->post('https://www.strava.com/oauth/token', [
                'form_params' => [
                    'client_id'     => env('STRAVA_CLIENT_ID'),
                    'client_secret' => env('STRAVA_CLIENT_SECRET'),
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                ],
                'http_errors' => false
            ]);

            if ($response->getStatusCode() === 200) {
                $data = json_decode($response->getBody(), true);
                
                // Sauvegarde temporaire en session
                session()->set('strava_access_token', $data['access_token']);
                session()->set('strava_refresh_token', $data['refresh_token']);
                session()->set('strava_expires_at', $data['expires_at']);
                session()->set('strava_athlete_id', $data['athlete']['id']);
                
                return redirect()->to('/')->with('success', 'Compte Strava connecté avec succès !');
            } else {
                return redirect()->to('/')->with('error', 'Échec de l\'authentification auprès de Strava.');
            }
        } catch (\Exception $e) {
            return redirect()->to('/')->with('error', 'Erreur API : ' . $e->getMessage());
        }
    }

    public function sync()
    {
        // Vérifier si l'utilisateur est connecté à Strava
        if (!session()->has('strava_access_token')) {
            return redirect()->to('/')->with('error', 'Vous devez d\'abord connecter votre compte Strava.');
        }

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (!$startDate || !$endDate) {
            return redirect()->to('/')->with('error', 'Veuillez sélectionner une plage de dates.');
        }

        // Convertir les dates en timestamp UNIX (requis par l'API Strava)
        $after = strtotime($startDate . ' 00:00:00');
        $before = strtotime($endDate . ' 23:59:59');

        $accessToken = session()->get('strava_access_token');
        $client = \Config\Services::curlrequest();
        $activityModel = new ActivityModel();
        
        $importedCount = 0;
        $page = 1;

        try {
            do {
                // Requête API Strava (pagination par 100 pour récupérer toutes les activités sur la période)
                $response = $client->get('https://www.strava.com/api/v3/athlete/activities', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                    ],
                    'query' => [
                        'before'   => $before,
                        'after'    => $after,
                        'page'     => $page,
                        'per_page' => 100
                    ],
                    'http_errors' => false
                ]);

                if ($response->getStatusCode() === 401) {
                    // Si le jeton est expiré
                    session()->remove('strava_access_token');
                    return redirect()->to('/')->with('error', 'Votre session Strava a expiré. Veuillez vous reconnecter.');
                }

                if ($response->getStatusCode() !== 200) {
                    return redirect()->to('/')->with('error', 'Erreur lors de la récupération des activités Strava.');
                }

                $activities = json_decode($response->getBody(), true);
                
                if (empty($activities)) {
                    break;
                }

                foreach ($activities as $stravaActivity) {
                    // Mapping des données Strava vers votre base existante (prévue initialement pour Coros)
                    $activityData = [
                        'coros_activity_id'       => 'STRAVA_' . $stravaActivity['id'],
                        'name'                    => $stravaActivity['name'] ?? 'Activité Strava',
                        'sport'                   => $stravaActivity['sport_type'] ?? ($stravaActivity['type'] ?? 'Inconnu'),
                        'started_at'              => date('Y-m-d H:i:s', strtotime($stravaActivity['start_date_local'])),
                        'duration_seconds'        => $stravaActivity['moving_time'] ?? ($stravaActivity['elapsed_time'] ?? 0),
                        'distance_meters'         => $stravaActivity['distance'] ?? 0,
                        'avg_heart_rate'          => $stravaActivity['average_heartrate'] ?? null,
                        'max_heart_rate'          => $stravaActivity['max_heartrate'] ?? null,
                        'elevation_gain_m'        => $stravaActivity['total_elevation_gain'] ?? null,
                        'avg_speed_mps'           => $stravaActivity['average_speed'] ?? null,
                        'raw_data'                => json_encode($stravaActivity)
                    ];

                    // Sauvegarde dans la base de données
                    if ($activityModel->upsertFromCoros($activityData)) {
                        $importedCount++;
                    }
                }

                $page++;
            } while (count($activities) === 100);

            return redirect()->to('/')->with('success', "Synchronisation terminée. {$importedCount} activités importées ou mises à jour.");

        } catch (\Exception $e) {
            return redirect()->to('/')->with('error', 'Erreur lors de la synchronisation : ' . $e->getMessage());
        }
    }
}