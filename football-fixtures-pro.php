<?php
/**
 * Plugin Name: Football Fixtures Pro
 * Plugin URI: https://mogadonko.com/football-fixtures-pro
 * Description: Professional football fixtures and odds display plugin with Elementor integration and API-Football.com support
 * Version: 1.0.2
 * Author: Mo Gadaphy - MOGADONKO AGENCY
 * Author URI: https://mogadonko.com
 * License: GPL v2 or later
 * Text Domain: football-fixtures-pro
 * Domain Path: /languages
 * Requires PHP: 7.4
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Elementor tested up to: 3.18
 * Elementor Pro tested up to: 3.18
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FFP_VERSION', '1.0.0');
define('FFP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FFP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FFP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Football Fixtures Pro Class
 */
class FootballFixturesPro {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        $this->init_hooks();
        
        // Check if Elementor is active
        if (did_action('elementor/loaded')) {
            add_action('elementor/elements/categories_registered', array($this, 'register_elementor_category'));
            add_action('elementor/widgets/widgets_registered', array($this, 'register_elementor_widgets'));
        } else {
            add_action('admin_notices', array($this, 'admin_notice_missing_elementor'));
        }
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once FFP_PLUGIN_PATH . 'includes/class-ffp-api.php';
        require_once FFP_PLUGIN_PATH . 'includes/class-ffp-admin.php';
        require_once FFP_PLUGIN_PATH . 'includes/class-ffp-settings.php';
        require_once FFP_PLUGIN_PATH . 'includes/class-ffp-cache.php';
        require_once FFP_PLUGIN_PATH . 'includes/elementor/class-ffp-elementor-widget.php';
        require_once FFP_PLUGIN_PATH . 'includes/class-ffp-shortcode.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Initialize components
        FFP_Admin::get_instance();
        FFP_Settings::get_instance();
        FFP_Shortcode::get_instance();
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('football-fixtures-pro', false, dirname(FFP_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue enhanced frontend CSS
        wp_enqueue_style('ffp-frontend', FFP_PLUGIN_URL . 'assets/css/frontend.css', array(), FFP_VERSION);
        
        // Enqueue enhanced frontend JavaScript
        wp_enqueue_script('ffp-frontend', FFP_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), FFP_VERSION, true);
        
        // Localize script with enhanced data
        wp_localize_script('ffp-frontend', 'ffp_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ffp_nonce'),
            'loading_text' => __('Loading fixtures...', 'football-fixtures-pro'),
            'error_text' => __('Error loading fixtures', 'football-fixtures-pro'),
            'no_matches_text' => __('No matches found', 'football-fixtures-pro'),
            'timezone' => wp_timezone_string(),
            'date_format' => get_option('date_format', 'F j, Y'),
            'time_format' => get_option('time_format', 'g:i a'),
            'is_rtl' => is_rtl(),
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '$',
            'api_rate_limit' => 100, // requests per hour
            'auto_refresh_interval' => 60000, // 1 minute
            'animation_duration' => 300,
            'enable_analytics' => true,
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG
        ));
        
        // Add custom CSS for Elementor compatibility
        $custom_css = "
            .elementor-widget-football-fixtures-pro .ffp-widget-container {
                max-width: 100%;
                margin: 0;
            }
            
            .elementor-widget-football-fixtures-pro .ffp-section-title {
                margin-top: 0;
            }
            
            .elementor-editor-active .ffp-match-card {
                pointer-events: none;
            }
            
            .elementor-editor-active .ffp-bet-button {
                pointer-events: none;
                cursor: default;
            }
        ";
        wp_add_inline_style('ffp-frontend', $custom_css);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'football-fixtures-pro') !== false) {
            wp_enqueue_style('ffp-admin', FFP_PLUGIN_URL . 'assets/css/admin.css', array(), FFP_VERSION);
            wp_enqueue_script('ffp-admin', FFP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), FFP_VERSION, true);
        }
    }
    
    /**
     * Register Elementor category
     */
    public function register_elementor_category($elements_manager) {
        $elements_manager->add_category('football-fixtures-pro', array(
            'title' => __('Football Fixtures Pro', 'football-fixtures-pro'),
            'icon' => 'fa fa-futbol-o',
        ));
    }
    
    /**
     * Register Elementor widgets
     */
    public function register_elementor_widgets($widgets_manager) {
        $widgets_manager->register_widget_type(new FFP_Elementor_Widget());
    }
    
    /**
     * Admin notice for missing Elementor
     */
    public function admin_notice_missing_elementor() {
        if (isset($_GET['activate'])) unset($_GET['activate']);
        
        $message = sprintf(
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'football-fixtures-pro'),
            '<strong>' . esc_html__('Football Fixtures Pro', 'football-fixtures-pro') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'football-fixtures-pro') . '</strong>'
        );
        
        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables if needed
        $this->create_tables();
        
        // Set default options
        $default_options = array(
            'api_key' => '',
            'cache_duration' => 300, // 5 minutes
            'default_timezone' => 'UTC',
            'show_team_logos' => true,
            'show_odds' => true,
            'show_team_form' => true,
            'matches_per_page' => 10
        );
        
        add_option('ffp_settings', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('ffp_clear_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'ffp_cache';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_value longtext NOT NULL,
            expiry datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cache_key (cache_key)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

/**
 * Initialize the plugin
 */
function ffp_init() {
    return FootballFixturesPro::get_instance();
}

// Start the plugin
ffp_init();