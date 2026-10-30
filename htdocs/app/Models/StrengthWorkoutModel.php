<?php namespace App\Models;

use CodeIgniter\Model;

class StrengthWorkoutModel extends Model
{
    protected $table            = 'strength_workouts';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    // AJOUT des champs user_id et strava_activity_id pour autoriser leur insertion
    protected $allowedFields    = ['user_id', 'strava_activity_id', 'date', 'notes'];

    // Filtre les récentes par utilisateur
    public function getRecent(int $userId, int $limit = 5): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('date', 'DESC')
                    ->findAll($limit);
    }
}