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
            'limit' => 10,
            'show_logos' => 'true',
            'show_odds' => 'true',
            'show_form' => 'true',
            'odds_mode' => 'separate_section',
            'theme' => 'default',
            'id' => uniqid('ffp_')
        ), $atts);
        
        // Sanitize attributes
        $atts['title'] = sanitize_text_field($atts['title']);
        $atts['date'] = sanitize_text_field($atts['date']);
        $atts['leagues'] = sanitize_text_field($atts['leagues']);
        $atts['limit'] = absint($atts['limit']);
        $atts['show_logos'] = $atts['show_logos'] === 'true';
        $atts['show_odds'] = $atts['show_odds'] === 'true';
        $atts['show_form'] = $atts['show_form'] === 'true';
        $atts['odds_mode'] = sanitize_text_field($atts['odds_mode']);
        $atts['theme'] = sanitize_text_field($atts['theme']);
        $atts['id'] = sanitize_text_field($atts['id']);
        
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
        
        // Convert leagues string to array
        $league_ids = array();
        if (!empty($atts['leagues'])) {
            $league_ids = array_map('absint', explode(',', $atts['leagues']));
            $league_ids = array_filter($league_ids);
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
             data-leagues="<?php echo esc_attr($atts['leagues']); ?>">
            
            <?php if ($atts['title']): ?>
                <h2 class="ffp-section-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>
            
            <div class="ffp-date-selector">
                <input type="date" class="ffp-date-input" value="<?php echo esc_attr($atts['date']); ?>" 
                       data-container="<?php echo esc_attr($atts['id']); ?>">
                <span class="ffp-selected-date"><?php echo esc_html(date('d/m/Y', strtotime($atts['date']))); ?></span>
            </div>
            
            <div class="ffp-fixtures-container">
                <?php $this->render_fixtures_html($grouped_fixtures, $atts); ?>
            </div>
            
            <div class="ffp-loading" style="display: none;">
                <span class="ffp-spinner"></span>
                <span class="ffp-loading-text"><?php echo esc_html__('Loading fixtures...', 'football-fixtures-pro'); ?></span>
            </div>
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
            ?>
            <div class="ffp-league-section">
                <h3 class="ffp-league-title">
                    <img src="<?php echo esc_url($league_data['league']['logo']); ?>" 
                         alt="<?php echo esc_attr($league_data['league']['name']); ?>" 
                         class="ffp-league-logo" loading="lazy">
                    <?php echo esc_html($league_data['league']['name']); ?>
                </h3>
                
                <?php foreach ($league_data['matches'] as $fixture): ?>
                    <?php
                    $match_time = date('H:i\A', strtotime($fixture['fixture']['date']));
                    $match_date = date('l, d M Y', strtotime($fixture['fixture']['date']));
                    
                    // Get team form if enabled
                    $home_form = '';
                    $away_form = '';
                    if ($atts['show_form']) {
                        $home_form_data = $api->get_team_form($fixture['teams']['home']['id'], $fixture['league']['id']);
                        $away_form_data = $api->get_team_form($fixture['teams']['away']['id'], $fixture['league']['id']);
                        $home_form = $api->process_team_form($home_form_data);
                        $away_form = $api->process_team_form($away_form_data);
                    }
                    
                    // Get odds if enabled
                    $odds_data = null;
                    if ($atts['show_odds']) {
                        $odds_response = $api->get_odds($fixture['fixture']['id']);
                        if (!is_wp_error($odds_response) && isset($odds_response['response'][0])) {
                            $odds_data = $odds_response['response'][0];
                        }
                    }
                    ?>
                    
                    <div class="ffp-match-card" data-fixture-id="<?php echo esc_attr($fixture['fixture']['id']); ?>">
                        <div class="ffp-match-header">
                            <div class="ffp-match-date"><?php echo esc_html($match_date); ?></div>
                            <div class="ffp-match-time"><?php echo esc_html($match_time); ?></div>
                            <?php if ($fixture['fixture']['status']['short'] !== 'NS'): ?>
                                <div class="ffp-match-status ffp-status-<?php echo esc_attr(strtolower($fixture['fixture']['status']['short'])); ?>">
                                    <?php echo esc_html($fixture['fixture']['status']['long']); ?>
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
                                             loading="lazy">
                                    </div>
                                <?php elseif ($atts['show_odds'] && $atts['odds_mode'] === 'replace_logos' && $odds_data): ?>
                                    <div class="ffp-team-odds">
                                        <?php 
                                        $home_odds = $this->get_team_odds($odds_data, 'Home');
                                        echo $home_odds ? esc_html($home_odds) : '-';
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
                                            echo $home_odds ? esc_html($home_odds) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- VS Separator -->
                            <div class="ffp-vs-separator">
                                <span class="ffp-vs-text">VS</span>
                                <?php if ($fixture['fixture']['status']['short'] === 'FT'): ?>
                                    <div class="ffp-score">
                                        <?php echo esc_html($fixture['goals']['home'] . ' - ' . $fixture['goals']['away']); ?>
                                    </div>
                                <?php elseif (in_array($fixture['fixture']['status']['short'], ['1H', '2H', 'HT', 'ET', 'BT', 'P'])): ?>
                                    <div class="ffp-score ffp-live-score">
                                        <?php echo esc_html($fixture['goals']['home'] . ' - ' . $fixture['goals']['away']); ?>
                                        <span class="ffp-live-indicator"><?php echo esc_html__('LIVE', 'football-fixtures-pro'); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Away Team -->
                            <div class="ffp-team ffp-away-team">
                                <?php if ($atts['show_logos'] && $atts['odds_mode'] !== 'replace_logos'): ?>
                                    <div class="ffp-team-logo">
                                        <img src="<?php echo esc_url($fixture['teams']['away']['logo']); ?>" 
                                             alt="<?php echo esc_attr($fixture['teams']['away']['name']); ?>" 
                                             loading="lazy">
                                    </div>
                                <?php elseif ($atts['show_odds'] && $atts['odds_mode'] === 'replace_logos' && $odds_data): ?>
                                    <div class="ffp-team-odds">
                                        <?php 
                                        $away_odds = $this->get_team_odds($odds_data, 'Away');
                                        echo $away_odds ? esc_html($away_odds) : '-';
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
                                            echo $away_odds ? esc_html($away_odds) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($atts['show_odds'] && $atts['odds_mode'] === 'separate_section' && $odds_data): ?>
                            <div class="ffp-odds-section">
                                <div class="ffp-odds-title"><?php echo esc_html__('Match Odds (1X2)', 'football-fixtures-pro'); ?></div>
                                <div class="ffp-odds-container">
                                    <div class="ffp-odd-item">
                                        <span class="ffp-odd-label"><?php echo esc_html__('1', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Home') ?: '-'); ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html($fixture['teams']['home']['name']); ?></span>
                                    </div>
                                    <div class="ffp-odd-item">
                                        <span class="ffp-odd-label"><?php echo esc_html__('X', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Draw') ?: '-'); ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html__('Draw', 'football-fixtures-pro'); ?></span>
                                    </div>
                                    <div class="ffp-odd-item">
                                        <span class="ffp-odd-label"><?php echo esc_html__('2', 'football-fixtures-pro'); ?></span>
                                        <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Away') ?: '-'); ?></span>
                                        <span class="ffp-odd-description"><?php echo esc_html($fixture['teams']['away']['name']); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="ffp-match-actions">
                            <button class="ffp-bet-button ffp-bet-now" data-fixture="<?php echo esc_attr($fixture['fixture']['id']); ?>">
                                <?php echo esc_html__('BET NOW', 'football-fixtures-pro'); ?>
                            </button>
                            <button class="ffp-stake-button" data-fixture="<?php echo esc_attr($fixture['fixture']['id']); ?>">
                                <?php echo esc_html__('STAKE', 'football-fixtures-pro'); ?>
                            </button>
                            <div class="ffp-match-info">
                                <span class="ffp-venue" title="<?php echo esc_attr($fixture['fixture']['venue']['name']); ?>">
                                    <i class="ffp-icon-location"></i>
                                    <?php echo esc_html($fixture['fixture']['venue']['city']); ?>
                                </span>
                                <?php if (isset($fixture['fixture']['referee']) && $fixture['fixture']['referee']): ?>
                                    <span class="ffp-referee" title="<?php echo esc_attr__('Referee', 'football-fixtures-pro'); ?>">
                                        <i class="ffp-icon-whistle"></i>
                                        <?php echo esc_html($fixture['fixture']['referee']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
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
     * AJAX handler for loading fixtures
     */
    public function ajax_load_fixtures() {
        check_ajax_referer('ffp_nonce', 'nonce');
        
        $date = sanitize_text_field($_POST['date']);
        $leagues = sanitize_text_field($_POST['leagues']);
        $container_id = sanitize_text_field($_POST['container_id']);
        $show_logos = $_POST['show_logos'] === 'true';
        $show_odds = $_POST['show_odds'] === 'true';
        $show_form = $_POST['show_form'] === 'true';
        $odds_mode = sanitize_text_field($_POST['odds_mode']);
        $limit = absint($_POST['limit']);
        
        // Validate date
        if (!strtotime($date)) {
            wp_send_json_error(__('Invalid date format', 'football-fixtures-pro'));
        }
        
        // Convert leagues string to array
        $league_ids = array();
        if (!empty($leagues)) {
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
            'limit' => $limit
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
}