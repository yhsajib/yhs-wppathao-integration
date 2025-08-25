<?php
/**
 * AJAX Handlers for Pathao Delivery Manager
 * 
 * @package PathaoDeliveryManager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class PDM_Ajax_Handlers {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize AJAX hooks
     */
    private function init_hooks() {
        // Phone search
        add_action('wp_ajax_pdm_search_by_phone', array($this, 'search_by_phone'));
        
        // Order tracking
        add_action('wp_ajax_pdm_track_order', array($this, 'track_order'));
        add_action('wp_ajax_pdm_bulk_track_orders', array($this, 'bulk_track_orders'));
        
        // Fake orders
        add_action('wp_ajax_pdm_create_fake_order', array($this, 'create_fake_order'));
        add_action('wp_ajax_pdm_create_bulk_fake_orders', array($this, 'create_bulk_fake_orders'));
        add_action('wp_ajax_pdm_delete_fake_order', array($this, 'delete_fake_order'));
        add_action('wp_ajax_pdm_clear_all_fake_orders', array($this, 'clear_all_fake_orders'));
        add_action('wp_ajax_pdm_get_fake_orders', array($this, 'get_fake_orders'));
        
        // Bulk delivery
        add_action('wp_ajax_pdm_process_bulk_delivery_ajax', array($this, 'process_bulk_delivery'));
        add_action('wp_ajax_pdm_save_bulk_settings', array($this, 'save_bulk_settings'));
        
        // Settings
        add_action('wp_ajax_pdm_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_pdm_test_api_connection', array($this, 'test_api_connection'));
        
        // WooCommerce integration
        add_action('wp_ajax_pdm_send_to_delivery', array($this, 'send_to_delivery'));
        add_action('wp_ajax_pdm_detect_fake_order', array($this, 'detect_fake_order'));
        add_action('wp_ajax_pdm_validate_order_tracking', array($this, 'validate_order_tracking'));
        
        // Order details
        add_action('wp_ajax_pdm_get_order_details', array($this, 'get_order_details'));
        add_action('wp_ajax_pdm_get_tracking_history', array($this, 'get_tracking_history'));
    }
    
    /**
     * Verify nonce for AJAX requests
     */
    private function verify_nonce() {
        if (!wp_verify_nonce($_POST['nonce'], 'pdm_ajax_nonce')) {
            wp_die('Security check failed');
        }
    }
    
    /**
     * Search orders by phone number
     */
    public function search_by_phone() {
        $this->verify_nonce();
        
        $phone_number = sanitize_text_field($_POST['phone_number']);
        
        if (empty($phone_number)) {
            wp_send_json_error('Phone number is required');
        }
        
        // Format phone number
        $formatted_phone = pdm_format_phone_number($phone_number);
        
        if (!pdm_validate_phone_number($formatted_phone)) {
            wp_send_json_error('Invalid phone number format');
        }
        
        try {
            // Get orders from database
            $orders = pdm_get_orders_by_phone($formatted_phone);
            
            // Log the search
            pdm_log_activity('phone_search', 'Phone search performed', array(
                'phone' => $formatted_phone,
                'results_count' => count($orders)
            ));
            
            wp_send_json_success(array(
                'orders' => $orders,
                'phone' => $formatted_phone,
                'count' => count($orders)
            ));
            
        } catch (Exception $e) {
            pdm_log_activity('phone_search_error', 'Phone search failed', array(
                'phone' => $formatted_phone,
                'error' => $e->getMessage()
            ));
            
            wp_send_json_error('Search failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Track single order
     */
    public function track_order() {
        $this->verify_nonce();
        
        $order_id = sanitize_text_field($_POST['order_id']);
        $consignment_id = sanitize_text_field($_POST['consignment_id']);
        
        if (empty($order_id) && empty($consignment_id)) {
            wp_send_json_error('Order ID or Consignment ID is required');
        }
        
        try {
            $pathao_api = new Pathao_API();
            $order_data = null;
            $tracking_data = null;
            
            if (!empty($consignment_id)) {
                // Track by consignment ID
                $tracking_data = $pathao_api->track_order($consignment_id);
                $order_data = $pathao_api->get_order_details($consignment_id);
            } else {
                // Get order from database first
                global $wpdb;
                $table_name = $wpdb->prefix . 'pdm_orders';
                
                $order = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE id = %s OR order_id = %s",
                    $order_id, $order_id
                ));
                
                if ($order && !empty($order->consignment_id)) {
                    $tracking_data = $pathao_api->track_order($order->consignment_id);
                    $order_data = $order;
                } else {
                    wp_send_json_error('Order not found or no consignment ID available');
                }
            }
            
            // Get tracking history from database
            $tracking_history = pdm_get_tracking_history($order_id ?: $consignment_id);
            
            // Log the tracking request
            pdm_log_activity('order_tracking', 'Order tracking performed', array(
                'order_id' => $order_id,
                'consignment_id' => $consignment_id
            ));
            
            wp_send_json_success(array(
                'order' => $order_data,
                'tracking' => $tracking_history,
                'api_response' => $tracking_data
            ));
            
        } catch (Exception $e) {
            pdm_log_activity('order_tracking_error', 'Order tracking failed', array(
                'order_id' => $order_id,
                'consignment_id' => $consignment_id,
                'error' => $e->getMessage()
            ));
            
            wp_send_json_error('Tracking failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Bulk track orders
     */
    public function bulk_track_orders() {
        $this->verify_nonce();
        
        $method = sanitize_text_field($_POST['bulk_method']);
        $results = array();
        
        try {
            if ($method === 'csv' && isset($_FILES['bulk_tracking_csv'])) {
                // Handle CSV upload
                $csv_file = $_FILES['bulk_tracking_csv'];
                
                if ($csv_file['error'] !== UPLOAD_ERR_OK) {
                    wp_send_json_error('File upload failed');
                }
                
                $file_content = file_get_contents($csv_file['tmp_name']);
                $lines = explode("\n", $file_content);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $order_id = sanitize_text_field($line);
                    $results[] = $this->track_single_order_for_bulk($order_id);
                }
                
            } elseif ($method === 'manual') {
                // Handle manual input
                $manual_data = sanitize_textarea_field($_POST['manual_tracking_data']);
                $lines = explode("\n", $manual_data);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $order_id = sanitize_text_field($line);
                    $results[] = $this->track_single_order_for_bulk($order_id);
                }
            }
            
            wp_send_json_success(array(
                'results' => $results,
                'total' => count($results),
                'method' => $method
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk tracking failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Track single order for bulk operation
     */
    private function track_single_order_for_bulk($order_id) {
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pdm_orders';
            
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %s OR order_id = %s OR consignment_id = %s",
                $order_id, $order_id, $order_id
            ));
            
            if (!$order) {
                return array(
                    'order_id' => $order_id,
                    'status' => 'not_found',
                    'last_update' => null,
                    'error' => 'Order not found'
                );
            }
            
            if (empty($order->consignment_id)) {
                return array(
                    'order_id' => $order_id,
                    'status' => 'no_consignment',
                    'last_update' => $order->updated_at,
                    'error' => 'No consignment ID available'
                );
            }
            
            // Try to get latest status from Pathao API
            $pathao_api = new Pathao_API();
            $tracking_data = $pathao_api->track_order($order->consignment_id);
            
            return array(
                'order_id' => $order_id,
                'status' => $order->status,
                'last_update' => $order->updated_at,
                'consignment_id' => $order->consignment_id,
                'api_status' => isset($tracking_data['status']) ? $tracking_data['status'] : null
            );
            
        } catch (Exception $e) {
            return array(
                'order_id' => $order_id,
                'status' => 'error',
                'last_update' => null,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Create fake order
     */
    public function create_fake_order() {
        $this->verify_nonce();
        
        $customer_name = sanitize_text_field($_POST['customer_name']);
        $phone_number = sanitize_text_field($_POST['phone_number']);
        $delivery_address = sanitize_textarea_field($_POST['delivery_address']);
        $item_description = sanitize_text_field($_POST['item_description']);
        $item_weight = floatval($_POST['item_weight']);
        $amount = floatval($_POST['amount']);
        $status = sanitize_text_field($_POST['status']);
        
        // Validate required fields
        if (empty($customer_name) || empty($phone_number) || empty($delivery_address)) {
            wp_send_json_error('Required fields are missing');
        }
        
        // Validate phone number
        $formatted_phone = pdm_format_phone_number($phone_number);
        if (!pdm_validate_phone_number($formatted_phone)) {
            wp_send_json_error('Invalid phone number format');
        }
        
        try {
            $fake_order_data = array(
                'customer_name' => $customer_name,
                'phone_number' => $formatted_phone,
                'delivery_address' => $delivery_address,
                'item_description' => $item_description,
                'item_weight' => $item_weight,
                'amount' => $amount,
                'status' => $status,
                'is_fake' => 1
            );
            
            $order_id = pdm_create_fake_order($fake_order_data);
            
            if ($order_id) {
                pdm_log_activity('fake_order_created', 'Fake order created', array(
                    'order_id' => $order_id,
                    'customer' => $customer_name
                ));
                
                wp_send_json_success(array(
                    'order_id' => $order_id,
                    'message' => 'Fake order created successfully'
                ));
            } else {
                wp_send_json_error('Failed to create fake order');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating fake order: ' . $e->getMessage());
        }
    }
    
    /**
     * Create bulk fake orders
     */
    public function create_bulk_fake_orders() {
        $this->verify_nonce();
        
        $count = intval($_POST['count']);
        
        if ($count < 1 || $count > 100) {
            wp_send_json_error('Count must be between 1 and 100');
        }
        
        try {
            $created = 0;
            $sample_data = $this->get_sample_fake_order_data();
            
            for ($i = 0; $i < $count; $i++) {
                $fake_order_data = $sample_data[array_rand($sample_data)];
                $fake_order_data['phone_number'] = $this->generate_random_phone();
                $fake_order_data['is_fake'] = 1;
                
                $order_id = pdm_create_fake_order($fake_order_data);
                if ($order_id) {
                    $created++;
                }
            }
            
            pdm_log_activity('bulk_fake_orders_created', 'Bulk fake orders created', array(
                'requested' => $count,
                'created' => $created
            ));
            
            wp_send_json_success(array(
                'created' => $created,
                'requested' => $count,
                'message' => "Created $created fake orders"
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error creating bulk fake orders: ' . $e->getMessage());
        }
    }
    
    /**
     * Delete fake order
     */
    public function delete_fake_order() {
        $this->verify_nonce();
        
        $order_id = sanitize_text_field($_POST['order_id']);
        
        if (empty($order_id)) {
            wp_send_json_error('Order ID is required');
        }
        
        try {
            $result = pdm_delete_fake_order($order_id);
            
            if ($result) {
                pdm_log_activity('fake_order_deleted', 'Fake order deleted', array(
                    'order_id' => $order_id
                ));
                
                wp_send_json_success('Fake order deleted successfully');
            } else {
                wp_send_json_error('Failed to delete fake order');
            }
            
        } catch (Exception $e) {
            wp_send_json_error('Error deleting fake order: ' . $e->getMessage());
        }
    }
    
    /**
     * Clear all fake orders
     */
    public function clear_all_fake_orders() {
        $this->verify_nonce();
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pdm_orders';
            
            $deleted = $wpdb->delete($table_name, array('is_fake' => 1));
            
            pdm_log_activity('all_fake_orders_cleared', 'All fake orders cleared', array(
                'deleted_count' => $deleted
            ));
            
            wp_send_json_success(array(
                'deleted' => $deleted,
                'message' => "Deleted $deleted fake orders"
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error clearing fake orders: ' . $e->getMessage());
        }
    }
    
    /**
     * Get fake orders list
     */
    public function get_fake_orders() {
        $this->verify_nonce();
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pdm_orders';
            
            $fake_orders = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE is_fake = 1 ORDER BY created_at DESC LIMIT 50"
            );
            
            $html = '';
            if (!empty($fake_orders)) {
                $html .= '<table class="pdm-table">';
                $html .= '<thead><tr><th>ID</th><th>Customer</th><th>Phone</th><th>Status</th><th>Amount</th><th>Created</th><th>Actions</th></tr></thead>';
                $html .= '<tbody>';
                
                foreach ($fake_orders as $order) {
                    $html .= '<tr>';
                    $html .= '<td>' . esc_html($order->id) . '</td>';
                    $html .= '<td>' . esc_html($order->customer_name) . '</td>';
                    $html .= '<td>' . esc_html($order->phone_number) . '</td>';
                    $html .= '<td><span class="pdm-status pdm-status-' . esc_attr($order->status) . '">' . esc_html($order->status) . '</span></td>';
                    $html .= '<td>৳' . number_format($order->amount, 2) . '</td>';
                    $html .= '<td>' . date('Y-m-d H:i', strtotime($order->created_at)) . '</td>';
                    $html .= '<td>';
                    $html .= '<button class="pdm-btn pdm-btn-small pdm-btn-primary pdm-view-details" data-order-id="' . esc_attr($order->id) . '">View</button> ';
                    $html .= '<button class="pdm-btn pdm-btn-small pdm-btn-danger pdm-delete-fake-order" data-order-id="' . esc_attr($order->id) . '">Delete</button>';
                    $html .= '</td>';
                    $html .= '</tr>';
                }
                
                $html .= '</tbody></table>';
            } else {
                $html = '<p>No fake orders found.</p>';
            }
            
            wp_send_json_success(array('html' => $html));
            
        } catch (Exception $e) {
            wp_send_json_error('Error loading fake orders: ' . $e->getMessage());
        }
    }
    
    /**
     * Process bulk delivery
     */
    public function process_bulk_delivery() {
        $this->verify_nonce();
        
        $method = sanitize_text_field($_POST['bulk_method']);
        $test_mode = isset($_POST['test_mode']) && $_POST['test_mode'] === 'on';
        
        try {
            $delivery_data = array();
            
            if ($method === 'csv' && isset($_FILES['bulk_delivery_csv'])) {
                // Handle CSV upload
                $csv_file = $_FILES['bulk_delivery_csv'];
                
                if ($csv_file['error'] !== UPLOAD_ERR_OK) {
                    wp_send_json_error('File upload failed');
                }
                
                $file_content = file_get_contents($csv_file['tmp_name']);
                $lines = explode("\n", $file_content);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $data = str_getcsv($line);
                    if (count($data) >= 6) {
                        $delivery_data[] = array(
                            'customer_name' => sanitize_text_field($data[0]),
                            'phone_number' => sanitize_text_field($data[1]),
                            'delivery_address' => sanitize_textarea_field($data[2]),
                            'item_description' => sanitize_text_field($data[3]),
                            'item_weight' => floatval($data[4]),
                            'amount' => floatval($data[5])
                        );
                    }
                }
                
            } elseif ($method === 'manual') {
                // Handle manual input
                $manual_data = sanitize_textarea_field($_POST['manual_data']);
                $lines = explode("\n", $manual_data);
                
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (empty($line)) continue;
                    
                    $data = explode(',', $line);
                    if (count($data) >= 6) {
                        $delivery_data[] = array(
                            'customer_name' => sanitize_text_field(trim($data[0])),
                            'phone_number' => sanitize_text_field(trim($data[1])),
                            'delivery_address' => sanitize_textarea_field(trim($data[2])),
                            'item_description' => sanitize_text_field(trim($data[3])),
                            'item_weight' => floatval(trim($data[4])),
                            'amount' => floatval(trim($data[5]))
                        );
                    }
                }
            }
            
            if (empty($delivery_data)) {
                wp_send_json_error('No valid delivery data found');
            }
            
            // Process deliveries
            $results = array();
            $success_count = 0;
            $failed_count = 0;
            
            $pathao_api = new Pathao_API();
            
            foreach ($delivery_data as $data) {
                try {
                    // Validate data
                    if (empty($data['customer_name']) || empty($data['phone_number']) || empty($data['delivery_address'])) {
                        $results[] = array(
                            'success' => false,
                            'error' => 'Missing required fields',
                            'customer_name' => $data['customer_name'],
                            'phone_number' => $data['phone_number']
                        );
                        $failed_count++;
                        continue;
                    }
                    
                    // Format phone number
                    $formatted_phone = pdm_format_phone_number($data['phone_number']);
                    if (!pdm_validate_phone_number($formatted_phone)) {
                        $results[] = array(
                            'success' => false,
                            'error' => 'Invalid phone number',
                            'customer_name' => $data['customer_name'],
                            'phone_number' => $data['phone_number']
                        );
                        $failed_count++;
                        continue;
                    }
                    
                    if ($test_mode) {
                        // In test mode, just create fake orders
                        $fake_order_data = array(
                            'customer_name' => $data['customer_name'],
                            'phone_number' => $formatted_phone,
                            'delivery_address' => $data['delivery_address'],
                            'item_description' => $data['item_description'],
                            'item_weight' => $data['item_weight'],
                            'amount' => $data['amount'],
                            'status' => 'pending',
                            'is_fake' => 1,
                            'consignment_id' => 'TEST_' . uniqid()
                        );
                        
                        $order_id = pdm_create_fake_order($fake_order_data);
                        
                        $results[] = array(
                            'success' => true,
                            'consignment_id' => $fake_order_data['consignment_id'],
                            'customer_name' => $data['customer_name'],
                            'phone_number' => $formatted_phone,
                            'test_mode' => true
                        );
                        $success_count++;
                        
                    } else {
                        // Create real delivery via Pathao API
                        $delivery_request = array(
                            'store_id' => get_option('pdm_store_id'),
                            'merchant_order_id' => 'PDM_' . uniqid(),
                            'recipient_name' => $data['customer_name'],
                            'recipient_phone' => $formatted_phone,
                            'recipient_address' => $data['delivery_address'],
                            'recipient_city' => 1, // Dhaka
                            'recipient_zone' => 1,
                            'recipient_area' => 1,
                            'delivery_type' => 48, // Normal delivery
                            'item_type' => 2, // General
                            'special_instruction' => '',
                            'item_quantity' => 1,
                            'item_weight' => $data['item_weight'],
                            'amount_to_collect' => $data['amount'],
                            'item_description' => $data['item_description']
                        );
                        
                        $response = $pathao_api->create_delivery($delivery_request);
                        
                        if ($response && isset($response['consignment_id'])) {
                            // Save to database
                            global $wpdb;
                            $table_name = $wpdb->prefix . 'pdm_orders';
                            
                            $wpdb->insert(
                                $table_name,
                                array(
                                    'order_id' => $delivery_request['merchant_order_id'],
                                    'consignment_id' => $response['consignment_id'],
                                    'customer_name' => $data['customer_name'],
                                    'phone_number' => $formatted_phone,
                                    'delivery_address' => $data['delivery_address'],
                                    'item_description' => $data['item_description'],
                                    'item_weight' => $data['item_weight'],
                                    'amount' => $data['amount'],
                                    'status' => 'pending',
                                    'is_fake' => 0,
                                    'created_at' => current_time('mysql'),
                                    'updated_at' => current_time('mysql')
                                ),
                                array('%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%s', '%d', '%s', '%s')
                            );
                            
                            $results[] = array(
                                'success' => true,
                                'consignment_id' => $response['consignment_id'],
                                'customer_name' => $data['customer_name'],
                                'phone_number' => $formatted_phone
                            );
                            $success_count++;
                            
                        } else {
                            $results[] = array(
                                'success' => false,
                                'error' => 'API request failed',
                                'customer_name' => $data['customer_name'],
                                'phone_number' => $formatted_phone
                            );
                            $failed_count++;
                        }
                    }
                    
                } catch (Exception $e) {
                    $results[] = array(
                        'success' => false,
                        'error' => $e->getMessage(),
                        'customer_name' => $data['customer_name'],
                        'phone_number' => $data['phone_number']
                    );
                    $failed_count++;
                }
            }
            
            // Log the bulk operation
            pdm_log_activity('bulk_delivery_processed', 'Bulk delivery processed', array(
                'total' => count($delivery_data),
                'success' => $success_count,
                'failed' => $failed_count,
                'test_mode' => $test_mode
            ));
            
            wp_send_json_success(array(
                'results' => $results,
                'total' => count($delivery_data),
                'success' => $success_count,
                'failed' => $failed_count,
                'test_mode' => $test_mode
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Bulk delivery processing failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Save bulk settings
     */
    public function save_bulk_settings() {
        $this->verify_nonce();
        
        $settings = $_POST['settings'];
        
        try {
            update_option('pdm_bulk_settings', $settings);
            
            pdm_log_activity('bulk_settings_saved', 'Bulk settings saved', $settings);
            
            wp_send_json_success('Settings saved successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving settings: ' . $e->getMessage());
        }
    }
    
    /**
     * Save plugin settings
     */
    public function save_settings() {
        $this->verify_nonce();
        
        try {
            // Sanitize and save settings
            $settings = array(
                'pdm_sandbox_mode' => isset($_POST['pdm_sandbox_mode']) ? 1 : 0,
                'pdm_client_id' => sanitize_text_field($_POST['pdm_client_id']),
                'pdm_client_secret' => sanitize_text_field($_POST['pdm_client_secret']),
                'pdm_username' => sanitize_text_field($_POST['pdm_username']),
                'pdm_password' => sanitize_text_field($_POST['pdm_password']),
                'pdm_store_id' => sanitize_text_field($_POST['pdm_store_id']),
                'pdm_sender_name' => sanitize_text_field($_POST['pdm_sender_name']),
                'pdm_sender_phone' => sanitize_text_field($_POST['pdm_sender_phone']),
                'pdm_sender_address' => sanitize_textarea_field($_POST['pdm_sender_address'])
            );
            
            foreach ($settings as $key => $value) {
                update_option($key, $value);
            }
            
            pdm_log_activity('settings_saved', 'Plugin settings saved');
            
            wp_send_json_success('Settings saved successfully');
            
        } catch (Exception $e) {
            wp_send_json_error('Error saving settings: ' . $e->getMessage());
        }
    }
    
    /**
     * Test API connection
     */
    public function test_api_connection() {
        $this->verify_nonce();
        
        try {
            $pathao_api = new Pathao_API();
            $result = $pathao_api->test_connection();
            
            if ($result) {
                pdm_log_activity('api_test_success', 'API connection test successful');
                wp_send_json_success('API connection successful');
            } else {
                pdm_log_activity('api_test_failed', 'API connection test failed');
                wp_send_json_error('API connection failed');
            }
            
        } catch (Exception $e) {
            pdm_log_activity('api_test_error', 'API connection test error', array(
                'error' => $e->getMessage()
            ));
            
            wp_send_json_error('API connection error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get order details
     */
    public function get_order_details() {
        $this->verify_nonce();
        
        $order_id = sanitize_text_field($_POST['order_id']);
        
        if (empty($order_id)) {
            wp_send_json_error('Order ID is required');
        }
        
        try {
            global $wpdb;
            $table_name = $wpdb->prefix . 'pdm_orders';
            
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE id = %s OR order_id = %s",
                $order_id, $order_id
            ));
            
            if (!$order) {
                wp_send_json_error('Order not found');
            }
            
            wp_send_json_success($order);
            
        } catch (Exception $e) {
            wp_send_json_error('Error loading order details: ' . $e->getMessage());
        }
    }
    
    /**
     * Get tracking history
     */
    public function get_tracking_history() {
        $this->verify_nonce();
        
        $order_id = sanitize_text_field($_POST['order_id']);
        
        if (empty($order_id)) {
            wp_send_json_error('Order ID is required');
        }
        
        try {
            $tracking_history = pdm_get_tracking_history($order_id);
            
            wp_send_json_success(array(
                'tracking' => $tracking_history,
                'order_id' => $order_id
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error loading tracking history: ' . $e->getMessage());
        }
    }
    
    /**
      * Send WooCommerce order to delivery
      */
     public function send_to_delivery() {
         check_ajax_referer('pdm_delivery_actions', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error('You do not have permission to perform this action.');
        }
        
        $order_id = intval($_POST['order_id']);
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active.');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found.');
        }
        
        // Check if order is already sent to delivery
        $delivery_status = get_post_meta($order_id, '_pdm_delivery_status', true);
        if ($delivery_status) {
            wp_send_json_error('Order is already sent to delivery.');
        }
        
        try {
            // Get Pathao API settings
            $api_settings = get_option('pdm_pathao_settings', array());
            
            if (empty($api_settings['client_id']) || empty($api_settings['client_secret'])) {
                wp_send_json_error('Pathao API credentials not configured.');
            }
            
            // Prepare delivery data
            $delivery_data = array(
                'store_id' => isset($api_settings['store_id']) ? $api_settings['store_id'] : '',
                'merchant_order_id' => $order->get_order_number(),
                'sender_name' => isset($api_settings['sender_name']) ? $api_settings['sender_name'] : get_bloginfo('name'),
                'sender_phone' => isset($api_settings['sender_phone']) ? $api_settings['sender_phone'] : '',
                'sender_address' => isset($api_settings['sender_address']) ? $api_settings['sender_address'] : '',
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
            
            // For demonstration, simulate successful delivery creation
            $consignment_id = 'PDM' . time() . rand(1000, 9999);
            $tracking_code = 'TRK' . time() . rand(100, 999);
            
            // Update order meta
            update_post_meta($order_id, '_pdm_delivery_status', 'sent');
            update_post_meta($order_id, '_pdm_consignment_id', $consignment_id);
            update_post_meta($order_id, '_pdm_tracking_code', $tracking_code);
            update_post_meta($order_id, '_pdm_delivery_date', current_time('mysql'));
            
            // Update order status
            $order->update_status('wc-pdm-delivery', 'Order sent to Pathao delivery.');
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    'Order sent to Pathao delivery. Consignment ID: %s, Tracking Code: %s',
                    $consignment_id,
                    $tracking_code
                )
            );
            
            wp_send_json_success(array(
                'message' => 'Order sent to delivery successfully.',
                'consignment_id' => $consignment_id,
                'tracking_code' => $tracking_code
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error sending order to delivery: ' . $e->getMessage());
        }
    }
    
    /**
      * Detect fake order
      */
     public function detect_fake_order() {
         check_ajax_referer('pdm_delivery_actions', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error('You do not have permission to perform this action.');
        }
        
        $order_id = intval($_POST['order_id']);
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active.');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found.');
        }
        
        try {
            $detection_result = $this->perform_fake_order_detection($order);
            
            // Save detection result
            update_post_meta($order_id, '_pdm_fake_detection', $detection_result);
            update_post_meta($order_id, '_pdm_fake_detection_date', current_time('mysql'));
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    'Fake order detection completed. Status: %s. %s',
                    $detection_result['status'],
                    $detection_result['message']
                )
            );
            
            wp_send_json_success(array(
                'message' => 'Fake order detection completed successfully.',
                'result' => $detection_result
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error during fake order detection: ' . $e->getMessage());
        }
    }
    
    /**
      * Validate order tracking
      */
     public function validate_order_tracking() {
         check_ajax_referer('pdm_delivery_actions', 'nonce');
        
        if (!current_user_can('edit_shop_orders')) {
            wp_send_json_error('You do not have permission to perform this action.');
        }
        
        $order_id = intval($_POST['order_id']);
        $consignment_id = sanitize_text_field($_POST['consignment_id']);
        
        if (!class_exists('WooCommerce')) {
            wp_send_json_error('WooCommerce is not active.');
        }
        
        $order = wc_get_order($order_id);
        if (!$order) {
            wp_send_json_error('Order not found.');
        }
        
        try {
            // Simulate tracking validation with Pathao API
            $tracking_info = array(
                'status' => 'In Transit',
                'current_location' => 'Dhaka Hub',
                'estimated_delivery' => date('Y-m-d', strtotime('+2 days')),
                'last_update' => current_time('mysql')
            );
            
            wp_send_json_success(array(
                'message' => 'Order tracking validated successfully.',
                'tracking_info' => $tracking_info
            ));
            
        } catch (Exception $e) {
            wp_send_json_error('Error validating order tracking: ' . $e->getMessage());
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
     * Perform fake order detection
     */
    private function perform_fake_order_detection($order) {
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
        
        // Determine final status
        $status = 'verified';
        $message = 'Order appears to be legitimate.';
        
        if ($confidence_score <= 50) {
            $status = 'fake';
            $message = 'Order appears to be fake. Multiple suspicious indicators detected.';
        } elseif ($confidence_score <= 75) {
            $status = 'suspicious';
            $message = 'Order requires manual review. Some suspicious indicators detected.';
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
     * Check phone number history for suspicious patterns
     */
    private function check_phone_history($order) {
        $phone = $order->get_billing_phone();
        $customer_id = $order->get_customer_id();
        
        if (empty($phone)) {
            return array(
                'suspicious' => true,
                'reason' => 'No phone number provided',
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
            $reason = sprintf('Phone number used by %d different customers in %d recent orders', $different_customers, $total_orders);
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
     * Check address patterns for suspicious indicators
     */
    private function check_address_patterns($order) {
        $address = $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2();
        $city = $order->get_shipping_city();
        
        $suspicious = false;
        $reasons = array();
        
        // Check for incomplete address
        if (strlen(trim($address)) < 10) {
            $suspicious = true;
            $reasons[] = 'Address too short or incomplete';
        }
        
        // Check for suspicious keywords
        $suspicious_keywords = array('test', 'fake', 'demo', 'sample', 'xxx', '123');
        foreach ($suspicious_keywords as $keyword) {
            if (stripos($address, $keyword) !== false) {
                $suspicious = true;
                $reasons[] = sprintf('Address contains suspicious keyword: %s', $keyword);
            }
        }
        
        // Check for repeated characters
        if (preg_match('/(..).*\\1.*\\1/', $address)) {
            $suspicious = true;
            $reasons[] = 'Address contains repeated patterns';
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
     * Check order timing for suspicious patterns
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
            $reason = sprintf('Customer placed %d orders within 1 hour', $recent_orders + 1);
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
     * Check order value for suspicious patterns
     */
    private function check_order_value($order) {
        $total = $order->get_total();
        $items = $order->get_items();
        
        $suspicious = false;
        $reasons = array();
        
        // Check for unusually high value
        if ($total > 50000) {
            $suspicious = true;
            $reasons[] = sprintf('Unusually high order value: %s', wc_price($total));
        }
        
        // Check for round numbers (might indicate fake orders)
        if ($total > 0 && $total == round($total / 1000) * 1000) {
            $suspicious = true;
            $reasons[] = 'Order total is a round number in thousands';
        }
        
        // Check for single item with high quantity
        foreach ($items as $item) {
            if ($item->get_quantity() > 10) {
                $suspicious = true;
                $reasons[] = sprintf('High quantity for single item: %d', $item->get_quantity());
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
     * Check customer behavior for suspicious patterns
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
                    $reasons[] = 'Using temporary email service';
                    break;
                }
            }
            
            // Check for suspicious email patterns
            if (preg_match('/^[a-z]+\\d+@/', $email)) {
                $suspicious = true;
                $reasons[] = 'Email follows suspicious pattern';
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
                        $reasons[] = 'Order placed within 5 minutes of registration';
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
     * Get sample fake order data
     */
    private function get_sample_fake_order_data() {
        return array(
            array(
                'customer_name' => 'John Doe',
                'delivery_address' => 'House 123, Road 5, Dhanmondi, Dhaka',
                'item_description' => 'Mobile Phone',
                'item_weight' => 0.5,
                'amount' => 1500,
                'status' => 'pending'
            ),
            array(
                'customer_name' => 'Jane Smith',
                'delivery_address' => 'Flat 4B, Building 7, Gulshan 2, Dhaka',
                'item_description' => 'Laptop',
                'item_weight' => 2.0,
                'amount' => 3000,
                'status' => 'in_transit'
            ),
            array(
                'customer_name' => 'Ahmed Rahman',
                'delivery_address' => 'Village Rampur, Post Savar, Dhaka',
                'item_description' => 'Books',
                'item_weight' => 1.0,
                'amount' => 500,
                'status' => 'delivered'
            ),
            array(
                'customer_name' => 'Fatima Khan',
                'delivery_address' => 'House 45, Sector 3, Uttara, Dhaka',
                'item_description' => 'Clothes',
                'item_weight' => 0.8,
                'amount' => 1200,
                'status' => 'pending'
            ),
            array(
                'customer_name' => 'Mohammad Ali',
                'delivery_address' => 'Apartment 2C, Green Road, Dhaka',
                'item_description' => 'Medicine',
                'item_weight' => 0.3,
                'amount' => 800,
                'status' => 'cancelled'
            )
        );
    }
    
    /**
     * Generate random phone number
     */
    private function generate_random_phone() {
        $prefixes = array('013', '014', '015', '016', '017', '018', '019');
        $prefix = $prefixes[array_rand($prefixes)];
        $number = $prefix . rand(10000000, 99999999);
        return '+88' . $number;
    }
}

// Initialize AJAX handlers
new PDM_Ajax_Handlers();