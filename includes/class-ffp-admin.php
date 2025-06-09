<?php
/**
 * Admin Handler Class
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FFP_Admin {
    
    /**
     * Admin instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_ffp_test_api', array($this, 'ajax_test_api'));
        add_action('wp_ajax_ffp_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Football Fixtures Pro', 'football-fixtures-pro'),
            __('Football Fixtures Pro', 'football-fixtures-pro'),
            'manage_options',
            'football-fixtures-pro',
            array($this, 'admin_page'),
            'dashicons-buddicons-activity',
            30
        );
        
        add_submenu_page(
            'football-fixtures-pro',
            __('Settings', 'football-fixtures-pro'),
            __('Settings', 'football-fixtures-pro'),
            'manage_options',
            'football-fixtures-pro-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'football-fixtures-pro',
            __('Cache Management', 'football-fixtures-pro'),
            __('Cache Management', 'football-fixtures-pro'),
            'manage_options',
            'football-fixtures-pro-cache',
            array($this, 'cache_page')
        );
        
        add_submenu_page(
            'football-fixtures-pro',
            __('Documentation', 'football-fixtures-pro'),
            __('Documentation', 'football-fixtures-pro'),
            'manage_options',
            'football-fixtures-pro-docs',
            array($this, 'documentation_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ffp_settings_group', 'ffp_settings', array($this, 'sanitize_settings'));
        
        // API Settings Section
        add_settings_section(
            'ffp_api_section',
            __('API Settings', 'football-fixtures-pro'),
            array($this, 'api_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'api_key',
            __('API Key', 'football-fixtures-pro'),
            array($this, 'api_key_callback'),
            'ffp_settings',
            'ffp_api_section'
        );
        
        // Display Settings Section
        add_settings_section(
            'ffp_display_section',
            __('Display Settings', 'football-fixtures-pro'),
            array($this, 'display_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'default_timezone',
            __('Default Timezone', 'football-fixtures-pro'),
            array($this, 'timezone_callback'),
            'ffp_settings',
            'ffp_display_section'
        );
        
        add_settings_field(
            'show_team_logos',
            __('Show Team Logos', 'football-fixtures-pro'),
            array($this, 'show_team_logos_callback'),
            'ffp_settings',
            'ffp_display_section'
        );
        
        add_settings_field(
            'show_odds',
            __('Show Odds', 'football-fixtures-pro'),
            array($this, 'show_odds_callback'),
            'ffp_settings',
            'ffp_display_section'
        );
        
        add_settings_field(
            'show_team_form',
            __('Show Team Form', 'football-fixtures-pro'),
            array($this, 'show_team_form_callback'),
            'ffp_settings',
            'ffp_display_section'
        );
        
        // Cache Settings Section
        add_settings_section(
            'ffp_cache_section',
            __('Cache Settings', 'football-fixtures-pro'),
            array($this, 'cache_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'cache_duration',
            __('Cache Duration (seconds)', 'football-fixtures-pro'),
            array($this, 'cache_duration_callback'),
            'ffp_settings',
            'ffp_cache_section'
        );
        
        add_settings_field(
            'matches_per_page',
            __('Matches Per Page', 'football-fixtures-pro'),
            array($this, 'matches_per_page_callback'),
            'ffp_settings',
            'ffp_display_section'
        );
    }
    
    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        $sanitized['cache_duration'] = absint($input['cache_duration']);
        $sanitized['default_timezone'] = sanitize_text_field($input['default_timezone']);
        $sanitized['show_team_logos'] = isset($input['show_team_logos']) ? 1 : 0;
        $sanitized['show_odds'] = isset($input['show_odds']) ? 1 : 0;
        $sanitized['show_team_form'] = isset($input['show_team_form']) ? 1 : 0;
        $sanitized['matches_per_page'] = absint($input['matches_per_page']);
        
        // Validate cache duration
        if ($sanitized['cache_duration'] < 60) {
            $sanitized['cache_duration'] = 60;
        }
        
        // Validate matches per page
        if ($sanitized['matches_per_page'] < 1) {
            $sanitized['matches_per_page'] = 10;
        } elseif ($sanitized['matches_per_page'] > 100) {
            $sanitized['matches_per_page'] = 100;
        }
        
        return $sanitized;
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $settings = get_option('ffp_settings', array());
        $api = FFP_API::get_instance();
        $cache = FFP_Cache::get_instance();
        $cache_stats = $cache->get_stats();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Football Fixtures Pro', 'football-fixtures-pro'); ?></h1>
            
            <div class="ffp-admin-grid">
                <div class="ffp-admin-card">
                    <h2><?php echo esc_html__('API Status', 'football-fixtures-pro'); ?></h2>
                    <div id="ffp-api-status">
                        <?php if (empty($settings['api_key'])): ?>
                            <p class="ffp-status-error">
                                <?php echo esc_html__('API key not configured', 'football-fixtures-pro'); ?>
                            </p>
                        <?php else: ?>
                            <button id="ffp-test-api" class="button button-secondary">
                                <?php echo esc_html__('Test API Connection', 'football-fixtures-pro'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="ffp-admin-card">
                    <h2><?php echo esc_html__('Cache Statistics', 'football-fixtures-pro'); ?></h2>
                    <ul class="ffp-stats-list">
                        <li><?php printf(__('Total Entries: %d', 'football-fixtures-pro'), $cache_stats['total_entries']); ?></li>
                        <li><?php printf(__('Active Entries: %d', 'football-fixtures-pro'), $cache_stats['active_entries']); ?></li>
                        <li><?php printf(__('Expired Entries: %d', 'football-fixtures-pro'), $cache_stats['expired_entries']); ?></li>
                    </ul>
                    <button id="ffp-clear-cache" class="button button-secondary">
                        <?php echo esc_html__('Clear Cache', 'football-fixtures-pro'); ?>
                    </button>
                </div>
                
                <div class="ffp-admin-card">
                    <h2><?php echo esc_html__('Quick Links', 'football-fixtures-pro'); ?></h2>
                    <ul class="ffp-quick-links">
                        <li><a href="<?php echo admin_url('admin.php?page=football-fixtures-pro-settings'); ?>"><?php echo esc_html__('Settings', 'football-fixtures-pro'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=football-fixtures-pro-cache'); ?>"><?php echo esc_html__('Cache Management', 'football-fixtures-pro'); ?></a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=football-fixtures-pro-docs'); ?>"><?php echo esc_html__('Documentation', 'football-fixtures-pro'); ?></a></li>
                        <li><a href="https://mogadonko.com" target="_blank"><?php echo esc_html__('Support', 'football-fixtures-pro'); ?></a></li>
                    </ul>
                </div>
                
                <div class="ffp-admin-card">
                    <h2><?php echo esc_html__('Usage Instructions', 'football-fixtures-pro'); ?></h2>
                    <ol class="ffp-instructions">
                        <li><?php echo esc_html__('Configure your API key in Settings', 'football-fixtures-pro'); ?></li>
                        <li><?php echo esc_html__('Add the Football Fixtures Pro widget to any Elementor page', 'football-fixtures-pro'); ?></li>
                        <li><?php echo esc_html__('Configure display options and select leagues', 'football-fixtures-pro'); ?></li>
                        <li><?php echo esc_html__('Publish and enjoy your football fixtures display!', 'football-fixtures-pro'); ?></li>
                    </ol>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Football Fixtures Pro Settings', 'football-fixtures-pro'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('ffp_settings_group');
                do_settings_sections('ffp_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Cache management page
     */
    public function cache_page() {
        $cache = FFP_Cache::get_instance();
        $stats = $cache->get_stats();
        
        if (isset($_POST['clear_cache']) && wp_verify_nonce($_POST['ffp_nonce'], 'ffp_clear_cache')) {
            $cache->clear_all();
            echo '<div class="notice notice-success"><p>' . esc_html__('Cache cleared successfully!', 'football-fixtures-pro') . '</p></div>';
            $stats = $cache->get_stats(); // Refresh stats
        }
        
        if (isset($_POST['clear_expired']) && wp_verify_nonce($_POST['ffp_nonce'], 'ffp_clear_expired')) {
            $cache->clear_expired_cache();
            echo '<div class="notice notice-success"><p>' . esc_html__('Expired cache cleared successfully!', 'football-fixtures-pro') . '</p></div>';
            $stats = $cache->get_stats(); // Refresh stats
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Cache Management', 'football-fixtures-pro'); ?></h1>
            
            <div class="ffp-cache-stats">
                <h2><?php echo esc_html__('Cache Statistics', 'football-fixtures-pro'); ?></h2>
                <table class="widefat fixed striped">
                    <tbody>
                        <tr>
                            <td><?php echo esc_html__('Total Cache Entries', 'football-fixtures-pro'); ?></td>
                            <td><?php echo esc_html($stats['total_entries']); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html__('Active Entries', 'football-fixtures-pro'); ?></td>
                            <td><?php echo esc_html($stats['active_entries']); ?></td>
                        </tr>
                        <tr>
                            <td><?php echo esc_html__('Expired Entries', 'football-fixtures-pro'); ?></td>
                            <td><?php echo esc_html($stats['expired_entries']); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="ffp-cache-actions">
                <h2><?php echo esc_html__('Cache Actions', 'football-fixtures-pro'); ?></h2>
                
                <form method="post" style="display: inline-block; margin-right: 10px;">
                    <?php wp_nonce_field('ffp_clear_expired', 'ffp_nonce'); ?>
                    <input type="submit" name="clear_expired" class="button button-secondary" 
                           value="<?php echo esc_attr__('Clear Expired Cache', 'football-fixtures-pro'); ?>"
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear expired cache?', 'football-fixtures-pro')); ?>');">
                </form>
                
                <form method="post" style="display: inline-block;">
                    <?php wp_nonce_field('ffp_clear_cache', 'ffp_nonce'); ?>
                    <input type="submit" name="clear_cache" class="button button-primary" 
                           value="<?php echo esc_attr__('Clear All Cache', 'football-fixtures-pro'); ?>"
                           onclick="return confirm('<?php echo esc_js(__('Are you sure you want to clear all cache? This action cannot be undone.', 'football-fixtures-pro')); ?>');">
                </form>
            </div>
        </div>
        <?php
    }
    
    /**
     * Documentation page
     */
    public function documentation_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Football Fixtures Pro Documentation', 'football-fixtures-pro'); ?></h1>
            
            <div class="ffp-documentation">
                <h2><?php echo esc_html__('Getting Started', 'football-fixtures-pro'); ?></h2>
                <p><?php echo esc_html__('Welcome to Football Fixtures Pro! This plugin allows you to display football fixtures, odds, and team information on your WordPress site using Elementor.', 'football-fixtures-pro'); ?></p>
                
                <h3><?php echo esc_html__('1. API Configuration', 'football-fixtures-pro'); ?></h3>
                <p><?php echo esc_html__('First, you need to obtain an API key from api-football.com:', 'football-fixtures-pro'); ?></p>
                <ol>
                    <li><?php echo esc_html__('Visit api-football.com and create an account', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Subscribe to a plan that meets your needs', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Copy your API key from the dashboard', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Paste it in the Settings page of this plugin', 'football-fixtures-pro'); ?></li>
                </ol>
                
                <h3><?php echo esc_html__('2. Using the Elementor Widget', 'football-fixtures-pro'); ?></h3>
                <p><?php echo esc_html__('Once your API key is configured:', 'football-fixtures-pro'); ?></p>
                <ol>
                    <li><?php echo esc_html__('Edit any page with Elementor', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Find the "Football Fixtures Pro" widget in the widgets panel', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Drag it to your page', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Configure the settings (date, leagues, display options)', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Preview and publish your page', 'football-fixtures-pro'); ?></li>
                </ol>
                
                <h3><?php echo esc_html__('3. Widget Options', 'football-fixtures-pro'); ?></h3>
                <ul>
                    <li><strong><?php echo esc_html__('Section Title:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Custom title for your fixtures section', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Select Date:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Choose which date to show fixtures for', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Select Leagues:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Filter by specific leagues or show all', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Show Team Logos:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Display team logos in the fixtures', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Show Odds:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Display betting odds for matches', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Show Team Form:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Show recent team performance (W/L/D)', 'football-fixtures-pro'); ?></li>
                </ul>
                
                <h3><?php echo esc_html__('4. Styling', 'football-fixtures-pro'); ?></h3>
                <p><?php echo esc_html__('The widget includes comprehensive styling options:', 'football-fixtures-pro'); ?></p>
                <ul>
                    <li><?php echo esc_html__('Typography controls for titles and team names', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Color controls for text and backgrounds', 'football-fixtures-pro'); ?></li>
                    <li><?php echo esc_html__('Border radius and spacing controls', 'football-fixtures-pro'); ?></li>
                </ul>
                
                <h3><?php echo esc_html__('5. Shortcode Usage', 'football-fixtures-pro'); ?></h3>
                <p><?php echo esc_html__('You can also use shortcodes in any post or page:', 'football-fixtures-pro'); ?></p>
                <code>[football_fixtures date="2025-06-07" leagues="39,140" show_odds="true" show_form="true"]</code>
                
                <h3><?php echo esc_html__('6. Troubleshooting', 'football-fixtures-pro'); ?></h3>
                <ul>
                    <li><strong><?php echo esc_html__('No matches showing:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Check your API key and internet connection', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('Slow loading:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Increase cache duration in settings', 'football-fixtures-pro'); ?></li>
                    <li><strong><?php echo esc_html__('API errors:', 'football-fixtures-pro'); ?></strong> <?php echo esc_html__('Check your API quota on api-football.com', 'football-fixtures-pro'); ?></li>
                </ul>
                
                <h3><?php echo esc_html__('Support', 'football-fixtures-pro'); ?></h3>
                <p><?php printf(__('For support and updates, visit %s or contact Mo Gadaphy at MOGADONKO AGENCY.', 'football-fixtures-pro'), '<a href="https://mogadonko.com" target="_blank">mogadonko.com</a>'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Section callbacks
     */
    public function api_section_callback() {
        echo '<p>' . esc_html__('Configure your API-Football.com settings below.', 'football-fixtures-pro') . '</p>';
    }
    
    public function display_section_callback() {
        echo '<p>' . esc_html__('Configure default display options for the plugin.', 'football-fixtures-pro') . '</p>';
    }
    
    public function cache_section_callback() {
        echo '<p>' . esc_html__('Configure caching options to improve performance.', 'football-fixtures-pro') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function api_key_callback() {
        $settings = get_option('ffp_settings', array());
        $api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        ?>
        <input type="password" name="ffp_settings[api_key]" value="<?php echo esc_attr($api_key); ?>" 
               class="regular-text" placeholder="<?php echo esc_attr__('Enter your API key', 'football-fixtures-pro'); ?>">
        <p class="description">
            <?php printf(__('Get your API key from %s', 'football-fixtures-pro'), '<a href="https://www.api-football.com/" target="_blank">api-football.com</a>'); ?>
        </p>
        <?php
    }
    
    public function timezone_callback() {
        $settings = get_option('ffp_settings', array());
        $timezone = isset($settings['default_timezone']) ? $settings['default_timezone'] : 'UTC';
        $timezones = timezone_identifiers_list();
        ?>
        <select name="ffp_settings[default_timezone]">
            <?php foreach ($timezones as $tz): ?>
                <option value="<?php echo esc_attr($tz); ?>" <?php selected($timezone, $tz); ?>>
                    <?php echo esc_html($tz); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function show_team_logos_callback() {
        $settings = get_option('ffp_settings', array());
        $checked = isset($settings['show_team_logos']) ? $settings['show_team_logos'] : 1;
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[show_team_logos]" value="1" <?php checked($checked, 1); ?>>
            <?php echo esc_html__('Show team logos by default', 'football-fixtures-pro'); ?>
        </label>
        <?php
    }
    
    public function show_odds_callback() {
        $settings = get_option('ffp_settings', array());
        $checked = isset($settings['show_odds']) ? $settings['show_odds'] : 1;
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[show_odds]" value="1" <?php checked($checked, 1); ?>>
            <?php echo esc_html__('Show betting odds by default', 'football-fixtures-pro'); ?>
        </label>
        <?php
    }
    
    public function show_team_form_callback() {
        $settings = get_option('ffp_settings', array());
        $checked = isset($settings['show_team_form']) ? $settings['show_team_form'] : 1;
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[show_team_form]" value="1" <?php checked($checked, 1); ?>>
            <?php echo esc_html__('Show team form by default', 'football-fixtures-pro'); ?>
        </label>
        <?php
    }
    
    public function cache_duration_callback() {
        $settings = get_option('ffp_settings', array());
        $duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 300;
        ?>
        <input type="number" name="ffp_settings[cache_duration]" value="<?php echo esc_attr($duration); ?>" 
               min="60" max="3600" class="small-text">
        <p class="description"><?php echo esc_html__('How long to cache API responses (60-3600 seconds)', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function matches_per_page_callback() {
        $settings = get_option('ffp_settings', array());
        $matches_per_page = isset($settings['matches_per_page']) ? $settings['matches_per_page'] : 10;
        ?>
        <input type="number" name="ffp_settings[matches_per_page]" value="<?php echo esc_attr($matches_per_page); ?>" 
               min="1" max="100" class="small-text">
        <p class="description"><?php echo esc_html__('Default number of matches to display (1-100)', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_test_api() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        $api = FFP_API::get_instance();
        $result = $api->test_connection();
        
        wp_send_json($result);
    }
    
    public function ajax_clear_cache() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        $cache = FFP_Cache::get_instance();
        $success = $cache->clear_all();
        
        if ($success) {
            wp_send_json_success(__('Cache cleared successfully!', 'football-fixtures-pro'));
        } else {
            wp_send_json_error(__('Failed to clear cache.', 'football-fixtures-pro'));
        }
    }
    
    /**
     * Admin notices
     */
    public function admin_notices() {
        $settings = get_option('ffp_settings', array());
        
        if (empty($settings['api_key'])) {
            $settings_url = admin_url('admin.php?page=football-fixtures-pro-settings');
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php printf(
                        __('Football Fixtures Pro requires an API key to function. Please %s to configure it.', 'football-fixtures-pro'),
                        '<a href="' . esc_url($settings_url) . '">' . __('click here', 'football-fixtures-pro') . '</a>'
                    ); ?>
                </p>
            </div>
            <?php
        }
    }
}