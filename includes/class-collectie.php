<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Collectie {
    
    private $id;
    private $naam;
    private $beschrijving;
    private $created_at;
    private $updated_at;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        if ($data) {
            $this->id = isset($data->id) ? intval($data->id) : null;
            $this->naam = isset($data->naam) ? $data->naam : '';
            $this->beschrijving = isset($data->beschrijving) ? $data->beschrijving : '';
            $this->created_at = isset($data->created_at) ? $data->created_at : '';
            $this->updated_at = isset($data->updated_at) ? $data->updated_at : '';
        } else {
            // Initialize with empty values for new objects
            $this->naam = '';
            $this->beschrijving = '';
            $this->created_at = '';
            $this->updated_at = '';
        }
    }
    
    /**
     * Get all collecties
     */
    public static function get_all() {
        global $wpdb;
        $table = GVS_Database::get_table_name('collecties');
        
        $results = $wpdb->get_results("
            SELECT c.*, 
                   COUNT(DISTINCT k.id) as aantal_kleuren,
                   COUNT(DISTINCT r.id) as aantal_rollen,
                   COALESCE(SUM(r.meters), 0) as totaal_meters
            FROM $table c
            LEFT JOIN {$wpdb->prefix}gvs_kleuren k ON c.id = k.collectie_id
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON k.id = r.kleur_id
            GROUP BY c.id
            ORDER BY c.naam ASC
        ");
        
        return $results;
    }
    
    /**
     * Get collectie by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = GVS_Database::get_table_name('collecties');
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        return $result ? new self($result) : null;
    }
    
    /**
     * Save collectie
     */
    public function save() {
        global $wpdb;
        $table = GVS_Database::get_table_name('collecties');
        
        $data = [
            'naam' => $this->naam,
            'beschrijving' => $this->beschrijving
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
     * Delete collectie
     */
    public function delete() {
        global $wpdb;
        $table = GVS_Database::get_table_name('collecties');
        
        return $wpdb->delete($table, ['id' => $this->id]) !== false;
    }
    
    /**
     * Get kleuren for this collectie
     */
    public function get_kleuren() {
        return GVS_Kleur::get_by_collectie($this->id);
    }
    
    /**
     * Get statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT k.id) as aantal_kleuren,
                COUNT(DISTINCT r.id) as aantal_rollen,
                COALESCE(SUM(r.meters), 0) as totaal_meters
            FROM {$wpdb->prefix}gvs_kleuren k
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON k.id = r.kleur_id
            WHERE k.collectie_id = %d
        ", $this->id));
        
        return $stats;
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_naam() { return $this->naam ?? ''; }
    public function get_beschrijving() { return $this->beschrijving ?? ''; }
    
    // Setters
    public function set_naam($naam) { $this->naam = sanitize_text_field($naam); }
    public function set_beschrijving($beschrijving) { $this->beschrijving = sanitize_textarea_field($beschrijving); }
}