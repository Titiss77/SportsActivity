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

    public function getRecent(int $limit = 20): array
    {
        return $this->orderBy('started_at', 'DESC')
            ->findAll(max(1, min($limit, 100)));
    }

    public function getStats(): array
    {
        $row = $this->select(
            'COUNT(*) AS total_activities, 
             COALESCE(SUM(distance_meters), 0) AS total_distance_meters, 
             COALESCE(SUM(duration_seconds), 0) AS total_duration_seconds, 
             COALESCE(AVG(avg_heart_rate), 0) AS average_heart_rate, 
             COALESCE(SUM(elevation_gain_m), 0) AS total_elevation_gain'
        )->first();

        return $row ?? [
            'total_activities'      => 0,
            'total_distance_meters' => 0,
            'total_duration_seconds'=> 0,
            'average_heart_rate'    => 0,
            'total_elevation_gain'  => 0,
        ];
    }

    // NOUVELLE MÉTHODE : Statistiques regroupées par sport
    public function getStatsBySport(): array
    {
        $sql = "
            SELECT sport,
                   COUNT(*) AS total_activities, 
                   COALESCE(SUM(distance_meters), 0) AS total_distance_meters, 
                   COALESCE(SUM(duration_seconds), 0) AS total_duration_seconds
            FROM strava_activities
            GROUP BY sport
            ORDER BY total_activities DESC
        ";
        return $this->db->query($sql)->getResultArray();
    }

    public function getMonthlyDistance(int $months = 6): array
    {
        $months = max(1, min($months, 24));
        $sql = "
            SELECT DATE_FORMAT(started_at, '%Y-%m') AS month_key,
                   COALESCE(SUM(distance_meters), 0) AS distance_meters
            FROM strava_activities
            WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY DATE_FORMAT(started_at, '%Y-%m')
            ORDER BY month_key ASC
        ";
        return $this->db->query($sql, [$months])->getResultArray();
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
}