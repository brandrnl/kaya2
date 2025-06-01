<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Locatie {
    
    private $id;
    private $naam;
    private $beschrijving;
    private $actief;
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
            $this->actief = isset($data->actief) ? intval($data->actief) : 1;
            $this->created_at = isset($data->created_at) ? $data->created_at : '';
            $this->updated_at = isset($data->updated_at) ? $data->updated_at : '';
        } else {
            // Initialize with default values for new objects
            $this->naam = '';
            $this->beschrijving = '';
            $this->actief = 1;
            $this->created_at = '';
            $this->updated_at = '';
        }
    }
    
    /**
     * Get all locaties with stock count
     */
    public static function get_all() {
        global $wpdb;
        
        $results = $wpdb->get_results("
            SELECT l.*,
                   COUNT(r.id) as aantal_rollen,
                   COALESCE(SUM(r.meters), 0) as totaal_meters
            FROM {$wpdb->prefix}gvs_locaties l
            LEFT JOIN {$wpdb->prefix}gvs_rollen r ON l.naam = r.locatie
            GROUP BY l.id
            ORDER BY l.naam ASC
        ");
        
        return $results;
    }
    
    /**
     * Get active locaties
     */
    public static function get_active() {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        return $wpdb->get_results("
            SELECT * FROM $table 
            WHERE actief = 1 
            ORDER BY naam ASC
        ");
    }
    
    /**
     * Get locatie by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ));
        
        return $result ? new self($result) : null;
    }
    
    /**
     * Get locatie by name
     */
    public static function get_by_name($naam) {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE naam = %s",
            $naam
        ));
        
        return $result ? new self($result) : null;
    }
    
    /**
     * Save locatie
     */
    public function save() {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        $data = [
            'naam' => $this->naam,
            'beschrijving' => $this->beschrijving,
            'actief' => $this->actief
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
     * Delete locatie
     */
    public function delete() {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        // Direct delete zonder checks - de check gebeurt in de page class
        $result = $wpdb->delete($table, ['id' => $this->id]);
        
        if ($result === false) {
            error_log('GVS Delete Locatie Error: ' . $wpdb->last_error);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get rollen count for this location
     */
    public function get_rollen_count() {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*) 
            FROM {$wpdb->prefix}gvs_rollen 
            WHERE locatie = %s
        ", $this->naam));
        
        return intval($count);
    }
    
    /**
     * Get rollen at this location
     */
    public function get_rollen() {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT r.*, k.kleur_naam, c.naam as collectie_naam
            FROM {$wpdb->prefix}gvs_rollen r
            JOIN {$wpdb->prefix}gvs_kleuren k ON r.kleur_id = k.id
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            WHERE r.locatie = %s
            ORDER BY c.naam, k.kleur_naam
        ", $this->naam));
    }
    
    /**
     * Check if name exists
     */
    public static function naam_exists($naam, $exclude_id = null) {
        global $wpdb;
        $table = GVS_Database::get_table_name('locaties');
        
        $query = $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE naam = %s", $naam);
        if ($exclude_id) {
            $query .= $wpdb->prepare(" AND id != %d", $exclude_id);
        }
        
        return $wpdb->get_var($query) > 0;
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_naam() { return $this->naam ?? ''; }
    public function get_beschrijving() { return $this->beschrijving ?? ''; }
    public function is_actief() { return $this->actief == 1; }
    
    // Setters
    public function set_naam($naam) { $this->naam = sanitize_text_field($naam); }
    public function set_beschrijving($beschrijving) { $this->beschrijving = sanitize_text_field($beschrijving); }
    public function set_actief($actief) { $this->actief = $actief ? 1 : 0; }
}