<?php
/**
 * Enhanced Elementor Football Fixtures Widget
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
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Schemes\Typography;
use Elementor\Core\Schemes\Color;

class FFP_Elementor_Widget extends \Elementor\Widget_Base {
    
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
        return array('football', 'fixtures', 'matches', 'odds', 'sports', 'api', 'live', 'scores');
    }
    
    /**
     * Register widget controls
     */
    protected function register_controls() {
        
        // ================================
        // CONTENT SECTION
        // ================================
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
                'dynamic' => array('active' => true),
            )
        );
        
        $this->add_control(
            'selected_date',
            array(
                'label' => __('Default Date', 'football-fixtures-pro'),
                'type' => Controls_Manager::DATE_TIME,
                'default' => date('Y-m-d'),
                'description' => __('Default date for fixtures (users can change via date picker)', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'enable_date_picker',
            array(
                'label' => __('Interactive Date Picker', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'football-fixtures-pro'),
                'label_off' => __('Disable', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Allow users to click and change the date', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'date_navigation',
            array(
                'label' => __('Date Navigation Arrows', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'football-fixtures-pro'),
                'label_off' => __('Hide', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => array('enable_date_picker' => 'yes'),
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
                'label' => __('Initial Load Limit', 'football-fixtures-pro'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 50,
                'step' => 1,
                'default' => 10,
                'description' => __('Number of matches to show initially', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'enable_load_more',
            array(
                'label' => __('Load More Button', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'football-fixtures-pro'),
                'label_off' => __('Disable', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'load_more_increment',
            array(
                'label' => __('Load More Increment', 'football-fixtures-pro'),
                'type' => Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'default' => 5,
                'condition' => array('enable_load_more' => 'yes'),
                'description' => __('How many matches to load each time', 'football-fixtures-pro'),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // DISPLAY OPTIONS SECTION
        // ================================
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
                'condition' => array('show_odds' => 'yes'),
            )
        );
        
        $this->add_control(
            'preferred_bookmaker',
            array(
                'label' => __('Preferred Bookmaker', 'football-fixtures-pro'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    '' => __('Auto (Best Available)', 'football-fixtures-pro'),
                    '1' => __('1xBet', 'football-fixtures-pro'),
                    '8' => __('Bet365', 'football-fixtures-pro'),
                    '11' => __('William Hill', 'football-fixtures-pro'),
                    '18' => __('Betway', 'football-fixtures-pro'),
                    '5' => __('Unibet', 'football-fixtures-pro'),
                    '9' => __('Ladbrokes', 'football-fixtures-pro'),
                    '10' => __('Coral', 'football-fixtures-pro'),
                    '6' => __('Bwin', 'football-fixtures-pro'),
                    '12' => __('Pinnacle', 'football-fixtures-pro'),
                    '14' => __('MarathonBet', 'football-fixtures-pro'),
                ),
                'default' => '1',
                'description' => __('Choose your preferred bookmaker for odds display. 1xBet is set as default.', 'football-fixtures-pro'),
                'condition' => array('show_odds' => 'yes'),
            )
        );
        
        $this->add_control(
            'enable_multi_color',
            array(
                'label' => __('Multi-Color Leagues', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Enable', 'football-fixtures-pro'),
                'label_off' => __('Disable', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'description' => __('Each league gets a unique color scheme', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'time_format',
            array(
                'label' => __('Time Format', 'football-fixtures-pro'),
                'type' => Controls_Manager::SELECT,
                'options' => array(
                    'H:i' => __('24 Hour (15:30)', 'football-fixtures-pro'),
                    'g:i A' => __('12 Hour (3:30 PM)', 'football-fixtures-pro'),
                    'g:i a' => __('12 Hour (3:30 pm)', 'football-fixtures-pro'),
                ),
                'default' => 'H:i',
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // BET BUTTON SECTION
        // ================================
        $this->start_controls_section(
            'bet_button_section',
            array(
                'label' => __('Bet Button Settings', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_CONTENT,
            )
        );
        
        $this->add_control(
            'show_bet_button',
            array(
                'label' => __('Show Bet Button', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'football-fixtures-pro'),
                'label_off' => __('Hide', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
            )
        );
        
        $this->add_control(
            'bet_button_text',
            array(
                'label' => __('Button Text', 'football-fixtures-pro'),
                'type' => Controls_Manager::TEXT,
                'default' => __('BET NOW', 'football-fixtures-pro'),
                'condition' => array('show_bet_button' => 'yes'),
                'dynamic' => array('active' => true),
            )
        );
        
        $this->add_control(
            'referral_link',
            array(
                'label' => __('Referral Link', 'football-fixtures-pro'),
                'type' => Controls_Manager::URL,
                'placeholder' => __('https://your-affiliate-link.com', 'football-fixtures-pro'),
                'condition' => array('show_bet_button' => 'yes'),
                'description' => __('Your affiliate/referral link for the betting site', 'football-fixtures-pro'),
                'dynamic' => array('active' => true),
            )
        );
        
        $this->add_control(
            'link_target',
            array(
                'label' => __('Open in New Tab', 'football-fixtures-pro'),
                'type' => Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'football-fixtures-pro'),
                'label_off' => __('No', 'football-fixtures-pro'),
                'return_value' => 'yes',
                'default' => 'yes',
                'condition' => array('show_bet_button' => 'yes'),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // SECTION TITLE STYLE
        // ================================
        $this->start_controls_section(
            'title_style_section',
            array(
                'label' => __('Section Title', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'title_typography',
                'label' => __('Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-section-title',
            )
        );
        
        $this->add_control(
            'title_color',
            array(
                'label' => __('Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-section-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_responsive_control(
            'title_alignment',
            array(
                'label' => __('Alignment', 'football-fixtures-pro'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-right',
                    ),
                ),
                'default' => 'center',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-section-title' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'title_margin',
            array(
                'label' => __('Margin', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '20',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-section-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // DATE PICKER STYLE
        // ================================
        $this->start_controls_section(
            'date_picker_style_section',
            array(
                'label' => __('Date Picker', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array('enable_date_picker' => 'yes'),
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'date_picker_background',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-date-selector',
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'date_picker_border',
                'label' => __('Border', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-date-selector',
            )
        );
        
        $this->add_responsive_control(
            'date_picker_border_radius',
            array(
                'label' => __('Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'default' => array(
                    'top' => '8',
                    'right' => '8',
                    'bottom' => '8',
                    'left' => '8',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-date-selector' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'date_picker_padding',
            array(
                'label' => __('Padding', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '15',
                    'right' => '20',
                    'bottom' => '15',
                    'left' => '20',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-date-selector' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'date_text_typography',
                'label' => __('Date Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-selected-date',
            )
        );
        
        $this->add_control(
            'date_text_color',
            array(
                'label' => __('Date Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-selected-date' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // LEAGUE TITLE STYLE
        // ================================
        $this->start_controls_section(
            'league_title_style_section',
            array(
                'label' => __('League Titles', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'league_title_typography',
                'label' => __('Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-league-title',
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'league_title_background',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-league-title',
            )
        );
        
        $this->add_control(
            'league_title_color',
            array(
                'label' => __('Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-league-title' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'league_title_border',
                'label' => __('Border', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-league-title',
            )
        );
        
        $this->add_responsive_control(
            'league_title_border_radius',
            array(
                'label' => __('Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'default' => array(
                    'top' => '8',
                    'right' => '8',
                    'bottom' => '8',
                    'left' => '8',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-league-title' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'league_title_padding',
            array(
                'label' => __('Padding', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '12',
                    'right' => '15',
                    'bottom' => '12',
                    'left' => '15',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-league-title' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'league_title_margin',
            array(
                'label' => __('Margin', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '15',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-league-title' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // MATCH CARD STYLE
        // ================================
        $this->start_controls_section(
            'match_card_style_section',
            array(
                'label' => __('Match Cards', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'match_card_background',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-match-card',
            )
        );
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'match_card_border',
                'label' => __('Border', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-match-card',
            )
        );
        
        $this->add_responsive_control(
            'match_card_border_radius',
            array(
                'label' => __('Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'default' => array(
                    'top' => '12',
                    'right' => '12',
                    'bottom' => '12',
                    'left' => '12',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-match-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'match_card_box_shadow',
                'label' => __('Box Shadow', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-match-card',
            )
        );
        
        $this->add_responsive_control(
            'match_card_margin',
            array(
                'label' => __('Margin', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '20',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-match-card' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'match_card_padding',
            array(
                'label' => __('Padding', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '0',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-match-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // TEAM NAMES STYLE
        // ================================
        $this->start_controls_section(
            'team_names_style_section',
            array(
                'label' => __('Team Names', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'team_names_typography',
                'label' => __('Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-team-name',
            )
        );
        
        $this->add_control(
            'team_names_color',
            array(
                'label' => __('Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#2c3e50',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-team-name' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_responsive_control(
            'team_names_alignment',
            array(
                'label' => __('Alignment', 'football-fixtures-pro'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'left' => array(
                        'title' => __('Left', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-center',
                    ),
                    'right' => array(
                        'title' => __('Right', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-right',
                    ),
                ),
                'default' => 'left',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-team-info' => 'text-align: {{VALUE}};',
                    '{{WRAPPER}} .ffp-away-team .ffp-team-info' => 'text-align: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // BET BUTTON STYLE
        // ================================
        $this->start_controls_section(
            'bet_button_style_section',
            array(
                'label' => __('Bet Button', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array('show_bet_button' => 'yes'),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'bet_button_typography',
                'label' => __('Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-bet-button',
            )
        );
        
        $this->start_controls_tabs('bet_button_tabs');
        
        $this->start_controls_tab(
            'bet_button_normal',
            array(
                'label' => __('Normal', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'bet_button_color',
            array(
                'label' => __('Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-bet-button' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'bet_button_background',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-bet-button',
            )
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'bet_button_hover',
            array(
                'label' => __('Hover', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'bet_button_hover_color',
            array(
                'label' => __('Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ffp-bet-button:hover' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'bet_button_background_hover',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-bet-button:hover',
            )
        );
        
        $this->add_control(
            'bet_button_hover_border_color',
            array(
                'label' => __('Border Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'condition' => array(
                    'bet_button_border_border!' => '',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-bet-button:hover' => 'border-color: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'bet_button_border',
                'label' => __('Border', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-bet-button',
            )
        );
        
        $this->add_responsive_control(
            'bet_button_border_radius',
            array(
                'label' => __('Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'default' => array(
                    'top' => '6',
                    'right' => '6',
                    'bottom' => '6',
                    'left' => '6',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-bet-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'bet_button_padding',
            array(
                'label' => __('Padding', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '10',
                    'right' => '20',
                    'bottom' => '10',
                    'left' => '20',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-bet-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'bet_button_box_shadow',
                'label' => __('Box Shadow', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-bet-button',
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // TEAM FORM STYLE
        // ================================
        $this->start_controls_section(
            'team_form_style_section',
            array(
                'label' => __('Team Form', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array('show_team_form' => 'yes'),
            )
        );
        
        $this->add_responsive_control(
            'form_size',
            array(
                'label' => __('Form Size', 'football-fixtures-pro'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 15,
                        'max' => 40,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 20,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-form-char' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}}; font-size: calc({{SIZE}}{{UNIT}} * 0.6);',
                ),
            )
        );
        
        $this->add_responsive_control(
            'form_spacing',
            array(
                'label' => __('Form Spacing', 'football-fixtures-pro'),
                'type' => Controls_Manager::SLIDER,
                'size_units' => array('px'),
                'range' => array(
                    'px' => array(
                        'min' => 1,
                        'max' => 10,
                        'step' => 1,
                    ),
                ),
                'default' => array(
                    'unit' => 'px',
                    'size' => 3,
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-team-form' => 'gap: {{SIZE}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_control(
            'form_win_color',
            array(
                'label' => __('Win Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#28a745',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-form-win' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'form_draw_color',
            array(
                'label' => __('Draw Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#ffc107',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-form-draw' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_control(
            'form_loss_color',
            array(
                'label' => __('Loss Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#dc3545',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-form-loss' => 'background-color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_responsive_control(
            'form_alignment',
            array(
                'label' => __('Form Alignment', 'football-fixtures-pro'),
                'type' => Controls_Manager::CHOOSE,
                'options' => array(
                    'flex-start' => array(
                        'title' => __('Left', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-left',
                    ),
                    'center' => array(
                        'title' => __('Center', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-center',
                    ),
                    'flex-end' => array(
                        'title' => __('Right', 'football-fixtures-pro'),
                        'icon' => 'fa fa-align-right',
                    ),
                ),
                'default' => 'flex-start',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-team-form' => 'justify-content: {{VALUE}};',
                ),
            )
        );
        
        $this->end_controls_section();
        
        // ================================
        // LOAD MORE BUTTON STYLE
        // ================================
        $this->start_controls_section(
            'load_more_style_section',
            array(
                'label' => __('Load More Button', 'football-fixtures-pro'),
                'tab' => Controls_Manager::TAB_STYLE,
                'condition' => array('enable_load_more' => 'yes'),
            )
        );
        
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name' => 'load_more_typography',
                'label' => __('Typography', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-load-more-button',
            )
        );
        
        $this->start_controls_tabs('load_more_tabs');
        
        $this->start_controls_tab(
            'load_more_normal',
            array(
                'label' => __('Normal', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'load_more_color',
            array(
                'label' => __('Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'default' => '#007cba',
                'selectors' => array(
                    '{{WRAPPER}} .ffp-load-more-button' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'load_more_background',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-load-more-button',
            )
        );
        
        $this->end_controls_tab();
        
        $this->start_controls_tab(
            'load_more_hover',
            array(
                'label' => __('Hover', 'football-fixtures-pro'),
            )
        );
        
        $this->add_control(
            'load_more_hover_color',
            array(
                'label' => __('Text Color', 'football-fixtures-pro'),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} .ffp-load-more-button:hover' => 'color: {{VALUE}}',
                ),
            )
        );
        
        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name' => 'load_more_background_hover',
                'label' => __('Background', 'football-fixtures-pro'),
                'types' => array('classic', 'gradient'),
                'selector' => '{{WRAPPER}} .ffp-load-more-button:hover',
            )
        );
        
        $this->end_controls_tab();
        
        $this->end_controls_tabs();
        
        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name' => 'load_more_border',
                'label' => __('Border', 'football-fixtures-pro'),
                'selector' => '{{WRAPPER}} .ffp-load-more-button',
            )
        );
        
        $this->add_responsive_control(
            'load_more_border_radius',
            array(
                'label' => __('Border Radius', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', '%'),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-load-more-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'load_more_padding',
            array(
                'label' => __('Padding', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '12',
                    'right' => '25',
                    'bottom' => '12',
                    'left' => '25',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-load-more-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->add_responsive_control(
            'load_more_margin',
            array(
                'label' => __('Margin', 'football-fixtures-pro'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => array('px', 'em', '%'),
                'default' => array(
                    'top' => '20',
                    'right' => '0',
                    'bottom' => '0',
                    'left' => '0',
                    'unit' => 'px',
                ),
                'selectors' => array(
                    '{{WRAPPER}} .ffp-load-more-container' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );
        
        $this->end_controls_section();
    }

    /**
     * Get leagues options for select control
     */
    private function get_leagues_options() {
        // Comprehensive league options organized by region
        $options = array(
            // Major European Leagues
            '39' => 'Premier League (England)',
            '140' => 'La Liga (Spain)',
            '135' => 'Serie A (Italy)',
            '78' => 'Bundesliga (Germany)',
            '61' => 'Ligue 1 (France)',
            '94' => 'Primeira Liga (Portugal)',
            '88' => 'Eredivisie (Netherlands)',
            
            // International Competitions
            '2' => 'UEFA Champions League',
            '3' => 'UEFA Europa League',
            '848' => 'UEFA Conference League',
            '1' => 'World Cup',
            '4' => 'Euro Championship',
            '5' => 'UEFA Nations League',
            '10' => 'International Friendlies',
            
            // World Cup Qualifiers
            '32' => 'WC Qualification CONCACAF',
            '34' => 'WC Qualification Africa',
            '33' => 'WC Qualification Asia',
            '35' => 'WC Qualification South America',
            
            // Africa Cup of Nations & African Competitions
            '6' => 'Africa Cup of Nations',
            '12' => 'CAF Champions League',
            '13' => 'CAF Confederation Cup',
            '14' => 'AFCON Qualification',
            
            // African National Leagues
            '233' => 'Elite One (Cameroon)',
            '200' => 'Egyptian Premier League',
            '202' => 'Botola Pro (Morocco)',
            '204' => 'Ligue 1 (Algeria)',
            '207' => 'Premier League (South Africa)',
            '208' => 'Ligue 1 (Tunisia)',
            '218' => 'Premier League (Ghana)',
            '220' => 'Girabola (Angola)',
            '244' => 'Premier League (Kenya)',
            '253' => 'Ligue 1 (Senegal)',
            
            // North American Leagues
            '253' => 'Major League Soccer (USA)',
            '262' => 'Liga MX (Mexico)',
            '254' => 'Canadian Premier League',
            
            // South American Leagues
            '71' => 'Serie A (Brazil)',
            '128' => 'Primera División (Argentina)',
            '131' => 'Primera División (Chile)',
            '239' => 'Primera A (Colombia)',
            '242' => 'Primera División (Ecuador)',
            '271' => 'Primera División (Uruguay)',
            '273' => 'Primera División (Paraguay)',
            
            // South American Competitions
            '11' => 'Copa Libertadores',
            '13' => 'Copa Sudamericana',
            '9' => 'Copa America',
            
            // Asian Leagues & Competitions
            '188' => 'J1 League (Japan)',
            '292' => 'K League 1 (South Korea)',
            '169' => 'Super League (China)',
            '15' => 'AFC Champions League',
            '16' => 'AFC Cup',
            '7' => 'Asian Cup',
            
            // CONCACAF Competitions
            '16' => 'CONCACAF Champions League',
            '17' => 'Gold Cup',
            
            // Youth & Special Competitions
            '18' => 'FIFA U-20 World Cup',
            '19' => 'FIFA U-17 World Cup',
            '8' => 'FIFA Women\'s World Cup',
            '18' => 'Olympic Games Football',
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
            
            <?php if ('yes' === $settings['enable_date_picker']): ?>
            <div class="ffp-date-selector">
                <?php if ('yes' === $settings['date_navigation']): ?>
                <button class="ffp-date-nav prev-date"><i class="fa fa-chevron-left"></i></button>
                <?php endif; ?>
                <span class="ffp-selected-date" data-date="<?php echo esc_attr($date); ?>"><?php echo esc_html(date('d/m/Y', strtotime($date))); ?></span>
                <?php if ('yes' === $settings['date_navigation']): ?>
                <button class="ffp-date-nav next-date"><i class="fa fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="ffp-fixtures-list">
            <?php foreach ($grouped_fixtures as $league_data): ?>
                <div class="ffp-league-section">
                    <h3 class="ffp-league-title"><?php echo esc_html($league_data['league']['name']); ?></h3>
                    
                    <?php foreach ($league_data['matches'] as $fixture): ?>
                        <?php
                        $match_time = date($settings['time_format'], strtotime($fixture['fixture']['date']));
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
                            $bookmaker = !empty($settings['preferred_bookmaker']) ? $settings['preferred_bookmaker'] : null;
                            $odds_response = $api->get_odds($fixture['fixture']['id'], '1X2', $bookmaker);
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
                                        <?php if ($settings['show_team_form'] === 'yes' && !empty($home_form)): ?>
                                            <div class="ffp-team-form">
                                                <?php echo $this->render_team_form($home_form); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Match Center -->
                                <div class="ffp-match-center">
                                    <div class="ffp-match-score">VS</div>
                                    <?php if ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'below_teams' && $odds_data): ?>
                                        <div class="ffp-match-odds-inline">
                                            <span><?php echo esc_html($this->get_team_odds($odds_data, 'Home')); ?></span>
                                            <span><?php echo esc_html($this->get_team_odds($odds_data, 'Draw')); ?></span>
                                            <span><?php echo esc_html($this->get_team_odds($odds_data, 'Away')); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Away Team -->
                                <div class="ffp-team ffp-away-team">
                                    <div class="ffp-team-info">
                                        <h4 class="ffp-team-name"><?php echo esc_html($fixture['teams']['away']['name']); ?></h4>
                                        <?php if ($settings['show_team_form'] === 'yes' && !empty($away_form)): ?>
                                            <div class="ffp-team-form">
                                                <?php echo $this->render_team_form($away_form); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
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
                                </div>
                            </div>
                            
                            <?php if ($settings['show_odds'] === 'yes' && $settings['odds_display_mode'] === 'separate_section' && $odds_data): ?>
                            <div class="ffp-match-odds-section">
                                <div class="ffp-odds-item">
                                    <span class="ffp-odds-label">1</span>
                                    <span class="ffp-odds-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Home')); ?></span>
                                </div>
                                <div class="ffp-odds-item">
                                    <span class="ffp-odds-label">X</span>
                                    <span class="ffp-odds-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Draw')); ?></span>
                                </div>
                                <div class="ffp-odds-item">
                                    <span class="ffp-odds-label">2</span>
                                    <span class="ffp-odds-value"><?php echo esc_html($this->get_team_odds($odds_data, 'Away')); ?></span>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($settings['show_bet_button'] === 'yes' && !empty($settings['referral_link']['url'])): ?>
                            <div class="ffp-bet-button-container">
                                <a href="<?php echo esc_url($settings['referral_link']['url']); ?>" 
                                   class="ffp-bet-button" 
                                   <?php if ($settings['link_target'] === 'yes') echo 'target="_blank"'; ?>>
                                    <?php echo esc_html($settings['bet_button_text']); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            </div>
            
            <?php if ($settings['enable_load_more'] === 'yes'): ?>
            <div class="ffp-load-more-container">
                <button class="ffp-load-more-button" data-increment="<?php echo esc_attr($settings['load_more_increment']); ?>">
                    <?php esc_html_e('Load More', 'football-fixtures-pro'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render team form string into HTML
     */
    private function render_team_form($form_string) {
        $html = '';
        $form_chars = str_split($form_string);
        foreach ($form_chars as $char) {
            $class = '';
            switch (strtoupper($char)) {
                case 'W':
                    $class = 'ffp-form-win';
                    break;
                case 'D':
                    $class = 'ffp-form-draw';
                    break;
                case 'L':
                    $class = 'ffp-form-loss';
                    break;
            }
            $html .= '<span class="ffp-form-char ' . $class . '">' . esc_html(strtoupper($char)) . '</span>';
        }
        return $html;
    }
    
    /**
     * Get specific odds value from odds data
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

