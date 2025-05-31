<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class GVS_Database {
    
    private static $db_version = '1.0.0';
    
    /**
     * Create all database tables
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Collecties table
        $table_collecties = $wpdb->prefix . 'gvs_collecties';
        $sql_collecties = "CREATE TABLE $table_collecties (
            id INT NOT NULL AUTO_INCREMENT,
            naam VARCHAR(255) NOT NULL,
            beschrijving TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Kleuren table
        $table_kleuren = $wpdb->prefix . 'gvs_kleuren';
        $sql_kleuren = "CREATE TABLE $table_kleuren (
            id INT NOT NULL AUTO_INCREMENT,
            collectie_id INT NOT NULL,
            kleur_naam VARCHAR(255) NOT NULL,
            min_voorraad_rollen INT DEFAULT 5,
            min_voorraad_meters DECIMAL(10,2) DEFAULT 100.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (collectie_id) REFERENCES $table_collecties(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Locaties table
        $table_locaties = $wpdb->prefix . 'gvs_locaties';
        $sql_locaties = "CREATE TABLE $table_locaties (
            id INT NOT NULL AUTO_INCREMENT,
            naam VARCHAR(50) NOT NULL UNIQUE,
            beschrijving VARCHAR(255),
            actief TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        // Rollen table
        $table_rollen = $wpdb->prefix . 'gvs_rollen';
        $sql_rollen = "CREATE TABLE $table_rollen (
            id INT NOT NULL AUTO_INCREMENT,
            kleur_id INT NOT NULL,
            qr_code VARCHAR(255) NOT NULL UNIQUE,
            meters DECIMAL(10,2) NOT NULL,
            locatie VARCHAR(50),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (kleur_id) REFERENCES $table_kleuren(id) ON DELETE CASCADE,
            INDEX idx_qr_code (qr_code),
            INDEX idx_locatie (locatie)
        ) $charset_collate;";
        
        // Transacties table
        $table_transacties = $wpdb->prefix . 'gvs_transacties';
        $sql_transacties = "CREATE TABLE $table_transacties (
            id INT NOT NULL AUTO_INCREMENT,
            rol_id INT,
            qr_code VARCHAR(255),
            type ENUM('inkomend', 'uitgaand') NOT NULL,
            meters DECIMAL(10,2) NOT NULL,
            user_id INT,
            notitie TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_type (type),
            INDEX idx_created (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_collecties);
        dbDelta($sql_kleuren);
        dbDelta($sql_locaties);
        dbDelta($sql_rollen);
        dbDelta($sql_transacties);
        
        update_option('gvs_db_version', self::$db_version);
        
        // Insert some default locaties
        self::insert_default_locaties();
    }
    
    /**
     * Insert default locations
     */
    private static function insert_default_locaties() {
        global $wpdb;
        $table = $wpdb->prefix . 'gvs_locaties';
        
        // Check if locaties already exist
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        if ($count > 0) {
            return;
        }
        
        // Insert default locations
        $locaties = [
            ['naam' => 'A01', 'beschrijving' => 'Stelling A, Vak 01'],
            ['naam' => 'A02', 'beschrijving' => 'Stelling A, Vak 02'],
            ['naam' => 'A03', 'beschrijving' => 'Stelling A, Vak 03'],
            ['naam' => 'B01', 'beschrijving' => 'Stelling B, Vak 01'],
            ['naam' => 'B02', 'beschrijving' => 'Stelling B, Vak 02'],
            ['naam' => 'B03', 'beschrijving' => 'Stelling B, Vak 03'],
            ['naam' => 'C01', 'beschrijving' => 'Stelling C, Vak 01'],
            ['naam' => 'C02', 'beschrijving' => 'Stelling C, Vak 02'],
            ['naam' => 'C03', 'beschrijving' => 'Stelling C, Vak 03'],
        ];
        
        foreach ($locaties as $locatie) {
            $wpdb->insert($table, $locatie);
        }
    }
    
    /**
     * Get table name with prefix
     */
    public static function get_table_name($table) {
        global $wpdb;
        return $wpdb->prefix . 'gvs_' . $table;
    }
}