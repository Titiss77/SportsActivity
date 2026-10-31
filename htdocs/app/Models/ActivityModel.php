<?php namespace App\Models;

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

    public function getRecent(int $userId, int$limit = 20): array
    {
        return $this->where('user_id',$userId)
                    ->orderBy('started_at', 'DESC')
                    ->findAll(max(1, min($limit, 500)));
    }

    public function getActivitiesSince(int $userId): array
    {
        $sql = "
            SELECT * 
            FROM strava_activities WHERE user_id = ?
            ORDER BY started_at DESC;
        ";
        return $this->db->query($sql, [$userId])->getResultArray();
    }

    public function getStatsBySport(int $userId): array
    {
        $sql = "
            SELECT sport,
                   COUNT(*) AS total_activities, 
                   ROUND(SUM(distance_meters)/1000, 2) AS total_distance, 
                   CONCAT(
                       FLOOR(COALESCE(SUM(duration_seconds), 0) / 3600), 'h ',
                       FLOOR((COALESCE(SUM(duration_seconds), 0) % 3600) / 60), 'min ',
                       COALESCE(SUM(duration_seconds), 0) % 60, 's'
                   ) AS total_duration
            FROM strava_activities
            WHERE user_id = ?
            GROUP BY sport
            ORDER BY total_activities DESC
        ";
        return $this->db->query($sql, [$userId])->getResultArray();
    }

    public function getAllStats(int $userId): array
    {
        $sql = "
            SELECT 
                 COUNT(*) AS total_activities, 
                 ROUND(SUM(distance_meters)/1000, 2) AS total_distance, 
                 CONCAT(
                    FLOOR(COALESCE(SUM(duration_seconds), 0) / 3600), 'h ',
                    FLOOR((COALESCE(SUM(duration_seconds), 0) % 3600) / 60), 'min ',
                    COALESCE(SUM(duration_seconds), 0) % 60, 's'
                ) AS total_duration
            FROM strava_activities
            WHERE user_id = ?
        ";
        return $this->db->query($sql, [$userId])->getRowArray();
    }

    public function getMonthlyDistance(int $userId, int$months = 6): array
    {
        $months = max(1, min($months, 24));$sql = "
            SELECT DATE_FORMAT(started_at, '%Y-%m') AS month_key,
                   COALESCE(SUM(distance_meters), 0) AS distance_meters
            FROM strava_activities
            WHERE user_id = ?
              AND sport NOT IN ('WeightTraining', 'Workout')
              AND started_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL ? MONTH), '%Y-%m-01')
            GROUP BY DATE_FORMAT(started_at, '%Y-%m')
            ORDER BY month_key ASC
        ";
        return $this->db->query($sql, [$userId,$months])->getResultArray();
    }

    public function upsertFromStrava(array $activity): bool
    {
        if (empty($activity['strava_activity_id'])) {
            return false;
        }

        $existing =$this->where('strava_activity_id', (string) $activity['strava_activity_id'])->first();$activity['synced_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            return $this->update($existing['id'],$activity);
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

    // ==========================================
    // AJOUTS PREMIUM
    // ==========================================

    public function getRecentCardioLoad(int $userId, int$days = 7): float
    {
        $sql = "SELECT COALESCE(SUM(duration_seconds), 0) / 3600 as total_hours FROM strava_activities WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND sport NOT IN ('WeightTraining')";
        return (float) $this->db->query($sql, [$userId,$days])->getRow()->total_hours;
    }

    public function getHeartRateZones(int $userId, int$days = 30): array
    {
        $sql = "
            SELECT 
                SUM(CASE WHEN avg_heart_rate < 120 THEN duration_seconds ELSE 0 END) as z1_active_recovery,
                SUM(CASE WHEN avg_heart_rate BETWEEN 120 AND 139 THEN duration_seconds ELSE 0 END) as z2_endurance,
                SUM(CASE WHEN avg_heart_rate BETWEEN 140 AND 159 THEN duration_seconds ELSE 0 END) as z3_tempo,
                SUM(CASE WHEN avg_heart_rate BETWEEN 160 AND 174 THEN duration_seconds ELSE 0 END) as z4_threshold,
                SUM(CASE WHEN avg_heart_rate >= 175 THEN duration_seconds ELSE 0 END) as z5_vo2max
            FROM strava_activities
            WHERE user_id = ? AND started_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY) AND avg_heart_rate IS NOT NULL AND avg_heart_rate > 0
        ";
        return $this->db->query($sql, [$userId,$days])->getRowArray();
    }

    public function getSwimMetrics(int $userId, int$limit = 5): array
    {
        $sql = "
            SELECT name, started_at, distance_meters, duration_seconds, 
                   (duration_seconds / (distance_meters / 100)) as pace_per_100m
            FROM strava_activities
            WHERE user_id = ? AND sport IN ('Swim', 'Natation', 'Open Water') AND distance_meters > 0
            ORDER BY started_at DESC LIMIT ?
        ";
        return $this->db->query($sql, [$userId,$limit])->getResultArray();
    }
}