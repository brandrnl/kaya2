<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Rol {
    
    private $id;
    private $kleur_id;
    private $qr_code;
    private $meters;
    private $locatie;
    private $created_at;
    private $updated_at;
    
    // Additional display properties
    public $kleur_naam;
    public $collectie_naam;
    public $collectie_id;
    
    /**
     * Constructor
     */
    public function __construct($data = null) {
        if ($data) {
            $this->id = isset($data->id) ? intval($data->id) : null;
            $this->kleur_id = isset($data->kleur_id) ? intval($data->kleur_id) : null;
            $this->qr_code = isset($data->qr_code) ? $data->qr_code : '';
            $this->meters = isset($data->meters) ? floatval($data->meters) : 0;
            $this->locatie = isset($data->locatie) ? $data->locatie : '';
            $this->created_at = isset($data->created_at) ? $data->created_at : '';
            $this->updated_at = isset($data->updated_at) ? $data->updated_at : '';
            
            // Optional display properties
            $this->kleur_naam = isset($data->kleur_naam) ? $data->kleur_naam : '';
            $this->collectie_naam = isset($data->collectie_naam) ? $data->collectie_naam : '';
            $this->collectie_id = isset($data->collectie_id) ? intval($data->collectie_id) : null;
        } else {
            // Initialize with default values
            $this->qr_code = '';
            $this->meters = 0;
            $this->locatie = '';
            $this->created_at = '';
            $this->updated_at = '';
            $this->kleur_naam = '';
            $this->collectie_naam = '';
        }
    }
    
    /**
     * Get all rollen with filters and sorting
     */
    public static function get_all($filters = []) {
        global $wpdb;
        
        $where = ['1=1'];
        $values = [];
        
        if (!empty($filters['collectie_id'])) {
            $where[] = 'c.id = %d';
            $values[] = intval($filters['collectie_id']);
        }
        
        if (!empty($filters['kleur_id'])) {
            $where[] = 'k.id = %d';
            $values[] = intval($filters['kleur_id']);
        }
        
        if (!empty($filters['locatie'])) {
            $where[] = 'r.locatie = %s';
            $values[] = $filters['locatie'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = '(r.qr_code LIKE %s OR k.kleur_naam LIKE %s OR c.naam LIKE %s)';
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $values[] = $search;
            $values[] = $search;
            $values[] = $search;
        }
        
        // Sorteer opties
        $sort_by = isset($filters['sort_by']) ? $filters['sort_by'] : 'r.created_at';
        $sort_order = isset($filters['sort_order']) && in_array(strtoupper($filters['sort_order']), ['ASC', 'DESC']) 
                      ? strtoupper($filters['sort_order']) : 'DESC';
        
        // Map sort fields to actual database columns
        $sort_map = [
            'qr_code' => 'r.qr_code',
            'collectie' => 'c.naam',
            'kleur' => 'k.kleur_naam',
            'meters' => 'r.meters',
            'locatie' => 'r.locatie',
            'created_at' => 'r.created_at'
        ];
        
        // Use mapped column or default
        $order_by = isset($sort_map[$sort_by]) ? $sort_map[$sort_by] : 'r.created_at';
        
        $where_clause = implode(' AND ', $where);
        $query = "
            SELECT r.*, k.kleur_naam, c.naam as collectie_naam, c.id as collectie_id
            FROM {$wpdb->prefix}gvs_rollen r
            JOIN {$wpdb->prefix}gvs_kleuren k ON r.kleur_id = k.id
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            WHERE $where_clause
            ORDER BY $order_by $sort_order
        ";
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Get rol by ID
     */
    public static function get_by_id($id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, k.kleur_naam, c.naam as collectie_naam, c.id as collectie_id
            FROM {$wpdb->prefix}gvs_rollen r
            JOIN {$wpdb->prefix}gvs_kleuren k ON r.kleur_id = k.id
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            WHERE r.id = %d
        ", $id));
        
        if ($result) {
            $rol = new self($result);
            // Store additional info for display
            $rol->kleur_naam = $result->kleur_naam;
            $rol->collectie_naam = $result->collectie_naam;
            $rol->collectie_id = $result->collectie_id;
            return $rol;
        }
        
        return null;
    }
    
    /**
     * Get rol by QR code
     */
    public static function get_by_qr_code($qr_code) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare("
            SELECT r.*, k.kleur_naam, c.naam as collectie_naam, c.id as collectie_id
            FROM {$wpdb->prefix}gvs_rollen r
            JOIN {$wpdb->prefix}gvs_kleuren k ON r.kleur_id = k.id
            JOIN {$wpdb->prefix}gvs_collecties c ON k.collectie_id = c.id
            WHERE r.qr_code = %s
        ", $qr_code));
        
        return $result;
    }
    
    /**
     * Save rol
     */
    public function save() {
        global $wpdb;
        $table = GVS_Database::get_table_name('rollen');
        
        // Generate QR code if not set
        if (!$this->qr_code) {
            $this->generate_qr_code();
        }
        
        $data = [
            'kleur_id' => $this->kleur_id,
            'qr_code' => $this->qr_code,
            'meters' => $this->meters,
            'locatie' => $this->locatie
        ];
        
        if ($this->id) {
            // Update
            $result = $wpdb->update($table, $data, ['id' => $this->id]);
        } else {
            // Insert
            $result = $wpdb->insert($table, $data);
            if ($result) {
                $this->id = $wpdb->insert_id;
                $this->log_transaction('inkomend');
            }
        }
        
        return $result !== false;
    }
    
    /**
     * Delete rol (uitgeven)
     */
    public function delete() {
        global $wpdb;
        $table = GVS_Database::get_table_name('rollen');
        
        // Store rol data for notification
        $rol_data = [
            'kleur_id' => $this->kleur_id,
            'qr_code' => $this->qr_code,
            'meters' => $this->meters,
            'locatie' => $this->locatie,
            'user_id' => get_current_user_id()
        ];
        
        // Log transaction before delete
        $this->log_transaction('uitgaand');
        
        $result = $wpdb->delete($table, ['id' => $this->id]) !== false;
        
        // Trigger action for stock check after successful deletion
        if ($result) {
            do_action('gvs_rol_deleted', $this->kleur_id, $rol_data);
        }
        
        return $result;
    }
    
    /**
     * Generate QR code
     */
    private function generate_qr_code() {
        // Get kleur and collectie info
        $kleur = GVS_Kleur::get_by_id($this->kleur_id);
        if (!$kleur) {
            return false;
        }
        
        $collectie = GVS_Collectie::get_by_id($kleur->get_collectie_id());
        if (!$collectie) {
            return false;
        }
        
        // Get prefix from settings (default to GVS if not set)
        $prefix = get_option('gvs_qr_prefix', 'GVS');
        
        // Generate code format: PREFIX_[COLLECTIE-3CHARS]_[KLEUR-3CHARS]_[RANDOM]
        $collectie_code = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $collectie->get_naam()), 0, 3));
        $kleur_code = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $kleur->get_kleur_naam()), 0, 3));
        $random = rand(100, 999);
        
        $this->qr_code = sprintf("%s_%s_%s_%d", $prefix, $collectie_code, $kleur_code, $random);
        
        // Check if exists and regenerate if needed
        while ($this->qr_code_exists($this->qr_code)) {
            $random = rand(100, 999);
            $this->qr_code = sprintf("%s_%s_%s_%d", $prefix, $collectie_code, $kleur_code, $random);
        }
        
        return true;
    }
    
    /**
     * Check if QR code exists
     */
    private function qr_code_exists($qr_code) {
        global $wpdb;
        $table = GVS_Database::get_table_name('rollen');
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE qr_code = %s",
            $qr_code
        ));
        
        return $count > 0;
    }
    
    /**
     * Log transaction
     */
    private function log_transaction($type) {
        global $wpdb;
        $table = GVS_Database::get_table_name('transacties');
        
        $wpdb->insert($table, [
            'rol_id' => $this->id,
            'qr_code' => $this->qr_code,
            'type' => $type,
            'meters' => $this->meters,
            'user_id' => get_current_user_id()
        ]);
    }
    
    /**
     * Get QR code image URL
     */
    public function get_qr_image_url($size = 300) {
        return GVS_QR_Generator::generate_url($this->qr_code, $size);
    }
    
    /**
     * Bulk create rollen
     */
    public static function bulk_create($kleur_id, $locatie, $aantal, $meters_per_rol) {
        $created = [];
        
        for ($i = 0; $i < $aantal; $i++) {
            $rol = new self();
            $rol->set_kleur_id($kleur_id);
            $rol->set_locatie($locatie);
            $rol->set_meters($meters_per_rol);
            
            if ($rol->save()) {
                $created[] = $rol;
            }
        }
        
        return $created;
    }
    
    // Getters
    public function get_id() { return $this->id; }
    public function get_kleur_id() { return $this->kleur_id; }
    public function get_qr_code() { return $this->qr_code; }
    public function get_meters() { return $this->meters; }
    public function get_locatie() { return $this->locatie; }
    
    // Setters
    public function set_kleur_id($id) { $this->kleur_id = intval($id); }
    public function set_meters($meters) { $this->meters = floatval($meters); }
    public function set_locatie($locatie) { $this->locatie = sanitize_text_field($locatie); }
}