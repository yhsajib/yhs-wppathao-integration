<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap">
    <h1>Order Tracking</h1>
    <p>Track individual orders using Order ID or Pathao Consignment ID to get real-time delivery status.</p>
    
    <div class="pdm-order-tracking-container">
        <div class="pdm-card">
            <h2>Track Order</h2>
            <form id="pdm-order-tracking-form" class="pdm-tracking-form">
                <div class="pdm-form-row">
                    <div class="pdm-form-group">
                        <label for="tracking_type">Tracking Type:</label>
                        <select id="tracking_type" name="tracking_type" class="regular-text">
                            <option value="order_id">Order ID</option>
                            <option value="consignment_id">Pathao Consignment ID</option>
                        </select>
                    </div>
                    <div class="pdm-form-group">
                        <label for="tracking_id">Tracking ID:</label>
                        <input type="text" id="tracking_id" name="tracking_id" placeholder="Enter Order ID or Consignment ID" class="regular-text" required>
                        <p class="description">Enter the Order ID or Pathao Consignment ID to track the delivery.</p>
                    </div>
                    <div class="pdm-form-group">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-visibility"></span>
                            Track Order
                        </button>
                        <button type="button" id="pdm-refresh-tracking" class="button button-secondary" style="display: none;">
                            <span class="dashicons dashicons-update"></span>
                            Refresh
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div id="pdm-tracking-results" class="pdm-tracking-results" style="display: none;">
            <div class="pdm-card">
                <h2>Tracking Information</h2>
                <div id="pdm-tracking-content"></div>
            </div>
        </div>
        
        <div class="pdm-card">
            <h2>Bulk Order Status Check</h2>
            <p>Check the status of multiple orders at once by uploading a CSV file or entering order IDs.</p>
            
            <div class="pdm-bulk-tracking-tabs">
                <button class="pdm-tab-btn active" data-tab="manual">Manual Entry</button>
                <button class="pdm-tab-btn" data-tab="csv">CSV Upload</button>
            </div>
            
            <div id="pdm-bulk-manual" class="pdm-tab-content active">
                <form id="pdm-bulk-manual-form">
                    <div class="pdm-form-group">
                        <label for="bulk_order_ids">Order IDs (one per line):</label>
                        <textarea id="bulk_order_ids" name="bulk_order_ids" rows="6" class="large-text" placeholder="Enter order IDs, one per line"></textarea>
                    </div>
                    <button type="submit" class="button button-primary">Check All Orders</button>
                </form>
            </div>
            
            <div id="pdm-bulk-csv" class="pdm-tab-content">
                <form id="pdm-bulk-csv-form" enctype="multipart/form-data">
                    <div class="pdm-form-group">
                        <label for="csv_file">CSV File:</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <p class="description">Upload a CSV file with order IDs in the first column.</p>
                    </div>
                    <button type="submit" class="button button-primary">Upload and Check</button>
                </form>
            </div>
            
            <div id="pdm-bulk-results" style="display: none;">
                <h3>Bulk Tracking Results</h3>
                <div id="pdm-bulk-results-content"></div>
            </div>
        </div>
        
        <div class="pdm-card">
            <h2>Recent Tracking Searches</h2>
            <div id="pdm-recent-tracking">
                <p>No recent tracking searches found.</p>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Tracking Modal -->
<div id="pdm-detailed-tracking-modal" class="pdm-modal" style="display: none;">
    <div class="pdm-modal-content">
        <div class="pdm-modal-header">
            <h3>Detailed Tracking Information</h3>
            <span class="pdm-modal-close">&times;</span>
        </div>
        <div class="pdm-modal-body">
            <div id="pdm-detailed-tracking-content"></div>
        </div>
    </div>
</div>

<style>
.pdm-order-tracking-container {
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

.pdm-tracking-form {
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

.pdm-form-group input,
.pdm-form-group select,
.pdm-form-group textarea {
    width: 100%;
}

.pdm-form-group .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
}

.pdm-tracking-results {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pdm-tracking-card {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 15px;
}

.pdm-tracking-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.pdm-tracking-id {
    font-size: 18px;
    font-weight: bold;
    color: #0073aa;
}

.pdm-tracking-status {
    padding: 6px 12px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
    font-size: 12px;
}

.pdm-status-pending { background: #fff3cd; color: #856404; }
.pdm-status-pickup_requested { background: #cce5ff; color: #004085; }
.pdm-status-picked_up { background: #d4edda; color: #155724; }
.pdm-status-in_transit { background: #cce5ff; color: #004085; }
.pdm-status-out_for_delivery { background: #e2e3e5; color: #383d41; }
.pdm-status-delivered { background: #d1ecf1; color: #0c5460; }
.pdm-status-cancelled { background: #f8d7da; color: #721c24; }
.pdm-status-returned { background: #ffeaa7; color: #6c757d; }
.pdm-status-hold { background: #f0f0f0; color: #495057; }

.pdm-tracking-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.pdm-detail-item {
    background: #fff;
    padding: 10px;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.pdm-detail-label {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
    font-size: 12px;
    text-transform: uppercase;
}

.pdm-detail-value {
    color: #666;
    font-size: 14px;
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

.pdm-timeline-item {
    position: relative;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 4px;
    border: 1px solid #e0e0e0;
}

.pdm-timeline-item::before {
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

.pdm-timeline-item.current::before {
    background: #28a745;
    box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.3);
}

.pdm-timeline-status {
    font-weight: bold;
    color: #0073aa;
    margin-bottom: 5px;
}

.pdm-timeline-message {
    color: #666;
    margin-bottom: 5px;
}

.pdm-timeline-time {
    font-size: 12px;
    color: #999;
}

.pdm-bulk-tracking-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.pdm-tab-btn {
    padding: 10px 20px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-weight: bold;
    color: #666;
}

.pdm-tab-btn.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
}

.pdm-tab-content {
    display: none;
}

.pdm-tab-content.active {
    display: block;
}

.pdm-bulk-results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.pdm-bulk-results-table th,
.pdm-bulk-results-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.pdm-bulk-results-table th {
    background-color: #f7f7f7;
    font-weight: bold;
}

.pdm-bulk-results-table tr:hover {
    background-color: #f9f9f9;
}

.pdm-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
    margin: 10px 0;
}

.pdm-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005a87);
    transition: width 0.3s ease;
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

.pdm-recent-tracking-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #f9f9f9;
}

.pdm-recent-tracking-info {
    flex: 1;
}

.pdm-recent-tracking-id {
    font-weight: bold;
    color: #0073aa;
}

.pdm-recent-tracking-time {
    font-size: 12px;
    color: #666;
}

.pdm-recent-tracking-status {
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
    margin: 3% auto;
    padding: 0;
    border: 1px solid #888;
    width: 90%;
    max-width: 900px;
    border-radius: 4px;
    max-height: 90vh;
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

@media (max-width: 768px) {
    .pdm-form-row {
        flex-direction: column;
    }
    
    .pdm-form-group {
        min-width: auto;
    }
    
    .pdm-tracking-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .pdm-tracking-details {
        grid-template-columns: 1fr;
    }
    
    .pdm-bulk-tracking-tabs {
        flex-direction: column;
    }
    
    .pdm-recent-tracking-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var recentTracking = JSON.parse(localStorage.getItem('pdm_recent_tracking') || '[]');
    var currentTrackingId = null;
    
    // Load recent tracking on page load
    loadRecentTracking();
    
    // Order tracking form submission
    $('#pdm-order-tracking-form').on('submit', function(e) {
        e.preventDefault();
        
        var trackingType = $('#tracking_type').val();
        var trackingId = $('#tracking_id').val().trim();
        
        if (!trackingId) {
            alert('Please enter a tracking ID.');
            return;
        }
        
        trackOrder(trackingType, trackingId);
    });
    
    // Refresh tracking
    $('#pdm-refresh-tracking').on('click', function() {
        if (currentTrackingId) {
            var trackingType = $('#tracking_type').val();
            trackOrder(trackingType, currentTrackingId, true);
        }
    });
    
    // Tab switching
    $('.pdm-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.pdm-tab-btn').removeClass('active');
        $('.pdm-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#pdm-bulk-' + tab).addClass('active');
    });
    
    // Bulk manual form
    $('#pdm-bulk-manual-form').on('submit', function(e) {
        e.preventDefault();
        
        var orderIds = $('#bulk_order_ids').val().trim().split('\n').filter(function(id) {
            return id.trim() !== '';
        });
        
        if (orderIds.length === 0) {
            alert('Please enter at least one order ID.');
            return;
        }
        
        bulkTrackOrders(orderIds);
    });
    
    // Bulk CSV form
    $('#pdm-bulk-csv-form').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $('#csv_file')[0];
        if (!fileInput.files.length) {
            alert('Please select a CSV file.');
            return;
        }
        
        var file = fileInput.files[0];
        var reader = new FileReader();
        
        reader.onload = function(e) {
            var csv = e.target.result;
            var lines = csv.split('\n');
            var orderIds = [];
            
            for (var i = 0; i < lines.length; i++) {
                var line = lines[i].trim();
                if (line) {
                    var columns = line.split(',');
                    if (columns[0] && columns[0].trim()) {
                        orderIds.push(columns[0].trim());
                    }
                }
            }
            
            if (orderIds.length === 0) {
                alert('No valid order IDs found in the CSV file.');
                return;
            }
            
            bulkTrackOrders(orderIds);
        };
        
        reader.readAsText(file);
    });
    
    function trackOrder(trackingType, trackingId, isRefresh = false) {
        var $results = $('#pdm-tracking-results');
        var $content = $('#pdm-tracking-content');
        var $refreshBtn = $('#pdm-refresh-tracking');
        
        // Show loading
        $content.html('<div class="pdm-loading"><div class="spinner is-active"></div><p>Tracking order...</p></div>');
        $results.show();
        
        currentTrackingId = trackingId;
        
        $.post(ajaxurl, {
            action: 'pdm_track_order',
            tracking_type: trackingType,
            tracking_id: trackingId,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayTrackingResults(response.data, trackingType, trackingId);
                $refreshBtn.show();
                
                if (!isRefresh) {
                    addToRecentTracking(trackingType, trackingId, response.data.status || 'unknown');
                }
            } else {
                $content.html('<div class="notice notice-error"><p>Error: ' + response.data + '</p></div>');
                $refreshBtn.hide();
            }
        }).fail(function() {
            $content.html('<div class="notice notice-error"><p>An error occurred while tracking. Please try again.</p></div>');
            $refreshBtn.hide();
        });
    }
    
    function displayTrackingResults(data, trackingType, trackingId) {
        var $content = $('#pdm-tracking-content');
        
        var html = '<div class="pdm-tracking-card">';
        
        // Header
        html += '<div class="pdm-tracking-header">';
        html += '<div class="pdm-tracking-id">' + escapeHtml(trackingId) + '</div>';
        html += '<div class="pdm-tracking-status pdm-status-' + (data.status || 'unknown') + '">';
        html += escapeHtml((data.status || 'Unknown').replace(/_/g, ' ').toUpperCase());
        html += '</div>';
        html += '</div>';
        
        // Details
        if (data.details) {
            html += '<div class="pdm-tracking-details">';
            
            if (data.details.order_id) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Order ID</div>';
                html += '<div class="pdm-detail-value">' + escapeHtml(data.details.order_id) + '</div>';
                html += '</div>';
            }
            
            if (data.details.consignment_id) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Consignment ID</div>';
                html += '<div class="pdm-detail-value">' + escapeHtml(data.details.consignment_id) + '</div>';
                html += '</div>';
            }
            
            if (data.details.customer_name) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Customer</div>';
                html += '<div class="pdm-detail-value">' + escapeHtml(data.details.customer_name) + '</div>';
                html += '</div>';
            }
            
            if (data.details.phone_number) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Phone</div>';
                html += '<div class="pdm-detail-value">' + escapeHtml(data.details.phone_number) + '</div>';
                html += '</div>';
            }
            
            if (data.details.created_at) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Created</div>';
                html += '<div class="pdm-detail-value">' + formatDateTime(data.details.created_at) + '</div>';
                html += '</div>';
            }
            
            if (data.details.estimated_delivery) {
                html += '<div class="pdm-detail-item">';
                html += '<div class="pdm-detail-label">Est. Delivery</div>';
                html += '<div class="pdm-detail-value">' + formatDateTime(data.details.estimated_delivery) + '</div>';
                html += '</div>';
            }
            
            html += '</div>';
        }
        
        // Timeline
        if (data.timeline && data.timeline.length > 0) {
            html += '<h3>Tracking Timeline</h3>';
            html += '<div class="pdm-tracking-timeline">';
            
            $.each(data.timeline, function(index, item) {
                var isCurrent = index === 0; // Assuming first item is current
                html += '<div class="pdm-timeline-item' + (isCurrent ? ' current' : '') + '">';
                html += '<div class="pdm-timeline-status">' + escapeHtml(item.status.replace(/_/g, ' ').toUpperCase()) + '</div>';
                html += '<div class="pdm-timeline-message">' + escapeHtml(item.message || '') + '</div>';
                html += '<div class="pdm-timeline-time">' + formatDateTime(item.timestamp) + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
        }
        
        // Actions
        html += '<div class="pdm-tracking-actions" style="margin-top: 20px; text-align: center;">';
        html += '<button class="button button-secondary pdm-view-detailed" data-tracking-id="' + trackingId + '">View Detailed Info</button>';
        html += '</div>';
        
        html += '</div>';
        
        $content.html(html);
    }
    
    function bulkTrackOrders(orderIds) {
        var $results = $('#pdm-bulk-results');
        var $content = $('#pdm-bulk-results-content');
        
        $content.html('<div class="pdm-loading"><div class="spinner is-active"></div><p>Tracking ' + orderIds.length + ' orders...</p></div>');
        $results.show();
        
        var processed = 0;
        var results = [];
        
        // Process orders in batches
        function processNext() {
            if (processed >= orderIds.length) {
                displayBulkResults(results);
                return;
            }
            
            var orderId = orderIds[processed];
            
            $.post(ajaxurl, {
                action: 'pdm_track_order',
                tracking_type: 'order_id',
                tracking_id: orderId,
                nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
            }, function(response) {
                results.push({
                    order_id: orderId,
                    success: response.success,
                    data: response.data
                });
            }).fail(function() {
                results.push({
                    order_id: orderId,
                    success: false,
                    data: 'Request failed'
                });
            }).always(function() {
                processed++;
                
                // Update progress
                var progress = (processed / orderIds.length) * 100;
                $content.html(
                    '<div class="pdm-loading">' +
                    '<div class="pdm-progress-bar"><div class="pdm-progress-fill" style="width: ' + progress + '%"></div></div>' +
                    '<p>Processing order ' + processed + ' of ' + orderIds.length + '...</p>' +
                    '</div>'
                );
                
                // Process next after a short delay
                setTimeout(processNext, 500);
            });
        }
        
        processNext();
    }
    
    function displayBulkResults(results) {
        var $content = $('#pdm-bulk-results-content');
        
        var html = '<div class="pdm-bulk-summary">';
        var successful = results.filter(function(r) { return r.success; }).length;
        var failed = results.length - successful;
        
        html += '<p><strong>Summary:</strong> ' + successful + ' successful, ' + failed + ' failed out of ' + results.length + ' orders.</p>';
        html += '</div>';
        
        html += '<table class="pdm-bulk-results-table">';
        html += '<thead><tr><th>Order ID</th><th>Status</th><th>Customer</th><th>Phone</th><th>Actions</th></tr></thead><tbody>';
        
        $.each(results, function(index, result) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(result.order_id) + '</strong></td>';
            
            if (result.success) {
                html += '<td><span class="pdm-tracking-status pdm-status-' + (result.data.status || 'unknown') + '">';
                html += escapeHtml((result.data.status || 'Unknown').replace(/_/g, ' ').toUpperCase());
                html += '</span></td>';
                html += '<td>' + escapeHtml(result.data.details?.customer_name || 'N/A') + '</td>';
                html += '<td>' + escapeHtml(result.data.details?.phone_number || 'N/A') + '</td>';
                html += '<td><button class="button button-small pdm-track-single" data-order-id="' + result.order_id + '">Track</button></td>';
            } else {
                html += '<td><span class="pdm-tracking-status pdm-status-cancelled">ERROR</span></td>';
                html += '<td colspan="2">' + escapeHtml(result.data) + '</td>';
                html += '<td>-</td>';
            }
            
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        $content.html(html);
    }
    
    function addToRecentTracking(trackingType, trackingId, status) {
        var tracking = {
            type: trackingType,
            id: trackingId,
            status: status,
            timestamp: new Date().toISOString()
        };
        
        // Remove existing entry for this tracking ID
        recentTracking = recentTracking.filter(function(item) {
            return item.id !== trackingId;
        });
        
        // Add to beginning
        recentTracking.unshift(tracking);
        
        // Keep only last 10 searches
        recentTracking = recentTracking.slice(0, 10);
        
        localStorage.setItem('pdm_recent_tracking', JSON.stringify(recentTracking));
        loadRecentTracking();
    }
    
    function loadRecentTracking() {
        var $container = $('#pdm-recent-tracking');
        
        if (recentTracking.length === 0) {
            $container.html('<p>No recent tracking searches found.</p>');
            return;
        }
        
        var html = '';
        $.each(recentTracking, function(index, tracking) {
            html += '<div class="pdm-recent-tracking-item">';
            html += '<div class="pdm-recent-tracking-info">';
            html += '<div class="pdm-recent-tracking-id">' + escapeHtml(tracking.id) + '</div>';
            html += '<div class="pdm-recent-tracking-time">' + formatDateTime(tracking.timestamp) + '</div>';
            html += '</div>';
            html += '<div class="pdm-recent-tracking-status">';
            html += '<span class="pdm-tracking-status pdm-status-' + tracking.status + '">' + escapeHtml(tracking.status.replace(/_/g, ' ').toUpperCase()) + '</span>';
            html += '</div>';
            html += '<button class="button button-small pdm-track-again" data-type="' + tracking.type + '" data-id="' + tracking.id + '">Track Again</button>';
            html += '</div>';
        });
        
        $container.html(html);
    }
    
    // Event handlers
    $(document).on('click', '.pdm-track-again', function() {
        var type = $(this).data('type');
        var id = $(this).data('id');
        
        $('#tracking_type').val(type);
        $('#tracking_id').val(id);
        trackOrder(type, id);
    });
    
    $(document).on('click', '.pdm-track-single', function() {
        var orderId = $(this).data('order-id');
        $('#tracking_type').val('order_id');
        $('#tracking_id').val(orderId);
        trackOrder('order_id', orderId);
    });
    
    $(document).on('click', '.pdm-view-detailed', function() {
        var trackingId = $(this).data('tracking-id');
        // Implementation for detailed view
        $('#pdm-detailed-tracking-content').html('<p>Loading detailed tracking information...</p>');
        $('#pdm-detailed-tracking-modal').show();
    });
    
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
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatDateTime(dateString) {
        if (!dateString) return 'N/A';
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' at ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    }
});
</script>