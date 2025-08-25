<?php

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;

$total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pdm_orders");
$real_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pdm_orders WHERE is_fake = 0");
$fake_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pdm_orders WHERE is_fake = 1");
$pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pdm_orders WHERE delivery_status = 'pending'");
$delivered_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pdm_orders WHERE delivery_status = 'delivered'");

// Get recent orders
$recent_orders = $wpdb->get_results(
    "SELECT * FROM {$wpdb->prefix}pdm_orders ORDER BY created_at DESC LIMIT 10"
);

// Get delivery status distribution
$status_distribution = $wpdb->get_results(
    "SELECT delivery_status, COUNT(*) as count FROM {$wpdb->prefix}pdm_orders GROUP BY delivery_status"
);

?>

<div class="wrap">
    <h1>Pathao Delivery Manager Dashboard</h1>
    
    <div class="pdm-dashboard-container">
        <!-- Statistics Cards -->
        <div class="pdm-stats-grid">
            <div class="pdm-stat-card">
                <div class="pdm-stat-icon">
                    <span class="dashicons dashicons-cart"></span>
                </div>
                <div class="pdm-stat-content">
                    <h3><?php echo number_format($total_orders); ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>
            
            <div class="pdm-stat-card">
                <div class="pdm-stat-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="pdm-stat-content">
                    <h3><?php echo number_format($real_orders); ?></h3>
                    <p>Real Orders</p>
                </div>
            </div>
            
            <div class="pdm-stat-card">
                <div class="pdm-stat-icon">
                    <span class="dashicons dashicons-admin-tools"></span>
                </div>
                <div class="pdm-stat-content">
                    <h3><?php echo number_format($fake_orders); ?></h3>
                    <p>Test Orders</p>
                </div>
            </div>
            
            <div class="pdm-stat-card">
                <div class="pdm-stat-icon">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="pdm-stat-content">
                    <h3><?php echo number_format($pending_orders); ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
            
            <div class="pdm-stat-card">
                <div class="pdm-stat-icon">
                    <span class="dashicons dashicons-truck"></span>
                </div>
                <div class="pdm-stat-content">
                    <h3><?php echo number_format($delivered_orders); ?></h3>
                    <p>Delivered Orders</p>
                </div>
            </div>
        </div>
        
        <div class="pdm-dashboard-content">
            <div class="pdm-dashboard-main">
                <!-- Quick Actions -->
                <div class="pdm-card">
                    <h2>Quick Actions</h2>
                    <div class="pdm-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=pathao-phone-tracking'); ?>" class="pdm-action-btn">
                            <span class="dashicons dashicons-search"></span>
                            Phone Tracking
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=pathao-order-tracking'); ?>" class="pdm-action-btn">
                            <span class="dashicons dashicons-visibility"></span>
                            Order Tracking
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=pathao-bulk-delivery'); ?>" class="pdm-action-btn">
                            <span class="dashicons dashicons-upload"></span>
                            Bulk Delivery
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=pathao-fake-orders'); ?>" class="pdm-action-btn">
                            <span class="dashicons dashicons-admin-tools"></span>
                            Manage Test Orders
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=pathao-settings'); ?>" class="pdm-action-btn">
                            <span class="dashicons dashicons-admin-settings"></span>
                            Settings
                        </a>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="pdm-card">
                    <h2>Recent Orders</h2>
                    <?php if (!empty($recent_orders)): ?>
                        <div class="pdm-table-container">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Type</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($order->order_id); ?></strong></td>
                                            <td><?php echo esc_html($order->customer_name); ?></td>
                                            <td><?php echo esc_html($order->phone_number); ?></td>
                                            <td>
                                                <span class="pdm-status pdm-status-<?php echo esc_attr($order->delivery_status); ?>">
                                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $order->delivery_status))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($order->is_fake): ?>
                                                    <span class="pdm-badge pdm-badge-test">Test</span>
                                                <?php else: ?>
                                                    <span class="pdm-badge pdm-badge-real">Real</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($order->created_at)); ?></td>
                                            <td>
                                                <button class="button button-small pdm-track-btn" data-order-id="<?php echo esc_attr($order->order_id); ?>">
                                                    Track
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p>No orders found. <a href="<?php echo admin_url('admin.php?page=pathao-bulk-delivery'); ?>">Create your first delivery</a>.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pdm-dashboard-sidebar">
                <!-- Status Distribution -->
                <div class="pdm-card">
                    <h3>Order Status Distribution</h3>
                    <?php if (!empty($status_distribution)): ?>
                        <div class="pdm-status-chart">
                            <?php foreach ($status_distribution as $status): ?>
                                <div class="pdm-status-item">
                                    <span class="pdm-status-label"><?php echo esc_html(ucfirst(str_replace('_', ' ', $status->delivery_status))); ?></span>
                                    <span class="pdm-status-count"><?php echo number_format($status->count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No data available.</p>
                    <?php endif; ?>
                </div>
                
                <!-- API Status -->
                <div class="pdm-card">
                    <h3>API Status</h3>
                    <div id="pdm-api-status">
                        <button id="pdm-check-api" class="button button-secondary">Check API Status</button>
                        <div id="pdm-api-result"></div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="pdm-card">
                    <h3>System Information</h3>
                    <div class="pdm-system-info">
                        <div class="pdm-info-item">
                            <strong>Plugin Version:</strong> <?php echo PDM_VERSION; ?>
                        </div>
                        <div class="pdm-info-item">
                            <strong>WordPress Version:</strong> <?php echo get_bloginfo('version'); ?>
                        </div>
                        <div class="pdm-info-item">
                            <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?>
                        </div>
                        <div class="pdm-info-item">
                            <strong>Environment:</strong> 
                            <?php echo get_option('pdm_pathao_sandbox', true) ? 'Sandbox' : 'Production'; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Shortcode Info -->
                <div class="pdm-card">
                    <h3>Tracking Shortcode</h3>
                    <p>Use this shortcode to add tracking functionality to any page or post:</p>
                    <code>[pathao_tracking]</code>
                    <p><small>This will display a tracking form for customers to track their orders.</small></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="pdm-order-modal" class="pdm-modal" style="display: none;">
    <div class="pdm-modal-content">
        <div class="pdm-modal-header">
            <h3>Order Details</h3>
            <span class="pdm-modal-close">&times;</span>
        </div>
        <div class="pdm-modal-body">
            <div id="pdm-order-details"></div>
        </div>
    </div>
</div>

<style>
.pdm-dashboard-container {
    margin-top: 20px;
}

.pdm-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.pdm-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.pdm-stat-icon {
    margin-right: 15px;
}

.pdm-stat-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #0073aa;
}

.pdm-stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #23282d;
}

.pdm-stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.pdm-dashboard-content {
    display: flex;
    gap: 20px;
}

.pdm-dashboard-main {
    flex: 2;
}

.pdm-dashboard-sidebar {
    flex: 1;
}

.pdm-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.pdm-card h2, .pdm-card h3 {
    margin-top: 0;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.pdm-quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.pdm-action-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 15px;
    background: #f7f7f7;
    border: 1px solid #ddd;
    border-radius: 4px;
    text-decoration: none;
    color: #333;
    transition: all 0.3s ease;
}

.pdm-action-btn:hover {
    background: #0073aa;
    color: #fff;
    text-decoration: none;
}

.pdm-action-btn .dashicons {
    margin-right: 8px;
}

.pdm-table-container {
    overflow-x: auto;
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

.pdm-status-chart {
    space-y: 10px;
}

.pdm-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

.pdm-status-item:last-child {
    border-bottom: none;
}

.pdm-status-count {
    font-weight: bold;
    color: #0073aa;
}

.pdm-system-info .pdm-info-item {
    margin-bottom: 8px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.pdm-system-info .pdm-info-item:last-child {
    border-bottom: none;
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
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
}

.pdm-modal-header {
    padding: 15px 20px;
    background: #f7f7f7;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.pdm-modal-header h3 {
    margin: 0;
}

.pdm-modal-close {
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.pdm-modal-close:hover {
    color: #0073aa;
}

.pdm-modal-body {
    padding: 20px;
}

@media (max-width: 768px) {
    .pdm-dashboard-content {
        flex-direction: column;
    }
    
    .pdm-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .pdm-quick-actions {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Track order button
    $('.pdm-track-btn').on('click', function() {
        var orderId = $(this).data('order-id');
        // Implementation for tracking modal
        alert('Tracking for order: ' + orderId);
    });
    
    // Check API status
    $('#pdm-check-api').on('click', function() {
        var $btn = $(this);
        var $result = $('#pdm-api-result');
        
        $btn.prop('disabled', true).text('Checking...');
        
        $.post(ajaxurl, {
            action: 'pdm_check_api_status',
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $result.html('<div class="notice notice-success inline"><p>API is working correctly!</p></div>');
            } else {
                $result.html('<div class="notice notice-error inline"><p>API connection failed: ' + response.data + '</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).text('Check API Status');
        });
    });
    
    // Modal functionality
    $('.pdm-modal-close').on('click', function() {
        $('.pdm-modal').hide();
    });
    
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('pdm-modal')) {
            $('.pdm-modal').hide();
        }
    });
});
</script>