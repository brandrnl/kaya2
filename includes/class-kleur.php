<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Kleur {
    
    private $id;
    private $collectie_id;
    private $kleur_naam;
    private $min_voorraad_rollen;
    private $min_voorraad_meters;
    private $created_at;
    private $updated_at;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        if ($data) {
            $this->id = isset($data->id) ? intval($data->id) : null;
            $this->collectie_id = isset($data->collectie_id) ? intval($data->collectie_id) : null;
            $this->kleur_naam = isset($data->kleur_naam) ? $data->kleur_naam : '';
            $this->min_voorraad_rollen = isset($data->min_voorraad_rollen) ? intval($data->min_voorraad_rollen) : 5;
            $this->min_voorraad_meters = isset($data->min_voorraad_meters) ? floatval($data->min_voorraad_meters) : 100.00;
            $this->created_at = isset($data->created_at) ? $data->created_at : '';
            $this->updated_at = isset($data->updated_at) ? $data->updated_at : '';
        } else {
            // Initialize with default values for new objects
            $this->kleur_naam = '';
            $this->min_voorraad_rollen = 5;
            $this->min_voorraad_meters = 100.00;
            $this->created_at = '';
            $this->updated_at = '';
        }
    }
    
    /**
     * Get all kleuren with stock info
     */
    public static function get_all_with_stock() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT k.*, c.naam as collectie_naam,
                   COUNT(r.id) as huidige_rollen,
                   COALESCE(SUM(r.meters), 0) as huidige_meters
            FROM {$wpdb->prefix}gvs_kleuren k
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON k.id = r.kleur_id
            GROUP BY k.id
            ORDER BY c.naam, k.kleur_naam
        ");
        
        return $results;
    }
    
    /**
     * Get kleuren by collectie
     */
    public static function get_by_collectie($collectie_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT k.*,
                   COUNT(r.id) as huidige_rollen,
                   COALESCE(SUM(r.meters), 0) as huidige_meters
            FROM {$wpdb->prefix}gvs_kleuren k
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON k.id = r.kleur_id
            WHERE k.collectie_id = %d
            GROUP BY k.id
            ORDER BY k.kleur_naam
        ", $collectie_id));
        
        return $results;
    }
    
    /**
     * Get kleur by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = GVS_Database::get_table_name('kleuren');
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        return $result ? new self($result) : null;
    }
    
    /**
     * Get low stock kleuren
     */
    public static function get_low_stock() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT k.*, c.naam as collectie_naam,
                   COUNT(r.id) as huidige_rollen,
                   COALESCE(SUM(r.meters), 0) as huidige_meters
            FROM {$wpdb->prefix}gvs_kleuren k
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON k.id = r.kleur_id
            GROUP BY k.id
            HAVING huidige_rollen < k.min_voorraad_rollen 
                OR huidige_meters < k.min_voorraad_meters
            ORDER BY c.naam, k.kleur_naam
        ");
        
        return $results;
    }
    
    /**
     * Save kleur
     */
    public function save() {
        global $wpdb;
        $table = GVS_Database::get_table_name('kleuren');
        
        $data = [
            'collectie_id' => $this->collectie_id,
            'kleur_naam' => $this->kleur_naam,
            'min_voorraad_rollen' => $this->min_voorraad_rollen,
            'min_voorraad_meters' => $this->min_voorraad_meters
        ];
        
        if ($this->id) {
            // Update
            $result = $wpdb->update($table, $data, ['id' => $this->id]);
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);
            if ($result) {
                $this->id = $wpdb->insert_id;
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Delete kleur
     */
    public function delete() {
        global $wpdb;
        $table = GVS_Database::get_table_name('kleuren');
        
        return $wpdb->delete($table, ['id' => $this->id]) !== false;
    }
    
    /**
     * Get stock info
     */
    public function get_stock_info() {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as aantal_rollen,
                COALESCE(SUM(meters), 0) as totaal_meters
            FROM {$wpdb->prefix}gvs_rollen
            WHERE kleur_id = %d
        ", $this->id));
    }
    
    /**
     * Check if low stock
     */
    public function is_low_stock() {
        $stock = $this->get_stock_info();
        return $stock->aantal_rollen < $this->min_voorraad_rollen || 
               $stock->totaal_meters < $this->min_voorraad_meters;
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_collectie_id() { return $this->collectie_id; }
    public function get_kleur_naam() { return $this->kleur_naam ?? ''; }
    public function get_min_voorraad_rollen() { return $this->min_voorraad_rollen; }
    public function get_min_voorraad_meters() { return $this->min_voorraad_meters; }
    
    // Setters
    public function set_collectie_id($id) { $this->collectie_id = intval($id); }
    public function set_kleur_naam($naam) { $this->kleur_naam = sanitize_text_field($naam); }
    public function set_min_voorraad_rollen($min) { $this->min_voorraad_rollen = intval($min); }
    public function set_min_voorraad_meters($min) { $this->min_voorraad_meters = floatval($min); }
}