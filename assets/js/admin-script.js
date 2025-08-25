/**
 * Pathao Delivery Manager - Admin JavaScript
 * Handles all interactive functionality for the admin interface
 */

(function($) {
    'use strict';
    
    // Global variables
    var PDM = {
        ajaxUrl: pdm_ajax.ajax_url,
        nonce: pdm_ajax.nonce,
        currentModal: null,
        searchCache: {},
        recentSearches: JSON.parse(localStorage.getItem('pdm_recent_searches') || '[]'),
        settings: {}
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        PDM.init();
    });
    
    // Main initialization function
    PDM.init = function() {
        this.bindEvents();
        this.initModals();
        this.initTabs();
        this.initTooltips();
        this.loadSettings();
        this.initSearchFunctionality();
        this.initFormValidation();
        this.initProgressBars();
        this.initNotifications();
    };
    
    // Bind all event handlers
    PDM.bindEvents = function() {
        // Phone tracking search
        $(document).on('submit', '#pdm-phone-search-form', this.handlePhoneSearch);
        $(document).on('click', '.pdm-recent-search-item', this.handleRecentSearchClick);
        $(document).on('click', '.pdm-clear-recent-searches', this.clearRecentSearches);
        
        // Order tracking
        $(document).on('submit', '#pdm-order-tracking-form', this.handleOrderTracking);
        $(document).on('submit', '#pdm-bulk-tracking-form', this.handleBulkTracking);
        $(document).on('change', '#bulk-tracking-csv', this.handleCSVUpload);
        
        // Fake orders management
        $(document).on('submit', '#pdm-fake-order-form', this.handleFakeOrderCreation);
        $(document).on('submit', '#pdm-bulk-fake-form', this.handleBulkFakeOrders);
        $(document).on('click', '.pdm-delete-fake-order', this.handleDeleteFakeOrder);
        $(document).on('click', '.pdm-edit-fake-order', this.handleEditFakeOrder);
        $(document).on('click', '#pdm-clear-all-fake', this.handleClearAllFakeOrders);
        $(document).on('click', '#pdm-generate-sample-orders', this.generateSampleOrders);
        
        // Bulk delivery
        $(document).on('submit', '.pdm-bulk-delivery-form', this.handleBulkDelivery);
        $(document).on('click', '#pdm-add-sample-data', this.addSampleDeliveryData);
        $(document).on('click', '#pdm-save-bulk-settings', this.saveBulkSettings);
        
        // Settings
        $(document).on('submit', '#pdm-settings-form', this.handleSettingsSave);
        $(document).on('click', '#pdm-test-api', this.testAPIConnection);
        
        // Modal events
        $(document).on('click', '.pdm-modal-trigger', this.openModal);
        $(document).on('click', '.pdm-modal-close, .pdm-modal-backdrop', this.closeModal);
        
        // Table actions
        $(document).on('click', '.pdm-view-details', this.viewOrderDetails);
        $(document).on('click', '.pdm-view-tracking', this.viewTrackingHistory);
        
        // Utility events
        $(document).on('click', '.pdm-copy-text', this.copyToClipboard);
        $(document).on('click', '.pdm-refresh-data', this.refreshData);
        $(document).on('change', '.pdm-auto-save', this.autoSave);
    };
    
    // Phone search functionality
    PDM.handlePhoneSearch = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var phoneNumber = $form.find('#phone_number').val().trim();
        
        if (!phoneNumber) {
            PDM.showNotification('Please enter a phone number', 'error');
            return;
        }
        
        if (!PDM.validatePhoneNumber(phoneNumber)) {
            PDM.showNotification('Please enter a valid phone number', 'error');
            return;
        }
        
        PDM.showLoading($form.find('.pdm-search-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_search_by_phone',
                phone_number: phoneNumber,
                nonce: PDM.nonce
            },
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-search-btn'));
                
                if (response.success) {
                    PDM.displayPhoneSearchResults(response.data);
                    PDM.addToRecentSearches(phoneNumber, 'phone');
                } else {
                    PDM.showNotification(response.data || 'Search failed', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-search-btn'));
                PDM.showNotification('An error occurred during search', 'error');
            }
        });
    };
    
    // Display phone search results
    PDM.displayPhoneSearchResults = function(data) {
        var $results = $('#pdm-search-results');
        var html = '';
        
        if (data.orders && data.orders.length > 0) {
            html += '<h3>Found ' + data.orders.length + ' order(s)</h3>';
            html += '<div class="pdm-table-container">';
            html += '<table class="pdm-table">';
            html += '<thead><tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Date</th><th>Amount</th><th>Actions</th></tr></thead><tbody>';
            
            data.orders.forEach(function(order) {
                html += '<tr>';
                html += '<td>' + PDM.escapeHtml(order.id) + '</td>';
                html += '<td>' + PDM.escapeHtml(order.customer_name) + '</td>';
                html += '<td><span class="pdm-status pdm-status-' + order.status + '">' + PDM.escapeHtml(order.status) + '</span></td>';
                html += '<td>' + PDM.formatDate(order.created_at) + '</td>';
                html += '<td>৳' + PDM.formatNumber(order.amount) + '</td>';
                html += '<td>';
                html += '<button class="pdm-btn pdm-btn-small pdm-btn-primary pdm-view-details" data-order-id="' + order.id + '">View</button> ';
                html += '<button class="pdm-btn pdm-btn-small pdm-btn-secondary pdm-view-tracking" data-order-id="' + order.id + '">Track</button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
        } else {
            html += '<div class="pdm-alert pdm-alert-info">';
            html += '<p>No orders found for this phone number.</p>';
            html += '</div>';
        }
        
        $results.html(html).show();
    };
    
    // Order tracking functionality
    PDM.handleOrderTracking = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var orderId = $form.find('#order_id').val().trim();
        var consignmentId = $form.find('#consignment_id').val().trim();
        
        if (!orderId && !consignmentId) {
            PDM.showNotification('Please enter either Order ID or Consignment ID', 'error');
            return;
        }
        
        PDM.showLoading($form.find('.pdm-track-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_track_order',
                order_id: orderId,
                consignment_id: consignmentId,
                nonce: PDM.nonce
            },
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-track-btn'));
                
                if (response.success) {
                    PDM.displayTrackingResults(response.data);
                    PDM.addToRecentSearches(orderId || consignmentId, 'tracking');
                } else {
                    PDM.showNotification(response.data || 'Tracking failed', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-track-btn'));
                PDM.showNotification('An error occurred during tracking', 'error');
            }
        });
    };
    
    // Display tracking results
    PDM.displayTrackingResults = function(data) {
        var $results = $('#pdm-tracking-results');
        var html = '';
        
        if (data.order) {
            var order = data.order;
            html += '<div class="pdm-card">';
            html += '<h3>Order Information</h3>';
            html += '<div class="pdm-grid pdm-grid-2">';
            html += '<div><strong>Order ID:</strong> ' + PDM.escapeHtml(order.id) + '</div>';
            html += '<div><strong>Consignment ID:</strong> ' + PDM.escapeHtml(order.consignment_id || 'N/A') + '</div>';
            html += '<div><strong>Customer:</strong> ' + PDM.escapeHtml(order.customer_name) + '</div>';
            html += '<div><strong>Phone:</strong> ' + PDM.escapeHtml(order.phone_number) + '</div>';
            html += '<div><strong>Status:</strong> <span class="pdm-status pdm-status-' + order.status + '">' + PDM.escapeHtml(order.status) + '</span></div>';
            html += '<div><strong>Amount:</strong> ৳' + PDM.formatNumber(order.amount) + '</div>';
            html += '</div>';
            html += '</div>';
        }
        
        if (data.tracking && data.tracking.length > 0) {
            html += '<div class="pdm-card">';
            html += '<h3>Tracking History</h3>';
            html += '<div class="pdm-tracking-timeline">';
            
            data.tracking.forEach(function(track, index) {
                html += '<div class="pdm-tracking-item' + (index === 0 ? ' current' : '') + '">';
                html += '<div class="pdm-tracking-icon"></div>';
                html += '<div class="pdm-tracking-content">';
                html += '<div class="pdm-tracking-status">' + PDM.escapeHtml(track.status) + '</div>';
                html += '<div class="pdm-tracking-message">' + PDM.escapeHtml(track.message) + '</div>';
                html += '<div class="pdm-tracking-time">' + PDM.formatDateTime(track.created_at) + '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            html += '</div>';
        }
        
        $results.html(html).show();
    };
    
    // Fake order creation
    PDM.handleFakeOrderCreation = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        
        PDM.showLoading($form.find('.pdm-create-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: formData + '&action=pdm_create_fake_order&nonce=' + PDM.nonce,
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-create-btn'));
                
                if (response.success) {
                    PDM.showNotification('Fake order created successfully', 'success');
                    $form[0].reset();
                    PDM.refreshFakeOrdersList();
                } else {
                    PDM.showNotification(response.data || 'Failed to create fake order', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-create-btn'));
                PDM.showNotification('An error occurred while creating fake order', 'error');
            }
        });
    };
    
    // Bulk fake orders creation
    PDM.handleBulkFakeOrders = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var count = parseInt($form.find('#bulk_count').val());
        
        if (count < 1 || count > 100) {
            PDM.showNotification('Please enter a count between 1 and 100', 'error');
            return;
        }
        
        PDM.showLoading($form.find('.pdm-bulk-create-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_create_bulk_fake_orders',
                count: count,
                nonce: PDM.nonce
            },
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-bulk-create-btn'));
                
                if (response.success) {
                    PDM.showNotification('Created ' + response.data.created + ' fake orders', 'success');
                    PDM.refreshFakeOrdersList();
                } else {
                    PDM.showNotification(response.data || 'Failed to create bulk fake orders', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-bulk-create-btn'));
                PDM.showNotification('An error occurred while creating bulk fake orders', 'error');
            }
        });
    };
    
    // Delete fake order
    PDM.handleDeleteFakeOrder = function(e) {
        e.preventDefault();
        
        var orderId = $(this).data('order-id');
        
        if (!confirm('Are you sure you want to delete this fake order?')) {
            return;
        }
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_delete_fake_order',
                order_id: orderId,
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    PDM.showNotification('Fake order deleted successfully', 'success');
                    PDM.refreshFakeOrdersList();
                } else {
                    PDM.showNotification(response.data || 'Failed to delete fake order', 'error');
                }
            },
            error: function() {
                PDM.showNotification('An error occurred while deleting fake order', 'error');
            }
        });
    };
    
    // Settings save
    PDM.handleSettingsSave = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        
        PDM.showLoading($form.find('.pdm-save-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: formData + '&action=pdm_save_settings&nonce=' + PDM.nonce,
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-save-btn'));
                
                if (response.success) {
                    PDM.showNotification('Settings saved successfully', 'success');
                } else {
                    PDM.showNotification(response.data || 'Failed to save settings', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-save-btn'));
                PDM.showNotification('An error occurred while saving settings', 'error');
            }
        });
    };
    
    // Test API connection
    PDM.testAPIConnection = function(e) {
        e.preventDefault();
        
        var $btn = $(this);
        PDM.showLoading($btn);
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_test_api_connection',
                nonce: PDM.nonce
            },
            success: function(response) {
                PDM.hideLoading($btn);
                
                if (response.success) {
                    PDM.showNotification('API connection successful', 'success');
                } else {
                    PDM.showNotification(response.data || 'API connection failed', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($btn);
                PDM.showNotification('An error occurred while testing API connection', 'error');
            }
        });
    };
    
    // Modal functionality
    PDM.initModals = function() {
        // Close modal on escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27 && PDM.currentModal) {
                PDM.closeModal();
            }
        });
    };
    
    PDM.openModal = function(e) {
        e.preventDefault();
        
        var modalId = $(this).data('modal');
        var $modal = $('#' + modalId);
        
        if ($modal.length) {
            $modal.addClass('show');
            PDM.currentModal = $modal;
            $('body').addClass('pdm-modal-open');
        }
    };
    
    PDM.closeModal = function(e) {
        if (e && $(e.target).hasClass('pdm-modal-close') === false && $(e.target).hasClass('pdm-modal-backdrop') === false) {
            return;
        }
        
        if (PDM.currentModal) {
            PDM.currentModal.removeClass('show');
            PDM.currentModal = null;
            $('body').removeClass('pdm-modal-open');
        }
    };
    
    // Tab functionality
    PDM.initTabs = function() {
        $(document).on('click', '.pdm-tab-btn', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var tab = $btn.data('tab');
            var $container = $btn.closest('.pdm-tabs-container');
            
            // Update active tab button
            $container.find('.pdm-tab-btn').removeClass('active');
            $btn.addClass('active');
            
            // Update active tab content
            $container.find('.pdm-tab-content').removeClass('active');
            $container.find('#pdm-tab-' + tab).addClass('active');
        });
    };
    
    // Tooltip functionality
    PDM.initTooltips = function() {
        $(document).on('mouseenter', '[data-tooltip]', function() {
            var $el = $(this);
            var text = $el.data('tooltip');
            
            if (!text) return;
            
            var $tooltip = $('<div class="pdm-tooltip">' + PDM.escapeHtml(text) + '</div>');
            $('body').append($tooltip);
            
            var offset = $el.offset();
            var elWidth = $el.outerWidth();
            var elHeight = $el.outerHeight();
            var tooltipWidth = $tooltip.outerWidth();
            var tooltipHeight = $tooltip.outerHeight();
            
            $tooltip.css({
                top: offset.top - tooltipHeight - 10,
                left: offset.left + (elWidth / 2) - (tooltipWidth / 2)
            });
            
            $el.data('tooltip-element', $tooltip);
        });
        
        $(document).on('mouseleave', '[data-tooltip]', function() {
            var $tooltip = $(this).data('tooltip-element');
            if ($tooltip) {
                $tooltip.remove();
                $(this).removeData('tooltip-element');
            }
        });
    };
    
    // Search functionality
    PDM.initSearchFunctionality = function() {
        // Real-time search
        $(document).on('input', '.pdm-search-input', PDM.debounce(function() {
            var query = $(this).val().trim();
            var searchType = $(this).data('search-type');
            
            if (query.length >= 3) {
                PDM.performSearch(query, searchType);
            }
        }, 300));
        
        // Display recent searches
        PDM.displayRecentSearches();
    };
    
    // Form validation
    PDM.initFormValidation = function() {
        $(document).on('submit', 'form[data-validate]', function(e) {
            var $form = $(this);
            var isValid = true;
            
            $form.find('[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    PDM.showFieldError($field, 'This field is required');
                    isValid = false;
                } else {
                    PDM.hideFieldError($field);
                }
            });
            
            // Phone number validation
            $form.find('input[type="tel"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value && !PDM.validatePhoneNumber(value)) {
                    PDM.showFieldError($field, 'Please enter a valid phone number');
                    isValid = false;
                }
            });
            
            // Email validation
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value && !PDM.validateEmail(value)) {
                    PDM.showFieldError($field, 'Please enter a valid email address');
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    };
    
    // Progress bars
    PDM.initProgressBars = function() {
        $('.pdm-progress-bar').each(function() {
            var $bar = $(this);
            var percentage = $bar.data('percentage') || 0;
            
            setTimeout(function() {
                $bar.css('width', percentage + '%');
            }, 100);
        });
    };
    
    // Notifications
    PDM.initNotifications = function() {
        // Auto-hide notifications after 5 seconds
        setTimeout(function() {
            $('.pdm-notification').fadeOut();
        }, 5000);
    };
    
    // Utility functions
    PDM.showLoading = function($element) {
        var originalText = $element.text();
        $element.data('original-text', originalText)
                .prop('disabled', true)
                .html('<span class="pdm-spinner"></span> Loading...');
    };
    
    PDM.hideLoading = function($element) {
        var originalText = $element.data('original-text') || 'Submit';
        $element.prop('disabled', false)
                .html(originalText)
                .removeData('original-text');
    };
    
    PDM.showNotification = function(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="pdm-alert pdm-alert-' + type + '">' + PDM.escapeHtml(message) + '</div>');
        
        $('.wrap').prepend($notification);
        
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    };
    
    PDM.showFieldError = function($field, message) {
        $field.addClass('pdm-field-error');
        
        var $error = $field.siblings('.pdm-field-error-message');
        if ($error.length === 0) {
            $error = $('<div class="pdm-field-error-message">' + PDM.escapeHtml(message) + '</div>');
            $field.after($error);
        } else {
            $error.text(message);
        }
    };
    
    PDM.hideFieldError = function($field) {
        $field.removeClass('pdm-field-error');
        $field.siblings('.pdm-field-error-message').remove();
    };
    
    PDM.validatePhoneNumber = function(phone) {
        // Bangladesh phone number validation
        var phoneRegex = /^(\+88)?01[3-9]\d{8}$/;
        return phoneRegex.test(phone.replace(/\s+/g, ''));
    };
    
    PDM.validateEmail = function(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    };
    
    PDM.escapeHtml = function(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    };
    
    PDM.formatDate = function(dateString) {
        if (!dateString) return 'N/A';
        var date = new Date(dateString);
        return date.toLocaleDateString('en-GB');
    };
    
    PDM.formatDateTime = function(dateString) {
        if (!dateString) return 'N/A';
        var date = new Date(dateString);
        return date.toLocaleDateString('en-GB') + ' ' + date.toLocaleTimeString('en-GB', {hour: '2-digit', minute: '2-digit'});
    };
    
    PDM.formatNumber = function(number) {
        if (!number) return '0';
        return parseFloat(number).toLocaleString('en-BD');
    };
    
    PDM.copyToClipboard = function(e) {
        e.preventDefault();
        
        var text = $(this).data('copy-text') || $(this).text();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                PDM.showNotification('Copied to clipboard', 'success');
            });
        } else {
            // Fallback for older browsers
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            PDM.showNotification('Copied to clipboard', 'success');
        }
    };
    
    PDM.refreshData = function(e) {
        e.preventDefault();
        location.reload();
    };
    
    PDM.autoSave = function() {
        var $field = $(this);
        var fieldName = $field.attr('name');
        var fieldValue = $field.val();
        
        // Save to localStorage
        localStorage.setItem('pdm_autosave_' + fieldName, fieldValue);
        
        // Show auto-save indicator
        var $indicator = $field.siblings('.pdm-autosave-indicator');
        if ($indicator.length === 0) {
            $indicator = $('<span class="pdm-autosave-indicator">Saved</span>');
            $field.after($indicator);
        }
        
        $indicator.show().delay(2000).fadeOut();
    };
    
    PDM.addToRecentSearches = function(query, type) {
        var search = {
            query: query,
            type: type,
            timestamp: Date.now()
        };
        
        // Remove existing search with same query
        PDM.recentSearches = PDM.recentSearches.filter(function(s) {
            return s.query !== query || s.type !== type;
        });
        
        // Add to beginning
        PDM.recentSearches.unshift(search);
        
        // Keep only last 10 searches
        PDM.recentSearches = PDM.recentSearches.slice(0, 10);
        
        // Save to localStorage
        localStorage.setItem('pdm_recent_searches', JSON.stringify(PDM.recentSearches));
        
        // Update display
        PDM.displayRecentSearches();
    };
    
    PDM.displayRecentSearches = function() {
        var $container = $('.pdm-recent-searches');
        if ($container.length === 0) return;
        
        if (PDM.recentSearches.length === 0) {
            $container.html('<p>No recent searches</p>');
            return;
        }
        
        var html = '<h4>Recent Searches</h4><ul>';
        PDM.recentSearches.forEach(function(search) {
            html += '<li class="pdm-recent-search-item" data-query="' + PDM.escapeHtml(search.query) + '" data-type="' + search.type + '">';
            html += '<span class="pdm-search-query">' + PDM.escapeHtml(search.query) + '</span>';
            html += '<span class="pdm-search-type">' + search.type + '</span>';
            html += '<span class="pdm-search-time">' + PDM.timeAgo(search.timestamp) + '</span>';
            html += '</li>';
        });
        html += '</ul>';
        html += '<button class="pdm-btn pdm-btn-small pdm-btn-secondary pdm-clear-recent-searches">Clear All</button>';
        
        $container.html(html);
    };
    
    PDM.clearRecentSearches = function(e) {
        e.preventDefault();
        
        if (confirm('Are you sure you want to clear all recent searches?')) {
            PDM.recentSearches = [];
            localStorage.removeItem('pdm_recent_searches');
            PDM.displayRecentSearches();
        }
    };
    
    PDM.timeAgo = function(timestamp) {
        var now = Date.now();
        var diff = now - timestamp;
        var minutes = Math.floor(diff / 60000);
        var hours = Math.floor(diff / 3600000);
        var days = Math.floor(diff / 86400000);
        
        if (days > 0) return days + 'd ago';
        if (hours > 0) return hours + 'h ago';
        if (minutes > 0) return minutes + 'm ago';
        return 'Just now';
    };
    
    PDM.debounce = function(func, wait) {
        var timeout;
        return function executedFunction() {
            var context = this;
            var args = arguments;
            var later = function() {
                timeout = null;
                func.apply(context, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    PDM.loadSettings = function() {
        // Load any saved settings from localStorage
        var savedSettings = localStorage.getItem('pdm_settings');
        if (savedSettings) {
            PDM.settings = JSON.parse(savedSettings);
        }
    };
    
    PDM.refreshFakeOrdersList = function() {
        var $container = $('#pdm-fake-orders-list');
        if ($container.length === 0) return;
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_get_fake_orders',
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    $container.html(response.data.html);
                }
            }
        });
    };
    
    // Generate sample orders
    PDM.generateSampleOrders = function(e) {
        e.preventDefault();
        
        var sampleData = [
            'John Doe, 01712345678, House 123 Road 5 Dhanmondi Dhaka, Mobile Phone, 0.5, 1500',
            'Jane Smith, 01812345679, Flat 4B Building 7 Gulshan 2 Dhaka, Laptop, 2.0, 3000',
            'Ahmed Rahman, 01912345680, Village Rampur Post Savar Dhaka, Books, 1.0, 500',
            'Fatima Khan, 01612345681, House 45 Sector 3 Uttara Dhaka, Clothes, 0.8, 1200',
            'Mohammad Ali, 01512345682, Apartment 2C Green Road Dhaka, Medicine, 0.3, 800'
        ];
        
        $('#manual_data').val(sampleData.join('\n'));
    };
    
    // Add sample delivery data
    PDM.addSampleDeliveryData = function(e) {
        e.preventDefault();
        PDM.generateSampleOrders(e);
    };
    
    // Save bulk settings
    PDM.saveBulkSettings = function(e) {
        e.preventDefault();
        
        var settings = {
            default_delivery_type: $('#default_delivery_type').val(),
            default_item_type: $('#default_item_type').val(),
            batch_size: $('#batch_size').val(),
            delay_between_batches: $('#delay_between_batches').val(),
            auto_retry_failed: $('#auto_retry_failed').is(':checked'),
            send_notifications: $('#send_notifications').is(':checked')
        };
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_save_bulk_settings',
                settings: settings,
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    PDM.showNotification('Settings saved successfully', 'success');
                } else {
                    PDM.showNotification(response.data || 'Failed to save settings', 'error');
                }
            },
            error: function() {
                PDM.showNotification('An error occurred while saving settings', 'error');
            }
        });
    };
    
    // View order details
    PDM.viewOrderDetails = function(e) {
        e.preventDefault();
        
        var orderId = $(this).data('order-id');
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_get_order_details',
                order_id: orderId,
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    PDM.showOrderDetailsModal(response.data);
                } else {
                    PDM.showNotification(response.data || 'Failed to load order details', 'error');
                }
            },
            error: function() {
                PDM.showNotification('An error occurred while loading order details', 'error');
            }
        });
    };
    
    // Show order details modal
    PDM.showOrderDetailsModal = function(order) {
        var html = '<div class="pdm-modal" id="pdm-order-details-modal">';
        html += '<div class="pdm-modal-content">';
        html += '<div class="pdm-modal-header">';
        html += '<h3 class="pdm-modal-title">Order Details</h3>';
        html += '<button class="pdm-modal-close">&times;</button>';
        html += '</div>';
        html += '<div class="pdm-modal-body">';
        html += '<div class="pdm-grid pdm-grid-2">';
        html += '<div><strong>Order ID:</strong> ' + PDM.escapeHtml(order.id) + '</div>';
        html += '<div><strong>Customer:</strong> ' + PDM.escapeHtml(order.customer_name) + '</div>';
        html += '<div><strong>Phone:</strong> ' + PDM.escapeHtml(order.phone_number) + '</div>';
        html += '<div><strong>Address:</strong> ' + PDM.escapeHtml(order.delivery_address) + '</div>';
        html += '<div><strong>Status:</strong> <span class="pdm-status pdm-status-' + order.status + '">' + PDM.escapeHtml(order.status) + '</span></div>';
        html += '<div><strong>Amount:</strong> ৳' + PDM.formatNumber(order.amount) + '</div>';
        html += '<div><strong>Created:</strong> ' + PDM.formatDateTime(order.created_at) + '</div>';
        html += '<div><strong>Updated:</strong> ' + PDM.formatDateTime(order.updated_at) + '</div>';
        html += '</div>';
        html += '</div>';
        html += '<div class="pdm-modal-footer">';
        html += '<button class="pdm-btn pdm-btn-secondary pdm-modal-close">Close</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        $('#pdm-order-details-modal').addClass('show');
        PDM.currentModal = $('#pdm-order-details-modal');
    };
    
    // View tracking history
    PDM.viewTrackingHistory = function(e) {
        e.preventDefault();
        
        var orderId = $(this).data('order-id');
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_get_tracking_history',
                order_id: orderId,
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    PDM.showTrackingHistoryModal(response.data);
                } else {
                    PDM.showNotification(response.data || 'Failed to load tracking history', 'error');
                }
            },
            error: function() {
                PDM.showNotification('An error occurred while loading tracking history', 'error');
            }
        });
    };
    
    // Show tracking history modal
    PDM.showTrackingHistoryModal = function(data) {
        var html = '<div class="pdm-modal" id="pdm-tracking-history-modal">';
        html += '<div class="pdm-modal-content">';
        html += '<div class="pdm-modal-header">';
        html += '<h3 class="pdm-modal-title">Tracking History</h3>';
        html += '<button class="pdm-modal-close">&times;</button>';
        html += '</div>';
        html += '<div class="pdm-modal-body">';
        
        if (data.tracking && data.tracking.length > 0) {
            html += '<div class="pdm-tracking-timeline">';
            data.tracking.forEach(function(track, index) {
                html += '<div class="pdm-tracking-item' + (index === 0 ? ' current' : '') + '">';
                html += '<div class="pdm-tracking-icon"></div>';
                html += '<div class="pdm-tracking-content">';
                html += '<div class="pdm-tracking-status">' + PDM.escapeHtml(track.status) + '</div>';
                html += '<div class="pdm-tracking-message">' + PDM.escapeHtml(track.message) + '</div>';
                html += '<div class="pdm-tracking-time">' + PDM.formatDateTime(track.created_at) + '</div>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
        } else {
            html += '<p>No tracking history available for this order.</p>';
        }
        
        html += '</div>';
        html += '<div class="pdm-modal-footer">';
        html += '<button class="pdm-btn pdm-btn-secondary pdm-modal-close">Close</button>';
        html += '</div>';
        html += '</div>';
        html += '</div>';
        
        $('body').append(html);
        $('#pdm-tracking-history-modal').addClass('show');
        PDM.currentModal = $('#pdm-tracking-history-modal');
    };
    
    // Handle recent search click
    PDM.handleRecentSearchClick = function(e) {
        e.preventDefault();
        
        var query = $(this).data('query');
        var type = $(this).data('type');
        
        if (type === 'phone') {
            $('#phone_number').val(query);
            $('#pdm-phone-search-form').submit();
        } else if (type === 'tracking') {
            if (query.startsWith('PDM')) {
                $('#order_id').val(query);
            } else {
                $('#consignment_id').val(query);
            }
            $('#pdm-order-tracking-form').submit();
        }
    };
    
    // Handle CSV upload
    PDM.handleCSVUpload = function(e) {
        var file = e.target.files[0];
        if (!file) return;
        
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            PDM.showNotification('Please select a valid CSV file', 'error');
            $(this).val('');
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) { // 2MB limit
            PDM.showNotification('File size must be less than 2MB', 'error');
            $(this).val('');
            return;
        }
        
        PDM.showNotification('CSV file selected: ' + file.name, 'success');
    };
    
    // Handle bulk tracking
    PDM.handleBulkTracking = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = new FormData($form[0]);
        formData.append('action', 'pdm_bulk_track_orders');
        formData.append('nonce', PDM.nonce);
        
        PDM.showLoading($form.find('.pdm-bulk-track-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-bulk-track-btn'));
                
                if (response.success) {
                    PDM.displayBulkTrackingResults(response.data);
                } else {
                    PDM.showNotification(response.data || 'Bulk tracking failed', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-bulk-track-btn'));
                PDM.showNotification('An error occurred during bulk tracking', 'error');
            }
        });
    };
    
    // Display bulk tracking results
    PDM.displayBulkTrackingResults = function(data) {
        var $results = $('#pdm-bulk-tracking-results');
        var html = '';
        
        if (data.results && data.results.length > 0) {
            html += '<h3>Bulk Tracking Results (' + data.results.length + ' orders)</h3>';
            html += '<table class="pdm-table">';
            html += '<thead><tr><th>Order ID</th><th>Status</th><th>Last Update</th><th>Actions</th></tr></thead><tbody>';
            
            data.results.forEach(function(result) {
                html += '<tr>';
                html += '<td>' + PDM.escapeHtml(result.order_id) + '</td>';
                html += '<td><span class="pdm-status pdm-status-' + result.status + '">' + PDM.escapeHtml(result.status) + '</span></td>';
                html += '<td>' + PDM.formatDateTime(result.last_update) + '</td>';
                html += '<td>';
                html += '<button class="pdm-btn pdm-btn-small pdm-btn-primary pdm-view-details" data-order-id="' + result.order_id + '">View</button> ';
                html += '<button class="pdm-btn pdm-btn-small pdm-btn-secondary pdm-view-tracking" data-order-id="' + result.order_id + '">Track</button>';
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        } else {
            html += '<div class="pdm-alert pdm-alert-info">';
            html += '<p>No tracking results found.</p>';
            html += '</div>';
        }
        
        $results.html(html).show();
    };
    
    // Handle bulk delivery
    PDM.handleBulkDelivery = function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var method = $form.find('input[name="bulk_method"]').val();
        var testMode = $form.find('input[name="test_mode"]').is(':checked');
        
        if (!testMode && !confirm('Are you sure you want to create these deliveries? This action cannot be undone.')) {
            return;
        }
        
        var formData = new FormData($form[0]);
        formData.append('action', 'pdm_process_bulk_delivery_ajax');
        formData.append('nonce', PDM.nonce);
        
        PDM.showLoading($form.find('.pdm-process-btn'));
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                PDM.hideLoading($form.find('.pdm-process-btn'));
                
                if (response.success) {
                    PDM.displayBulkDeliveryResults(response.data);
                } else {
                    PDM.showNotification(response.data || 'Bulk delivery processing failed', 'error');
                }
            },
            error: function() {
                PDM.hideLoading($form.find('.pdm-process-btn'));
                PDM.showNotification('An error occurred during bulk delivery processing', 'error');
            }
        });
    };
    
    // Display bulk delivery results
    PDM.displayBulkDeliveryResults = function(data) {
        var $results = $('#pdm-bulk-delivery-results');
        var html = '';
        
        html += '<div class="pdm-card">';
        html += '<h3>Bulk Delivery Results</h3>';
        html += '<div class="pdm-grid pdm-grid-3">';
        html += '<div class="pdm-stat-card stat-orders">';
        html += '<span class="pdm-stat-value">' + data.total + '</span>';
        html += '<span class="pdm-stat-label">Total</span>';
        html += '</div>';
        html += '<div class="pdm-stat-card stat-delivered">';
        html += '<span class="pdm-stat-value">' + data.success + '</span>';
        html += '<span class="pdm-stat-label">Success</span>';
        html += '</div>';
        html += '<div class="pdm-stat-card stat-failed">';
        html += '<span class="pdm-stat-value">' + data.failed + '</span>';
        html += '<span class="pdm-stat-label">Failed</span>';
        html += '</div>';
        html += '</div>';
        
        if (data.results && data.results.length > 0) {
            html += '<h4>Detailed Results</h4>';
            html += '<table class="pdm-table">';
            html += '<thead><tr><th>Customer</th><th>Phone</th><th>Status</th><th>Consignment ID</th><th>Error</th></tr></thead><tbody>';
            
            data.results.forEach(function(result) {
                html += '<tr>';
                html += '<td>' + PDM.escapeHtml(result.customer_name || 'N/A') + '</td>';
                html += '<td>' + PDM.escapeHtml(result.phone_number || 'N/A') + '</td>';
                
                if (result.success) {
                    html += '<td><span class="pdm-status pdm-status-success">SUCCESS</span></td>';
                    html += '<td>' + PDM.escapeHtml(result.consignment_id || 'N/A') + '</td>';
                    html += '<td>-</td>';
                } else {
                    html += '<td><span class="pdm-status pdm-status-danger">FAILED</span></td>';
                    html += '<td>-</td>';
                    html += '<td>' + PDM.escapeHtml(result.error || 'Unknown error') + '</td>';
                }
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        html += '</div>';
        
        $results.html(html).show();
    };
    
    // Clear all fake orders
    PDM.handleClearAllFakeOrders = function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete ALL fake orders? This action cannot be undone.')) {
            return;
        }
        
        $.ajax({
            url: PDM.ajaxUrl,
            type: 'POST',
            data: {
                action: 'pdm_clear_all_fake_orders',
                nonce: PDM.nonce
            },
            success: function(response) {
                if (response.success) {
                    PDM.showNotification('All fake orders cleared successfully', 'success');
                    PDM.refreshFakeOrdersList();
                } else {
                    PDM.showNotification(response.data || 'Failed to clear fake orders', 'error');
                }
            },
            error: function() {
                PDM.showNotification('An error occurred while clearing fake orders', 'error');
            }
        });
    };
    
    // Edit fake order
    PDM.handleEditFakeOrder = function(e) {
        e.preventDefault();
        
        var orderId = $(this).data('order-id');
        
        // This would open an edit modal - for now just show a message
        PDM.showNotification('Edit functionality coming soon', 'info');
    };
    
    // Expose PDM object globally for debugging
    window.PDM = PDM;
    
})(jQuery);