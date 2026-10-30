<?php namespace App\Controllers;

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
                
                // Sauvegarde temporaire en session (à basculer en base de données pour le multi-utilisateur)
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
}