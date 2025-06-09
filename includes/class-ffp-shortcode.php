<?php
/**
 * Shortcode Handler Class
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class FFP_Shortcode {
    
    /**
     * Shortcode instance
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
        add_shortcode('football_fixtures', array($this, 'render_shortcode'));
        add_action('wp_ajax_ffp_load_fixtures', array($this, 'ajax_load_fixtures'));
        add_action('wp_ajax_nopriv_ffp_load_fixtures', array($this, 'ajax_load_fixtures'));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Free VIP TIPS',
            'date' => date('Y-m-d'),
            'leagues' => '',
            'region' => '',
            'limit' => 10,
            'show_logos' => 'true',
            'show_odds' => 'true',
            'show_form' => 'true',
            'odds_mode' => 'separate_section',
            'bookmaker' => '1',
            'theme' => 'default',
            'id' => uniqid('ffp_'),
            'auto_refresh' => 'false',
            'show_venue' => 'true',
            'odds_format' => 'decimal'
        ), $atts);
        
        // Sanitize attributes
        $atts['title'] = sanitize_text_field($atts['title']);
        $atts['date'] = sanitize_text_field($atts['date']);
        $atts['leagues'] = sanitize_text_field($atts['leagues']);
        $atts['region'] = sanitize_text_field($atts['region']);
        $atts['limit'] = absint($atts['limit']);
        $atts['show_logos'] = $atts['show_logos'] === 'true';
        $atts['show_odds'] = $atts['show_odds'] === 'true';
        $atts['show_form'] = $atts['show_form'] === 'true';
        $atts['odds_mode'] = sanitize_text_field($atts['odds_mode']);
        $atts['bookmaker'] = sanitize_text_field($atts['bookmaker']);
        $atts['theme'] = sanitize_text_field($atts['theme']);
        $atts['id'] = sanitize_text_field($atts['id']);
        $atts['auto_refresh'] = $atts['auto_refresh'] === 'true';
        $atts['show_venue'] = $atts['show_venue'] === 'true';
        $atts['odds_format'] = sanitize_text_field($atts['odds_format']);
        
        // Validate date
        if (!strtotime($atts['date'])) {
            $atts['date'] = date('Y-m-d');
        }
        
        // Validate limit
        if ($atts['limit'] < 1) {
            $atts['limit'] = 10;
        } elseif ($atts['limit'] > 100) {
            $atts['limit'] = 100;
        }
        
        // Validate bookmaker
        if (empty($atts['bookmaker'])) {
            $atts['bookmaker'] = '1'; // Default to 1xBet
        }
        
        // Validate theme
        $valid_themes = array('default', 'dark', 'compact', 'minimal', 'african', 'cameroon', 'mls', 'international');
        if (!in_array($atts['theme'], $valid_themes)) {
            $atts['theme'] = 'default';
        }
        
        // Validate odds mode
        $valid_odds_modes = array('replace_logos', 'below_teams', 'separate_section');
        if (!in_array($atts['odds_mode'], $valid_odds_modes)) {
            $atts['odds_mode'] = 'separate_section';
        }
        
        // Convert leagues string to array or get by region
        $league_ids = array();
        if (!empty($atts['region'])) {
            // Get leagues by region
            if (class_exists('FFP_API')) {
                $api = FFP_API::get_instance();
                $region_leagues = $api->get_leagues_by_region($atts['region']);
                $league_ids = array_column($region_leagues, 'id');
            }
        } elseif (!empty($atts['leagues'])) {
            $league_ids = array_map('absint', explode(',', $atts['leagues']));
            $league_ids = array_filter($league_ids);
        }
        
        // Check if API class exists
        if (!class_exists('FFP_API')) {
            return '<div class="ffp-error">' . esc_html__('Football Fixtures Pro API not available. Please check plugin installation.', 'football-fixtures-pro') . '</div>';
        }
        
        // Get fixtures
        $api = FFP_API::get_instance();
        $fixtures_response = $api->get_fixtures($atts['date']);
        
        if (is_wp_error($fixtures_response)) {
            return '<div class="ffp-error">' . esc_html($fixtures_response->get_error_message()) . '</div>';
        }
        
        if (!isset($fixtures_response['response']) || empty($fixtures_response['response'])) {
            return '<div class="ffp-no-matches">' . esc_html__('No matches found for selected date.', 'football-fixtures-pro') . '</div>';
        }
        
        $fixtures = $fixtures_response['response'];
        
        // Filter by leagues if specified
        if (!empty($league_ids)) {
            $fixtures = array_filter($fixtures, function($fixture) use ($league_ids) {
                return in_array($fixture['league']['id'], $league_ids);
            });
        }
        
        // Limit fixtures
        $fixtures = array_slice($fixtures, 0, $atts['limit']);
        
        // Group by league
        $grouped_fixtures = array();
        foreach ($fixtures as $fixture) {
            $league_id = $fixture['league']['id'];
            $grouped_fixtures[$league_id]['league'] = $fixture['league'];
            $grouped_fixtures[$league_id]['matches'][] = $fixture;
        }
        
        // Generate HTML
        ob_start();
        ?>
        <div class="ffp-shortcode-container ffp-theme-<?php echo esc_attr($atts['theme']); ?>" 
             id="<?php echo esc_attr($atts['id']); ?>" 
             data-date="<?php echo esc_attr($atts['date']); ?>"
             data-leagues="<?php echo esc_attr($atts['leagues']); ?>"
             data-region="<?php echo esc_attr($atts['region']); ?>"
             data-bookmaker="<?php echo esc_attr($atts['bookmaker']); ?>"
             data-auto-refresh="<?php echo esc_attr($atts['auto_refresh'] ? 'true' : 'false'); ?>"
             data-show-logos="<?php echo esc_attr($atts['show_logos'] ? 'true' : 'false'); ?>"
             data-show-odds="<?php echo esc_attr($atts['show_odds'] ? 'true' : 'false'); ?>"
             data-show-form="<?php echo esc_attr($atts['show_form'] ? 'true' : 'false'); ?>"
             data-odds-mode="<?php echo esc_attr($atts['odds_mode']); ?>"
             data-limit="<?php echo esc_attr($atts['limit']); ?>">
            
            <?php if ($atts['title']): ?>
                <h2 class="ffp-section-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="ffp-date-selector">
                <input type="date" class="ffp-date-input" value="<?php echo esc_attr($atts['date']); ?>" 
                       data-container="<?php echo esc_attr($atts['id']); ?>">
                <span class="ffp-selected-date"><?php echo esc_html(date('d/m/Y', strtotime($atts['date']))); ?></span>
                
                <?php if (!empty($atts['region'])): ?>
                    <span class="ffp-region-indicator">
                        <?php printf(__('Showing: %s', 'football-fixtures-pro'), esc_html(ucwords(str_replace('_', ' ', $atts['region'])))); ?>
                    </span>
                <?php endif; ?>
                
                <?php if ($atts['auto_refresh']): ?>
                    <span class="ffp-auto-refresh-indicator" title="<?php echo esc_attr__('Auto-refreshing live matches', 'football-fixtures-pro'); ?>">
                        <i class="ffp-icon-refresh"></i>
                        <?php echo esc_html__('Live', 'football-fixtures-pro'); ?>
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="ffp-fixtures-container">
                <?php $this->render_fixtures_html($grouped_fixtures, $atts); ?>
            </div>
            
            <div class="ffp-loading" style="display: none;">
                <span class="ffp-spinner"></span>
                <span class="ffp-loading-text"><?php echo esc_html__('Loading fixtures...', 'football-fixtures-pro'); ?></span>
            </div>
            
            <?php if (empty($grouped_fixtures)): ?>
                <div class="ffp-empty-state">
                    <div class="ffp-empty-icon">⚽</div>
                    <h3><?php echo esc_html__('No matches found', 'football-fixtures-pro'); ?></h3>
                    <p><?php echo esc_html__('Try selecting a different date or region.', 'football-fixtures-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Render fixtures HTML
     */
    private function render_fixtures_html($grouped_fixtures, $atts) {
        $api = FFP_API::get_instance();
        
        foreach ($grouped_fixtures as $league_data):
            $league_priority = $this->get_league_priority($league_data['league']['id']);
            $competition_type = $this->get_competition_type($league_data['league']['id']);
            ?>
            <div class="ffp-league-section ffp-league-priority-<?php echo esc_attr($league_priority); ?> ffp-competition-<?php echo esc_attr($competition_type); ?>">
                <h3 class="ffp-league-title">
                    <div class="ffp-league-info">
                        <img src="<?php echo esc_url($league_data['league']['logo']); ?>" 
                             alt="<?php echo esc_attr($league_data['league']['name']); ?>" 
                             class="ffp-league-logo" loading="lazy">
                        <span class="ffp-league-name"><?php echo esc_html($league_data['league']['name']); ?></span>
                        <span class="ffp-league-country"><?php echo esc_html($league_data['league']['country']); ?></span>
                    </div>
                    <div class="ffp-league-meta">
                        <span class="ffp-match-count"><?php printf(__('%d matches', 'football-fixtures-pro'), count($league_data['matches'])); ?></span>
                        <?php if ($league_data['league']['season']): ?>
                            <span class="ffp-season-info"><?php echo esc_html($league_data['league']['season']); ?></span>
                        <?php endif; ?>
                    </div>
                </h3>
                
                <?php foreach ($league_data['matches'] as $fixture): ?>
                    <?php
                    $match_time = date('H:i\A', strtotime($fixture['fixture']['date']));
                    $match_date = date('l, d M Y', strtotime($fixture['fixture']['date']));
                    $is_live = in_array($fixture['fixture']['status']['short'], ['1H', '2H', 'HT', 'ET', 'BT', 'P']);
                    $is_finished = $fixture['fixture']['status']['short'] === 'FT';
                    
                    // Get team form if enabled
                    $home_form = '';
                    $away_form = '';
                    if ($atts['show_form']) {
                        $home_form_data = $api->get_team_form($fixture['teams']['home']['id'], $fixture['league']['id']);
                        $away_form_data = $api->get_team_form($fixture['teams']['away']['id'], $fixture['league']['id']);
                        if (!is_wp_error($home_form_data)) {
                            $home_form = $api->process_team_form($home_form_data);
                        }
                        if (!is_wp_error($away_form_data)) {
                            $away_form = $api->process_team_form($away_form_data);
                        }
                    }
                    
                    // Get odds if enabled with preferred bookmaker
                    $odds_data = null;
                    $bookmaker_name = '';
                    if ($atts['show_odds']) {
                        $bookmaker = !empty($atts['bookmaker']) ? $atts['bookmaker'] : '1';
                        $odds_response = $api->get_odds($fixture['fixture']['id'], '1X2', $bookmaker);
                        if (!is_wp_error($odds_response) && isset($odds_response['response'][0])) {
                            $odds_data = $odds_response['response'][0];
                            if (isset($odds_data['bookmakers'][0]['name'])) {
                                $bookmaker_name = $odds_data['bookmakers'][0]['name'];
                            }
                        }
                    }
                    
                    $match_classes = array('ffp-match-card');
                    if ($is_live) $match_classes[] = 'ffp-match-live';
                    if ($is_finished) $match_classes[] = 'ffp-match-finished';
                    ?>
                    
                    <div class="<?php echo esc_attr(implode(' ', $match_classes)); ?>" 
                         data-fixture-id="<?php echo esc_attr($fixture['fixture']['id']); ?>"
                         data-status="<?php echo esc_attr($fixture['fixture']['status']['short']); ?>">
                        
                        <div class="ffp-match-header">
                            <div class="ffp-match-date"><?php echo esc_html($match_date); ?></div>
                            <div class="ffp-match-time"><?php echo esc_html($match_time); ?></div>
                            
                            <?php if ($fixture['fixture']['status']['short'] !== 'NS'): ?>
                                <div class="ffp-match-status ffp-status-<?php echo esc_attr(strtolower($fixture['fixture']['status']['short'])); ?>">
                                    <?php echo esc_html($fixture['fixture']['status']['long']); ?>
                                    <?php if ($is_live): ?>
                                        <span class="ffp-live-pulse"></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($bookmaker_name): ?>
                                <div class="ffp-bookmaker-badge ffp-bookmaker-<?php echo esc_attr(strtolower(str_replace(' ', '', $bookmaker_name))); ?>">
                                    <span class="ffp-bookmaker-name"><?php echo esc_html($bookmaker_name); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="ffp-match-teams">
                            <!-- Home Team -->
                            <div class="ffp-team ffp-home-team">
                                <?php if ($atts['show_logos'] && $atts['odds_mode'] !== 'replace_logos'): ?>
                                    <div class="ffp-team-logo">
                                        <img src="<?php echo esc_url($fixture['teams']['home']['logo']); ?>" 
                                             alt="<?php echo esc_attr($fixture['teams']['home']['name']); ?>" 
                                             loading="lazy"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php elseif ($atts['show_odds'] && $atts['odds_mode'] === 'replace_logos' && $odds_data): ?>
                                    <div class="ffp-team-odds">
                                        <?php 
                                        $home_odds = $this->get_team_odds($odds_data, 'Home');
                                        echo $home_odds ? esc_html($this->format_odds($home_odds, $atts['odds_format'])) : '-';
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ffp-team-info">
                                    <h4 class="ffp-team-name"><?php echo esc_html($fixture['teams']['home']['name']); ?></h4>
                                    <?php if ($atts['show_form'] && $home_form): ?>
                                        <div class="ffp-team-form">
                                            <?php echo $this->render_team_form($home_form); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($atts['show_odds'] && $atts['odds_mode'] === 'below_teams' && $odds_data): ?>
                                        <div class="ffp-team-odds-below">
                                            <?php 
                                            $home_odds = $this->get_team_odds($odds_data, 'Home');
                                            echo $home_odds ? esc_html($this->format_odds($home_odds, $atts['odds_format'])) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- VS Separator -->
                            <div class="ffp-vs-separator">
                                <span class="ffp-vs-text">VS</span>
                                <?php if ($is_finished): ?>
                                    <div class="ffp-score ffp-final-score">
                                        <?php echo esc_html($fixture['goals']['home'] . ' - ' . $fixture['goals']['away']); ?>
                                    </div>
                                <?php elseif ($is_live): ?>
                                    <div class="ffp-score ffp-live-score">
                                        <?php echo esc_html($fixture['goals']['home'] . ' - ' . $fixture['goals']['away']); ?>
                                        <span class="ffp-live-indicator"><?php echo esc_html__('LIVE', 'football-fixtures-pro'); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="ffp-kickoff-time">
                                        <?php echo esc_html($match_time); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Away Team -->
                            <div class="ffp-team ffp-away-team">
                                <?php if ($atts['show_logos'] && $atts['odds_mode'] !== 'replace_logos'): ?>
                                    <div class="ffp-team-logo">
                                        <img src="<?php echo esc_url($fixture['teams']['away']['logo']); ?>" 
                                             alt="<?php echo esc_attr($fixture['teams']['away']['name']); ?>" 
                                             loading="lazy"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php elseif ($atts['show_odds'] && $atts['odds_mode'] === 'replace_logos' && $odds_data): ?>
                                    <div class="ffp-team-odds">
                                        <?php 
                                        $away_odds = $this->get_team_odds($odds_data, 'Away');
                                        echo $away_odds ? esc_html($this->format_odds($away_odds, $atts['odds_format'])) : '-';
                                        ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ffp-team-info">
                                    <h4 class="ffp-team-name"><?php echo esc_html($fixture['teams']['away']['name']); ?></h4>
                                    <?php if ($atts['show_form'] && $away_form): ?>
                                        <div class="ffp-team-form">
                                            <?php echo $this->render_team_form($away_form); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($atts['show_odds'] && $atts['odds_mode'] === 'below_teams' && $odds_data): ?>
                                        <div class="ffp-team-odds-below">
                                            <?php 
                                            $away_odds = $this->get_team_odds($odds_data, 'Away');
                                            echo $away_odds ? esc_html($this->format_odds($away_odds, $atts['odds_format'])) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($atts['show_odds'] && $atts['odds_mode'] === 'separate_section' && $odds_data): ?>
                            <div class="ffp-odds-section">
                                <div class="ffp-odds-title">
                                    <?php echo esc_html__('Match Odds (1X2)', 'football-fixtures-pro'); ?>
                                    <?php if ($bookmaker_name): ?>
                                        <span class="ffp-odds-source">via <?php echo esc_html($bookmaker_name); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="ffp-odds-container">
                                    <div class="ffp-odd-item" data-bet-type="home">
                                        <span class="ffp-odd-label"><?php echo esc_html__('1', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php 
                                            $home_odds = $this->get_team_odds($odds_data, 'Home');
                                            echo $home_odds ? esc_html($this->format_odds($home_odds, $atts['odds_format'])) : '-';
                                        ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html($fixture['teams']['home']['name']); ?></span>
                                    </div>
                                    <div class="ffp-odd-item" data-bet-type="draw">
                                        <span class="ffp-odd-label"><?php echo esc_html__('X', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php 
                                            $draw_odds = $this->get_team_odds($odds_data, 'Draw');
                                            echo $draw_odds ? esc_html($this->format_odds($draw_odds, $atts['odds_format'])) : '-';
                                        ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html__('Draw', 'football-fixtures-pro'); ?></span>
                                    </div>
                                    <div class="ffp-odd-item" data-bet-type="away">
                                        <span class="ffp-odd-label"><?php echo esc_html__('2', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php 
                                            $away_odds = $this->get_team_odds($odds_data, 'Away');
                                            echo $away_odds ? esc_html($this->format_odds($away_odds, $atts['odds_format'])) : '-';
                                        ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html($fixture['teams']['away']['name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ffp-match-actions">
                            <div class="ffp-action-buttons">
                                <button class="ffp-bet-button ffp-bet-now" data-fixture="<?php echo esc_attr($fixture['fixture']['id']); ?>">
                                    <?php echo esc_html__('BET NOW', 'football-fixtures-pro'); ?>
                                </button>
                                <button class="ffp-stake-button" data-fixture="<?php echo esc_attr($fixture['fixture']['id']); ?>">
                                    <?php echo esc_html__('STAKE', 'football-fixtures-pro'); ?>
                                </button>
                            </div>
                            
                            <?php if ($atts['show_venue'] || isset($fixture['fixture']['referee'])): ?>
                                <div class="ffp-match-info">
                                    <?php if ($atts['show_venue'] && isset($fixture['fixture']['venue']['city'])): ?>
                                        <span class="ffp-venue" title="<?php echo esc_attr($fixture['fixture']['venue']['name'] ?? ''); ?>">
                                            <i class="ffp-icon-location"></i>
                                            <?php echo esc_html($fixture['fixture']['venue']['city']); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($fixture['fixture']['referee']) && $fixture['fixture']['referee']): ?>
                                        <span class="ffp-referee" title="<?php echo esc_attr__('Referee', 'football-fixtures-pro'); ?>">
                                            <i class="ffp-icon-whistle"></i>
                                            <?php echo esc_html($fixture['fixture']['referee']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach;
    }
    
    /**
     * Render team form
     */
    private function render_team_form($form) {
        $form_html = '';
        $form_chars = str_split($form);
        
        foreach ($form_chars as $char) {
            $class = '';
            $title = '';
            switch ($char) {
                case 'W':
                    $class = 'ffp-form-win';
                    $title = __('Win', 'football-fixtures-pro');
                    break;
                case 'L':
                    $class = 'ffp-form-loss';
                    $title = __('Loss', 'football-fixtures-pro');
                    break;
                case 'D':
                    $class = 'ffp-form-draw';
                    $title = __('Draw', 'football-fixtures-pro');
                    break;
            }
            $form_html .= '<span class="ffp-form-char ' . $class . '" title="' . esc_attr($title) . '">' . esc_html($char) . '</span>';
        }
        
        return $form_html;
    }
    
    /**
     * Get team odds from odds data
     */
    private function get_team_odds($odds_data, $type) {
        if (!isset($odds_data['bookmakers'][0]['bets'][0]['values'])) {
            return null;
        }
        
        $values = $odds_data['bookmakers'][0]['bets'][0]['values'];
        
        foreach ($values as $value) {
            if ($value['value'] === $type) {
                return $value['odd'];
            }
        }
        
        return null;
    }
    
    /**
     * Format odds based on format preference
     */
    private function format_odds($decimal_odds, $format = 'decimal') {
        switch ($format) {
            case 'fractional':
                return $this->decimal_to_fractional($decimal_odds);
            case 'american':
                return $this->decimal_to_american($decimal_odds);
            case 'decimal':
            default:
                return number_format($decimal_odds, 2);
        }
    }
    
    /**
     * Convert decimal odds to fractional
     */
    private function decimal_to_fractional($decimal) {
        $decimal = (float) $decimal;
        if ($decimal <= 1) return '1/1';
        
        $decimal = $decimal - 1;
        $tolerance = 1.e-6;
        $h1 = 1; $h2 = 0;
        $k1 = 0; $k2 = 1;
        $b = 1 / $decimal;
        do {
            $b = 1 / $b;
            $a = floor($b);
            $aux = $h1; $h1 = $a * $h1 + $h2; $h2 = $aux;
            $aux = $k1; $k1 = $a * $k1 + $k2; $k2 = $aux;
            $b = $b - $a;
        } while (abs($decimal - $h1 / $k1) > $decimal * $tolerance);
        
        return "$h1/$k1";
    }
    
    /**
     * Convert decimal odds to American
     */
    private function decimal_to_american($decimal) {
        $decimal = (float) $decimal;
        if ($decimal >= 2) {
            return '+' . round(($decimal - 1) * 100);
        } else {
            return round(-100 / ($decimal - 1));
        }
    }
    
    /**
     * Get league priority
     */
    private function get_league_priority($league_id) {
        $high_priority = array(1, 2, 3, 39, 140, 135, 78, 61); // World Cup, Champions League, Top 5 leagues
        $medium_priority = array(6, 12, 13, 94, 88, 253, 262, 71, 128); // AFCON, CAF, MLS, etc.
        
        if (in_array($league_id, $high_priority)) {
            return 'high';
        } elseif (in_array($league_id, $medium_priority)) {
            return 'medium';
        }
        
        return 'low';
    }
    
    /**
     * Get competition type
     */
    private function get_competition_type($league_id) {
        $international = array(1, 4, 6, 7, 8, 9, 10, 17, 18, 19); // World Cup, Continental championships, Friendlies
        $continental = array(2, 3, 5, 11, 12, 13, 15, 16, 848); // Champions League, Europa League, etc.
        $friendly = array(10); // International friendlies
        
        if (in_array($league_id, $friendly)) {
            return 'friendly';
        } elseif (in_array($league_id, $international)) {
            return 'international';
        } elseif (in_array($league_id, $continental)) {
            return 'continental';
        }
        
        return 'national';
    }
    
    /**
     * AJAX handler for loading fixtures
     */
    public function ajax_load_fixtures() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $leagues = sanitize_text_field($_POST['leagues']);
        $region = sanitize_text_field($_POST['region']);
        $container_id = sanitize_text_field($_POST['container_id']);
        $show_logos = $_POST['show_logos'] === 'true';
        $show_odds = $_POST['show_odds'] === 'true';
        $show_form = $_POST['show_form'] === 'true';
        $odds_mode = sanitize_text_field($_POST['odds_mode']);
        $bookmaker = sanitize_text_field($_POST['bookmaker']);
        $limit = absint($_POST['limit']);
        $show_venue = isset($_POST['show_venue']) ? $_POST['show_venue'] === 'true' : true;
        $odds_format = sanitize_text_field($_POST['odds_format'] ?? 'decimal');
        
        // Validate date
        if (!strtotime($date)) {
            wp_send_json_error(__('Invalid date format', 'football-fixtures-pro'));
        }
        
        // Convert leagues string to array or get by region
        $league_ids = array();
        if (!empty($region)) {
            // Get leagues by region
            $api = FFP_API::get_instance();
            $region_leagues = $api->get_leagues_by_region($region);
            $league_ids = array_column($region_leagues, 'id');
        } elseif (!empty($leagues)) {
            $league_ids = array_map('absint', explode(',', $leagues));
            $league_ids = array_filter($league_ids);
        }
        
        // Get fixtures
        $api = FFP_API::get_instance();
        $fixtures_response = $api->get_fixtures($date);
        
        if (is_wp_error($fixtures_response)) {
            wp_send_json_error($fixtures_response->get_error_message());
        }
        
        if (!isset($fixtures_response['response']) || empty($fixtures_response['response'])) {
            wp_send_json_success('<div class="ffp-no-matches">' . esc_html__('No matches found for selected date.', 'football-fixtures-pro') . '</div>');
        }
        
        $fixtures = $fixtures_response['response'];
        
        // Filter by leagues if specified
        if (!empty($league_ids)) {
            $fixtures = array_filter($fixtures, function($fixture) use ($league_ids) {
                return in_array($fixture['league']['id'], $league_ids);
            });
        }
        
        // Limit fixtures
        $fixtures = array_slice($fixtures, 0, $limit);
        
        // Group by league
        $grouped_fixtures = array();
        foreach ($fixtures as $fixture) {
            $league_id = $fixture['league']['id'];
            $grouped_fixtures[$league_id]['league'] = $fixture['league'];
            $grouped_fixtures[$league_id]['matches'][] = $fixture;
        }
        
        // Prepare attributes for rendering
        $atts = array(
            'show_logos' => $show_logos,
            'show_odds' => $show_odds,
            'show_form' => $show_form,
            'odds_mode' => $odds_mode,
            'bookmaker' => $bookmaker,
            'limit' => $limit,
            'show_venue' => $show_venue,
            'odds_format' => $odds_format
        );
        
        // Generate HTML
        ob_start();
        $this->render_fixtures_html($grouped_fixtures, $atts);
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    /**
     * Get available leagues for admin
     */
    public function get_available_leagues() {
        $api = FFP_API::get_instance();
        $popular_leagues = $api->get_popular_leagues();
        
        return $popular_leagues;
    }
    
    /**
     * Get shortcode usage examples
     */
    public function get_shortcode_examples() {
        return array(
            'basic' => array(
                'title' => __('Basic Usage', 'football-fixtures-pro'),
                'code' => '[football_fixtures]',
                'description' => __('Shows all matches for today with default settings', 'football-fixtures-pro')
            ),
            'world_cup' => array(
                'title' => __('World Cup & International', 'football-fixtures-pro'),
                'code' => '[football_fixtures region="international" bookmaker="1" title="World Cup & International Matches"]',
                'description' => __('Shows international competitions and friendlies', 'football-fixtures-pro')
            ),
            'african' => array(
                'title' => __('African Football', 'football-fixtures-pro'),
                'code' => '[football_fixtures region="africa" bookmaker="1" title="African Football Today" theme="african"]',
                'description' => __('Shows AFCON, CAF competitions, and African leagues', 'football-fixtures-pro')
            ),
            'cameroon' => array(
                'title' => __('Cameroon Elite One', 'football-fixtures-pro'),
                'code' => '[football_fixtures leagues="233" bookmaker="1" title="Cameroon Elite One" theme="cameroon"]',
                'description' => __('Shows Cameroon domestic league matches', 'football-fixtures-pro')
            ),
            'mls' => array(
                'title' => __('Major League Soccer', 'football-fixtures-pro'),
                'code' => '[football_fixtures leagues="253" bookmaker="1" title="MLS Today" theme="mls"]',
                'description' => __('Shows MLS matches with MLS theme', 'football-fixtures-pro')
            ),
            'european' => array(
                'title' => __('Top European Leagues', 'football-fixtures-pro'),
                'code' => '[football_fixtures leagues="39,140,135,78,61" bookmaker="1" title="Top 5 European Leagues"]',
                'description' => __('Shows Premier League, La Liga, Serie A, Bundesliga, Ligue 1', 'football-fixtures-pro')
            ),
            'champions' => array(
                'title' => __('European Competitions', 'football-fixtures-pro'),
                'code' => '[football_fixtures leagues="2,3,848" bookmaker="1" title="European Competitions"]',
                'description' => __('Shows Champions League, Europa League, Conference League', 'football-fixtures-pro')
            ),
            'south_america' => array(
                'title' => __('South American Football', 'football-fixtures-pro'),
                'code' => '[football_fixtures region="south_america" bookmaker="1" title="South American Football"]',
                'description' => __('Shows Copa Libertadores, Serie A, Primera División, etc.', 'football-fixtures-pro')
            ),
            'custom_date' => array(
                'title' => __('Custom Date & Settings', 'football-fixtures-pro'),
                'code' => '[football_fixtures date="2025-06-15" leagues="39,2" show_form="true" odds_mode="below_teams" limit="5"]',
                'description' => __('Shows specific date with custom display options', 'football-fixtures-pro')
            ),
            'minimal' => array(
                'title' => __('Minimal Design', 'football-fixtures-pro'),
                'code' => '[football_fixtures theme="minimal" show_odds="false" show_form="false" title="Today\'s Matches"]',
                'description' => __('Clean, minimal display without odds or form', 'football-fixtures-pro')
            )
        );
    }
    
    /**
     * Validate shortcode parameters
     */
    public function validate_shortcode_params($atts) {
        $errors = array();
        
        // Validate date
        if (!empty($atts['date']) && !strtotime($atts['date'])) {
            $errors[] = __('Invalid date format. Please use YYYY-MM-DD.', 'football-fixtures-pro');
        }
        
        // Validate limit
        if (!empty($atts['limit'])) {
            $limit = absint($atts['limit']);
            if ($limit < 1 || $limit > 100) {
                $errors[] = __('Limit must be between 1 and 100.', 'football-fixtures-pro');
            }
        }
        
        // Validate theme
        if (!empty($atts['theme'])) {
            $valid_themes = array('default', 'dark', 'compact', 'minimal', 'african', 'cameroon', 'mls', 'international');
            if (!in_array($atts['theme'], $valid_themes)) {
                $errors[] = sprintf(__('Invalid theme. Valid themes are: %s', 'football-fixtures-pro'), implode(', ', $valid_themes));
            }
        }
        
        // Validate odds mode
        if (!empty($atts['odds_mode'])) {
            $valid_modes = array('replace_logos', 'below_teams', 'separate_section');
            if (!in_array($atts['odds_mode'], $valid_modes)) {
                $errors[] = sprintf(__('Invalid odds mode. Valid modes are: %s', 'football-fixtures-pro'), implode(', ', $valid_modes));
            }
        }
        
        // Validate region
        if (!empty($atts['region'])) {
            $valid_regions = array('europe', 'africa', 'north_america', 'south_america', 'asia', 'international');
            if (!in_array($atts['region'], $valid_regions)) {
                $errors[] = sprintf(__('Invalid region. Valid regions are: %s', 'football-fixtures-pro'), implode(', ', $valid_regions));
            }
        }
        
        // Validate odds format
        if (!empty($atts['odds_format'])) {
            $valid_formats = array('decimal', 'fractional', 'american');
            if (!in_array($atts['odds_format'], $valid_formats)) {
                $errors[] = sprintf(__('Invalid odds format. Valid formats are: %s', 'football-fixtures-pro'), implode(', ', $valid_formats));
            }
        }
        
        return $errors;
    }
    
    /**
     * Get default shortcode attributes
     */
    public function get_default_attributes() {
        return array(
            'title' => 'Free VIP TIPS',
            'date' => date('Y-m-d'),
            'leagues' => '',
            'region' => '',
            'limit' => 10,
            'show_logos' => 'true',
            'show_odds' => 'true',
            'show_form' => 'true',
            'odds_mode' => 'separate_section',
            'bookmaker' => '1',
            'theme' => 'default',
            'auto_refresh' => 'false',
            'show_venue' => 'true',
            'odds_format' => 'decimal'
        );
    }
    
    /**
     * Get regional league mappings
     */
    public function get_regional_leagues() {
        return array(
            'europe' => array(39, 140, 135, 78, 61, 94, 88, 2, 3, 5, 848),
            'africa' => array(6, 12, 13, 14, 233, 200, 202, 204, 207, 208, 218, 220, 244, 253),
            'north_america' => array(253, 262, 254, 16, 17),
            'south_america' => array(71, 128, 131, 239, 242, 271, 273, 11, 13, 9),
            'asia' => array(188, 292, 169, 15, 16, 7),
            'international' => array(1, 4, 6, 7, 8, 9, 10, 32, 33, 34, 35, 18, 19)
        );
    }
    
    /**
     * Get bookmaker information
     */
    public function get_bookmaker_info() {
        return array(
            '1' => array('name' => '1xBet', 'default' => true),
            '8' => array('name' => 'Bet365', 'default' => false),
            '11' => array('name' => 'William Hill', 'default' => false),
            '18' => array('name' => 'Betway', 'default' => false),
            '5' => array('name' => 'Unibet', 'default' => false),
            '9' => array('name' => 'Ladbrokes', 'default' => false),
            '10' => array('name' => 'Coral', 'default' => false),
            '6' => array('name' => 'Bwin', 'default' => false),
            '12' => array('name' => 'Pinnacle', 'default' => false),
            '14' => array('name' => 'MarathonBet', 'default' => false)
        );
    }
    
    /**
     * Generate shortcode documentation
     */
    public function generate_documentation() {
        $examples = $this->get_shortcode_examples();
        $attributes = $this->get_default_attributes();
        $bookmakers = $this->get_bookmaker_info();
        
        $doc = "## Football Fixtures Pro Shortcode Documentation\n\n";
        $doc .= "### Basic Usage\n";
        $doc .= "`[football_fixtures]`\n\n";
        
        $doc .= "### Available Parameters\n";
        foreach ($attributes as $param => $default) {
            $doc .= "- **{$param}**: Default `{$default}`\n";
        }
        
        $doc .= "\n### Examples\n";
        foreach ($examples as $example) {
            $doc .= "#### {$example['title']}\n";
            $doc .= "`{$example['code']}`\n";
            $doc .= "{$example['description']}\n\n";
        }
        
        $doc .= "### Available Bookmakers\n";
        foreach ($bookmakers as $id => $info) {
            $default_text = $info['default'] ? ' (Default)' : '';
            $doc .= "- **{$id}**: {$info['name']}{$default_text}\n";
        }
        
        return $doc;
    }
}