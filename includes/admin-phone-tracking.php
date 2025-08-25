<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1>Phone Number Tracking</h1>
    <p>Search for orders using customer phone numbers to view order history and details.</p>
    
    <div class="pdm-phone-tracking-container">
        <div class="pdm-card">
            <h2>Search by Phone Number</h2>
            <form id="pdm-phone-search-form" class="pdm-search-form">
                <div class="pdm-form-row">
                    <div class="pdm-form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="text" id="phone_number" name="phone_number" placeholder="Enter phone number (e.g., 01712345678)" class="regular-text" required>
                        <p class="description">Enter the customer's phone number to search for their orders.</p>
                    </div>
                    <div class="pdm-form-group">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-search"></span>
                            Search Orders
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div id="pdm-search-results" class="pdm-search-results" style="display: none;">
            <div class="pdm-card">
                <h2>Search Results</h2>
                <div id="pdm-results-content"></div>
            </div>
        </div>
        
        <div class="pdm-card">
            <h2>Recent Phone Searches</h2>
            <div id="pdm-recent-searches">
                <p>No recent searches found.</p>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="pdm-order-details-modal" class="pdm-modal" style="display: none;">
    <div class="pdm-modal-content">
        <div class="pdm-modal-header">
            <h3>Order Details</h3>
            <span class="pdm-modal-close">&times;</span>
        </div>
        <div class="pdm-modal-body">
            <div id="pdm-order-details-content"></div>
        </div>
    </div>
</div>

<!-- Tracking History Modal -->
<div id="pdm-tracking-history-modal" class="pdm-modal" style="display: none;">
    <div class="pdm-modal-content">
        <div class="pdm-modal-header">
            <h3>Tracking History</h3>
            <span class="pdm-modal-close">&times;</span>
        </div>
        <div class="pdm-modal-body">
            <div id="pdm-tracking-history-content"></div>
        </div>
    </div>
</div>

<style>
.pdm-phone-tracking-container {
    margin-top: 20px;
}

.pdm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.pdm-card h2 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.pdm-search-form {
    margin-bottom: 0;
}

.pdm-form-row {
    display: flex;
    align-items: end;
    gap: 20px;
    flex-wrap: wrap;
}

.pdm-form-group {
    flex: 1;
    min-width: 200px;
}

.pdm-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.pdm-form-group input {
    width: 100%;
}

.pdm-form-group .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
}

.pdm-search-results {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pdm-results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.pdm-results-table th,
.pdm-results-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.pdm-results-table th {
    background-color: #f7f7f7;
    font-weight: bold;
}

.pdm-results-table tr:hover {
    background-color: #f9f9f9;
}

.pdm-status {
    padding: 4px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.pdm-status-pending { background: #fff3cd; color: #856404; }
.pdm-status-picked_up { background: #d4edda; color: #155724; }
.pdm-status-in_transit { background: #cce5ff; color: #004085; }
.pdm-status-delivered { background: #d1ecf1; color: #0c5460; }
.pdm-status-cancelled { background: #f8d7da; color: #721c24; }

.pdm-badge {
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}

.pdm-badge-real { background: #28a745; color: #fff; }
.pdm-badge-test { background: #ffc107; color: #212529; }

.pdm-action-buttons {
    display: flex;
    gap: 5px;
}

.pdm-action-buttons .button {
    padding: 4px 8px;
    font-size: 12px;
}

.pdm-no-results {
    text-align: center;
    padding: 40px;
    color: #666;
}

.pdm-no-results .dashicons {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ccc;
}

.pdm-loading {
    text-align: center;
    padding: 20px;
}

.pdm-loading .spinner {
    visibility: visible;
    float: none;
    margin: 0 auto 10px;
}

.pdm-recent-search-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #f9f9f9;
}

.pdm-recent-search-info {
    flex: 1;
}

.pdm-recent-search-phone {
    font-weight: bold;
    color: #0073aa;
}

.pdm-recent-search-time {
    font-size: 12px;
    color: #666;
}

.pdm-recent-search-count {
    background: #0073aa;
    color: #fff;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    margin-right: 10px;
}

.pdm-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.pdm-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 800px;
    border-radius: 4px;
    max-height: 80vh;
    overflow-y: auto;
}

.pdm-modal-header {
    padding: 15px 20px;
    background: #f7f7f7;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 1;
}

.pdm-modal-header h3 {
    margin: 0;
}

.pdm-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.pdm-modal-close:hover {
    color: #0073aa;
}

.pdm-modal-body {
    padding: 20px;
}

.pdm-order-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.pdm-order-detail-item {
    padding: 10px;
    background: #f9f9f9;
    border-radius: 4px;
}

.pdm-order-detail-label {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.pdm-order-detail-value {
    color: #666;
}

.pdm-tracking-timeline {
    position: relative;
    padding-left: 30px;
}

.pdm-tracking-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #ddd;
}

.pdm-tracking-item {
    position: relative;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.pdm-tracking-item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 20px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #0073aa;
    border: 2px solid #fff;
}

.pdm-tracking-status {
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.pdm-tracking-message {
    color: #666;
    margin-bottom: 5px;
}

.pdm-tracking-time {
    font-size: 12px;
    color: #999;
}

@media (max-width: 768px) {
    .pdm-form-row {
        flex-direction: column;
    }
    
    .pdm-form-group {
        min-width: auto;
    }
    
    .pdm-results-table {
        font-size: 14px;
    }
    
    .pdm-action-buttons {
        flex-direction: column;
    }
    
    .pdm-recent-search-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var recentSearches = JSON.parse(localStorage.getItem('pdm_recent_searches') || '[]');
    
    // Load recent searches on page load
    loadRecentSearches();
    
    // Phone search form submission
    $('#pdm-phone-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var phoneNumber = $('#phone_number').val().trim();
        if (!phoneNumber) {
            alert('Please enter a phone number.');
            return;
        }
        
        searchByPhone(phoneNumber);
    });
    
    function searchByPhone(phoneNumber) {
        var $results = $('#pdm-search-results');
        var $content = $('#pdm-results-content');
        
        // Show loading
        $content.html('<div class="pdm-loading"><div class="spinner is-active"></div><p>Searching for orders...</p></div>');
        $results.show();
        
        $.post(ajaxurl, {
            action: 'pdm_search_by_phone',
            phone_number: phoneNumber,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displaySearchResults(response.data, phoneNumber);
                addToRecentSearches(phoneNumber, response.data.length);
            } else {
                $content.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
            }
        }).fail(function() {
            $content.html('<div class="notice notice-error"><p>An error occurred while searching. Please try again.</p></div>');
        });
    }
    
    function displaySearchResults(orders, phoneNumber) {
        var $content = $('#pdm-results-content');
        
        if (orders.length === 0) {
            $content.html(
                '<div class="pdm-no-results">' +
                '<div class="dashicons dashicons-search"></div>' +
                '<h3>No Orders Found</h3>' +
                '<p>No orders found for phone number: <strong>' + phoneNumber + '</strong></p>' +
                '<p>Please check the phone number and try again.</p>' +
                '</div>'
            );
            return;
        }
        
        var html = '<div class="pdm-results-summary">';
        html += '<p><strong>' + orders.length + '</strong> order(s) found for phone number: <strong>' + phoneNumber + '</strong></p>';
        html += '</div>';
        
        html += '<table class="pdm-results-table">';
        html += '<thead><tr>';
        html += '<th>Order ID</th>';
        html += '<th>Customer Name</th>';
        html += '<th>Status</th>';
        html += '<th>Type</th>';
        html += '<th>Created Date</th>';
        html += '<th>Actions</th>';
        html += '</tr></thead><tbody>';
        
        $.each(orders, function(index, order) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(order.order_id) + '</strong></td>';
            html += '<td>' + escapeHtml(order.customer_name) + '</td>';
            html += '<td><span class="pdm-status pdm-status-' + order.delivery_status + '">' + 
                    escapeHtml(order.delivery_status.replace(/_/g, ' ').toUpperCase()) + '</span></td>';
            html += '<td>';
            if (order.is_fake == '1') {
                html += '<span class="pdm-badge pdm-badge-test">Test</span>';
            } else {
                html += '<span class="pdm-badge pdm-badge-real">Real</span>';
            }
            html += '</td>';
            html += '<td>' + formatDate(order.created_at) + '</td>';
            html += '<td><div class="pdm-action-buttons">';
            html += '<button class="button button-small pdm-view-details" data-order-id="' + order.order_id + '">Details</button>';
            html += '<button class="button button-small pdm-view-tracking" data-order-id="' + order.order_id + '">Tracking</button>';
            if (order.pathao_consignment_id) {
                html += '<button class="button button-small pdm-track-live" data-consignment-id="' + order.pathao_consignment_id + '">Live Track</button>';
            }
            html += '</div></td>';
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $content.html(html);
    }
    
    function addToRecentSearches(phoneNumber, orderCount) {
        var search = {
            phone: phoneNumber,
            count: orderCount,
            timestamp: new Date().toISOString()
        };
        
        // Remove existing entry for this phone number
        recentSearches = recentSearches.filter(function(item) {
            return item.phone !== phoneNumber;
        });
        
        // Add to beginning
        recentSearches.unshift(search);
        
        // Keep only last 10 searches
        recentSearches = recentSearches.slice(0, 10);
        
        localStorage.setItem('pdm_recent_searches', JSON.stringify(recentSearches));
        loadRecentSearches();
    }
    
    function loadRecentSearches() {
        var $container = $('#pdm-recent-searches');
        
        if (recentSearches.length === 0) {
            $container.html('<p>No recent searches found.</p>');
            return;
        }
        
        var html = '';
        $.each(recentSearches, function(index, search) {
            html += '<div class="pdm-recent-search-item">';
            html += '<div class="pdm-recent-search-info">';
            html += '<div class="pdm-recent-search-phone">' + escapeHtml(search.phone) + '</div>';
            html += '<div class="pdm-recent-search-time">' + formatDateTime(search.timestamp) + '</div>';
            html += '</div>';
            html += '<div class="pdm-recent-search-count">' + search.count + ' orders</div>';
            html += '<button class="button button-small pdm-search-again" data-phone="' + search.phone + '">Search Again</button>';
            html += '</div>';
        });
        
        $container.html(html);
    }
    
    // Event handlers
    $(document).on('click', '.pdm-search-again', function() {
        var phone = $(this).data('phone');
        $('#phone_number').val(phone);
        searchByPhone(phone);
    });
    
    $(document).on('click', '.pdm-view-details', function() {
        var orderId = $(this).data('order-id');
        viewOrderDetails(orderId);
    });
    
    $(document).on('click', '.pdm-view-tracking', function() {
        var orderId = $(this).data('order-id');
        viewTrackingHistory(orderId);
    });
    
    $(document).on('click', '.pdm-track-live', function() {
        var consignmentId = $(this).data('consignment-id');
        trackLiveOrder(consignmentId);
    });
    
    function viewOrderDetails(orderId) {
        // Implementation for viewing order details
        $('#pdm-order-details-content').html('<p>Loading order details...</p>');
        $('#pdm-order-details-modal').show();
        
        // Add AJAX call to get order details
    }
    
    function viewTrackingHistory(orderId) {
        $('#pdm-tracking-history-content').html('<p>Loading tracking history...</p>');
        $('#pdm-tracking-history-modal').show();
        
        $.post(ajaxurl, {
            action: 'pdm_get_tracking_history',
            order_id: orderId,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayTrackingHistory(response.data);
            } else {
                $('#pdm-tracking-history-content').html('<p>Error loading tracking history.</p>');
            }
        });
    }
    
    function displayTrackingHistory(history) {
        var html = '<div class="pdm-tracking-timeline">';
        
        if (history.length === 0) {
            html += '<p>No tracking history available.</p>';
        } else {
            $.each(history, function(index, item) {
                html += '<div class="pdm-tracking-item">';
                html += '<div class="pdm-tracking-status">' + escapeHtml(item.status.replace(/_/g, ' ').toUpperCase()) + '</div>';
                html += '<div class="pdm-tracking-message">' + escapeHtml(item.message) + '</div>';
                html += '<div class="pdm-tracking-time">' + formatDateTime(item.timestamp) + '</div>';
                html += '</div>';
            });
        }
        
        html += '</div>';
        $('#pdm-tracking-history-content').html(html);
    }
    
    function trackLiveOrder(consignmentId) {
        // Implementation for live tracking
        alert('Live tracking for: ' + consignmentId);
    }
    
    // Modal close functionality
    $('.pdm-modal-close').on('click', function() {
        $('.pdm-modal').hide();
    });
    
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('pdm-modal')) {
            $('.pdm-modal').hide();
        }
    });
    
    // Utility functions
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    function formatDateTime(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
});
</script>