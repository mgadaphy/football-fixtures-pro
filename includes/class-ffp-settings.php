<?php
/**
 * Settings Handler Class
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FFP_Settings {
    
    /**
     * Settings instance
     */
    private static $instance = null;
    
    /**
     * Default settings
     */
    private $defaults = array(
        'api_key' => '',
        'cache_duration' => 300,
        'default_timezone' => 'UTC',
        'show_team_logos' => true,
        'show_odds' => true,
        'show_team_form' => true,
        'matches_per_page' => 10,
        'enable_auto_refresh' => true,
        'auto_refresh_interval' => 60,
        'enable_notifications' => false,
        'default_theme' => 'default',
        'enable_lazy_loading' => true,
        'enable_tooltips' => true,
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i',
        'enable_keyboard_navigation' => true,
        'enable_animations' => true,
        'default_odds_format' => 'decimal',
        'preferred_bookmaker' => '',
        'enable_live_scores' => true,
        'enable_push_notifications' => false,
        'favorite_leagues' => array(),
        'favorite_teams' => array(),
        'custom_css' => '',
        'enable_debug_mode' => false
    );
    
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
        add_action('admin_init', array($this, 'register_advanced_settings'));
        add_action('wp_ajax_ffp_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_ffp_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_ffp_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_ffp_import_settings', array($this, 'ajax_import_settings'));
    }
    
    /**
     * Get setting value
     */
    public function get($key, $default = null) {
        $settings = get_option('ffp_settings', array());
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        if ($default !== null) {
            return $default;
        }
        
        return isset($this->defaults[$key]) ? $this->defaults[$key] : null;
    }
    
    /**
     * Set setting value
     */
    public function set($key, $value) {
        $settings = get_option('ffp_settings', array());
        $settings[$key] = $value;
        return update_option('ffp_settings', $settings);
    }
    
    /**
     * Get all settings
     */
    public function get_all() {
        $settings = get_option('ffp_settings', array());
        return wp_parse_args($settings, $this->defaults);
    }
    
    /**
     * Update multiple settings
     */
    public function update($new_settings) {
        $current_settings = $this->get_all();
        $updated_settings = wp_parse_args($new_settings, $current_settings);
        return update_option('ffp_settings', $updated_settings);
    }
    
    /**
     * Reset to defaults
     */
    public function reset() {
        return update_option('ffp_settings', $this->defaults);
    }
    
    /**
     * Register advanced settings
     */
    public function register_advanced_settings() {
        // Performance Settings Section
        add_settings_section(
            'ffp_performance_section',
            __('Performance Settings', 'football-fixtures-pro'),
            array($this, 'performance_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'enable_auto_refresh',
            __('Enable Auto Refresh', 'football-fixtures-pro'),
            array($this, 'enable_auto_refresh_callback'),
            'ffp_settings',
            'ffp_performance_section'
        );
        
        add_settings_field(
            'auto_refresh_interval',
            __('Auto Refresh Interval (seconds)', 'football-fixtures-pro'),
            array($this, 'auto_refresh_interval_callback'),
            'ffp_settings',
            'ffp_performance_section'
        );
        
        add_settings_field(
            'enable_lazy_loading',
            __('Enable Lazy Loading', 'football-fixtures-pro'),
            array($this, 'enable_lazy_loading_callback'),
            'ffp_settings',
            'ffp_performance_section'
        );
        
        // UI/UX Settings Section
        add_settings_section(
            'ffp_ui_section',
            __('UI/UX Settings', 'football-fixtures-pro'),
            array($this, 'ui_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'default_theme',
            __('Default Theme', 'football-fixtures-pro'),
            array($this, 'default_theme_callback'),
            'ffp_settings',
            'ffp_ui_section'
        );
        
        add_settings_field(
            'enable_animations',
            __('Enable Animations', 'football-fixtures-pro'),
            array($this, 'enable_animations_callback'),
            'ffp_settings',
            'ffp_ui_section'
        );
        
        add_settings_field(
            'enable_tooltips',
            __('Enable Tooltips', 'football-fixtures-pro'),
            array($this, 'enable_tooltips_callback'),
            'ffp_settings',
            'ffp_ui_section'
        );
        
        add_settings_field(
            'enable_keyboard_navigation',
            __('Enable Keyboard Navigation', 'football-fixtures-pro'),
            array($this, 'enable_keyboard_navigation_callback'),
            'ffp_settings',
            'ffp_ui_section'
        );
        
        // Date & Time Settings Section
        add_settings_section(
            'ffp_datetime_section',
            __('Date & Time Settings', 'football-fixtures-pro'),
            array($this, 'datetime_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'date_format',
            __('Date Format', 'football-fixtures-pro'),
            array($this, 'date_format_callback'),
            'ffp_settings',
            'ffp_datetime_section'
        );
        
        add_settings_field(
            'time_format',
            __('Time Format', 'football-fixtures-pro'),
            array($this, 'time_format_callback'),
            'ffp_settings',
            'ffp_datetime_section'
        );
        
        // Odds Settings Section
        add_settings_section(
            'ffp_odds_section',
            __('Odds Settings', 'football-fixtures-pro'),
            array($this, 'odds_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'default_odds_format',
            __('Default Odds Format', 'football-fixtures-pro'),
            array($this, 'default_odds_format_callback'),
            'ffp_settings',
            'ffp_odds_section'
        );
        
        add_settings_field(
            'preferred_bookmaker',
            __('Preferred Bookmaker', 'football-fixtures-pro'),
            array($this, 'preferred_bookmaker_callback'),
            'ffp_settings',
            'ffp_odds_section'
        );
        
        // Advanced Settings Section
        add_settings_section(
            'ffp_advanced_section',
            __('Advanced Settings', 'football-fixtures-pro'),
            array($this, 'advanced_section_callback'),
            'ffp_settings'
        );
        
        add_settings_field(
            'custom_css',
            __('Custom CSS', 'football-fixtures-pro'),
            array($this, 'custom_css_callback'),
            'ffp_settings',
            'ffp_advanced_section'
        );
        
        add_settings_field(
            'enable_debug_mode',
            __('Enable Debug Mode', 'football-fixtures-pro'),
            array($this, 'enable_debug_mode_callback'),
            'ffp_settings',
            'ffp_advanced_section'
        );
    }
    
    /**
     * Section callbacks
     */
    public function performance_section_callback() {
        echo '<p>' . esc_html__('Configure performance and caching options.', 'football-fixtures-pro') . '</p>';
    }
    
    public function ui_section_callback() {
        echo '<p>' . esc_html__('Configure user interface and experience settings.', 'football-fixtures-pro') . '</p>';
    }
    
    public function datetime_section_callback() {
        echo '<p>' . esc_html__('Configure date and time display formats.', 'football-fixtures-pro') . '</p>';
    }
    
    public function odds_section_callback() {
        echo '<p>' . esc_html__('Configure odds display and bookmaker preferences.', 'football-fixtures-pro') . '</p>';
    }
    
    public function advanced_section_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for developers.', 'football-fixtures-pro') . '</p>';
    }
    
    /**
     * Field callbacks
     */
    public function enable_auto_refresh_callback() {
        $value = $this->get('enable_auto_refresh');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_auto_refresh]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Automatically refresh live matches', 'football-fixtures-pro'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Automatically refresh fixtures for today\'s matches', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function auto_refresh_interval_callback() {
        $value = $this->get('auto_refresh_interval');
        ?>
        <input type="number" name="ffp_settings[auto_refresh_interval]" value="<?php echo esc_attr($value); ?>" 
               min="30" max="300" class="small-text">
        <p class="description"><?php echo esc_html__('How often to refresh live matches (30-300 seconds)', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function enable_lazy_loading_callback() {
        $value = $this->get('enable_lazy_loading');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_lazy_loading]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Enable lazy loading for images', 'football-fixtures-pro'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Improve page load times by loading images as they become visible', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function default_theme_callback() {
        $value = $this->get('default_theme');
        $themes = array(
            'default' => __('Default', 'football-fixtures-pro'),
            'dark' => __('Dark', 'football-fixtures-pro'),
            'compact' => __('Compact', 'football-fixtures-pro'),
            'minimal' => __('Minimal', 'football-fixtures-pro')
        );
        ?>
        <select name="ffp_settings[default_theme]">
            <?php foreach ($themes as $theme_key => $theme_name): ?>
                <option value="<?php echo esc_attr($theme_key); ?>" <?php selected($value, $theme_key); ?>>
                    <?php echo esc_html($theme_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html__('Default theme for new widgets', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function enable_animations_callback() {
        $value = $this->get('enable_animations');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_animations]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Enable CSS animations and transitions', 'football-fixtures-pro'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Disable if you prefer reduced motion', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function enable_tooltips_callback() {
        $value = $this->get('enable_tooltips');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_tooltips]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Show helpful tooltips on hover', 'football-fixtures-pro'); ?>
        </label>
        <?php
    }
    
    public function enable_keyboard_navigation_callback() {
        $value = $this->get('enable_keyboard_navigation');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_keyboard_navigation]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Enable keyboard navigation support', 'football-fixtures-pro'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Allows users to navigate matches using arrow keys', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function date_format_callback() {
        $value = $this->get('date_format');
        $formats = array(
            'd/m/Y' => date('d/m/Y'),
            'm/d/Y' => date('m/d/Y'),
            'Y-m-d' => date('Y-m-d'),
            'j F Y' => date('j F Y'),
            'F j, Y' => date('F j, Y')
        );
        ?>
        <select name="ffp_settings[date_format]">
            <?php foreach ($formats as $format => $example): ?>
                <option value="<?php echo esc_attr($format); ?>" <?php selected($value, $format); ?>>
                    <?php echo esc_html($format . ' (' . $example . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function time_format_callback() {
        $value = $this->get('time_format');
        $formats = array(
            'H:i' => date('H:i') . ' (24-hour)',
            'g:i A' => date('g:i A') . ' (12-hour)',
            'g:i a' => date('g:i a') . ' (12-hour lowercase)'
        );
        ?>
        <select name="ffp_settings[time_format]">
            <?php foreach ($formats as $format => $example): ?>
                <option value="<?php echo esc_attr($format); ?>" <?php selected($value, $format); ?>>
                    <?php echo esc_html($example); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function default_odds_format_callback() {
        $value = $this->get('default_odds_format');
        $formats = array(
            'decimal' => __('Decimal (1.85)', 'football-fixtures-pro'),
            'fractional' => __('Fractional (17/20)', 'football-fixtures-pro'),
            'american' => __('American (-118)', 'football-fixtures-pro')
        );
        ?>
        <select name="ffp_settings[default_odds_format]">
            <?php foreach ($formats as $format_key => $format_name): ?>
                <option value="<?php echo esc_attr($format_key); ?>" <?php selected($value, $format_key); ?>>
                    <?php echo esc_html($format_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }
    
    public function preferred_bookmaker_callback() {
        $value = $this->get('preferred_bookmaker');
        ?>
        <input type="text" name="ffp_settings[preferred_bookmaker]" value="<?php echo esc_attr($value); ?>" 
               class="regular-text" placeholder="<?php echo esc_attr__('e.g., Bet365, William Hill', 'football-fixtures-pro'); ?>">
        <p class="description"><?php echo esc_html__('Preferred bookmaker for odds display (if available)', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function custom_css_callback() {
        $value = $this->get('custom_css');
        ?>
        <textarea name="ffp_settings[custom_css]" rows="10" cols="80" class="large-text code"><?php echo esc_textarea($value); ?></textarea>
        <p class="description"><?php echo esc_html__('Add custom CSS to override default styles', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    public function enable_debug_mode_callback() {
        $value = $this->get('enable_debug_mode');
        ?>
        <label>
            <input type="checkbox" name="ffp_settings[enable_debug_mode]" value="1" <?php checked($value, 1); ?>>
            <?php echo esc_html__('Enable debug mode (for developers)', 'football-fixtures-pro'); ?>
        </label>
        <p class="description"><?php echo esc_html__('Shows detailed error messages and API response information', 'football-fixtures-pro'); ?></p>
        <?php
    }
    
    /**
     * AJAX handlers
     */
    public function ajax_save_settings() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        $settings = $_POST['settings'];
        
        // Sanitize settings
        $sanitized_settings = array();
        foreach ($settings as $key => $value) {
            switch ($key) {
                case 'custom_css':
                    $sanitized_settings[$key] = wp_strip_all_tags($value);
                    break;
                case 'cache_duration':
                case 'matches_per_page':
                case 'auto_refresh_interval':
                    $sanitized_settings[$key] = absint($value);
                    break;
                default:
                    if (is_bool($value) || in_array($value, array('0', '1', 'true', 'false'))) {
                        $sanitized_settings[$key] = (bool) $value;
                    } else {
                        $sanitized_settings[$key] = sanitize_text_field($value);
                    }
                    break;
            }
        }
        
        if ($this->update($sanitized_settings)) {
            wp_send_json_success(__('Settings saved successfully!', 'football-fixtures-pro'));
        } else {
            wp_send_json_error(__('Failed to save settings.', 'football-fixtures-pro'));
        }
    }
    
    public function ajax_reset_settings() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        if ($this->reset()) {
            wp_send_json_success(__('Settings reset to defaults!', 'football-fixtures-pro'));
        } else {
            wp_send_json_error(__('Failed to reset settings.', 'football-fixtures-pro'));
        }
    }
    
    public function ajax_export_settings() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        $settings = $this->get_all();
        $export_data = array(
            'version' => FFP_VERSION,
            'timestamp' => current_time('mysql'),
            'site_url' => get_site_url(),
            'settings' => $settings
        );
        
        wp_send_json_success($export_data);
    }
    
    public function ajax_import_settings() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'football-fixtures-pro'));
        }
        
        $import_data = $_POST['import_data'];
        
        if (!is_array($import_data) || !isset($import_data['settings'])) {
            wp_send_json_error(__('Invalid import data format.', 'football-fixtures-pro'));
        }
        
        $settings = $import_data['settings'];
        
        // Validate and sanitize imported settings
        $valid_settings = array();
        foreach ($this->defaults as $key => $default_value) {
            if (isset($settings[$key])) {
                $valid_settings[$key] = $settings[$key];
            }
        }
        
        if ($this->update($valid_settings)) {
            wp_send_json_success(__('Settings imported successfully!', 'football-fixtures-pro'));
        } else {
            wp_send_json_error(__('Failed to import settings.', 'football-fixtures-pro'));
        }
    }
    
    /**
     * Get available leagues for favorites
     */
    public function get_available_leagues() {
        $api = FFP_API::get_instance();
        return $api->get_popular_leagues();
    }
    
    /**
     * Validate API key
     */
    public function validate_api_key($api_key) {
        if (empty($api_key)) {
            return false;
        }
        
        // Store current key temporarily
        $current_key = $this->get('api_key');
        $this->set('api_key', $api_key);
        
        // Test the API
        $api = FFP_API::get_instance();
        $result = $api->test_connection();
        
        // Restore original key if test failed
        if (!$result['success']) {
            $this->set('api_key', $current_key);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get setting with fallback
     */
    public function get_with_fallback($key, $fallback_key = null) {
        $value = $this->get($key);
        
        if (empty($value) && $fallback_key) {
            $value = $this->get($fallback_key);
        }
        
        return $value;
    }
    
    /**
     * Check if feature is enabled
     */
    public function is_enabled($feature) {
        return (bool) $this->get($feature, false);
    }
    
    /**
     * Get sanitized custom CSS
     */
    public function get_custom_css() {
        $css = $this->get('custom_css', '');
        
        // Basic CSS sanitization
        $css = wp_strip_all_tags($css);
        $css = preg_replace('/[^a-zA-Z0-9\s\-_{}();:.,#%!@]/', '', $css);
        
        return $css;
    }
    
    /**
     * Get debug info
     */
    public function get_debug_info() {
        if (!$this->is_enabled('enable_debug_mode')) {
            return array();
        }
        
        return array(
            'plugin_version' => FFP_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'api_key_set' => !empty($this->get('api_key')),
            'cache_stats' => FFP_Cache::get_instance()->get_stats(),
            'settings' => $this->get_all()
        );
    }
}