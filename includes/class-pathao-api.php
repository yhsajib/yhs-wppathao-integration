<?php

if (!defined('ABSPATH')) {
    exit;
}

class PathaoAPI {
    
    private $api_base_url;
    private $client_id;
    private $client_secret;
    private $username;
    private $password;
    private $access_token;
    private $is_sandbox;
    
    public function __construct() {
        $this->is_sandbox = get_option('pdm_pathao_sandbox', true);
        $this->api_base_url = $this->is_sandbox ? 'https://courier-api-sandbox.pathao.com' : 'https://courier-api.pathao.com';
        $this->client_id = get_option('pdm_pathao_client_id', '');
        $this->client_secret = get_option('pdm_pathao_client_secret', '');
        $this->username = get_option('pdm_pathao_username', '');
        $this->password = get_option('pdm_pathao_password', '');
        $this->access_token = get_option('pdm_pathao_access_token', '');
    }
    
    /**
     * Authenticate with Pathao API and get access token
     */
    public function authenticate() {
        $url = $this->api_base_url . '/aladdin/api/v1/issue-token';
        
        $body = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'username' => $this->username,
            'password' => $this->password,
            'grant_type' => 'password'
        );
        
        $response = wp_remote_post($url, array(
            'body' => json_encode($body),
            'headers' => array(
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            update_option('pdm_pathao_access_token', $this->access_token);
            update_option('pdm_pathao_token_expires', time() + $data['expires_in']);
            return array('success' => true, 'token' => $this->access_token);
        }
        
        return array('success' => false, 'message' => 'Authentication failed', 'data' => $data);
    }
    
    /**
     * Check if token is valid and refresh if needed
     */
    private function ensure_valid_token() {
        $expires = get_option('pdm_pathao_token_expires', 0);
        
        if (empty($this->access_token) || time() >= $expires) {
            return $this->authenticate();
        }
        
        return array('success' => true);
    }
    
    /**
     * Make authenticated API request
     */
    private function make_request($endpoint, $method = 'GET', $data = null) {
        $token_check = $this->ensure_valid_token();
        if (!$token_check['success']) {
            return $token_check;
        }
        
        $url = $this->api_base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if ($data && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return array('success' => true, 'data' => $data);
        }
        
        return array('success' => false, 'message' => 'API request failed', 'status_code' => $status_code, 'data' => $data);
    }
    
    /**
     * Get cities list
     */
    public function get_cities() {
        return $this->make_request('/aladdin/api/v1/cities');
    }
    
    /**
     * Get zones by city
     */
    public function get_zones($city_id) {
        return $this->make_request('/aladdin/api/v1/cities/' . $city_id . '/zone-list');
    }
    
    /**
     * Get areas by zone
     */
    public function get_areas($zone_id) {
        return $this->make_request('/aladdin/api/v1/zones/' . $zone_id . '/area-list');
    }
    
    /**
     * Create a delivery order
     */
    public function create_delivery($order_data) {
        $delivery_data = array(
            'store_id' => get_option('pdm_pathao_store_id', ''),
            'merchant_order_id' => $order_data['order_id'],
            'sender_name' => get_option('pdm_pathao_sender_name', ''),
            'sender_phone' => get_option('pdm_pathao_sender_phone', ''),
            'recipient_name' => $order_data['customer_name'],
            'recipient_phone' => $order_data['phone_number'],
            'recipient_address' => $order_data['customer_address'],
            'recipient_city' => $order_data['city_id'],
            'recipient_zone' => $order_data['zone_id'],
            'recipient_area' => $order_data['area_id'],
            'delivery_type' => $order_data['delivery_type'] ?? 48,
            'item_type' => $order_data['item_type'] ?? 2,
            'special_instruction' => $order_data['special_instruction'] ?? '',
            'item_quantity' => $order_data['item_quantity'] ?? 1,
            'item_weight' => $order_data['item_weight'] ?? 0.5,
            'amount_to_collect' => $order_data['amount_to_collect'] ?? 0,
            'item_description' => $order_data['item_description'] ?? 'General Item'
        );
        
        $result = $this->make_request('/aladdin/api/v1/orders', 'POST', $delivery_data);
        
        if ($result['success']) {
            // Save to database
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'pdm_orders',
                array(
                    'order_id' => $order_data['order_id'],
                    'phone_number' => $order_data['phone_number'],
                    'customer_name' => $order_data['customer_name'],
                    'customer_address' => $order_data['customer_address'],
                    'pathao_consignment_id' => $result['data']['consignment_id'] ?? '',
                    'delivery_status' => 'pending'
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s')
            );
            
            // Add tracking history
            $this->add_tracking_history($order_data['order_id'], 'created', 'Order created and submitted to Pathao');
        }
        
        return $result;
    }
    
    /**
     * Track an order
     */
    public function track_order($consignment_id) {
        $result = $this->make_request('/aladdin/api/v1/orders/' . $consignment_id . '/track');
        
        if ($result['success']) {
            // Update local database
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'pdm_orders',
                array('delivery_status' => $result['data']['order_status'] ?? 'unknown'),
                array('pathao_consignment_id' => $consignment_id),
                array('%s'),
                array('%s')
            );
            
            // Add tracking history if status changed
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE pathao_consignment_id = %s",
                $consignment_id
            ));
            
            if ($order) {
                $this->add_tracking_history(
                    $order->order_id,
                    $result['data']['order_status'] ?? 'unknown',
                    $result['data']['delivery_status_message'] ?? 'Status updated'
                );
            }
        }
        
        return $result;
    }
    
    /**
     * Get order details
     */
    public function get_order_details($consignment_id) {
        return $this->make_request('/aladdin/api/v1/orders/' . $consignment_id);
    }
    
    /**
     * Cancel an order
     */
    public function cancel_order($consignment_id) {
        $result = $this->make_request('/aladdin/api/v1/orders/' . $consignment_id . '/cancel', 'PUT');
        
        if ($result['success']) {
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'pdm_orders',
                array('delivery_status' => 'cancelled'),
                array('pathao_consignment_id' => $consignment_id),
                array('%s'),
                array('%s')
            );
            
            $order = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}pdm_orders WHERE pathao_consignment_id = %s",
                $consignment_id
            ));
            
            if ($order) {
                $this->add_tracking_history($order->order_id, 'cancelled', 'Order cancelled');
            }
        }
        
        return $result;
    }
    
    /**
     * Add tracking history entry
     */
    private function add_tracking_history($order_id, $status, $message) {
        global $wpdb;
        
        $wpdb->insert(
            $wpdb->prefix . 'pdm_tracking_history',
            array(
                'order_id' => $order_id,
                'status' => $status,
                'message' => $message
            ),
            array('%s', '%s', '%s')
        );
    }
    
    /**
     * Get price calculation
     */
    public function get_price_calculation($data) {
        return $this->make_request('/aladdin/api/v1/merchant/price-plan', 'POST', $data);
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        $auth_result = $this->authenticate();
        
        if (!$auth_result['success']) {
            return $auth_result;
        }
        
        $cities_result = $this->get_cities();
        
        if ($cities_result['success']) {
            return array('success' => true, 'message' => 'API connection successful');
        }
        
        return array('success' => false, 'message' => 'API connection failed');
    }
}

?>