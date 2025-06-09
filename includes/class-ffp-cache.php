<?php
/**
 * Cache Handler Class
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FFP_Cache {
    
    /**
     * Cache instance
     */
    private static $instance = null;
    
    /**
     * Database table name
     */
    private $table_name;
    
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
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ffp_cache';
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('ffp_clear_expired_cache')) {
            wp_schedule_event(time(), 'hourly', 'ffp_clear_expired_cache');
        }
        
        add_action('ffp_clear_expired_cache', array($this, 'clear_expired_cache'));
    }
    
    /**
     * Set cache
     */
    public function set($key, $value, $expiry_seconds = 300) {
        global $wpdb;
        
        $expiry_time = date('Y-m-d H:i:s', time() + $expiry_seconds);
        $serialized_value = maybe_serialize($value);
        
        $result = $wpdb->replace(
            $this->table_name,
            array(
                'cache_key' => $key,
                'cache_value' => $serialized_value,
                'expiry' => $expiry_time,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get cache
     */
    public function get($key) {
        global $wpdb;
        
        $result = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT cache_value, expiry FROM {$this->table_name} WHERE cache_key = %s",
                $key
            )
        );
        
        if (!$result) {
            return false;
        }
        
        // Check if expired
        if (strtotime($result->expiry) < time()) {
            $this->delete($key);
            return false;
        }
        
        return maybe_unserialize($result->cache_value);
    }
    
    /**
     * Delete cache
     */
    public function delete($key) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('cache_key' => $key),
            array('%s')
        );
    }
    
    /**
     * Clear all cache
     */
    public function clear_all() {
        global $wpdb;
        
        return $wpdb->query("TRUNCATE TABLE {$this->table_name}");
    }
    
    /**
     * Clear expired cache
     */
    public function clear_expired_cache() {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE expiry < %s",
                current_time('mysql')
            )
        );
    }
    
    /**
     * Get cache statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $total_entries = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $expired_entries = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE expiry < %s",
                current_time('mysql')
            )
        );
        
        return array(
            'total_entries' => (int) $total_entries,
            'expired_entries' => (int) $expired_entries,
            'active_entries' => (int) ($total_entries - $expired_entries)
        );
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function exists($key) {
        global $wpdb;
        
        $result = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->table_name} WHERE cache_key = %s AND expiry > %s",
                $key,
                current_time('mysql')
            )
        );
        
        return (int) $result > 0;
    }
    
    /**
     * Get cache with refresh callback
     */
    public function get_or_set($key, $callback, $expiry_seconds = 300) {
        $cached_value = $this->get($key);
        
        if ($cached_value !== false) {
            return $cached_value;
        }
        
        // Call callback to get fresh data
        $fresh_value = call_user_func($callback);
        
        if ($fresh_value !== false && !is_wp_error($fresh_value)) {
            $this->set($key, $fresh_value, $expiry_seconds);
        }
        
        return $fresh_value;
    }
    
    /**
     * Increment cache value (for counters)
     */
    public function increment($key, $step = 1, $initial_value = 0, $expiry_seconds = 300) {
        $current_value = $this->get($key);
        
        if ($current_value === false) {
            $new_value = $initial_value + $step;
        } else {
            $new_value = (int) $current_value + $step;
        }
        
        $this->set($key, $new_value, $expiry_seconds);
        
        return $new_value;
    }
    
    /**
     * Decrement cache value
     */
    public function decrement($key, $step = 1, $initial_value = 0, $expiry_seconds = 300) {
        return $this->increment($key, -$step, $initial_value, $expiry_seconds);
    }
    
    /**
     * Get multiple cache values
     */
    public function get_multiple($keys) {
        if (empty($keys) || !is_array($keys)) {
            return array();
        }
        
        global $wpdb;
        
        $placeholders = implode(',', array_fill(0, count($keys), '%s'));
        $query = $wpdb->prepare(
            "SELECT cache_key, cache_value, expiry FROM {$this->table_name} WHERE cache_key IN ({$placeholders})",
            $keys
        );
        
        $results = $wpdb->get_results($query);
        $return_data = array();
        
        foreach ($results as $result) {
            // Check if expired
            if (strtotime($result->expiry) >= time()) {
                $return_data[$result->cache_key] = maybe_unserialize($result->cache_value);
            } else {
                $this->delete($result->cache_key);
            }
        }
        
        return $return_data;
    }
    
    /**
     * Set multiple cache values
     */
    public function set_multiple($data, $expiry_seconds = 300) {
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        $success_count = 0;
        
        foreach ($data as $key => $value) {
            if ($this->set($key, $value, $expiry_seconds)) {
                $success_count++;
            }
        }
        
        return $success_count === count($data);
    }
    
    /**
     * Flush cache by pattern
     */
    public function flush_by_pattern($pattern) {
        global $wpdb;
        
        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE cache_key LIKE %s",
                $pattern
            )
        );
    }
}