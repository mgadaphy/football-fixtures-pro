<?php
/**
 * API Football Handler Class
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FFP_API {
    
    /**
     * API base URL
     */
    const API_BASE_URL = 'https://v3.football.api-sports.io/';
    
    /**
     * API instance
     */
    private static $instance = null;
    
    /**
     * API key
     */
    private $api_key;
    
    /**
     * Cache instance
     */
    private $cache;
    
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
        $settings = get_option('ffp_settings', array());
        $this->api_key = isset($settings['api_key']) ? $settings['api_key'] : '';
        $this->cache = FFP_Cache::get_instance();
    }
    
    /**
     * Make API request
     */
    private function make_request($endpoint, $params = array()) {
        if (empty($this->api_key)) {
            return new WP_Error('no_api_key', __('API key is required', 'football-fixtures-pro'));
        }
        
        // Generate cache key
        $cache_key = 'ffp_' . md5($endpoint . serialize($params));
        
        // Check cache first
        $cached_data = $this->cache->get($cache_key);
        if ($cached_data !== false) {
            return $cached_data;
        }
        
        // Build URL
        $url = self::API_BASE_URL . ltrim($endpoint, '/');
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        // Make request
        $response = wp_remote_get($url, array(
            'headers' => array(
                'X-RapidAPI-Key' => $this->api_key,
                'X-RapidAPI-Host' => 'v3.football.api-sports.io'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON response', 'football-fixtures-pro'));
        }
        
        // Cache the response
        $settings = get_option('ffp_settings', array());
        $cache_duration = isset($settings['cache_duration']) ? $settings['cache_duration'] : 300;
        $this->cache->set($cache_key, $data, $cache_duration);
        
        return $data;
    }
    
    /**
     * Get fixtures by date
     */
    public function get_fixtures($date = null, $league_id = null, $season = null) {
        if (empty($date)) {
            $date = date('Y-m-d');
        }
        
        $params = array(
            'date' => $date
        );
        
        if ($league_id) {
            $params['league'] = $league_id;
        }
        
        if ($season) {
            $params['season'] = $season;
        } else {
            $params['season'] = date('Y');
        }
        
        return $this->make_request('fixtures', $params);
    }
    
    /**
     * Get leagues
     */
    public function get_leagues($country = null, $season = null) {
        $params = array();
        
        if ($country) {
            $params['country'] = $country;
        }
        
        if ($season) {
            $params['season'] = $season;
        } else {
            $params['season'] = date('Y');
        }
        
        return $this->make_request('leagues', $params);
    }
    
    /**
     * Get team information
     */
    public function get_team($team_id) {
        $params = array(
            'id' => $team_id
        );
        
        return $this->make_request('teams', $params);
    }
    
    /**
     * Get team form (last 5 matches)
     */
    public function get_team_form($team_id, $league_id = null, $season = null) {
        $params = array(
            'team' => $team_id,
            'last' => 5
        );
        
        if ($league_id) {
            $params['league'] = $league_id;
        }
        
        if ($season) {
            $params['season'] = $season;
        } else {
            $params['season'] = date('Y');
        }
        
        return $this->make_request('fixtures', $params);
    }
    
    /**
     * Get odds for a fixture
     */
    public function get_odds($fixture_id, $bet = '1X2') {
        $params = array(
            'fixture' => $fixture_id,
            'bet' => $bet
        );
        
        return $this->make_request('odds', $params);
    }
    
    /**
     * Get countries
     */
    public function get_countries() {
        return $this->make_request('countries');
    }
    
    /**
     * Process team form data
     */
    public function process_team_form($fixtures_data) {
        if (!isset($fixtures_data['response']) || empty($fixtures_data['response'])) {
            return '';
        }
        
        $form = '';
        $matches = array_slice($fixtures_data['response'], 0, 5); // Last 5 matches
        
        foreach ($matches as $match) {
            if ($match['fixture']['status']['short'] !== 'FT') {
                continue; // Skip unfinished matches
            }
            
            $home_goals = $match['goals']['home'];
            $away_goals = $match['goals']['away'];
            $team_id = $match['teams']['home']['id']; // Assuming we're checking home team
            
            if ($home_goals > $away_goals) {
                $form .= 'W';
            } elseif ($home_goals < $away_goals) {
                $form .= 'L';
            } else {
                $form .= 'D';
            }
        }
        
        return $form;
    }
    
    /**
     * Format fixture data for display
     */
    public function format_fixture_data($fixture) {
        $formatted = array(
            'fixture_id' => $fixture['fixture']['id'],
            'date' => $fixture['fixture']['date'],
            'timestamp' => $fixture['fixture']['timestamp'],
            'status' => $fixture['fixture']['status'],
            'league' => array(
                'id' => $fixture['league']['id'],
                'name' => $fixture['league']['name'],
                'country' => $fixture['league']['country'],
                'logo' => $fixture['league']['logo'],
                'season' => $fixture['league']['season']
            ),
            'home_team' => array(
                'id' => $fixture['teams']['home']['id'],
                'name' => $fixture['teams']['home']['name'],
                'logo' => $fixture['teams']['home']['logo']
            ),
            'away_team' => array(
                'id' => $fixture['teams']['away']['id'],
                'name' => $fixture['teams']['away']['name'],
                'logo' => $fixture['teams']['away']['logo']
            ),
            'goals' => $fixture['goals'],
            'score' => $fixture['score']
        );
        
        return $formatted;
    }
    
    /**
     * Get popular leagues
     */
    public function get_popular_leagues() {
        $popular_leagues = array(
            array('id' => 39, 'name' => 'Premier League', 'country' => 'England'),
            array('id' => 140, 'name' => 'La Liga', 'country' => 'Spain'),
            array('id' => 135, 'name' => 'Serie A', 'country' => 'Italy'),
            array('id' => 78, 'name' => 'Bundesliga', 'country' => 'Germany'),
            array('id' => 61, 'name' => 'Ligue 1', 'country' => 'France'),
            array('id' => 2, 'name' => 'UEFA Champions League', 'country' => 'World'),
            array('id' => 3, 'name' => 'UEFA Europa League', 'country' => 'World'),
            array('id' => 1, 'name' => 'World Cup', 'country' => 'World'),
            array('id' => 4, 'name' => 'Euro Championship', 'country' => 'World')
        );
        
        return $popular_leagues;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $response = $this->make_request('status');
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        if (isset($response['response']['requests'])) {
            return array(
                'success' => true,
                'message' => __('API connection successful', 'football-fixtures-pro'),
                'requests_remaining' => $response['response']['requests']['current']
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Unable to verify API connection', 'football-fixtures-pro')
        );
    }
}