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
     * Get odds for a fixture with bookmaker support
     */
    public function get_odds($fixture_id, $bet = '1X2', $bookmaker = null) {
        $params = array(
            'fixture' => $fixture_id,
            'bet' => $bet
        );
        
        // Add bookmaker preference if specified
        if ($bookmaker) {
            $params['bookmaker'] = $bookmaker;
        }
        
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
     * Get popular leagues with comprehensive coverage
     */
    public function get_popular_leagues() {
        $popular_leagues = array(
            // Major European Leagues
            array('id' => 39, 'name' => 'Premier League', 'country' => 'England', 'active' => true),
            array('id' => 140, 'name' => 'La Liga', 'country' => 'Spain', 'active' => true),
            array('id' => 135, 'name' => 'Serie A', 'country' => 'Italy', 'active' => true),
            array('id' => 78, 'name' => 'Bundesliga', 'country' => 'Germany', 'active' => true),
            array('id' => 61, 'name' => 'Ligue 1', 'country' => 'France', 'active' => true),
            array('id' => 94, 'name' => 'Primeira Liga', 'country' => 'Portugal', 'active' => true),
            array('id' => 88, 'name' => 'Eredivisie', 'country' => 'Netherlands', 'active' => true),
            
            // International Competitions
            array('id' => 2, 'name' => 'UEFA Champions League', 'country' => 'Europe', 'active' => true),
            array('id' => 3, 'name' => 'UEFA Europa League', 'country' => 'Europe', 'active' => true),
            array('id' => 848, 'name' => 'UEFA Conference League', 'country' => 'Europe', 'active' => true),
            array('id' => 1, 'name' => 'World Cup', 'country' => 'World', 'active' => true),
            array('id' => 4, 'name' => 'Euro Championship', 'country' => 'Europe', 'active' => false),
            array('id' => 5, 'name' => 'UEFA Nations League', 'country' => 'Europe', 'active' => true),
            
            // World Cup Qualifiers & Friendlies
            array('id' => 32, 'name' => 'World Cup - Qualification CONCACAF', 'country' => 'North America', 'active' => true),
            array('id' => 34, 'name' => 'World Cup - Qualification Africa', 'country' => 'Africa', 'active' => true),
            array('id' => 33, 'name' => 'World Cup - Qualification Asia', 'country' => 'Asia', 'active' => true),
            array('id' => 35, 'name' => 'World Cup - Qualification South America', 'country' => 'South America', 'active' => true),
            array('id' => 10, 'name' => 'Friendlies', 'country' => 'World', 'active' => true),
            
            // Africa Cup of Nations & African Competitions
            array('id' => 6, 'name' => 'Africa Cup of Nations', 'country' => 'Africa', 'active' => true),
            array('id' => 12, 'name' => 'CAF Champions League', 'country' => 'Africa', 'active' => true),
            array('id' => 13, 'name' => 'CAF Confederation Cup', 'country' => 'Africa', 'active' => true),
            array('id' => 14, 'name' => 'AFCON Qualification', 'country' => 'Africa', 'active' => true),
            
            // African National Leagues
            array('id' => 233, 'name' => 'Elite One', 'country' => 'Cameroon', 'active' => true),
            array('id' => 200, 'name' => 'Egyptian Premier League', 'country' => 'Egypt', 'active' => true),
            array('id' => 202, 'name' => 'Botola Pro', 'country' => 'Morocco', 'active' => true),
            array('id' => 204, 'name' => 'Ligue 1', 'country' => 'Algeria', 'active' => true),
            array('id' => 207, 'name' => 'Premier League', 'country' => 'South Africa', 'active' => true),
            array('id' => 208, 'name' => 'Ligue 1', 'country' => 'Tunisia', 'active' => true),
            array('id' => 218, 'name' => 'Premier League', 'country' => 'Ghana', 'active' => true),
            array('id' => 220, 'name' => 'Girabola', 'country' => 'Angola', 'active' => true),
            array('id' => 244, 'name' => 'Premier League', 'country' => 'Kenya', 'active' => true),
            array('id' => 253, 'name' => 'Ligue 1', 'country' => 'Senegal', 'active' => true),
            
            // North American Leagues
            array('id' => 253, 'name' => 'Major League Soccer', 'country' => 'USA', 'active' => true),
            array('id' => 262, 'name' => 'Liga MX', 'country' => 'Mexico', 'active' => true),
            array('id' => 254, 'name' => 'Canadian Premier League', 'country' => 'Canada', 'active' => true),
            
            // South American Leagues
            array('id' => 71, 'name' => 'Serie A', 'country' => 'Brazil', 'active' => true),
            array('id' => 128, 'name' => 'Primera División', 'country' => 'Argentina', 'active' => true),
            array('id' => 131, 'name' => 'Primera División', 'country' => 'Chile', 'active' => true),
            array('id' => 239, 'name' => 'Primera A', 'country' => 'Colombia', 'active' => true),
            array('id' => 242, 'name' => 'Primera División', 'country' => 'Ecuador', 'active' => true),
            array('id' => 271, 'name' => 'Primera División', 'country' => 'Uruguay', 'active' => true),
            array('id' => 273, 'name' => 'Primera División', 'country' => 'Paraguay', 'active' => true),
            
            // South American Competitions
            array('id' => 11, 'name' => 'Copa Libertadores', 'country' => 'South America', 'active' => true),
            array('id' => 13, 'name' => 'Copa Sudamericana', 'country' => 'South America', 'active' => true),
            array('id' => 9, 'name' => 'Copa America', 'country' => 'South America', 'active' => true),
            
            // Asian Leagues & Competitions
            array('id' => 188, 'name' => 'J1 League', 'country' => 'Japan', 'active' => true),
            array('id' => 292, 'name' => 'K League 1', 'country' => 'South Korea', 'active' => true),
            array('id' => 169, 'name' => 'Super League', 'country' => 'China', 'active' => true),
            array('id' => 15, 'name' => 'AFC Champions League', 'country' => 'Asia', 'active' => true),
            array('id' => 16, 'name' => 'AFC Cup', 'country' => 'Asia', 'active' => true),
            array('id' => 7, 'name' => 'Asian Cup', 'country' => 'Asia', 'active' => true),
            
            // Oceania
            array('id' => 188, 'name' => 'A-League', 'country' => 'Australia', 'active' => true),
            
            // CONCACAF Competitions
            array('id' => 16, 'name' => 'CONCACAF Champions League', 'country' => 'North America', 'active' => true),
            array('id' => 17, 'name' => 'Gold Cup', 'country' => 'North America', 'active' => true),
            
            // Youth & Women's Competitions
            array('id' => 18, 'name' => 'FIFA U-20 World Cup', 'country' => 'World', 'active' => true),
            array('id' => 19, 'name' => 'FIFA U-17 World Cup', 'country' => 'World', 'active' => true),
            array('id' => 8, 'name' => 'FIFA Women\'s World Cup', 'country' => 'World', 'active' => true),
            
            // Olympic Football
            array('id' => 18, 'name' => 'Olympic Games', 'country' => 'World', 'active' => true),
        );
        
        return $popular_leagues;
    }
    
    /**
     * Get available bookmakers
     */
    public function get_available_bookmakers() {
        $bookmakers = array(
            '1xbet' => array(
                'id' => 1,
                'name' => '1xBet',
                'default' => true
            ),
            'bet365' => array(
                'id' => 8,
                'name' => 'Bet365',
                'default' => false
            ),
            'william_hill' => array(
                'id' => 11,
                'name' => 'William Hill',
                'default' => false
            ),
            'betway' => array(
                'id' => 18,
                'name' => 'Betway',
                'default' => false
            ),
            'unibet' => array(
                'id' => 5,
                'name' => 'Unibet',
                'default' => false
            ),
            'ladbrokes' => array(
                'id' => 9,
                'name' => 'Ladbrokes',
                'default' => false
            ),
            'coral' => array(
                'id' => 10,
                'name' => 'Coral',
                'default' => false
            ),
            'bwin' => array(
                'id' => 6,
                'name' => 'Bwin',
                'default' => false
            ),
            'pinnacle' => array(
                'id' => 12,
                'name' => 'Pinnacle',
                'default' => false
            ),
            'marathonbet' => array(
                'id' => 14,
                'name' => 'MarathonBet',
                'default' => false
            )
        );
        
        return $bookmakers;
    }
    
    /**
     * Get leagues by region/continent
     */
    public function get_leagues_by_region($region = 'all') {
        $all_leagues = $this->get_popular_leagues();
        
        if ($region === 'all') {
            return $all_leagues;
        }
        
        $region_mapping = array(
            'europe' => array('England', 'Spain', 'Italy', 'Germany', 'France', 'Portugal', 'Netherlands', 'Europe'),
            'africa' => array('Africa', 'Cameroon', 'Egypt', 'Morocco', 'Algeria', 'South Africa', 'Tunisia', 'Ghana', 'Angola', 'Kenya', 'Senegal'),
            'north_america' => array('USA', 'Mexico', 'Canada', 'North America'),
            'south_america' => array('Brazil', 'Argentina', 'Chile', 'Colombia', 'Ecuador', 'Uruguay', 'Paraguay', 'South America'),
            'asia' => array('Japan', 'South Korea', 'China', 'Asia'),
            'international' => array('World', 'Europe', 'Africa', 'Asia', 'North America', 'South America')
        );
        
        if (!isset($region_mapping[$region])) {
            return array();
        }
        
        return array_filter($all_leagues, function($league) use ($region_mapping, $region) {
            return in_array($league['country'], $region_mapping[$region]);
        });
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