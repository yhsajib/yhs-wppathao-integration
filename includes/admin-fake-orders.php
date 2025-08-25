<?php

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['pdm_create_fake_order']) && wp_verify_nonce($_POST['pdm_nonce'], 'pdm_create_fake_order')) {
        $result = pdm_create_fake_order($_POST);
        if ($result) {
            echo '<div class="notice notice-success is-dismissible"><p>Fake order created successfully! Order ID: ' . $result . '</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to create fake order. Please try again.</p></div>';
        }
    }
    
    if (isset($_POST['pdm_bulk_create_fake_orders']) && wp_verify_nonce($_POST['pdm_nonce'], 'pdm_bulk_create_fake_orders')) {
        $count = intval($_POST['bulk_count']);
        $created = 0;
        
        for ($i = 0; $i < $count; $i++) {
            $fake_data = pdm_generate_random_order_data();
            if (pdm_create_fake_order($fake_data)) {
                $created++;
            }
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Created ' . $created . ' out of ' . $count . ' fake orders successfully!</p></div>';
    }
    
    if (isset($_POST['pdm_delete_fake_order']) && wp_verify_nonce($_POST['pdm_nonce'], 'pdm_delete_fake_order')) {
        $order_id = intval($_POST['order_id']);
        if (pdm_delete_fake_order($order_id)) {
            echo '<div class="notice notice-success is-dismissible"><p>Fake order deleted successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Failed to delete fake order.</p></div>';
        }
    }
    
    if (isset($_POST['pdm_clear_all_fake_orders']) && wp_verify_nonce($_POST['pdm_nonce'], 'pdm_clear_all_fake_orders')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'pdm_orders';
        $deleted = $wpdb->delete($table_name, array('is_fake' => 1));
        
        echo '<div class="notice notice-success is-dismissible"><p>Deleted ' . $deleted . ' fake orders successfully!</p></div>';
    }
}

// Get fake orders for display
global $wpdb;
$table_name = $wpdb->prefix . 'pdm_orders';
$fake_orders = $wpdb->get_results("SELECT * FROM $table_name WHERE is_fake = 1 ORDER BY created_at DESC LIMIT 50");
$total_fake_orders = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE is_fake = 1");

?>

<div class="wrap">
    <h1>Fake Order Management</h1>
    <p>Create and manage fake orders for testing and development purposes. These orders won't be sent to Pathao API.</p>
    
    <div class="pdm-fake-orders-container">
        <!-- Create Single Fake Order -->
        <div class="pdm-card">
            <h2>Create Single Fake Order</h2>
            <form method="post" class="pdm-fake-order-form">
                <?php wp_nonce_field('pdm_create_fake_order', 'pdm_nonce'); ?>
                <input type="hidden" name="pdm_create_fake_order" value="1">
                
                <div class="pdm-form-grid">
                    <div class="pdm-form-group">
                        <label for="customer_name">Customer Name *</label>
                        <input type="text" id="customer_name" name="customer_name" class="regular-text" required>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="phone_number">Phone Number *</label>
                        <input type="tel" id="phone_number" name="phone_number" class="regular-text" placeholder="01XXXXXXXXX" required>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="delivery_address">Delivery Address *</label>
                        <textarea id="delivery_address" name="delivery_address" class="large-text" rows="3" required></textarea>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="delivery_status">Delivery Status</label>
                        <select id="delivery_status" name="delivery_status" class="regular-text">
                            <?php foreach (pdm_get_delivery_status_options() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="delivery_type">Delivery Type</label>
                        <select id="delivery_type" name="delivery_type" class="regular-text">
                            <?php foreach (pdm_get_delivery_type_options() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="item_type">Item Type</label>
                        <select id="item_type" name="item_type" class="regular-text">
                            <?php foreach (pdm_get_item_type_options() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="item_quantity">Item Quantity</label>
                        <input type="number" id="item_quantity" name="item_quantity" class="regular-text" value="1" min="1">
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="item_weight">Item Weight (kg)</label>
                        <input type="number" id="item_weight" name="item_weight" class="regular-text" value="0.5" step="0.1" min="0.1">
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="amount_to_collect">Amount to Collect (BDT)</label>
                        <input type="number" id="amount_to_collect" name="amount_to_collect" class="regular-text" value="0" min="0">
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="item_description">Item Description</label>
                        <textarea id="item_description" name="item_description" class="large-text" rows="2" placeholder="Brief description of the item"></textarea>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="special_instruction">Special Instructions</label>
                        <textarea id="special_instruction" name="special_instruction" class="large-text" rows="2" placeholder="Any special delivery instructions"></textarea>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="consignment_id">Fake Consignment ID</label>
                        <input type="text" id="consignment_id" name="consignment_id" class="regular-text" placeholder="Leave empty to auto-generate">
                        <p class="description">Optional: Provide a custom consignment ID for testing</p>
                    </div>
                </div>
                
                <div class="pdm-form-actions">
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        Create Fake Order
                    </button>
                    <button type="button" id="pdm-randomize-data" class="button button-secondary">
                        <span class="dashicons dashicons-randomize"></span>
                        Fill Random Data
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Bulk Create Fake Orders -->
        <div class="pdm-card">
            <h2>Bulk Create Fake Orders</h2>
            <p>Generate multiple fake orders with random data for testing purposes.</p>
            
            <form method="post" class="pdm-bulk-fake-form">
                <?php wp_nonce_field('pdm_bulk_create_fake_orders', 'pdm_nonce'); ?>
                <input type="hidden" name="pdm_bulk_create_fake_orders" value="1">
                
                <div class="pdm-form-row">
                    <div class="pdm-form-group">
                        <label for="bulk_count">Number of Orders to Create:</label>
                        <input type="number" id="bulk_count" name="bulk_count" class="regular-text" value="10" min="1" max="100" required>
                        <p class="description">Maximum 100 orders at once</p>
                    </div>
                    <div class="pdm-form-group">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-admin-page"></span>
                            Create Bulk Orders
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Fake Orders List -->
        <div class="pdm-card">
            <div class="pdm-card-header">
                <h2>Fake Orders (<?php echo $total_fake_orders; ?> total)</h2>
                <div class="pdm-card-actions">
                    <?php if ($total_fake_orders > 0): ?>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete ALL fake orders? This action cannot be undone.');">
                            <?php wp_nonce_field('pdm_clear_all_fake_orders', 'pdm_nonce'); ?>
                            <input type="hidden" name="pdm_clear_all_fake_orders" value="1">
                            <button type="submit" class="button button-secondary">
                                <span class="dashicons dashicons-trash"></span>
                                Clear All Fake Orders
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($fake_orders)): ?>
                <div class="pdm-no-orders">
                    <div class="dashicons dashicons-clipboard"></div>
                    <h3>No Fake Orders Found</h3>
                    <p>Create some fake orders using the forms above to test the plugin functionality.</p>
                </div>
            <?php else: ?>
                <div class="pdm-orders-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Consignment ID</th>
                                <th>Amount</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fake_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?php echo esc_html($order->id); ?></strong></td>
                                    <td><?php echo esc_html($order->customer_name); ?></td>
                                    <td><?php echo esc_html($order->phone_number); ?></td>
                                    <td>
                                        <span class="pdm-status-badge pdm-status-<?php echo esc_attr($order->delivery_status); ?>">
                                            <?php echo esc_html(ucwords(str_replace('_', ' ', $order->delivery_status))); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($order->consignment_id ?: 'N/A'); ?></td>
                                    <td>৳<?php echo esc_html(number_format($order->amount_to_collect, 2)); ?></td>
                                    <td><?php echo esc_html(date('M j, Y g:i A', strtotime($order->created_at))); ?></td>
                                    <td>
                                        <button class="button button-small pdm-view-order" data-order-id="<?php echo esc_attr($order->id); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                            View
                                        </button>
                                        <button class="button button-small pdm-edit-order" data-order-id="<?php echo esc_attr($order->id); ?>">
                                            <span class="dashicons dashicons-edit"></span>
                                            Edit
                                        </button>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this fake order?');">
                                            <?php wp_nonce_field('pdm_delete_fake_order', 'pdm_nonce'); ?>
                                            <input type="hidden" name="pdm_delete_fake_order" value="1">
                                            <input type="hidden" name="order_id" value="<?php echo esc_attr($order->id); ?>">
                                            <button type="submit" class="button button-small button-link-delete">
                                                <span class="dashicons dashicons-trash"></span>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_fake_orders > 50): ?>
                    <div class="pdm-pagination-info">
                        <p>Showing latest 50 orders out of <?php echo $total_fake_orders; ?> total fake orders.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Quick Actions -->
        <div class="pdm-card">
            <h2>Quick Actions</h2>
            <div class="pdm-quick-actions">
                <button class="button button-secondary pdm-create-sample-orders">
                    <span class="dashicons dashicons-admin-page"></span>
                    Create Sample Orders (5)
                </button>
                <button class="button button-secondary pdm-create-different-statuses">
                    <span class="dashicons dashicons-randomize"></span>
                    Create Orders with Different Statuses
                </button>
                <button class="button button-secondary pdm-export-fake-orders">
                    <span class="dashicons dashicons-download"></span>
                    Export Fake Orders (CSV)
                </button>
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
            <div id="pdm-order-details-content"></div>
        </div>
    </div>
</div>

<!-- Edit Order Modal -->
<div id="pdm-edit-order-modal" class="pdm-modal" style="display: none;">
    <div class="pdm-modal-content">
        <div class="pdm-modal-header">
            <h3>Edit Fake Order</h3>
            <span class="pdm-modal-close">&times;</span>
        </div>
        <div class="pdm-modal-body">
            <form id="pdm-edit-order-form">
                <div id="pdm-edit-order-content"></div>
                <div class="pdm-modal-actions">
                    <button type="submit" class="button button-primary">Update Order</button>
                    <button type="button" class="button button-secondary pdm-modal-close">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.pdm-fake-orders-container {
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

.pdm-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.pdm-card-header h2 {
    margin: 0;
    border: none;
    padding: 0;
}

.pdm-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.pdm-form-group {
    margin-bottom: 15px;
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
    font-size: 13px;
}

.pdm-form-row {
    display: flex;
    align-items: end;
    gap: 20px;
    flex-wrap: wrap;
}

.pdm-form-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.pdm-orders-table-container {
    overflow-x: auto;
}

.pdm-status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
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

.pdm-no-orders {
    text-align: center;
    padding: 60px 20px;
    color: #666;
}

.pdm-no-orders .dashicons {
    font-size: 64px;
    margin-bottom: 20px;
    color: #ccc;
}

.pdm-no-orders h3 {
    margin-bottom: 10px;
    color: #333;
}

.pdm-pagination-info {
    text-align: center;
    padding: 15px;
    background: #f7f7f7;
    border-top: 1px solid #ddd;
    margin-top: 15px;
}

.pdm-quick-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
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
    max-width: 800px;
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

.pdm-modal-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding-top: 15px;
    border-top: 1px solid #eee;
    margin-top: 20px;
}

.pdm-order-detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.pdm-detail-item {
    background: #f9f9f9;
    padding: 15px;
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
    word-break: break-word;
}

@media (max-width: 768px) {
    .pdm-form-grid {
        grid-template-columns: 1fr;
    }
    
    .pdm-form-row {
        flex-direction: column;
    }
    
    .pdm-form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .pdm-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .pdm-quick-actions {
        flex-direction: column;
    }
    
    .pdm-order-detail-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Sample data for randomization
    var sampleNames = ['John Doe', 'Jane Smith', 'Ahmed Rahman', 'Fatima Khan', 'Mohammad Ali', 'Rashida Begum', 'Karim Hassan', 'Nasreen Akter'];
    var samplePhones = ['01712345678', '01812345679', '01912345680', '01612345681', '01512345682', '01312345683'];
    var sampleAddresses = [
        'House 123, Road 5, Dhanmondi, Dhaka',
        'Flat 4B, Building 7, Gulshan 2, Dhaka',
        'Village: Rampur, Post: Savar, Dhaka',
        'House 45, Sector 3, Uttara, Dhaka',
        'Apartment 2C, Green Road, Dhaka',
        'House 78, Banani, Dhaka'
    ];
    var sampleItems = ['Mobile Phone', 'Laptop', 'Books', 'Clothes', 'Medicine', 'Food Items', 'Electronics', 'Documents'];
    
    // Randomize form data
    $('#pdm-randomize-data').on('click', function() {
        $('#customer_name').val(sampleNames[Math.floor(Math.random() * sampleNames.length)]);
        $('#phone_number').val(samplePhones[Math.floor(Math.random() * samplePhones.length)]);
        $('#delivery_address').val(sampleAddresses[Math.floor(Math.random() * sampleAddresses.length)]);
        $('#item_quantity').val(Math.floor(Math.random() * 5) + 1);
        $('#item_weight').val((Math.random() * 5 + 0.5).toFixed(1));
        $('#amount_to_collect').val(Math.floor(Math.random() * 5000) + 100);
        $('#item_description').val(sampleItems[Math.floor(Math.random() * sampleItems.length)]);
        $('#consignment_id').val('FAKE' + Date.now());
    });
    
    // View order details
    $('.pdm-view-order').on('click', function() {
        var orderId = $(this).data('order-id');
        
        $.post(ajaxurl, {
            action: 'pdm_get_order_details',
            order_id: orderId,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayOrderDetails(response.data);
                $('#pdm-order-modal').show();
            } else {
                alert('Error loading order details: ' + response.data);
            }
        });
    });
    
    // Edit order
    $('.pdm-edit-order').on('click', function() {
        var orderId = $(this).data('order-id');
        
        $.post(ajaxurl, {
            action: 'pdm_get_order_details',
            order_id: orderId,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayEditOrderForm(response.data);
                $('#pdm-edit-order-modal').show();
            } else {
                alert('Error loading order details: ' + response.data);
            }
        });
    });
    
    // Quick actions
    $('.pdm-create-sample-orders').on('click', function() {
        if (confirm('Create 5 sample orders with different statuses?')) {
            createSampleOrders();
        }
    });
    
    $('.pdm-create-different-statuses').on('click', function() {
        if (confirm('Create orders with all different delivery statuses?')) {
            createOrdersWithDifferentStatuses();
        }
    });
    
    $('.pdm-export-fake-orders').on('click', function() {
        exportFakeOrders();
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
    
    // Edit order form submission
    $('#pdm-edit-order-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        formData += '&action=pdm_update_fake_order&nonce=<?php echo wp_create_nonce('pdm_nonce'); ?>';
        
        $.post(ajaxurl, formData, function(response) {
            if (response.success) {
                alert('Order updated successfully!');
                location.reload();
            } else {
                alert('Error updating order: ' + response.data);
            }
        });
    });
    
    function displayOrderDetails(order) {
        var html = '<div class="pdm-order-detail-grid">';
        
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Order ID</div><div class="pdm-detail-value">#' + order.id + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Customer Name</div><div class="pdm-detail-value">' + escapeHtml(order.customer_name) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Phone Number</div><div class="pdm-detail-value">' + escapeHtml(order.phone_number) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Delivery Status</div><div class="pdm-detail-value"><span class="pdm-status-badge pdm-status-' + order.delivery_status + '">' + escapeHtml(order.delivery_status.replace(/_/g, ' ').toUpperCase()) + '</span></div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Consignment ID</div><div class="pdm-detail-value">' + escapeHtml(order.consignment_id || 'N/A') + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Amount to Collect</div><div class="pdm-detail-value">৳' + parseFloat(order.amount_to_collect).toFixed(2) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Delivery Type</div><div class="pdm-detail-value">' + escapeHtml(order.delivery_type) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Item Type</div><div class="pdm-detail-value">' + escapeHtml(order.item_type) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Item Quantity</div><div class="pdm-detail-value">' + order.item_quantity + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Item Weight</div><div class="pdm-detail-value">' + order.item_weight + ' kg</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Created At</div><div class="pdm-detail-value">' + formatDateTime(order.created_at) + '</div></div>';
        html += '<div class="pdm-detail-item"><div class="pdm-detail-label">Updated At</div><div class="pdm-detail-value">' + formatDateTime(order.updated_at) + '</div></div>';
        
        html += '</div>';
        
        if (order.delivery_address) {
            html += '<div class="pdm-detail-item" style="grid-column: 1 / -1;"><div class="pdm-detail-label">Delivery Address</div><div class="pdm-detail-value">' + escapeHtml(order.delivery_address) + '</div></div>';
        }
        
        if (order.item_description) {
            html += '<div class="pdm-detail-item" style="grid-column: 1 / -1;"><div class="pdm-detail-label">Item Description</div><div class="pdm-detail-value">' + escapeHtml(order.item_description) + '</div></div>';
        }
        
        if (order.special_instruction) {
            html += '<div class="pdm-detail-item" style="grid-column: 1 / -1;"><div class="pdm-detail-label">Special Instructions</div><div class="pdm-detail-value">' + escapeHtml(order.special_instruction) + '</div></div>';
        }
        
        $('#pdm-order-details-content').html(html);
    }
    
    function displayEditOrderForm(order) {
        var html = '<div class="pdm-form-grid">';
        
        html += '<input type="hidden" name="order_id" value="' + order.id + '">';
        
        html += '<div class="pdm-form-group">';
        html += '<label for="edit_customer_name">Customer Name</label>';
        html += '<input type="text" id="edit_customer_name" name="customer_name" class="regular-text" value="' + escapeHtml(order.customer_name) + '" required>';
        html += '</div>';
        
        html += '<div class="pdm-form-group">';
        html += '<label for="edit_phone_number">Phone Number</label>';
        html += '<input type="tel" id="edit_phone_number" name="phone_number" class="regular-text" value="' + escapeHtml(order.phone_number) + '" required>';
        html += '</div>';
        
        html += '<div class="pdm-form-group">';
        html += '<label for="edit_delivery_status">Delivery Status</label>';
        html += '<select id="edit_delivery_status" name="delivery_status" class="regular-text">';
        <?php foreach (pdm_get_delivery_status_options() as $key => $label): ?>
            html += '<option value="<?php echo esc_attr($key); ?>"' + (order.delivery_status === '<?php echo esc_attr($key); ?>' ? ' selected' : '') + '><?php echo esc_html($label); ?></option>';
        <?php endforeach; ?>
        html += '</select>';
        html += '</div>';
        
        html += '<div class="pdm-form-group">';
        html += '<label for="edit_amount_to_collect">Amount to Collect</label>';
        html += '<input type="number" id="edit_amount_to_collect" name="amount_to_collect" class="regular-text" value="' + order.amount_to_collect + '" min="0">';
        html += '</div>';
        
        html += '</div>';
        
        html += '<div class="pdm-form-group">';
        html += '<label for="edit_delivery_address">Delivery Address</label>';
        html += '<textarea id="edit_delivery_address" name="delivery_address" class="large-text" rows="3" required>' + escapeHtml(order.delivery_address) + '</textarea>';
        html += '</div>';
        
        $('#pdm-edit-order-content').html(html);
    }
    
    function createSampleOrders() {
        var sampleOrders = [
            { status: 'pending', name: 'Sample Customer 1' },
            { status: 'picked_up', name: 'Sample Customer 2' },
            { status: 'in_transit', name: 'Sample Customer 3' },
            { status: 'delivered', name: 'Sample Customer 4' },
            { status: 'cancelled', name: 'Sample Customer 5' }
        ];
        
        var created = 0;
        
        function createNext(index) {
            if (index >= sampleOrders.length) {
                alert('Created ' + created + ' sample orders successfully!');
                location.reload();
                return;
            }
            
            var order = sampleOrders[index];
            var data = {
                action: 'pdm_create_sample_order',
                delivery_status: order.status,
                customer_name: order.name,
                nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    created++;
                }
                createNext(index + 1);
            }).fail(function() {
                createNext(index + 1);
            });
        }
        
        createNext(0);
    }
    
    function createOrdersWithDifferentStatuses() {
        var statuses = ['pending', 'pickup_requested', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled', 'returned', 'hold'];
        var created = 0;
        
        function createNext(index) {
            if (index >= statuses.length) {
                alert('Created ' + created + ' orders with different statuses!');
                location.reload();
                return;
            }
            
            var data = {
                action: 'pdm_create_sample_order',
                delivery_status: statuses[index],
                customer_name: 'Test Customer ' + (index + 1),
                nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
            };
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    created++;
                }
                createNext(index + 1);
            }).fail(function() {
                createNext(index + 1);
            });
        }
        
        createNext(0);
    }
    
    function exportFakeOrders() {
        window.location.href = ajaxurl + '?action=pdm_export_fake_orders&nonce=<?php echo wp_create_nonce('pdm_nonce'); ?>';
    }
    
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

<?php

// Helper function to generate random order data
function pdm_generate_random_order_data() {
    $names = ['John Doe', 'Jane Smith', 'Ahmed Rahman', 'Fatima Khan', 'Mohammad Ali', 'Rashida Begum', 'Karim Hassan', 'Nasreen Akter'];
    $phones = ['01712345678', '01812345679', '01912345680', '01612345681', '01512345682', '01312345683'];
    $addresses = [
        'House 123, Road 5, Dhanmondi, Dhaka',
        'Flat 4B, Building 7, Gulshan 2, Dhaka',
        'Village: Rampur, Post: Savar, Dhaka',
        'House 45, Sector 3, Uttara, Dhaka',
        'Apartment 2C, Green Road, Dhaka',
        'House 78, Banani, Dhaka'
    ];
    $items = ['Mobile Phone', 'Laptop', 'Books', 'Clothes', 'Medicine', 'Food Items', 'Electronics', 'Documents'];
    $statuses = array_keys(pdm_get_delivery_status_options());
    $types = array_keys(pdm_get_delivery_type_options());
    $item_types = array_keys(pdm_get_item_type_options());
    
    return [
        'customer_name' => $names[array_rand($names)],
        'phone_number' => $phones[array_rand($phones)],
        'delivery_address' => $addresses[array_rand($addresses)],
        'delivery_status' => $statuses[array_rand($statuses)],
        'delivery_type' => $types[array_rand($types)],
        'item_type' => $item_types[array_rand($item_types)],
        'item_quantity' => rand(1, 5),
        'item_weight' => round(rand(5, 50) / 10, 1),
        'amount_to_collect' => rand(100, 5000),
        'item_description' => $items[array_rand($items)],
        'special_instruction' => rand(0, 1) ? 'Handle with care' : '',
        'consignment_id' => 'FAKE' . time() . rand(100, 999)
    ];
}

?>