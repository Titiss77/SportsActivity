<?php namespace App\Models;

use CodeIgniter\Model;

class TradModel extends Model
{
    protected $table            = 'traduction_activities';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array'; // Corrigé : CodeIgniter n'accepte pas 'String|array|null' ici
    protected $allowedFields    = ['id', 'nom_base', 'nom_trad'];

    public function getTrad(string $nomBase): ?string
    {
        $sql = "SELECT nom_trad FROM traduction_activities WHERE nom_base = ?";
        $row = $this->db->query($sql, [$nomBase])->getRow();
        
        return $row ? $row->nom_trad : null;
    }
}