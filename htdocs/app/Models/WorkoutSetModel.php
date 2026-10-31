<?php namespace App\Models;

use CodeIgniter\Model;

class WorkoutSetModel extends Model
{
    protected $table            = 'workout_sets';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    
    protected $allowedFields = [
        'workout_id', 
        'exercise_id', 
        'set_number', 
        'reps', 
        'weight'
    ];

    public function getTotalVolumePerUser(int $userId): array
    {
        $sql = "
            SELECT 
                 DATE_FORMAT(COALESCE(sa.started_at, sw.date), '%Y-%m-%d') as workout_date,
                SUM(ws.reps * ws.weight) as total_volume_kg
            FROM strength_workouts sw
            JOIN workout_sets ws ON sw.id = ws.workout_id
            LEFT JOIN strava_activities sa 
                    ON sw.strava_activity_id = sa.strava_activity_id AND sa.user_id = sw.user_id
            WHERE sw.user_id = ?
            GROUP BY workout_date
            ORDER BY workout_date ASC
        ";
        return $this->db->query($sql, [$userId])->getResultArray();
    }

    public function getExerciseEvolution(int $userId, int $exerciseId): array
    {
        // AJOUT : estimated_1rm basé sur la formule d'Epley
        $sql = "
            SELECT 
                 DATE_FORMAT(COALESCE(sa.started_at, sw.date), '%Y-%m-%d') as workout_date,
                ROUND(SUM(ws.weight * ws.reps), 0) as total_weight_lifted,
                ROUND(AVG(ws.weight), 0) as weight_used,
                SUM(ws.reps) as total_reps,
                MAX(ROUND(ws.weight * (1 + (ws.reps / 30)), 1)) as estimated_1rm
            FROM strength_workouts sw
            JOIN workout_sets ws ON sw.id = ws.workout_id
            LEFT JOIN strava_activities sa 
                    ON sw.strava_activity_id = sa.strava_activity_id AND sa.user_id = sw.user_id
            WHERE ws.exercise_id = ? AND sw.user_id = ?
            GROUP BY workout_date
            ORDER BY workout_date ASC
        ";
        return $this->db->query($sql, [$exerciseId, $userId])->getResultArray();
    }

    public function getWorkoutDetails(int $workoutId): array
    {
        $sql = "
            SELECT 
                 ws.set_number, ws.reps, ws.weight,
                e.id as exercise_id, e.name as exercise_name, e.muscle_group
            FROM workout_sets ws
            JOIN exercises e ON ws.exercise_id = e.id
            WHERE ws.workout_id = ?
            ORDER BY ws.exercise_id, ws.set_number ASC
        ";
        return $this->db->query($sql, [$workoutId])->getResultArray();
    }

    // ==========================================
    // AJOUTS PREMIUM
    // ==========================================

    public function getRecentStrengthLoad(int $userId, int $days = 7): float
    {
        $sql = "
            SELECT COALESCE(SUM(ws.reps * ws.weight), 0) as total_volume
            FROM strength_workouts sw
            JOIN workout_sets ws ON sw.id = ws.workout_id
            WHERE sw.user_id = ? AND sw.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        return (float) $this->db->query($sql, [$userId, $days])->getRow()->total_volume;
    }

    public function getMuscleHeatmap(int $userId, int $days = 15): array
    {
        $sql = "
            SELECT e.muscle_group, COALESCE(SUM(ws.reps * ws.weight), 0) as volume
            FROM strength_workouts sw
            JOIN workout_sets ws ON sw.id = ws.workout_id
            JOIN exercises e ON ws.exercise_id = e.id
            WHERE sw.user_id = ? AND sw.date >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND e.muscle_group IS NOT NULL
            GROUP BY e.muscle_group
        ";
        return $this->db->query($sql, [$userId, $days])->getResultArray();
    }
}