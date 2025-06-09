/**
 * Football Fixtures Pro Frontend JavaScript
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
            animationDuration: 300
        },

        // Initialize the plugin
        init: function() {
            this.bindEvents();
            this.initDatePickers();
            this.initAutoRefresh();
            this.initLazyLoading();
            this.initTooltips();
            this.handleResponsive();
        },

        // Bind event handlers
        bindEvents: function() {
            var self = this;

            // Date change handler
            $(document).on('change', '.ffp-date-input', function() {
                self.handleDateChange($(this));
            });

            // Bet button clicks
            $(document).on('click', '.ffp-bet-button', function(e) {
                e.preventDefault();
                self.handleBetClick($(this));
            });

            // Stake button clicks
            $(document).on('click', '.ffp-stake-button', function(e) {
                e.preventDefault();
                self.handleStakeClick($(this));
            });

            // Odds item clicks
            $(document).on('click', '.ffp-odd-item', function(e) {
                e.preventDefault();
                self.handleOddsClick($(this));
            });

            // Match card hover effects
            $(document).on('mouseenter', '.ffp-match-card', function() {
                self.handleMatchHover($(this), true);
            }).on('mouseleave', '.ffp-match-card', function() {
                self.handleMatchHover($(this), false);
            });

            // Window resize handler
            $(window).on('resize', function() {
                self.handleResponsive();
            });

            // Keyboard navigation
            $(document).on('keydown', '.ffp-match-card', function(e) {
                self.handleKeyboardNavigation(e, $(this));
            });
        },

        // Initialize date pickers
        initDatePickers: function() {
            $('.ffp-date-input').each(function() {
                var $input = $(this);
                
                // Set min date to today
                var today = new Date().toISOString().split('T')[0];
                $input.attr('min', today);
                
                // Set max date to 30 days from now
                var maxDate = new Date();
                maxDate.setDate(maxDate.getDate() + 30);
                $input.attr('max', maxDate.toISOString().split('T')[0]);
            });
        },

        // Handle date changes
        handleDateChange: function($input) {
            var self = this;
            var containerId = $input.data('container');
            var $container = $('#' + containerId);
            var newDate = $input.val();

            if (!newDate || !containerId) {
                return;
            }

            // Update displayed date
            var formattedDate = this.formatDate(newDate);
            $container.find('.ffp-selected-date').text(formattedDate);

            // Show loading state
            this.showLoading($container);

            // Get container settings
            var settings = this.getContainerSettings($container);
            settings.date = newDate;

            // Load new fixtures
            this.loadFixtures($container, settings);
        },

        // Get container settings
        getContainerSettings: function($container) {
            return {
                date: $container.data('date'),
                leagues: $container.data('leagues') || '',
                showLogos: $container.data('show-logos') !== false,
                showOdds: $container.data('show-odds') !== false,
                showForm: $container.data('show-form') !== false,
                oddsMode: $container.data('odds-mode') || 'separate_section',
                limit: $container.data('limit') || 10
            };
        },

        // Load fixtures via AJAX
        loadFixtures: function($container, settings) {
            var self = this;

            $.ajax({
                url: this.config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ffp_load_fixtures',
                    nonce: this.config.nonce,
                    date: settings.date,
                    leagues: settings.leagues,
                    container_id: $container.attr('id'),
                    show_logos: settings.showLogos,
                    show_odds: settings.showOdds,
                    show_form: settings.showForm,
                    odds_mode: settings.oddsMode,
                    limit: settings.limit
                },
                success: function(response) {
                    self.hideLoading($container);
                    
                    if (response.success) {
                        var $fixturesContainer = $container.find('.ffp-fixtures-container');
                        $fixturesContainer.fadeOut(self.config.animationDuration, function() {
                            $fixturesContainer.html(response.data).fadeIn(self.config.animationDuration);
                            self.initTooltips();
                            self.triggerCustomEvent('fixtures_loaded', $container, response.data);
                        });
                    } else {
                        self.showError($container, response.data || 'Failed to load fixtures');
                    }
                },
                error: function(xhr, status, error) {
                    self.hideLoading($container);
                    self.showError($container, 'Network error: ' + error);
                }
            });
        },

        // Show loading state
        showLoading: function($container) {
            $container.find('.ffp-fixtures-container').fadeOut(this.config.animationDuration);
            $container.find('.ffp-loading').fadeIn(this.config.animationDuration);
        },

        // Hide loading state
        hideLoading: function($container) {
            $container.find('.ffp-loading').fadeOut(this.config.animationDuration);
        },

        // Show error message
        showError: function($container, message) {
            var errorHtml = '<div class="ffp-error">' + this.escapeHtml(message) + '</div>';
            var $fixturesContainer = $container.find('.ffp-fixtures-container');
            $fixturesContainer.html(errorHtml).fadeIn(this.config.animationDuration);
        },

        // Handle bet button clicks
        handleBetClick: function($button) {
            var fixtureId = $button.data('fixture');
            
            // Add visual feedback
            $button.addClass('ffp-button-clicked');
            setTimeout(function() {
                $button.removeClass('ffp-button-clicked');
            }, 200);

            // Trigger custom event
            this.triggerCustomEvent('bet_clicked', $button, {
                fixtureId: fixtureId,
                button: $button
            });

            // You can customize this to integrate with your betting system
            console.log('Bet clicked for fixture:', fixtureId);
        },

        // Handle stake button clicks
        handleStakeClick: function($button) {
            var fixtureId = $button.data('fixture');
            
            // Add visual feedback
            $button.addClass('ffp-button-clicked');
            setTimeout(function() {
                $button.removeClass('ffp-button-clicked');
            }, 200);

            // Trigger custom event
            this.triggerCustomEvent('stake_clicked', $button, {
                fixtureId: fixtureId,
                button: $button
            });

            // You can customize this to integrate with your betting system
            console.log('Stake clicked for fixture:', fixtureId);
        },

        // Handle odds clicks
        handleOddsClick: function($oddsItem) {
            var $card = $oddsItem.closest('.ffp-match-card');
            var fixtureId = $card.data('fixture-id');
            var oddLabel = $oddsItem.find('.ffp-odd-label').text();
            var oddValue = $oddsItem.find('.ffp-odd-value').text();

            // Visual feedback
            $oddsItem.addClass('ffp-odds-selected');
            setTimeout(function() {
                $oddsItem.removeClass('ffp-odds-selected');
            }, 1000);

            // Trigger custom event
            this.triggerCustomEvent('odds_clicked', $oddsItem, {
                fixtureId: fixtureId,
                oddLabel: oddLabel,
                oddValue: oddValue,
                element: $oddsItem
            });

            console.log('Odds clicked:', { fixtureId, oddLabel, oddValue });
        },

        // Handle match card hover
        handleMatchHover: function($card, isEntering) {
            if (isEntering) {
                $card.addClass('ffp-match-hover');
                this.triggerCustomEvent('match_hover_enter', $card, {
                    fixtureId: $card.data('fixture-id')
                });
            } else {
                $card.removeClass('ffp-match-hover');
                this.triggerCustomEvent('match_hover_leave', $card, {
                    fixtureId: $card.data('fixture-id')
                });
            }
        },

        // Handle keyboard navigation
        handleKeyboardNavigation: function(e, $card) {
            var keyCode = e.which || e.keyCode;
            
            switch (keyCode) {
                case 13: // Enter
                case 32: // Space
                    e.preventDefault();
                    $card.find('.ffp-bet-button').first().click();
                    break;
                case 37: // Left arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'prev');
                    break;
                case 39: // Right arrow
                    e.preventDefault();
                    this.navigateToCard($card, 'next');
                    break;
            }
        },

        // Navigate between cards
        navigateToCard: function($currentCard, direction) {
            var $targetCard;
            
            if (direction === 'prev') {
                $targetCard = $currentCard.prev('.ffp-match-card');
            } else {
                $targetCard = $currentCard.next('.ffp-match-card');
            }
            
            if ($targetCard.length) {
                $targetCard.focus();
                this.scrollToCard($targetCard);
            }
        },

        // Scroll to card
        scrollToCard: function($card) {
            var cardTop = $card.offset().top;
            var windowHeight = $(window).height();
            var scrollTop = cardTop - (windowHeight / 2);
            
            $('html, body').animate({
                scrollTop: scrollTop
            }, this.config.animationDuration);
        },

        // Initialize auto-refresh
        initAutoRefresh: function() {
            var self = this;
            
            // Only auto-refresh for today's matches
            setInterval(function() {
                $('.ffp-widget-container, .ffp-shortcode-container').each(function() {
                    var $container = $(this);
                    var settings = self.getContainerSettings($container);
                    var today = new Date().toISOString().split('T')[0];
                    
                    // Only refresh if showing today's matches
                    if (settings.date === today) {
                        self.loadFixtures($container, settings);
                    }
                });
            }, this.config.autoRefreshInterval);
        },

        // Initialize lazy loading for images
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('ffp-lazy');
                            imageObserver.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('.ffp-lazy').forEach(function(img) {
                    imageObserver.observe(img);
                });
            }
        },

        // Initialize tooltips
        initTooltips: function() {
            // Simple tooltip implementation
            $(document).off('mouseenter.ffp mouseleave.ffp', '[title]');
            $(document).on('mouseenter.ffp', '[title]', function() {
                var $this = $(this);
                var title = $this.attr('title');
                
                if (title) {
                    $this.data('original-title', title);
                    $this.removeAttr('title');
                    
                    var $tooltip = $('<div class="ffp-tooltip">' + title + '</div>');
                    $('body').append($tooltip);
                    
                    var offset = $this.offset();
                    $tooltip.css({
                        top: offset.top - $tooltip.outerHeight() - 5,
                        left: offset.left + ($this.outerWidth() / 2) - ($tooltip.outerWidth() / 2)
                    }).fadeIn(200);
                }
            }).on('mouseleave.ffp', '[data-original-title]', function() {
                var $this = $(this);
                $this.attr('title', $this.data('original-title'));
                $('.ffp-tooltip').remove();
            });
        },

        // Handle responsive design
        handleResponsive: function() {
            var windowWidth = $(window).width();
            var $containers = $('.ffp-widget-container, .ffp-shortcode-container');
            
            if (windowWidth < 768) {
                $containers.addClass('ffp-mobile');
            } else {
                $containers.removeClass('ffp-mobile');
            }
            
            if (windowWidth < 480) {
                $containers.addClass('ffp-small-mobile');
            } else {
                $containers.removeClass('ffp-small-mobile');
            }
        },

        // Format date for display
        formatDate: function(dateString) {
            var date = new Date(dateString);
            var options = {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            };
            return date.toLocaleDateString('en-GB', options);
        },

        // Escape HTML to prevent XSS
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        },

        // Trigger custom events
        triggerCustomEvent: function(eventName, $element, data) {
            var event = new CustomEvent('ffp:' + eventName, {
                detail: data,
                bubbles: true
            });
            $element[0].dispatchEvent(event);
        },

        // Public API methods
        api: {
            // Refresh fixtures for a specific container
            refreshFixtures: function(containerId) {
                var $container = $('#' + containerId);
                if ($container.length) {
                    var settings = FootballFixturesPro.getContainerSettings($container);
                    FootballFixturesPro.loadFixtures($container, settings);
                }
            },

            // Change date for a specific container
            setDate: function(containerId, date) {
                var $container = $('#' + containerId);
                var $dateInput = $container.find('.ffp-date-input');
                if ($dateInput.length) {
                    $dateInput.val(date).trigger('change');
                }
            },

            // Get current data for a container
            getContainerData: function(containerId) {
                var $container = $('#' + containerId);
                return FootballFixturesPro.getContainerSettings($container);
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        FootballFixturesPro.init();
    });

    // Expose API globally
    window.FFP = FootballFixturesPro.api;

})(jQuery);

// Add CSS for dynamic effects
jQuery(document).ready(function($) {
    // Add dynamic styles
    if (!document.getElementById('ffp-dynamic-styles')) {
        var dynamicStyles = `
            <style id="ffp-dynamic-styles">
                .ffp-button-clicked {
                    transform: scale(0.95) !important;
                    opacity: 0.8 !important;
                }
                
                .ffp-odds-selected {
                    background: #007cba !important;
                    color: white !important;
                    transform: scale(1.05) !important;
                }
                
                .ffp-match-hover {
                    transform: translateY(-2px) !important;
                }
                
                .ffp-tooltip {
                    position: absolute;
                    background: rgba(0, 0, 0, 0.8);
                    color: white;
                    padding: 5px 10px;
                    border-radius: 4px;
                    font-size: 12px;
                    z-index: 9999;
                    white-space: nowrap;
                    pointer-events: none;
                }
                
                .ffp-lazy {
                    opacity: 0;
                    transition: opacity 0.3s;
                }
                
                .ffp-lazy:not([src]) {
                    background: #f0f0f0;
                }
                
                @media (prefers-reduced-motion: reduce) {
                    .ffp-button-clicked,
                    .ffp-odds-selected,
                    .ffp-match-hover {
                        transform: none !important;
                    }
                }
            </style>
        `;
        $('head').append(dynamicStyles);
    }
});