<?php
/**
 * WooCommerce Integration Class
 * 
 * Handles WooCommerce order integration for Pathao delivery
 * 
 * @package PathaoDeliveryManager
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PDM_WooCommerce_Integration {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize WooCommerce integration
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Add hooks for order edit page
        add_action('add_meta_boxes', array($this, 'add_order_meta_boxes'));
        add_action('wp_ajax_pdm_send_to_delivery', array($this, 'ajax_send_to_delivery'));
        add_action('wp_ajax_pdm_detect_fake_order', array($this, 'ajax_detect_fake_order'));
        add_action('wp_ajax_pdm_validate_order_tracking', array($this, 'ajax_validate_order_tracking'));
        
        // Add order status for delivery
        add_action('init', array($this, 'register_delivery_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_delivery_order_status'));
        
        // Add delivery info to order details
        add_action('woocommerce_admin_order_data_after_shipping_address', array($this, 'display_delivery_info'));
        
        // Enqueue scripts for order edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_order_scripts'));
    }
    
    /**
     * Add meta boxes to order edit page
     */
    public function add_order_meta_boxes() {
        add_meta_box(
            'pdm_delivery_actions',
            __('Pathao Delivery Manager', 'pathao-delivery-manager'),
            array($this, 'render_delivery_meta_box'),
            'shop_order',
            'side',
            'high'
        );
    }
    
    /**
     * Render delivery meta box
     */
    public function render_delivery_meta_box($post) {
        $order = wc_get_order($post->ID);
        if (!$order) {
            return;
        }
        
        $delivery_status = get_post_meta($post->ID, '_pdm_delivery_status', true);
        $consignment_id = get_post_meta($post->ID, '_pdm_consignment_id', true);
        $tracking_code = get_post_meta($post->ID, '_pdm_tracking_code', true);
        $fake_detection_result = get_post_meta($post->ID, '_pdm_fake_detection', true);
        
        wp_nonce_field('pdm_delivery_actions', 'pdm_delivery_nonce');
        ?>
        <div class="pdm-delivery-meta-box">
            <style>
                .pdm-delivery-meta-box { padding: 10px 0; }
                .pdm-delivery-status { margin-bottom: 15px; }
                .pdm-delivery-info { background: #f9f9f9; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
                .pdm-delivery-actions { margin-bottom: 10px; }
                .pdm-delivery-btn { width: 100%; margin-bottom: 8px; }
                .pdm-fake-detection { padding: 10px; border-radius: 5px; margin-bottom: 15px; }
                .pdm-fake-detection.suspicious { background: #fff3cd; border-left: 4px solid #ffc107; }
                .pdm-fake-detection.verified { background: #d1f2eb; border-left: 4px solid #28a745; }
                .pdm-fake-detection.fake { background: #f8d7da; border-left: 4px solid #dc3545; }
                .pdm-loading { display: none; text-align: center; padding: 10px; }
            </style>
            
            <?php if ($fake_detection_result): ?>
                <div class="pdm-fake-detection <?php echo esc_attr($fake_detection_result['status']); ?>">
                    <strong><?php _e('Order Verification:', 'pathao-delivery-manager'); ?></strong><br>
                    <span><?php echo esc_html($fake_detection_result['message']); ?></span>
                    <?php if (isset($fake_detection_result['confidence'])): ?>
                        <br><small><?php printf(__('Confidence: %s%%', 'pathao-delivery-manager'), $fake_detection_result['confidence']); ?></small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($delivery_status): ?>
                <div class="pdm-delivery-info">
                    <strong><?php _e('Delivery Status:', 'pathao-delivery-manager'); ?></strong> <?php echo esc_html($delivery_status); ?><br>
                    <?php if ($consignment_id): ?>
                        <strong><?php _e('Consignment ID:', 'pathao-delivery-manager'); ?></strong> <?php echo esc_html($consignment_id); ?><br>
                    <?php endif; ?>
                    <?php if ($tracking_code): ?>
                        <strong><?php _e('Tracking Code:', 'pathao-delivery-manager'); ?></strong> <?php echo esc_html($tracking_code); ?><br>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="pdm-delivery-actions">
                <button type="button" class="button button-primary pdm-delivery-btn" id="pdm-detect-fake-order" data-order-id="<?php echo $post->ID; ?>">
                    <?php _e('🔍 Detect Fake Order', 'pathao-delivery-manager'); ?>
                </button>
                
                <button type="button" class="button button-secondary pdm-delivery-btn" id="pdm-send-to-delivery" data-order-id="<?php echo $post->ID; ?>" <?php echo $delivery_status ? 'disabled' : ''; ?>>
                    <?php _e('📦 Send to Delivery', 'pathao-delivery-manager'); ?>
                </button>
                
                <?php if ($consignment_id): ?>
                    <button type="button" class="button pdm-delivery-btn" id="pdm-track-delivery" data-consignment-id="<?php echo esc_attr($consignment_id); ?>">
                        <?php _e('📍 Track Delivery', 'pathao-delivery-manager'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="pdm-loading">
                <span class="spinner is-active"></span>
                <span id="pdm-loading-text"><?php _e('Processing...', 'pathao-delivery-manager'); ?></span>
            </div>
            
            <div id="pdm-delivery-result"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Detect fake order
            $('#pdm-detect-fake-order').on('click', function() {
                var orderId = $(this).data('order-id');
                var $button = $(this);
                var $loading = $('.pdm-loading');
                var $result = $('#pdm-delivery-result');
                
                $button.prop('disabled', true);
                $loading.show();
                $('#pdm-loading-text').text('<?php _e('Analyzing order...', 'pathao-delivery-manager'); ?>');
                $result.empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pdm_detect_fake_order',
                        order_id: orderId,
                        nonce: $('#pdm_delivery_nonce').val()
                    },
                    success: function(response) {
                        $loading.hide();
                        $button.prop('disabled', false);
                        
                        if (response.success) {
                            location.reload(); // Reload to show updated detection result
                        } else {
                            $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $loading.hide();
                        $button.prop('disabled', false);
                        $result.html('<div class="notice notice-error"><p><?php _e('Error occurred while detecting fake order.', 'pathao-delivery-manager'); ?></p></div>');
                    }
                });
            });
            
            // Send to delivery
            $('#pdm-send-to-delivery').on('click', function() {
                var orderId = $(this).data('order-id');
                var $button = $(this);
                var $loading = $('.pdm-loading');
                var $result = $('#pdm-delivery-result');
                
                if (!confirm('<?php _e('Are you sure you want to send this order to delivery?', 'pathao-delivery-manager'); ?>')) {
                    return;
                }
                
                $button.prop('disabled', true);
                $loading.show();
                $('#pdm-loading-text').text('<?php _e('Creating delivery...', 'pathao-delivery-manager'); ?>');
                $result.empty();
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'pdm_send_to_delivery',
                        order_id: orderId,
                        nonce: $('#pdm_delivery_nonce').val()
                    },
                    success: function(response) {
                        $loading.hide();
                        
                        if (response.success) {
                            $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                            location.reload(); // Reload to show updated delivery info
                        } else {
                            $button.prop('disabled', false);
                            $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                        }
                    },
                    error: function() {
                        $loading.hide();
                        $button.prop('disabled', false);
                        $result.html('<div class="notice notice-error"><p><?php _e('Error occurred while sending to delivery.', 'pathao-delivery-manager'); ?></p></div>');
                    }
                });
            });
            
            // Track delivery
            $('#pdm-track-delivery').on('click', function() {
                var consignmentId = $(this).data('consignment-id');
                window.open('<?php echo admin_url('admin.php?page=pathao-delivery-manager-tracking'); ?>&consignment_id=' + consignmentId, '_blank');
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX handler for detecting fake orders
     */
    public function ajax_detect_fake_order() {
        check_ajax_referer('pdm_delivery_actions', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have permission to perform this action.', 'pathao-delivery-manager'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Order not found.', 'pathao-delivery-manager'));
        }
        
        try {
            $detection_result = $this->detect_fake_order($order);
            
            // Save detection result
            update_post_meta($order_id, '_pdm_fake_detection', $detection_result);
            update_post_meta($order_id, '_pdm_fake_detection_date', current_time('mysql'));
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Fake order detection completed. Status: %s. %s', 'pathao-delivery-manager'),
                    $detection_result['status'],
                    $detection_result['message']
                )
            );
            
            wp_send_json_success(array(
                'message' => __('Fake order detection completed successfully.', 'pathao-delivery-manager'),
                'result' => $detection_result
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(__('Error during fake order detection: ', 'pathao-delivery-manager') . $e->getMessage());
        }
    }
    
    /**
     * AJAX handler for sending order to delivery
     */
    public function ajax_send_to_delivery() {
        check_ajax_referer('pdm_delivery_actions', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_die(__('You do not have permission to perform this action.', 'pathao-delivery-manager'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order) {
            wp_send_json_error(__('Order not found.', 'pathao-delivery-manager'));
        }
        
        // Check if order is already sent to delivery
        $delivery_status = get_post_meta($order_id, '_pdm_delivery_status', true);
        if ($delivery_status) {
            wp_send_json_error(__('Order is already sent to delivery.', 'pathao-delivery-manager'));
        }
        
        try {
            $delivery_result = $this->send_order_to_delivery($order);
            
            if ($delivery_result['success']) {
                // Update order meta
                update_post_meta($order_id, '_pdm_delivery_status', 'sent');
                update_post_meta($order_id, '_pdm_consignment_id', $delivery_result['consignment_id']);
                update_post_meta($order_id, '_pdm_tracking_code', $delivery_result['tracking_code']);
                update_post_meta($order_id, '_pdm_delivery_date', current_time('mysql'));
                
                // Update order status
                $order->update_status('pdm-delivery', __('Order sent to Pathao delivery.', 'pathao-delivery-manager'));
                
                // Add order note
                $order->add_order_note(
                    sprintf(
                        __('Order sent to Pathao delivery. Consignment ID: %s, Tracking Code: %s', 'pathao-delivery-manager'),
                        $delivery_result['consignment_id'],
                        $delivery_result['tracking_code']
                    )
                );
                
                wp_send_json_success(array(
                    'message' => __('Order sent to delivery successfully.', 'pathao-delivery-manager'),
                    'consignment_id' => $delivery_result['consignment_id'],
                    'tracking_code' => $delivery_result['tracking_code']
                ));
            } else {
                wp_send_json_error($delivery_result['message']);
            }
            
        } catch (Exception $e) {
            wp_send_json_error(__('Error sending order to delivery: ', 'pathao-delivery-manager') . $e->getMessage());
        }
    }
    
    /**
     * Detect fake order using various methods
     */
    private function detect_fake_order($order) {
        $detection_methods = array();
        $suspicious_indicators = array();
        $confidence_score = 100;
        
        // Method 1: Check customer phone number against previous orders
        $phone_check = $this->check_phone_history($order);
        $detection_methods['phone_history'] = $phone_check;
        
        if ($phone_check['suspicious']) {
            $suspicious_indicators[] = $phone_check['reason'];
            $confidence_score -= 20;
        }
        
        // Method 2: Check delivery address patterns
        $address_check = $this->check_address_patterns($order);
        $detection_methods['address_patterns'] = $address_check;
        
        if ($address_check['suspicious']) {
            $suspicious_indicators[] = $address_check['reason'];
            $confidence_score -= 15;
        }
        
        // Method 3: Check order timing patterns
        $timing_check = $this->check_order_timing($order);
        $detection_methods['timing_patterns'] = $timing_check;
        
        if ($timing_check['suspicious']) {
            $suspicious_indicators[] = $timing_check['reason'];
            $confidence_score -= 10;
        }
        
        // Method 4: Check order value patterns
        $value_check = $this->check_order_value($order);
        $detection_methods['value_patterns'] = $value_check;
        
        if ($value_check['suspicious']) {
            $suspicious_indicators[] = $value_check['reason'];
            $confidence_score -= 15;
        }
        
        // Method 5: Check customer behavior patterns
        $behavior_check = $this->check_customer_behavior($order);
        $detection_methods['behavior_patterns'] = $behavior_check;
        
        if ($behavior_check['suspicious']) {
            $suspicious_indicators[] = $behavior_check['reason'];
            $confidence_score -= 25;
        }
        
        // Method 6: Pathao API validation (if available)
        $pathao_check = $this->validate_with_pathao_api($order);
        $detection_methods['pathao_validation'] = $pathao_check;
        
        if ($pathao_check['suspicious']) {
            $suspicious_indicators[] = $pathao_check['reason'];
            $confidence_score -= 30;
        }
        
        // Determine final status
        $status = 'verified';
        $message = __('Order appears to be legitimate.', 'pathao-delivery-manager');
        
        if ($confidence_score <= 50) {
            $status = 'fake';
            $message = __('Order appears to be fake. Multiple suspicious indicators detected.', 'pathao-delivery-manager');
        } elseif ($confidence_score <= 75) {
            $status = 'suspicious';
            $message = __('Order requires manual review. Some suspicious indicators detected.', 'pathao-delivery-manager');
        }
        
        return array(
            'status' => $status,
            'message' => $message,
            'confidence' => max(0, $confidence_score),
            'suspicious_indicators' => $suspicious_indicators,
            'detection_methods' => $detection_methods,
            'timestamp' => current_time('mysql')
        );
    }
    
    /**
     * Check phone number history
     */
    private function check_phone_history($order) {
        $phone = $order->get_billing_phone();
        $customer_id = $order->get_customer_id();
        
        if (empty($phone)) {
            return array(
                'suspicious' => true,
                'reason' => __('No phone number provided', 'pathao-delivery-manager'),
                'details' => array()
            );
        }
        
        // Check for multiple orders with same phone but different customers
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT DISTINCT p.ID, pm1.meta_value as phone, pm2.meta_value as customer_id
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_billing_phone'
            LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_customer_user'
            WHERE p.post_type = 'shop_order'
            AND pm1.meta_value = %s
            AND p.ID != %d
            ORDER BY p.post_date DESC
            LIMIT 10
        ", $phone, $order->get_id());
        
        $similar_orders = $wpdb->get_results($query);
        
        $different_customers = 0;
        $total_orders = count($similar_orders);
        
        foreach ($similar_orders as $similar_order) {
            if ($similar_order->customer_id != $customer_id) {
                $different_customers++;
            }
        }
        
        $suspicious = false;
        $reason = '';
        
        if ($total_orders > 5 && $different_customers > 2) {
            $suspicious = true;
            $reason = sprintf(__('Phone number used by %d different customers in %d recent orders', 'pathao-delivery-manager'), $different_customers, $total_orders);
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => $reason,
            'details' => array(
                'total_orders' => $total_orders,
                'different_customers' => $different_customers,
                'phone' => $phone
            )
        );
    }
    
    /**
     * Check address patterns
     */
    private function check_address_patterns($order) {
        $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $city = $order->get_shipping_city();
        
        $suspicious = false;
        $reasons = array();
        
        // Check for incomplete address
        if (strlen(trim($address)) < 10) {
            $suspicious = true;
            $reasons[] = __('Address too short or incomplete', 'pathao-delivery-manager');
        }
        
        // Check for suspicious keywords
        $suspicious_keywords = array('test', 'fake', 'demo', 'sample', 'xxx', '123');
        foreach ($suspicious_keywords as $keyword) {
            if (stripos($address, $keyword) !== false) {
                $suspicious = true;
                $reasons[] = sprintf(__('Address contains suspicious keyword: %s', 'pathao-delivery-manager'), $keyword);
            }
        }
        
        // Check for repeated characters
        if (preg_match('/(..).*\1.*\1/', $address)) {
            $suspicious = true;
            $reasons[] = __('Address contains repeated patterns', 'pathao-delivery-manager');
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => implode(', ', $reasons),
            'details' => array(
                'address' => $address,
                'city' => $city,
                'reasons' => $reasons
            )
        );
    }
    
    /**
     * Check order timing patterns
     */
    private function check_order_timing($order) {
        $order_date = $order->get_date_created();
        $customer_id = $order->get_customer_id();
        
        // Check for multiple orders in short time frame
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT COUNT(*) as order_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
            WHERE p.post_type = 'shop_order'
            AND pm.meta_value = %s
            AND p.post_date >= DATE_SUB(%s, INTERVAL 1 HOUR)
            AND p.ID != %d
        ", $customer_id, $order_date->format('Y-m-d H:i:s'), $order->get_id());
        
        $recent_orders = $wpdb->get_var($query);
        
        $suspicious = false;
        $reason = '';
        
        if ($recent_orders > 2) {
            $suspicious = true;
            $reason = sprintf(__('Customer placed %d orders within 1 hour', 'pathao-delivery-manager'), $recent_orders + 1);
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => $reason,
            'details' => array(
                'recent_orders' => $recent_orders,
                'order_date' => $order_date->format('Y-m-d H:i:s')
            )
        );
    }
    
    /**
     * Check order value patterns
     */
    private function check_order_value($order) {
        $total = $order->get_total();
        $items = $order->get_items();
        
        $suspicious = false;
        $reasons = array();
        
        // Check for unusually high value
        if ($total > 50000) {
            $suspicious = true;
            $reasons[] = sprintf(__('Unusually high order value: %s', 'pathao-delivery-manager'), wc_price($total));
        }
        
        // Check for round numbers (might indicate fake orders)
        if ($total > 0 && $total == round($total / 1000) * 1000) {
            $suspicious = true;
            $reasons[] = __('Order total is a round number in thousands', 'pathao-delivery-manager');
        }
        
        // Check for single item with high quantity
        foreach ($items as $item) {
            if ($item->get_quantity() > 10) {
                $suspicious = true;
                $reasons[] = sprintf(__('High quantity for single item: %d', 'pathao-delivery-manager'), $item->get_quantity());
            }
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => implode(', ', $reasons),
            'details' => array(
                'total' => $total,
                'item_count' => count($items),
                'reasons' => $reasons
            )
        );
    }
    
    /**
     * Check customer behavior patterns
     */
    private function check_customer_behavior($order) {
        $customer_id = $order->get_customer_id();
        $email = $order->get_billing_email();
        
        $suspicious = false;
        $reasons = array();
        
        // Check for guest orders with suspicious email patterns
        if ($customer_id == 0) {
            // Check for temporary email services
            $temp_email_domains = array('10minutemail.com', 'tempmail.org', 'guerrillamail.com', 'mailinator.com');
            foreach ($temp_email_domains as $domain) {
                if (stripos($email, $domain) !== false) {
                    $suspicious = true;
                    $reasons[] = __('Using temporary email service', 'pathao-delivery-manager');
                    break;
                }
            }
            
            // Check for suspicious email patterns
            if (preg_match('/^[a-z]+\d+@/', $email)) {
                $suspicious = true;
                $reasons[] = __('Email follows suspicious pattern', 'pathao-delivery-manager');
            }
        } else {
            // Check customer order history
            $customer_orders = wc_get_orders(array(
                'customer_id' => $customer_id,
                'limit' => 10,
                'exclude' => array($order->get_id())
            ));
            
            if (count($customer_orders) == 0) {
                // First time customer - check registration date
                $user = get_user_by('id', $customer_id);
                if ($user) {
                    $registration_date = strtotime($user->user_registered);
                    $order_date = $order->get_date_created()->getTimestamp();
                    
                    if (($order_date - $registration_date) < 300) { // 5 minutes
                        $suspicious = true;
                        $reasons[] = __('Order placed within 5 minutes of registration', 'pathao-delivery-manager');
                    }
                }
            }
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => implode(', ', $reasons),
            'details' => array(
                'customer_id' => $customer_id,
                'email' => $email,
                'reasons' => $reasons
            )
        );
    }
    
    /**
     * Validate with Pathao API
     */
    private function validate_with_pathao_api($order) {
        // This would integrate with Pathao API to validate delivery address
        // For now, we'll simulate the validation
        
        $phone = $order->get_billing_phone();
        $address = $order->get_shipping_address_1();
        
        $suspicious = false;
        $reason = '';
        
        // Simulate API validation
        try {
            // Here you would make actual API calls to Pathao
            // For demonstration, we'll use simple validation
            
            if (empty($phone) || strlen($phone) < 10) {
                $suspicious = true;
                $reason = __('Invalid phone number format for delivery', 'pathao-delivery-manager');
            }
            
            if (empty($address) || strlen($address) < 5) {
                $suspicious = true;
                $reason = __('Invalid address format for delivery', 'pathao-delivery-manager');
            }
            
        } catch (Exception $e) {
            // API validation failed, but don't mark as suspicious
            $reason = __('Could not validate with Pathao API', 'pathao-delivery-manager');
        }
        
        return array(
            'suspicious' => $suspicious,
            'reason' => $reason,
            'details' => array(
                'phone' => $phone,
                'address' => $address
            )
        );
    }
    
    /**
     * Send order to delivery
     */
    private function send_order_to_delivery($order) {
        // Get Pathao API settings
        $api_settings = get_option('pdm_pathao_settings', array());
        
        if (empty($api_settings['client_id']) || empty($api_settings['client_secret'])) {
            throw new Exception(__('Pathao API credentials not configured.', 'pathao-delivery-manager'));
        }
        
        // Prepare delivery data
        $delivery_data = array(
            'store_id' => $api_settings['store_id'],
            'merchant_order_id' => $order->get_order_number(),
            'sender_name' => $api_settings['sender_name'],
            'sender_phone' => $api_settings['sender_phone'],
            'sender_address' => $api_settings['sender_address'],
            'recipient_name' => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
            'recipient_phone' => $order->get_billing_phone(),
            'recipient_address' => $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
            'recipient_city' => $order->get_shipping_city(),
            'recipient_zone' => $order->get_shipping_state(),
            'delivery_type' => 48, // Normal delivery
            'item_type' => 2, // Parcel
            'special_instruction' => $order->get_customer_note(),
            'item_quantity' => $order->get_item_count(),
            'item_weight' => 0.5, // Default weight
            'amount_to_collect' => $order->get_payment_method() === 'cod' ? $order->get_total() : 0,
            'item_description' => $this->get_order_items_description($order)
        );
        
        // For demonstration, we'll simulate a successful API response
        // In real implementation, you would make actual API calls to Pathao
        
        $success = true; // Simulate success
        
        if ($success) {
            return array(
                'success' => true,
                'consignment_id' => 'PDM' . time() . rand(1000, 9999),
                'tracking_code' => 'TRK' . time() . rand(100, 999),
                'message' => __('Order sent to delivery successfully.', 'pathao-delivery-manager')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to send order to delivery.', 'pathao-delivery-manager')
            );
        }
    }
    
    /**
     * Get order items description
     */
    private function get_order_items_description($order) {
        $items = array();
        foreach ($order->get_items() as $item) {
            $items[] = $item->get_name() . ' x' . $item->get_quantity();
        }
        return implode(', ', $items);
    }
    
    /**
     * Register custom order status for delivery
     */
    public function register_delivery_order_status() {
        register_post_status('wc-pdm-delivery', array(
            'label' => __('Pathao Delivery', 'pathao-delivery-manager'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Pathao Delivery <span class="count">(%s)</span>', 'Pathao Delivery <span class="count">(%s)</span>', 'pathao-delivery-manager')
        ));
    }
    
    /**
     * Add custom order status to WooCommerce
     */
    public function add_delivery_order_status($order_statuses) {
        $new_order_statuses = array();
        
        foreach ($order_statuses as $key => $status) {
            $new_order_statuses[$key] = $status;
            if ('wc-processing' === $key) {
                $new_order_statuses['wc-pdm-delivery'] = __('Pathao Delivery', 'pathao-delivery-manager');
            }
        }
        
        return $new_order_statuses;
    }
    
    /**
     * Display delivery info in order details
     */
    public function display_delivery_info($order) {
        $delivery_status = get_post_meta($order->get_id(), '_pdm_delivery_status', true);
        $consignment_id = get_post_meta($order->get_id(), '_pdm_consignment_id', true);
        $tracking_code = get_post_meta($order->get_id(), '_pdm_tracking_code', true);
        
        if ($delivery_status) {
            echo '<div class="pdm-delivery-info-display">';
            echo '<h3>' . __('Pathao Delivery Information', 'pathao-delivery-manager') . '</h3>';
            echo '<p><strong>' . __('Status:', 'pathao-delivery-manager') . '</strong> ' . esc_html($delivery_status) . '</p>';
            if ($consignment_id) {
                echo '<p><strong>' . __('Consignment ID:', 'pathao-delivery-manager') . '</strong> ' . esc_html($consignment_id) . '</p>';
            }
            if ($tracking_code) {
                echo '<p><strong>' . __('Tracking Code:', 'pathao-delivery-manager') . '</strong> ' . esc_html($tracking_code) . '</p>';
            }
            echo '</div>';
        }
    }
    
    /**
     * Enqueue scripts for order edit page
     */
    public function enqueue_order_scripts($hook) {
        global $post_type;
        
        if ($hook === 'post.php' && $post_type === 'shop_order') {
            wp_enqueue_script('jquery');
        }
    }
}

// Initialize the integration
new PDM_WooCommerce_Integration();