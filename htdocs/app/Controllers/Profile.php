<?php namespace App\Controllers;

class Profile extends BaseController
{
    public function index()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();
        
        $settings = $db->table('user_settings')->where('user_id', $userId)->get()->getRowArray();
        $user = auth()->user();

        return view('profile/index', [
            'title'    => 'Mon Profil',
            'settings' => $settings,
            'user'     => $user
        ]);
    }

    public function updateGoals()
    {
        $userId = auth()->id();
        $db = \Config\Database::connect();
        
        $data = [
            'goal_ride_km' => (int) $this->request->getPost('goal_ride_km'),
            'goal_run_km'  => (int) $this->request->getPost('goal_run_km'),
            'goal_swim_km' => (int) $this->request->getPost('goal_swim_km'),
            'goal_walk_km' => (int) $this->request->getPost('goal_walk_km'),
        ];

        $exists = $db->table('user_settings')->where('user_id', $userId)->countAllResults();
        
        if ($exists) {
            $db->table('user_settings')->where('user_id', $userId)->update($data);
        } else {
            $data['user_id'] = $userId;
            $db->table('user_settings')->insert($data);
        }

        return redirect()->to('/profile')->with('success', 'Objectifs mis à jour avec succès.');
    }

    public function updateAccount()
    {
        // En utilisant Shield, l'update du mot de passe passe par l'entité User.
        $user = auth()->user();
        
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $user->fill([
                'password' => $password
            ]);
            $users = auth()->getProvider();
            $users->save($user);
            return redirect()->to('/profile')->with('success', 'Mot de passe mis à jour.');
        }

        return redirect()->to('/profile');
    }
}