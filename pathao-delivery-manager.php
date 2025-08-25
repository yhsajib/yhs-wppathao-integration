<?php
/**
 * Plugin Name: Pathao Delivery Manager
 * Plugin URI: https://example.com/pathao-delivery-manager
 * Description: A comprehensive plugin for managing Pathao deliveries with phone number tracking, order management, and bulk delivery features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: pathao-delivery-manager
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PDM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PDM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PDM_VERSION', '1.0.0');

class PathaoDeliveryManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('init', array($this, 'add_tracking_shortcode'));
        // Database setup
        register_activation_hook(__FILE__, array($this, 'create_tables'));
        
        // Include required files
        $this->includes();
        
        // Initialize WooCommerce integration
        $this->init_woocommerce_integration();
    }
    
    public function init() {
        load_plugin_textdomain('pathao-delivery-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    

    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'pathao-delivery') === false) {
            return;
        }
        
        wp_enqueue_style('pdm-admin-style', PDM_PLUGIN_URL . 'assets/css/admin-style.css', array(), PDM_VERSION);
        wp_enqueue_script('pdm-admin-script', PDM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), PDM_VERSION, true);
        
        // Localize script for AJAX
        wp_localize_script('pdm-admin-script', 'pdm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pdm_ajax_nonce')
        ));
    }
    
    public function enqueue_frontend_scripts() {
        wp_enqueue_script('pdm-frontend-js', PDM_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), PDM_VERSION, true);
        wp_enqueue_style('pdm-frontend-css', PDM_PLUGIN_URL . 'assets/css/frontend.css', array(), PDM_VERSION);
        
        wp_localize_script('pdm-frontend-js', 'pdm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pdm_nonce')
        ));
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Orders table
        $table_orders = $wpdb->prefix . 'pdm_orders';
        $sql_orders = "CREATE TABLE $table_orders (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            phone_number varchar(20) NOT NULL,
            customer_name varchar(255) NOT NULL,
            customer_address text NOT NULL,
            delivery_status varchar(50) DEFAULT 'pending',
            pathao_consignment_id varchar(100),
            is_fake tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY phone_number (phone_number),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        // Tracking history table
        $table_tracking = $wpdb->prefix . 'pdm_tracking_history';
        $sql_tracking = "CREATE TABLE $table_tracking (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            order_id varchar(100) NOT NULL,
            status varchar(100) NOT NULL,
            message text,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_orders);
        dbDelta($sql_tracking);
    }
    
    // Admin page methods
    public function admin_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-dashboard.php';
    }
    
    public function settings_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-settings.php';
    }
    
    public function phone_tracking_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-phone-tracking.php';
    }
    
    public function order_tracking_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-order-tracking.php';
    }
    
    public function bulk_delivery_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-bulk-delivery.php';
    }
    
    public function fake_orders_page() {
        include PDM_PLUGIN_PATH . 'includes/admin-fake-orders.php';
    }
    
    /**
     * Add tracking shortcode
     */
    public function add_tracking_shortcode() {
        add_shortcode('pdm_tracking', array($this, 'tracking_shortcode'));
    }
    
    /**
     * Tracking shortcode callback
     */
    public function tracking_shortcode($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Track Your Order',
            'placeholder' => 'Enter Order ID or Consignment ID',
            'button_text' => 'Track Order'
        ), $atts);
        
        // Enqueue frontend styles and scripts
        wp_enqueue_style('pdm-frontend-style', PDM_PLUGIN_URL . 'assets/css/admin-style.css', array(), PDM_VERSION);
        wp_enqueue_script('pdm-frontend-script', PDM_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), PDM_VERSION, true);
        
        wp_localize_script('pdm-frontend-script', 'pdm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pdm_ajax_nonce')
        ));
        
        ob_start();
        ?>
        <div class="pdm-tracking-widget">
            <h3><?php echo esc_html($atts['title']); ?></h3>
            <form id="pdm-frontend-tracking-form" class="pdm-form">
                <div class="pdm-form-group">
                    <input type="text" id="tracking_id" name="tracking_id" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" required>
                </div>
                <div class="pdm-form-group">
                    <button type="submit" class="pdm-btn pdm-btn-primary"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
            </form>
            <div id="pdm-frontend-tracking-results" style="display: none;"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('#pdm-frontend-tracking-form').on('submit', function(e) {
                e.preventDefault();
                
                var trackingId = $('#tracking_id').val().trim();
                if (!trackingId) {
                    alert('Please enter an Order ID or Consignment ID');
                    return;
                }
                
                $.ajax({
                    url: pdm_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pdm_track_order',
                        order_id: trackingId.startsWith('PDM') ? trackingId : '',
                        consignment_id: !trackingId.startsWith('PDM') ? trackingId : '',
                        nonce: pdm_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            var html = '<div class="pdm-tracking-result">';
                            if (response.data.order) {
                                var order = response.data.order;
                                html += '<h4>Order Information</h4>';
                                html += '<p><strong>Order ID:</strong> ' + order.id + '</p>';
                                html += '<p><strong>Status:</strong> <span class="pdm-status pdm-status-' + order.status + '">' + order.status + '</span></p>';
                                html += '<p><strong>Customer:</strong> ' + order.customer_name + '</p>';
                            }
                            if (response.data.tracking && response.data.tracking.length > 0) {
                                html += '<h4>Tracking History</h4>';
                                html += '<div class="pdm-tracking-timeline">';
                                response.data.tracking.forEach(function(track, index) {
                                    html += '<div class="pdm-tracking-item' + (index === 0 ? ' current' : '') + '">';
                                    html += '<div class="pdm-tracking-status">' + track.status + '</div>';
                                    html += '<div class="pdm-tracking-message">' + track.message + '</div>';
                                    html += '<div class="pdm-tracking-time">' + new Date(track.created_at).toLocaleString() + '</div>';
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            html += '</div>';
                            $('#pdm-frontend-tracking-results').html(html).show();
                        } else {
                            $('#pdm-frontend-tracking-results').html('<div class="pdm-alert pdm-alert-error">' + (response.data || 'Tracking failed') + '</div>').show();
                        }
                    },
                    error: function() {
                        $('#pdm-frontend-tracking-results').html('<div class="pdm-alert pdm-alert-error">An error occurred during tracking</div>').show();
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }
    
    /**
      * Include required files
      */
     private function includes() {
         require_once PDM_PLUGIN_PATH . 'includes/class-pathao-api.php';
         require_once PDM_PLUGIN_PATH . 'includes/functions.php';
         require_once PDM_PLUGIN_PATH . 'admin/admin-pages.php';
         require_once PDM_PLUGIN_PATH . 'includes/ajax-handlers.php';
         require_once PDM_PLUGIN_PATH . 'includes/woocommerce-integration.php';
     }
    
    /**
     * Initialize WooCommerce integration
     */
    private function init_woocommerce_integration() {
        if (class_exists('WooCommerce')) {
            new PDM_WooCommerce_Integration();
        }
    }
}

// Initialize the plugin
new PathaoDeliveryManager();

?>