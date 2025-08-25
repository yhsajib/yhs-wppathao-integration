<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get orders by phone number
 */
function pdm_get_orders_by_phone($phone_number) {
    global $wpdb;
    
    $orders = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE phone_number = %s ORDER BY created_at DESC",
        $phone_number
    ));
    
    return $orders;
}

/**
 * Get order tracking history
 */
function pdm_get_tracking_history($order_id) {
    global $wpdb;
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pdm_tracking_history WHERE order_id = %s ORDER BY timestamp DESC",
        $order_id
    ));
    
    return $history;
}

/**
 * Format phone number
 */
function pdm_format_phone_number($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Add country code if not present
    if (strlen($phone) == 11 && substr($phone, 0, 1) == '0') {
        $phone = '88' . $phone;
    } elseif (strlen($phone) == 10) {
        $phone = '880' . $phone;
    } elseif (strlen($phone) == 11 && substr($phone, 0, 2) != '88') {
        $phone = '88' . $phone;
    }
    
    return $phone;
}

/**
 * Validate phone number
 */
function pdm_validate_phone_number($phone) {
    $formatted_phone = pdm_format_phone_number($phone);
    
    // Bangladesh phone number validation
    if (preg_match('/^88(01[3-9]\d{8})$/', $formatted_phone)) {
        return true;
    }
    
    return false;
}

/**
 * Create fake order
 */
function pdm_create_fake_order($order_data) {
    global $wpdb;
    
    $fake_consignment_id = 'FAKE_' . time() . '_' . rand(1000, 9999);
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'pdm_orders',
        array(
            'order_id' => $order_data['order_id'],
            'phone_number' => pdm_format_phone_number($order_data['phone_number']),
            'customer_name' => $order_data['customer_name'],
            'customer_address' => $order_data['customer_address'],
            'pathao_consignment_id' => $fake_consignment_id,
            'delivery_status' => $order_data['status'] ?? 'pending',
            'is_fake' => 1
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%d')
    );
    
    if ($result) {
        // Add initial tracking history
        $wpdb->insert(
            $wpdb->prefix . 'pdm_tracking_history',
            array(
                'order_id' => $order_data['order_id'],
                'status' => 'created',
                'message' => 'Fake order created for testing purposes'
            ),
            array('%s', '%s', '%s')
        );
        
        return array('success' => true, 'consignment_id' => $fake_consignment_id);
    }
    
    return array('success' => false, 'message' => 'Failed to create fake order');
}

/**
 * Update fake order status
 */
function pdm_update_fake_order_status($consignment_id, $status, $message = '') {
    global $wpdb;
    
    // Update order status
    $result = $wpdb->update(
        $wpdb->prefix . 'pdm_orders',
        array('delivery_status' => $status),
        array('pathao_consignment_id' => $consignment_id, 'is_fake' => 1),
        array('%s'),
        array('%s', '%d')
    );
    
    if ($result) {
        // Get order details
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE pathao_consignment_id = %s AND is_fake = 1",
            $consignment_id
        ));
        
        if ($order) {
            // Add tracking history
            $wpdb->insert(
                $wpdb->prefix . 'pdm_tracking_history',
                array(
                    'order_id' => $order->order_id,
                    'status' => $status,
                    'message' => $message ?: 'Status updated to ' . $status
                ),
                array('%s', '%s', '%s')
            );
        }
        
        return array('success' => true);
    }
    
    return array('success' => false, 'message' => 'Failed to update fake order status');
}

/**
 * Get all fake orders
 */
function pdm_get_fake_orders() {
    global $wpdb;
    
    $orders = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE is_fake = 1 ORDER BY created_at DESC"
    );
    
    return $orders;
}

/**
 * Delete fake order
 */
function pdm_delete_fake_order($consignment_id) {
    global $wpdb;
    
    // Get order details first
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE pathao_consignment_id = %s AND is_fake = 1",
        $consignment_id
    ));
    
    if (!$order) {
        return array('success' => false, 'message' => 'Fake order not found');
    }
    
    // Delete tracking history
    $wpdb->delete(
        $wpdb->prefix . 'pdm_tracking_history',
        array('order_id' => $order->order_id),
        array('%s')
    );
    
    // Delete order
    $result = $wpdb->delete(
        $wpdb->prefix . 'pdm_orders',
        array('pathao_consignment_id' => $consignment_id, 'is_fake' => 1),
        array('%s', '%d')
    );
    
    if ($result) {
        return array('success' => true);
    }
    
    return array('success' => false, 'message' => 'Failed to delete fake order');
}

/**
 * Get delivery status options
 */
function pdm_get_delivery_status_options() {
    return array(
        'pending' => 'Pending',
        'pickup_requested' => 'Pickup Requested',
        'picked_up' => 'Picked Up',
        'in_transit' => 'In Transit',
        'out_for_delivery' => 'Out for Delivery',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
        'hold' => 'On Hold'
    );
}

/**
 * Get delivery type options
 */
function pdm_get_delivery_type_options() {
    return array(
        '48' => 'Normal Delivery (48 Hours)',
        '24' => 'Express Delivery (24 Hours)',
        '12' => 'Same Day Delivery (12 Hours)'
    );
}

/**
 * Get item type options
 */
function pdm_get_item_type_options() {
    return array(
        '1' => 'Document',
        '2' => 'Parcel',
        '3' => 'Liquid',
        '4' => 'Fragile'
    );
}

/**
 * Sanitize and validate bulk delivery data
 */
function pdm_validate_bulk_delivery_data($data) {
    $errors = array();
    $validated_data = array();
    
    foreach ($data as $index => $order) {
        $row_errors = array();
        
        // Validate required fields
        if (empty($order['order_id'])) {
            $row_errors[] = 'Order ID is required';
        }
        
        if (empty($order['customer_name'])) {
            $row_errors[] = 'Customer name is required';
        }
        
        if (empty($order['phone_number'])) {
            $row_errors[] = 'Phone number is required';
        } elseif (!pdm_validate_phone_number($order['phone_number'])) {
            $row_errors[] = 'Invalid phone number format';
        }
        
        if (empty($order['customer_address'])) {
            $row_errors[] = 'Customer address is required';
        }
        
        if (!empty($row_errors)) {
            $errors[$index] = $row_errors;
        } else {
            $validated_data[] = array(
                'order_id' => sanitize_text_field($order['order_id']),
                'customer_name' => sanitize_text_field($order['customer_name']),
                'phone_number' => pdm_format_phone_number($order['phone_number']),
                'customer_address' => sanitize_textarea_field($order['customer_address']),
                'city_id' => intval($order['city_id'] ?? 1),
                'zone_id' => intval($order['zone_id'] ?? 1),
                'area_id' => intval($order['area_id'] ?? 1),
                'delivery_type' => intval($order['delivery_type'] ?? 48),
                'item_type' => intval($order['item_type'] ?? 2),
                'item_quantity' => intval($order['item_quantity'] ?? 1),
                'item_weight' => floatval($order['item_weight'] ?? 0.5),
                'amount_to_collect' => floatval($order['amount_to_collect'] ?? 0),
                'item_description' => sanitize_text_field($order['item_description'] ?? 'General Item'),
                'special_instruction' => sanitize_textarea_field($order['special_instruction'] ?? '')
            );
        }
    }
    
    return array(
        'errors' => $errors,
        'validated_data' => $validated_data,
        'has_errors' => !empty($errors)
    );
}

/**
 * Generate tracking shortcode
 */
function pdm_tracking_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type' => 'phone', // phone or order
        'placeholder' => 'Enter phone number or order ID'
    ), $atts);
    
    ob_start();
    ?>
    <div class="pdm-tracking-widget">
        <form id="pdm-tracking-form" class="pdm-form">
            <div class="pdm-form-group">
                <input type="text" id="pdm-tracking-input" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required>
                <button type="submit" class="pdm-btn pdm-btn-primary">Track</button>
            </div>
        </form>
        <div id="pdm-tracking-results" class="pdm-results"></div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('pathao_tracking', 'pdm_tracking_shortcode');

/**
 * Log plugin activities
 */
function pdm_log($message, $type = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[Pathao Delivery Manager] [' . strtoupper($type) . '] ' . $message);
    }
}

?>