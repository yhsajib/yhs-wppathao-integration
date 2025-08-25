<?php
/**
 * Admin Pages Handler
 *
 * @package PathaoDeliveryManager
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class PDM_Admin_Pages
 * 
 * Handles all admin page functionality for the Pathao Delivery Manager plugin
 */
class PDM_Admin_Pages {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Pathao Delivery', 'pathao-delivery-manager'),
            __('Pathao Delivery', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-delivery-manager',
            array($this, 'admin_dashboard_page'),
            'dashicons-truck',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Dashboard', 'pathao-delivery-manager'),
            __('Dashboard', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-delivery-manager',
            array($this, 'admin_dashboard_page')
        );
        
        // Order Tracking submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Order Tracking', 'pathao-delivery-manager'),
            __('Order Tracking', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-order-tracking',
            array($this, 'admin_order_tracking_page')
        );
        
        // Phone Tracking submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Phone Tracking', 'pathao-delivery-manager'),
            __('Phone Tracking', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-phone-tracking',
            array($this, 'admin_phone_tracking_page')
        );
        
        // Bulk Delivery submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Bulk Delivery', 'pathao-delivery-manager'),
            __('Bulk Delivery', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-bulk-delivery',
            array($this, 'admin_bulk_delivery_page')
        );
        
        // Fake Orders submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Fake Orders', 'pathao-delivery-manager'),
            __('Fake Orders', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-fake-orders',
            array($this, 'admin_fake_orders_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'pathao-delivery-manager',
            __('Settings', 'pathao-delivery-manager'),
            __('Settings', 'pathao-delivery-manager'),
            'manage_options',
            'pathao-settings',
            array($this, 'admin_settings_page')
        );
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register settings
        register_setting('pathao_delivery_settings', 'pathao_api_settings');
        
        // Add settings sections and fields
        add_settings_section(
            'pathao_api_section',
            __('Pathao API Configuration', 'pathao-delivery-manager'),
            array($this, 'api_section_callback'),
            'pathao_delivery_settings'
        );
        
        add_settings_field(
            'api_base_url',
            __('API Base URL', 'pathao-delivery-manager'),
            array($this, 'api_base_url_callback'),
            'pathao_delivery_settings',
            'pathao_api_section'
        );
        
        add_settings_field(
            'client_id',
            __('Client ID', 'pathao-delivery-manager'),
            array($this, 'client_id_callback'),
            'pathao_delivery_settings',
            'pathao_api_section'
        );
        
        add_settings_field(
            'client_secret',
            __('Client Secret', 'pathao-delivery-manager'),
            array($this, 'client_secret_callback'),
            'pathao_delivery_settings',
            'pathao_api_section'
        );
        
        add_settings_field(
            'username',
            __('Username', 'pathao-delivery-manager'),
            array($this, 'username_callback'),
            'pathao_delivery_settings',
            'pathao_api_section'
        );
        
        add_settings_field(
            'password',
            __('Password', 'pathao-delivery-manager'),
            array($this, 'password_callback'),
            'pathao_delivery_settings',
            'pathao_api_section'
        );
    }
    
    /**
     * Dashboard page callback
     */
    public function admin_dashboard_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-dashboard.php';
    }
    
    /**
     * Order tracking page callback
     */
    public function admin_order_tracking_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-order-tracking.php';
    }
    
    /**
     * Phone tracking page callback
     */
    public function admin_phone_tracking_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-phone-tracking.php';
    }
    
    /**
     * Bulk delivery page callback
     */
    public function admin_bulk_delivery_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-bulk-delivery.php';
    }
    
    /**
     * Fake orders page callback
     */
    public function admin_fake_orders_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-fake-orders.php';
    }
    
    /**
     * Settings page callback
     */
    public function admin_settings_page() {
        include_once PDM_PLUGIN_PATH . 'includes/admin-settings.php';
    }
    
    /**
     * API section callback
     */
    public function api_section_callback() {
        echo '<p>' . __('Configure your Pathao API credentials below:', 'pathao-delivery-manager') . '</p>';
    }
    
    /**
     * API base URL field callback
     */
    public function api_base_url_callback() {
        $options = get_option('pathao_api_settings');
        $value = isset($options['api_base_url']) ? $options['api_base_url'] : 'https://api-hermes.pathao.com';
        echo '<input type="url" name="pathao_api_settings[api_base_url]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . __('Default: https://api-hermes.pathao.com', 'pathao-delivery-manager') . '</p>';
    }
    
    /**
     * Client ID field callback
     */
    public function client_id_callback() {
        $options = get_option('pathao_api_settings');
        $value = isset($options['client_id']) ? $options['client_id'] : '';
        echo '<input type="text" name="pathao_api_settings[client_id]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Client secret field callback
     */
    public function client_secret_callback() {
        $options = get_option('pathao_api_settings');
        $value = isset($options['client_secret']) ? $options['client_secret'] : '';
        echo '<input type="password" name="pathao_api_settings[client_secret]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Username field callback
     */
    public function username_callback() {
        $options = get_option('pathao_api_settings');
        $value = isset($options['username']) ? $options['username'] : '';
        echo '<input type="text" name="pathao_api_settings[username]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
    
    /**
     * Password field callback
     */
    public function password_callback() {
        $options = get_option('pathao_api_settings');
        $value = isset($options['password']) ? $options['password'] : '';
        echo '<input type="password" name="pathao_api_settings[password]" value="' . esc_attr($value) . '" class="regular-text" />';
    }
}

// Initialize the admin pages
new PDM_Admin_Pages();