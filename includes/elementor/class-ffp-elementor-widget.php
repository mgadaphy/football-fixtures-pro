<?php
/**
 * Elementor Football Fixtures Widget
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class FFP_Elementor_Widget extends Widget_Base {
    
    /**
     * Get widget name
     */
    public function get_name() {
        return 'football-fixtures-pro';
    }
    
    /**
     * Get widget title
     */
    public function get_title() {
        return __('Football Fixtures Pro', 'football-fixtures-pro');
    }
    
    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'fa fa-futbol-o';
    }
    
    /**
     * Get widget categories
     */
    public function get_categories() {
        return array('football-fixtures-pro');
    }
    
    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return array('football', 'fixtures', 'matches', 'odds', 'sports', 'api');
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // Content Section
        $this->start_controls_section(
            'content_section',
            array(
                'label' => __('Content Settings', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'section_title',
            array(
                'label' => __('Section Title', 'football-fixtures-pro'),
                'type' => Controls_Manager::TEXT,
                'default' => __('Free VIP TIPS', 'football-fixtures-pro'),
                'placeholder' => __('Enter section title', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'selected_date',
            array(
                'label' => __('Select Date', 'football-fixtures-pro'),
                'type' => Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d'),
                'description' => __('Select the date for fixtures', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'selected_leagues',
            array(
                'label' => __('Select Leagues', 'football-fixtures-pro'),
                'type' => Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => $this->get_leagues_options(),
                'default' => array(),
                'description' => __('Leave empty to show all leagues', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'matches_limit',
            array(
                'label' => __('Number of Matches', 'football-fixtures-pro'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 10,
            )
        );
        
        $this->end_controls_section();
        
        // Display Options Section
        $this->start_controls_section(
            'display_section',
            array(
                'label' => __('Display Options', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_team_logos',
            array(
                'label' => __('Show Team Logos', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'football-fixtures-pro'),
                'label_off' => __('Hide', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_odds',
            array(
                'label' => __('Show Odds', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'football-fixtures-pro'),
                'label_off' => __('Hide', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'show_team_form',
            array(
                'label' => __('Show Team Form', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'football-fixtures-pro'),
                'label_off' => __('Hide', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'odds_display_mode',
            array(
                'label' => __('Odds Display Mode', 'football-fixtures-pro'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'replace_logos' => __('Replace Team Logos', 'football-fixtures-pro'),
                    'below_teams' => __('Below Team Names', 'football-fixtures-pro'),
                    'separate_section' => __('Separate Section', 'football-fixtures-pro'),
                ),
                'default' => 'separate_section',
                'condition' => array(
                    'show_odds' => 'yes',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // Style Section
        $this->start_controls_section(
            'style_section',
            array(
                'label' => __('Style', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'label' => __('Title Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-section-title',
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Title Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ffp-section-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'team_typography',
                'label' => __('Team Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-team-name',
            )
        );
        
        $this->add_control(
            'card_background',
            array(
                'label' => __('Card Background', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ffp-match-card' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'card_border_radius',
            array(
                'label' => __('Card Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-match-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }
    
    /**
     * Get leagues options for select control
     */
    private function get_leagues_options() {
        // Return static options to avoid API calls in admin
        $options = array(
            '39' => 'Premier League (England)',
            '140' => 'La Liga (Spain)',
            '135' => 'Serie A (Italy)',
            '78' => 'Bundesliga (Germany)',
            '61' => 'Ligue 1 (France)',
            '2' => 'UEFA Champions League',
            '3' => 'UEFA Europa League',
            '1' => 'World Cup',
            '4' => 'Euro Championship'
        );
        
        return $options;
    }
    
    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Check if API class exists
        if (!class_exists('FFP_API')) {
            echo '<div class="ffp-error">' . esc_html__('API class not found. Please check plugin installation.', 'football-fixtures-pro') . '</div>';
            return;
        }
        
        $api = FFP_API::get_instance();
        
        // Get fixtures
        $date = $settings['selected_date'] ? date('Y-m-d', strtotime($settings['selected_date'])) : date('Y-m-d');
        $fixtures_response = $api->get_fixtures($date);
        
        if (is_wp_error($fixtures_response)) {
            echo '<div class="ffp-error">' . esc_html($fixtures_response->get_error_message()) . '</div>';
            return;
        }
        
        if (!isset($fixtures_response['response']) || empty($fixtures_response['response'])) {
            echo '<div class="ffp-no-matches">' . esc_html__('No matches found for selected date.', 'football-fixtures-pro') . '</div>';
            return;
        }
        
        $fixtures = $fixtures_response['response'];
        
        // Filter by selected leagues if any
        if (!empty($settings['selected_leagues'])) {
            $fixtures = array_filter($fixtures, function($fixture) use ($settings) {
                return in_array($fixture['league']['id'], $settings['selected_leagues']);
            });
        }
        
        // Limit matches
        $fixtures = array_slice($fixtures, 0, $settings['matches_limit']);
        
        // Group fixtures by league
        $grouped_fixtures = array();
        foreach ($fixtures as $fixture) {
            $league_id = $fixture['league']['id'];
            $grouped_fixtures[$league_id]['league'] = $fixture['league'];
            $grouped_fixtures[$league_id]['matches'][] = $fixture;
        }
        
        ?>
        <div class="ffp-widget-container">
            <?php if ($settings['section_title']): ?>
                <h2 class="ffp-section-title"><?php echo esc_html($settings['section_title']); ?></h2>
            <?php endif; ?>
            
            <div class="ffp-date-selector">
                <span class="ffp-selected-date"><?php echo esc_html(date('d/m/Y', strtotime($date))); ?></span>
            </div>
            
            <?php foreach ($grouped_fixtures as $league_data): ?>
                <div class="ffp-league-section">
                    <h3 class="ffp-league-title"><?php echo esc_html($league_data['league']['name']); ?></h3>
                    
                    <?php foreach ($league_data['matches'] as $fixture): ?>
                        <?php
                        $match_time = date('H:i\A', strtotime($fixture['fixture']['date']));
                        $match_date = date('l, d M Y', strtotime($fixture['fixture']['date']));
                        
                        // Get team form if enabled
                        $home_form = '';
                        $away_form = '';
                        if ($settings['show_team_form'] === 'yes') {
                            $home_form_data = $api->get_team_form($fixture['teams']['home']['id'], $fixture['league']['id']);
                            $away_form_data = $api->get_team_form($fixture['teams']['away']['id'], $fixture['league']['id']);
                            if (!is_wp_error($home_form_data)) {
                                $home_form = $api->process_team_form($home_form_data);
                            }
                            if (!is_wp_error($away_form_data)) {
                                $away_form = $api->process_team_form($away_form_data);
                            }
                        }
                        
                        // Get odds if enabled
                        $odds_data = null;
                        if ($settings['show_odds'] === 'yes') {
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
                            </div>
                            
                            <div class="ffp-match-teams">
                                <!-- Home Team -->
                                <div class="ffp-team ffp-home-team">
                                    <?php if ($settings['show_team_logos'] === 'yes' && $settings['odds_display_mode'] !== 'replace_logos'): ?>
                                        <div class="ffp-team-logo">
                                            <img src="<?php echo esc_url($fixture['teams']['home']['logo']); ?>" 
                                                 alt="<?php echo esc_attr($fixture['teams']['home']['name']); ?>" 
                                                 loading="lazy">
                                        </div>
                                    <?php elseif ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'replace_logos' && $odds_data): ?>
                                        <div class="ffp-team-odds">
                                            <?php 
                                            $home_odds = $this->get_team_odds($odds_data, 'Home');
                                            echo $home_odds ? esc_html($home_odds) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ffp-team-info">
                                        <h4 class="ffp-team-name"><?php echo esc_html($fixture['teams']['home']['name']); ?></h4>
                                        <?php if ($settings['show_team_form'] === 'yes' && $home_form): ?>
                                            <div class="ffp-team-form">
                                                <?php echo $this->render_team_form($home_form); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'below_teams' && $odds_data): ?>
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
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Away Team -->
                                <div class="ffp-team ffp-away-team">
                                    <?php if ($settings['show_team_logos'] === 'yes' && $settings['odds_display_mode'] !== 'replace_logos'): ?>
                                        <div class="ffp-team-logo">
                                            <img src="<?php echo esc_url($fixture['teams']['away']['logo']); ?>" 
                                                 alt="<?php echo esc_attr($fixture['teams']['away']['name']); ?>" 
                                                 loading="lazy">
                                        </div>
                                    <?php elseif ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'replace_logos' && $odds_data): ?>
                                        <div class="ffp-team-odds">
                                            <?php 
                                            $away_odds = $this->get_team_odds($odds_data, 'Away');
                                            echo $away_odds ? esc_html($away_odds) : '-';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="ffp-team-info">
                                        <h4 class="ffp-team-name"><?php echo esc_html($fixture['teams']['away']['name']); ?></h4>
                                        <?php if ($settings['show_team_form'] === 'yes' && $away_form): ?>
                                            <div class="ffp-team-form">
                                                <?php echo $this->render_team_form($away_form); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'below_teams' && $odds_data): ?>
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
                            
                            <?php if ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'separate_section' && $odds_data): ?>
                                <div class="ffp-odds-section">
                                    <div class="ffp-odds-container">
                                        <div class="ffp-odd-item">
                                            <span class="ffp-odd-label"><?php echo esc_html__('1', 'football-fixtures-pro'); ?></span>
                                            <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Home') ?: '-'); ?></span>
                                        </div>
                                        <div class="ffp-odd-item">
                                            <span class="ffp-odd-label"><?php echo esc_html__('X', 'football-fixtures-pro'); ?></span>
                                            <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Draw') ?: '-'); ?></span>
                                        </div>
                                        <div class="ffp-odd-item">
                                            <span class="ffp-odd-label"><?php echo esc_html__('2', 'football-fixtures-pro'); ?></span>
                                            <span class="ffp-odd-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Away') ?: '-'); ?></span>
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
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
    
    /**
     * Render team form
     */
    private function render_team_form($form) {
        $form_html = '';
        $form_chars = str_split($form);
        
        foreach ($form_chars as $char) {
            $class = '';
            switch ($char) {
                case 'W':
                    $class = 'ffp-form-win';
                    break;
                case 'L':
                    $class = 'ffp-form-loss';
                    break;
                case 'D':
                    $class = 'ffp-form-draw';
                    break;
            }
            $form_html .= '<span class="ffp-form-char ' . $class . '">' . esc_html($char) . '</span>';
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
}