/**
 * Enhanced Football Fixtures Pro Frontend JavaScript - Phase 1
 * 
 * @package FootballFixturesPro
 * @author Mo Gadaphy - MOGADONKO AGENCY
 */

(function($) {
    'use strict';

    // Main Football Fixtures Pro Object
    window.FootballFixturesPro = {
        
        // Configuration
        config: {
            ajaxUrl: ffp_ajax.ajax_url,
            nonce: ffp_ajax.nonce,
            loadingText: ffp_ajax.loading_text,
            autoRefreshInterval: 60000, // 1 minute
            animationDuration: 300,
            datePickerVisible: false,
            loadMoreInProgress: false
        },

        // Initialize the plugin
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.initAutoRefresh();
            this.initLazyLoading();
            this.initTooltips();
            this.initLoadMore();
            this.initMatchInteractions();
            this.handleResponsive();
            
            // Initialize on page load
            $(document).ready(() => {
                this.initializeWidgets();
            });
        },

        // Initialize all widgets on the page
        initializeWidgets: function() {
            $('.ffp-widget-container, .ffp-shortcode-container').each((index, element) => {
                this.initializeWidget($(element));
            });
        },

        // Initialize individual widget
        initializeWidget: function($container) {
            const settings = $container.data('settings') || {};
            
            // Set up date picker if enabled
            if (settings.enable_date_picker === 'yes') {
                this.setupDatePicker($container);
            }
            
            // Set up load more if enabled
            if (settings.enable_load_more === 'yes') {
                this.setupLoadMore($container);
            }
            
            // Set up auto-refresh for live matches
            if (settings.auto_refresh === 'yes' || this.hasLiveMatches($container)) {
                this.setupAutoRefresh($container);
            }
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Date picker interactions
            $(document).on('click', '.ffp-selected-date', function(e) {
                e.preventDefault();
                self.toggleDatePicker($(this));
            });

            // Date navigation
            $(document).on('click', '.ffp-date-nav', function(e) {
                e.preventDefault();
                self.handleDateNavigation($(this));
            });

            // Date input change
            $(document).on('change', '.ffp-date-input', function() {
                self.handleDateChange($(this));
            });

            // Load more button
            $(document).on('click', '.ffp-load-more-button', function(e) {
                e.preventDefault();
                self.handleLoadMore($(this));
            });

            // Bet button clicks
            $(document).on('click', '.ffp-bet-button', function(e) {
                self.handleBetClick($(this));
            });

            // Odds item clicks
            $(document).on('click', '.ffp-odd-item', function(e) {
                e.preventDefault();
                self.handleOddsClick($(this));
            });

            // Match card interactions
            $(document).on('mouseenter', '.ffp-match-card', function() {
                self.handleMatchHover($(this), true);
            }).on('mouseleave', '.ffp-match-card', function() {
                self.handleMatchHover($(this), false);
            });

            // Close date picker when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.ffp-date-selector').length) {
                    self.closeDatePicker();
                }
            });

            // Keyboard navigation
            $(document).on('keydown', '.ffp-match-card', function(e) {
                self.handleKeyboardNavigation(e, $(this));
            });

            // Window resize handler
            $(window).on('resize', function() {
                self.handleResponsive();
            });

            // Escape key to close date picker
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    self.closeDatePicker();
                }
            });
        },

        // Setup date picker for container
        setupDatePicker: function($container) {
            const $dateSelector = $container.find('.ffp-date-selector');
            const $dateInput = $container.find('.ffp-date-input');
            const currentDate = $container.data('date');
            
            // Set initial date
            if (currentDate) {
                $dateInput.val(currentDate);
                this.updateDateDisplay($container, currentDate);
            }
            
            // Add click handler for date display
            $dateSelector.find('.ffp-selected-date').attr('tabindex', '0').attr('role', 'button');
        },

        // Toggle date picker visibility
        toggleDatePicker: function($button) {
            const $container = $button.closest('.ffp-widget-container, .ffp-shortcode-container');
            const $dateInput = $container.find('.ffp-date-input');
            
            if (this.config.datePickerVisible) {
                this.closeDatePicker();
            } else {
                this.openDatePicker($container, $dateInput);
            }
        },

        // Open date picker
        openDatePicker: function($container, $dateInput) {
            $dateInput.show().focus();
            this.config.datePickerVisible = true;
            $container.find('.ffp-date-selector').addClass('ffp-date-picker-open');
        },

        // Close date picker
        closeDatePicker: function() {
            $('.ffp-date-input').hide();
            $('.ffp-date-selector').removeClass('ffp-date-picker-open');
            this.config.datePickerVisible = false;
        },

        // Handle date navigation (prev/next day)
        handleDateNavigation: function($button) {
            const $container = $button.closest('.ffp-widget-container, .ffp-shortcode-container');
            const $dateInput = $container.find('.ffp-date-input');
            const currentDate = new Date($dateInput.val());
            const action = $button.data('action');
            
            if (action === 'prev-day') {
                currentDate.setDate(currentDate.getDate() - 1);
            } else if (action === 'next-day') {
                currentDate.setDate(currentDate.getDate() + 1);
            }
            
            const newDate = currentDate.toISOString().split('T')[0];
            $dateInput.val(newDate);
            this.handleDateChange($dateInput);
        },

        // Handle date changes
        handleDateChange: function($input) {
            const $container = $input.closest('.ffp-widget-container, .ffp-shortcode-container');
            const newDate = $input.val();
            
            if (!newDate) return;
            
            // Update container data
            $container.data('date', newDate);
            
            // Update date display
            this.updateDateDisplay($container, newDate);
            
            // Close date picker
            this.closeDatePicker();
            
            // Show loading state
            this.showLoading($container);
            
            // Load new fixtures
            this.loadFixtures($container, { date: newDate });
        },

        // Update date display text
        updateDateDisplay: function($container, dateString) {
            const date = new Date(dateString);
            const options = { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric', 
                year: 'numeric' 
            };
            const formattedDate = date.toLocaleDateString('en-US', options);
            
            $container.find('.ffp-selected-date').text(formattedDate);
        },

        // Setup load more functionality
        setupLoadMore: function($container) {
            const $loadMoreBtn = $container.find('.ffp-load-more-button');
            const totalMatches = $container.find('.ffp-match-card').length;
            const settings = $container.data('settings') || {};
            const initialLoad = settings.matches_limit || 10;
            
            // Hide load more if not needed
            if (totalMatches <= initialLoad) {
                $loadMoreBtn.closest('.ffp-load-more-container').hide();
            }
        },

        // Handle load more button click
        handleLoadMore: function($button) {
            if (this.config.loadMoreInProgress) return;
            
            this.config.loadMoreInProgress = true;
            const $container = $button.closest('.ffp-widget-container, .ffp-shortcode-container');
            const currentLoaded = parseInt($button.data('loaded')) || 10;
            const increment = parseInt($button.data('increment')) || 5;
            const newLimit = currentLoaded + increment;
            
            // Show loading state on button
            $button.find('.ffp-load-more-text').hide();
            $button.find('.ffp-load-more-spinner').show();
            $button.prop('disabled', true);
            
            // Load more fixtures
            this.loadFixtures($container, { 
                limit: newLimit,
                loadMore: true 
            }).then(() => {
                // Update button state
                $button.data('loaded', newLimit);
                
                // Check if we need to hide the button
                const totalMatches = $container.find('.ffp-match-card').length;
                if (newLimit >= totalMatches) {
                    $button.closest('.ffp-load-more-container').fadeOut(this.config.animationDuration);
                }
            }).finally(() => {
                // Reset button state
                $button.find('.ffp-load-more-spinner').hide();
                $button.find('.ffp-load-more-text').show();
                $button.prop('disabled', false);
                this.config.loadMoreInProgress = false;
            });
        },

        // Load fixtures via AJAX
        loadFixtures: function($container, options = {}) {
            const settings = $container.data('settings') || {};
            const currentDate = options.date || $container.data('date') || settings.selected_date;
            
            const requestData = {
                action: 'ffp_load_fixtures',
                nonce: this.config.nonce,
                date: currentDate,
                leagues: settings.selected_leagues ? settings.selected_leagues.join(',') : '',
                container_id: $container.attr('id'),
                show_logos: settings.show_team_logos === 'yes',
                show_odds: settings.show_odds === 'yes',
                show_form: settings.show_team_form === 'yes',
                odds_mode: settings.odds_display_mode || 'separate_section',
                bookmaker: settings.preferred_bookmaker || '1',
                limit: options.limit || settings.matches_limit || 10,
                enable_multi_color: settings.enable_multi_color === 'yes',
                time_format: settings.time_format || 'H:i',
                load_more: options.loadMore || false
            };
            
            return $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: requestData
            }).done((response) => {
                if (response.success) {
                    const $fixturesContainer = $container.find('.ffp-fixtures-container');
                    
                    if (options.loadMore) {
                        // Append new content for load more
                        $fixturesContainer.append(response.data);
                        this.animateNewContent($fixturesContainer.children().slice(-options.increment || 5));
                    } else {
                        // Replace content for date changes
                        $fixturesContainer.fadeOut(this.config.animationDuration, () => {
                            $fixturesContainer.html(response.data).fadeIn(this.config.animationDuration);
                            this.initTooltips();
                            this.initLazyLoading();
                        });
                    }
                    
                    this.triggerCustomEvent('fixtures_loaded', $container, response.data);
                } else {
                    this.showError($container, response.data || 'Failed to load fixtures');
                }
            }).fail((xhr, status, error) => {
                this.showError($container, 'Network error: ' + error);
            }).always(() => {
                this.hideLoading($container);
            });
        },

        // Animate new content
        animateNewContent: function($newElements) {
            $newElements.each(function(index) {
                const $element = $(this);
                $element.css({ opacity: 0, transform: 'translateY(20px)' });
                
                setTimeout(() => {
                    $element.animate({
                        opacity: 1
                    }, 300).css('transform', 'translateY(0)');
                }, index * 100);
            });
        },

        // Check if container has live matches
        hasLiveMatches: function($container) {
            return $container.find('.ffp-match-live').length > 0;
        },

        // Setup auto-refresh for live matches
        setupAutoRefresh: function($container) {
            if (!this.hasLiveMatches($container)) return;
            
            const refreshInterval = setInterval(() => {
                if (this.hasLiveMatches($container)) {
                    this.loadFixtures($container, { silent: true });
                } else {
                    clearInterval(refreshInterval);
                }
            }, this.config.autoRefreshInterval);
            
            // Store interval ID for cleanup
            $container.data('refresh-interval', refreshInterval);
        },

        // Show loading state
        showLoading: function($container) {
            $container.find('.ffp-loading').fadeIn(this.config.animationDuration);
        },

        // Hide loading state
        hideLoading: function($container) {
            $container.find('.ffp-loading').fadeOut(this.config.animationDuration);
        },

        // Show error message
        showError: function($container, message) {
            const errorHtml = '<div class="ffp-error">' + this.escapeHtml(message) + '</div>';
            const $fixturesContainer = $container.find('.ffp-fixtures-container');
            $fixturesContainer.html(errorHtml).fadeIn(this.config.animationDuration);
        },

        // Handle bet button clicks
        handleBetClick: function($button) {
            const fixtureId = $button.data('fixture');
            const href = $button.attr('href');
            
            // Add visual feedback
            $button.addClass('ffp-button-clicked');
            setTimeout(() => $button.removeClass('ffp-button-clicked'), 200);

            // Track click event
            this.trackEvent('bet_click', {
                fixture_id: fixtureId,
                referral_link: href
            });

            // Trigger custom event
            this.triggerCustomEvent('bet_clicked', $button, {
                fixtureId: fixtureId,
                referralLink: href,
                button: $button
            });
        },

        // Handle odds clicks
        handleOddsClick: function($oddsItem) {
            const $card = $oddsItem.closest('.ffp-match-card');
            const fixtureId = $card.data('fixture-id');
            const oddLabel = $oddsItem.find('.ffp-odd-label').text();
            const oddValue = $oddsItem.find('.ffp-odd-value').text();
            const betType = $oddsItem.data('bet-type');

            // Visual feedback
            $oddsItem.addClass('ffp-odds-selected');
            setTimeout(() => $oddsItem.removeClass('ffp-odds-selected'), 1000);

            // Track click event
            this.trackEvent('odds_click', {
                fixture_id: fixtureId,
                bet_type: betType,
                odd_value: oddValue
            });

            // Trigger custom event
            this.triggerCustomEvent('odds_clicked', $oddsItem, {
                fixtureId: fixtureId,
                oddLabel: oddLabel,
                oddValue: oddValue,
                betType: betType,
                element: $oddsItem
            });
        },

        // Handle match card hover
        handleMatchHover: function($card, isEntering) {
            if (isEntering) {
                $card.addClass('ffp-match-hover');
                this.showMatchDetails($card);
                this.triggerCustomEvent('match_hover_enter', $card, {
                    fixtureId: $card.data('fixture-id')
                });
            } else {
                $card.removeClass('ffp-match-hover');
                this.hideMatchDetails($card);
                this.triggerCustomEvent('match_hover_leave', $card, {
                    fixtureId: $card.data('fixture-id')
                });
            }
        },

        // Show additional match details on hover
        showMatchDetails: function($card) {
            const $details = $card.find('.ffp-match-details');
            if ($details.length) {
                $details.slideDown(200);
            }
        },

        // Hide additional match details
        hideMatchDetails: function($card) {
            const $details = $card.find('.ffp-match-details');
            if ($details.length) {
                $details.slideUp(200);
            }
        },

        // Initialize match interactions
        initMatchInteractions: function() {
            // Add hover effects for better UX
            $('.ffp-match-card').each(function() {
                const $card = $(this);
                $card.attr('tabindex', '0').attr('role', 'button');
                
                // Add keyboard accessibility
                $card.on('keypress', function(e) {
                    if (e.which === 13 || e.which === 32) { // Enter or Space
                        e.preventDefault();
                        $card.find('.ffp-bet-button').first().click();
                    }
                });
            });
        },

        // Handle keyboard navigation
        handleKeyboardNavigation: function(e, $card) {
            const keyCode = e.which || e.keyCode;
            
            switch (keyCode) {
                case 37: // Left arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'prev');
                    break;
                case 39: // Right arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'next');
                    break;
                case 38: // Up arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'up');
                    break;
                case 40: // Down arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'down');
                    break;
            }
        },

        // Navigate between cards
        navigateToCard: function($currentCard, direction) {
            let $targetCard;
            
            switch (direction) {
                case 'prev':
                    $targetCard = $currentCard.prev('.ffp-match-card');
                    break;
                case 'next':
                    $targetCard = $currentCard.next('.ffp-match-card');
                    break;
                case 'up':
                    $targetCard = $currentCard.parent().prev().find('.ffp-match-card').first();
                    break;
                case 'down':
                    $targetCard = $currentCard.parent().next().find('.ffp-match-card').first();
                    break;
            }
            
            if ($targetCard && $targetCard.length) {
                $targetCard.focus();
                this.scrollToCard($targetCard);
            }
        },

        // Scroll to card smoothly
        scrollToCard: function($card) {
            const cardTop = $card.offset().top;
            const windowHeight = $(window).height();
            const scrollTop = cardTop - (windowHeight / 2) + ($card.height() / 2);
            
            $('html, body').animate({
                scrollTop: scrollTop
            }, this.config.animationDuration);
        },

        // Initialize auto-refresh for live matches
        initAutoRefresh: function() {
            const self = this;
            
            setInterval(() => {
                $('.ffp-widget-container, .ffp-shortcode-container').each(function() {
                    const $container = $(this);
                    if (self.hasLiveMatches($container)) {
                        const settings = self.getContainerSettings($container);
                        const today = new Date().toISOString().split('T')[0];
                        
                        // Only refresh if showing today's matches
                        if (settings.date === today) {
                            self.loadFixtures($container, { silent: true });
                        }
                    }
                });
            }, this.config.autoRefreshInterval);
        },

        // Initialize lazy loading for images
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('ffp-lazy');
                                imageObserver.unobserve(img);
                            }
                        }
                    });
                });

                document.querySelectorAll('.ffp-lazy').forEach(img => {
                    imageObserver.observe(img);
                });
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            // Enhanced tooltip system
            $(document).off('mouseenter.ffp mouseleave.ffp', '[title], [data-tooltip]');
            
            $(document).on('mouseenter.ffp', '[title], [data-tooltip]', function() {
                const $this = $(this);
                const title = $this.attr('title') || $this.data('tooltip');
                
                if (title && !$this.data('tooltip-shown')) {
                    $this.data('original-title', $this.attr('title'));
                    $this.removeAttr('title');
                    
                    const $tooltip = $('<div class="ffp-tooltip">' + title + '</div>');
                    $('body').append($tooltip);
                    
                    const offset = $this.offset();
                    const tooltipWidth = $tooltip.outerWidth();
                    const tooltipHeight = $tooltip.outerHeight();
                    
                    // Position tooltip above element, centered
                    let left = offset.left + ($this.outerWidth() / 2) - (tooltipWidth / 2);
                    let top = offset.top - tooltipHeight - 8;
                    
                    // Adjust if tooltip goes off screen
                    if (left < 0) left = 5;
                    if (left + tooltipWidth > $(window).width()) {
                        left = $(window).width() - tooltipWidth - 5;
                    }
                    if (top < 0) {
                        top = offset.top + $this.outerHeight() + 8;
                        $tooltip.addClass('ffp-tooltip-bottom');
                    }
                    
                    $tooltip.css({ top: top, left: left }).fadeIn(200);
                    $this.data('tooltip-shown', true);
                }
            }).on('mouseleave.ffp', '[data-original-title], [data-tooltip]', function() {
                const $this = $(this);
                if ($this.data('original-title')) {
                    $this.attr('title', $this.data('original-title'));
                }
                $('.ffp-tooltip').remove();
                $this.removeData('tooltip-shown');
            });
        },

        // Handle responsive design
        handleResponsive: function() {
            const windowWidth = $(window).width();
            const $containers = $('.ffp-widget-container, .ffp-shortcode-container');
            
            // Add responsive classes
            $containers.removeClass('ffp-mobile ffp-tablet ffp-desktop');
            
            if (windowWidth < 768) {
                $containers.addClass('ffp-mobile');
            } else if (windowWidth < 1024) {
                $containers.addClass('ffp-tablet');
            } else {
                $containers.addClass('ffp-desktop');
            }
            
            // Adjust date picker behavior on mobile
            if (windowWidth < 768) {
                $('.ffp-date-input').attr('type', 'date');
            }
        },

        // Get container settings
        getContainerSettings: function($container) {
            return $container.data('settings') || {
                date: $container.data('date') || new Date().toISOString().split('T')[0],
                leagues: '',
                show_logos: true,
                show_odds: true,
                show_form: true,
                odds_mode: 'separate_section',
                bookmaker: '1',
                limit: 10
            };
        },

        // Track events for analytics
        trackEvent: function(eventName, data) {
            // Send to Google Analytics if available
            if (typeof gtag !== 'undefined') {
                gtag('event', eventName, {
                    custom_parameter: data
                });
            }
            
            // Send to Facebook Pixel if available
            if (typeof fbq !== 'undefined') {
                fbq('track', 'CustomEvent', {
                    event_name: eventName,
                    event_data: data
                });
            }
            
            // Trigger WordPress action for custom tracking
            this.triggerCustomEvent('track_event', document, {
                event: eventName,
                data: data
            });
        },

        // Escape HTML to prevent XSS
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        // Trigger custom events
        triggerCustomEvent: function(eventName, element, data) {
            const event = new CustomEvent('ffp:' + eventName, {
                detail: data,
                bubbles: true
            });
            
            if (element instanceof jQuery) {
                element[0].dispatchEvent(event);
            } else {
                element.dispatchEvent(event);
            }
        },

        // Initialize date pickers (legacy support)
        initDatePickers: function() {
            $('.ffp-date-input').each(function() {
                const $input = $(this);
                
                // Set min date to today
                const today = new Date().toISOString().split('T')[0];
                $input.attr('min', today);
                
                // Set max date to 30 days from now
                const maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 30);
                $input.attr('max', maxDate.toISOString().split('T')[0]);
            });
        },

        // Public API methods
        api: {
            // Refresh fixtures for a specific container
            refreshFixtures: function(containerId) {
                const $container = $('#' + containerId);
                if ($container.length) {
                    FootballFixturesPro.loadFixtures($container);
                }
            },

            // Change date for a specific container
            setDate: function(containerId, date) {
                const $container = $('#' + containerId);
                const $dateInput = $container.find('.ffp-date-input');
                if ($dateInput.length) {
                    $dateInput.val(date);
                    FootballFixturesPro.handleDateChange($dateInput);
                }
            },

            // Get current data for a container
            getContainerData: function(containerId) {
                const $container = $('#' + containerId);
                return FootballFixturesPro.getContainerSettings($container);
            },

            // Load more matches programmatically
            loadMore: function(containerId) {
                const $container = $('#' + containerId);
                const $loadMoreBtn = $container.find('.ffp-load-more-button');
                if ($loadMoreBtn.length) {
                    FootballFixturesPro.handleLoadMore($loadMoreBtn);
                }
            },

            // Toggle auto-refresh
            toggleAutoRefresh: function(containerId, enable) {
                const $container = $('#' + containerId);
                if (enable) {
                    FootballFixturesPro.setupAutoRefresh($container);
                } else {
                    const intervalId = $container.data('refresh-interval');
                    if (intervalId) {
                        clearInterval(intervalId);
                        $container.removeData('refresh-interval');
                    }
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        FootballFixturesPro.init();
    });

    // Expose API globally
    window.FFP = FootballFixturesPro.api;

})(jQuery);