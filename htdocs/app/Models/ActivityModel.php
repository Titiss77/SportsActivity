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

    // ==========================================
    // SÉRIES, RECORDS & ZONES
    // ==========================================

    public function getStreakStats(int $userId): array
    {
        // On récupère toutes les dates d'activités (en excluant les activités de moins d'1 minute souvent ignorées par Strava)
        $sql = "
            SELECT started_at
            FROM strava_activities 
            WHERE user_id = ? 
              AND started_at IS NOT NULL
              AND duration_seconds >= 60
            ORDER BY started_at DESC
        ";
        $activities = $this->db->query($sql, [$userId])->getResultArray();
        
        if (empty($activities)) {
            return ['streak_weeks' => 0, 'total_in_streak' => 0];
        }
        
        // On groupe les activités par "Année + Numéro de semaine ISO" (ex: 202644)
        $weeksMap = [];
        foreach ($activities as $act) {
            $dt = new \DateTime($act['started_at']);
            // 'o' = Année ISO, 'W' = Semaine ISO (01-53) (Commence le lundi)
            $yw = $dt->format('oW'); 
            
            if (!isset($weeksMap[$yw])) {
                $weeksMap[$yw] = 0;
            }
            $weeksMap[$yw]++;
        }
        
        $streakWeeks = 0;
        $totalInStreak = 0;
        
        $iterDate = new \DateTime();
        $currentYw = $iterDate->format('oW');
        
        // Si aucune activité n'a ENCORE été faite cette semaine, on regarde la semaine dernière
        if (!isset($weeksMap[$currentYw])) {
            $iterDate->modify('-1 week');
            $iterYw = $iterDate->format('oW');
            
            if (!isset($weeksMap[$iterYw])) {
                return ['streak_weeks' => 0, 'total_in_streak' => 0];
            }
        } else {
            $iterYw = $currentYw;
        }
        
        // On remonte le temps semaine après semaine de façon continue
        while (isset($weeksMap[$iterYw])) {
            $streakWeeks++;
            $totalInStreak += $weeksMap[$iterYw];
            
            $iterDate->modify('-1 week');
            $iterYw = $iterDate->format('oW');
        }
        
        return [
            'streak_weeks' => $streakWeeks,
            'total_in_streak' => $totalInStreak
        ];
    }

    public function getSportDetails(int $userId): array
    {
        return [
            'Course à pied' => [
                'records' => [
                    '400 m' => '1:10',
                    '1/2 mille' => '2:47',
                    '1 km' => '4:24',
                    '1 mille' => '7:19',
                    '2 milles' => '15:02',
                    '5 km' => '23:21',
                    '10 km' => '53:52',
                    '15 km' => '1:19:59',
                    '10 milles' => '1:31:54',
                    '20 km' => '1:57:20',
                    'Semi-marathon' => '2:03:48'
                ],
                'zones' => [
                    'Z5' => ['percent' => 1, 'range' => '>179 ppm', 'color' => '#b30000'],
                    'Z4' => ['percent' => 13, 'range' => '170 - 178 ppm', 'color' => '#dc2626'],
                    'Z3' => ['percent' => 39, 'range' => '155 - 169 ppm', 'color' => '#ef4444'],
                    'Z2' => ['percent' => 42, 'range' => '137 - 154 ppm', 'color' => '#f87171'],
                    'Z1' => ['percent' => 5, 'range' => '<136 ppm', 'color' => '#fca5a5']
                ]
            ]
        ];
    }
}