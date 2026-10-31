<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table            = 'strava_activities';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'strava_activity_id',
        'name',
        'sport',
        'started_at',
        'duration_seconds',
        'distance_meters',
        'avg_heart_rate',
        'max_heart_rate',
        'elevation_gain_m',
        'avg_speed_mps',
        'avg_pace_seconds_per_km',
        'raw_data',
        'synced_at',
    ];

    public function getRecent(int $userId, int $limit = 20): array
    {
        return $this->where('user_id', $userId)
                    ->orderBy('started_at', 'DESC')
                    ->findAll(max(1, min($limit, 500)));
    }

    /**
     * Récupère tout l'historique sur X mois complets (sans limite de 100 lignes)
     */
    public function getActivitiesSince(int $userId, int $months = 6): array
    {
        $sql = "
            SELECT *
            FROM strava_activities
            WHERE user_id = ?
              AND started_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
            ORDER BY started_at DESC
        ";
        return $this->db->query($sql, [$userId, $months])->getResultArray();
    }

    public function getStatsBySport(int $userId): array
    {
        $sql = "
            SELECT sport,
                   COUNT(*) AS total_activities, 
                   COALESCE(SUM(distance_meters), 0) AS total_distance_meters, 
                   COALESCE(SUM(duration_seconds), 0) AS total_duration_seconds
            FROM strava_activities
            WHERE user_id = ?
            GROUP BY sport
            ORDER BY total_activities DESC
        ";
        return $this->db->query($sql, [$userId])->getResultArray();
    }

    public function getMonthlyDistance(int $userId, int $months = 6): array
    {
        $months = max(1, min($months, 24));
        $sql = "
            SELECT DATE_FORMAT(started_at, '%Y-%m') AS month_key,
                   COALESCE(SUM(distance_meters), 0) AS distance_meters
            FROM strava_activities
            WHERE user_id = ? 
              AND sport NOT IN ('WeightTraining', 'Workout')
              AND started_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
            GROUP BY DATE_FORMAT(started_at, '%Y-%m')
            ORDER BY month_key ASC
        ";
        return $this->db->query($sql, [$userId, $months])->getResultArray();
    }

    public function upsertFromStrava(array $activity): bool
    {
        if (empty($activity['strava_activity_id'])) {
            return false;
        }

        $existing = $this->where('strava_activity_id', (string) $activity['strava_activity_id'])->first();
        $activity['synced_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            return $this->update($existing['id'], $activity);
        }

        return (bool) $this->insert($activity);
    }

    public function getCurrentMonthStats(int $userId): array
    {
        $sql = "
            SELECT sport, 
                   COUNT(*) as total_sessions, 
                   COALESCE(SUM(distance_meters), 0) AS total_distance,
                   COALESCE(SUM(duration_seconds), 0) AS total_time
            FROM strava_activities
            WHERE user_id = ? 
              AND MONTH(started_at) = MONTH(CURDATE()) 
              AND YEAR(started_at) = YEAR(CURDATE())
            GROUP BY sport
        ";
        return $this->db->query($sql, [$userId])->getResultArray();
    }
}