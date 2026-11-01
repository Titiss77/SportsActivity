<?php

namespace App\Controllers;

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
                
                $userId = auth()->id();
                $db = \Config\Database::connect();
                $settings = $db->table('user_settings')->where('user_id', $userId)->get()->getRow();

                $tokensData = [
                    'user_id'              => $userId,
                    'strava_access_token'  => $data['access_token'],
                    'strava_refresh_token' => $data['refresh_token'],
                    'strava_expires_at'    => $data['expires_at'],
                    'strava_athlete_id'    => $data['athlete']['id']
                ];

                if ($settings) {
                    $db->table('user_settings')->where('user_id', $userId)->update($tokensData);
                } else {
                    $db->table('user_settings')->insert($tokensData);
                }
                
                return redirect()->to('/')->with('success', 'Compte Strava connecté avec succès !');
            } else {
                return redirect()->to('/')->with('error', 'Échec de l\'authentification auprès de Strava.');
            }

        } catch (\Exception $e) {
            return redirect()->to('/')->with('error', 'Erreur API : ' . $e->getMessage());
        }
    }

    public function disconnect()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();

        $db->table('user_settings')->where('user_id', $userId)->update([
            'strava_access_token'  => null,
            'strava_refresh_token' => null,
            'strava_expires_at'    => null,
            'strava_athlete_id'    => null
        ]);

        return redirect()->to('/profile')->with('success', 'Compte Strava déconnecté.');
    }

    public function sync()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();
        $settings = $db->table('user_settings')->where('user_id', $userId)->get()->getRow();

        if (!$settings || empty($settings->strava_access_token)) {
            return redirect()->to('/')->with('error', 'Vous devez d\'abord connecter votre compte Strava.');
        }

        $startDate = $this->request->getPost('start_date');
        $endDate = $this->request->getPost('end_date');

        if (!$startDate || !$endDate) {
            return redirect()->to('/')->with('error', 'Veuillez sélectionner une plage de dates.');
        }

        $after = strtotime($startDate . ' 00:00:00');
        $before = strtotime($endDate . ' 23:59:59');

        $client = \Config\Services::curlrequest();
        $activityModel = new ActivityModel();
        
        $importedCount = 0;
        $page = 1;
        $fetchedStravaIds = [];

        try {
            do {
                $response = $client->get('https://www.strava.com/api/v3/athlete/activities', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $settings->strava_access_token,
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
                    $db->table('user_settings')->where('user_id', $userId)->update(['strava_access_token' => null]);
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
                    $stravaId = (string) $stravaActivity['id'];
                    $fetchedStravaIds[] = $stravaId;

                    $activityData = [
                        'user_id'                 => $userId,
                        'strava_activity_id'      => $stravaId,
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

                    if ($activityModel->upsertFromStrava($activityData)) {
                        $importedCount++;
                    }
                }

                $page++;
            } while (count($activities) === 100);

            $deletedCount = 0;

            // SÉCURITÉ : Si l'API Strava a renvoyé une liste vide d'IDs (zéro activité récupérée)
            // On vérifie s'il y a des données locales sur cette même période.
            if (empty($fetchedStravaIds)) {
                $localCount = $activityModel->where('user_id', $userId)
                                            ->where('started_at >=', $startDate . ' 00:00:00')
                                            ->where('started_at <=', $endDate . ' 23:59:59')
                                            ->countAllResults();
                
                if ($localCount > 0) {
                    return redirect()->to('/')->with('success', "Synchronisation terminée. 0 activité importée. La suppression de vos {$localCount} activités locales a été annulée par sécurité car Strava n'a renvoyé aucune donnée.");
                }
            } else {
                // Nettoyage standard si l'API a bien répondu avec des éléments
                $deletedCount = $activityModel->deleteMissingActivities($userId, $startDate, $endDate, $fetchedStravaIds);
            }

            return redirect()->to('/')->with('success', "Synchronisation terminée. {$importedCount} activités importées. {$deletedCount} supprimées.");

        } catch (\Exception $e) {
            return redirect()->to('/')->with('error', 'Erreur lors de la synchronisation : ' . $e->getMessage());
        }
    }
}