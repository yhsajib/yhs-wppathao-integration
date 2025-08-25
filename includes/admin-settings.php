<?php

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['pdm_settings_nonce'], 'pdm_settings')) {
    // Save API settings
    update_option('pdm_pathao_sandbox', isset($_POST['pdm_pathao_sandbox']) ? 1 : 0);
    update_option('pdm_pathao_client_id', sanitize_text_field($_POST['pdm_pathao_client_id']));
    update_option('pdm_pathao_client_secret', sanitize_text_field($_POST['pdm_pathao_client_secret']));
    update_option('pdm_pathao_username', sanitize_text_field($_POST['pdm_pathao_username']));
    update_option('pdm_pathao_password', sanitize_text_field($_POST['pdm_pathao_password']));
    update_option('pdm_pathao_store_id', sanitize_text_field($_POST['pdm_pathao_store_id']));
    
    // Save sender information
    update_option('pdm_pathao_sender_name', sanitize_text_field($_POST['pdm_pathao_sender_name']));
    update_option('pdm_pathao_sender_phone', sanitize_text_field($_POST['pdm_pathao_sender_phone']));
    update_option('pdm_pathao_sender_address', sanitize_textarea_field($_POST['pdm_pathao_sender_address']));
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Test API connection
if (isset($_POST['test_connection']) && wp_verify_nonce($_POST['pdm_test_nonce'], 'pdm_test_connection')) {
    $pathao_api = new PathaoAPI();
    $test_result = $pathao_api->test_connection();
    
    if ($test_result['success']) {
        echo '<div class="notice notice-success"><p>API connection successful!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>API connection failed: ' . esc_html($test_result['message']) . '</p></div>';
    }
}

// Get current settings
$sandbox = get_option('pdm_pathao_sandbox', true);
$client_id = get_option('pdm_pathao_client_id', '');
$client_secret = get_option('pdm_pathao_client_secret', '');
$username = get_option('pdm_pathao_username', '');
$password = get_option('pdm_pathao_password', '');
$store_id = get_option('pdm_pathao_store_id', '');
$sender_name = get_option('pdm_pathao_sender_name', '');
$sender_phone = get_option('pdm_pathao_sender_phone', '');
$sender_address = get_option('pdm_pathao_sender_address', '');

?>

<div class="wrap">
    <h1>Pathao Delivery Manager - Settings</h1>
    
    <div class="pdm-settings-container">
        <div class="pdm-settings-main">
            <form method="post" action="">
                <?php wp_nonce_field('pdm_settings', 'pdm_settings_nonce'); ?>
                
                <div class="pdm-card">
                    <h2>API Configuration</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Environment</th>
                            <td>
                                <label>
                                    <input type="checkbox" name="pdm_pathao_sandbox" value="1" <?php checked($sandbox, 1); ?>>
                                    Use Sandbox Environment (for testing)
                                </label>
                                <p class="description">Enable this for testing. Disable for production use.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Client ID</th>
                            <td>
                                <input type="text" name="pdm_pathao_client_id" value="<?php echo esc_attr($client_id); ?>" class="regular-text" required>
                                <p class="description">Your Pathao API Client ID</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Client Secret</th>
                            <td>
                                <input type="password" name="pdm_pathao_client_secret" value="<?php echo esc_attr($client_secret); ?>" class="regular-text" required>
                                <p class="description">Your Pathao API Client Secret</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Username</th>
                            <td>
                                <input type="text" name="pdm_pathao_username" value="<?php echo esc_attr($username); ?>" class="regular-text" required>
                                <p class="description">Your Pathao merchant username</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Password</th>
                            <td>
                                <input type="password" name="pdm_pathao_password" value="<?php echo esc_attr($password); ?>" class="regular-text" required>
                                <p class="description">Your Pathao merchant password</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Store ID</th>
                            <td>
                                <input type="text" name="pdm_pathao_store_id" value="<?php echo esc_attr($store_id); ?>" class="regular-text" required>
                                <p class="description">Your Pathao Store ID</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="pdm-card">
                    <h2>Sender Information</h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Sender Name</th>
                            <td>
                                <input type="text" name="pdm_pathao_sender_name" value="<?php echo esc_attr($sender_name); ?>" class="regular-text" required>
                                <p class="description">Default sender name for deliveries</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sender Phone</th>
                            <td>
                                <input type="text" name="pdm_pathao_sender_phone" value="<?php echo esc_attr($sender_phone); ?>" class="regular-text" required>
                                <p class="description">Default sender phone number</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Sender Address</th>
                            <td>
                                <textarea name="pdm_pathao_sender_address" rows="3" class="large-text" required><?php echo esc_textarea($sender_address); ?></textarea>
                                <p class="description">Default sender address</p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <p class="submit">
                    <input type="submit" name="submit" class="button-primary" value="Save Settings">
                </p>
            </form>
            
            <div class="pdm-card">
                <h2>Test API Connection</h2>
                <p>Test your API credentials to ensure they are working correctly.</p>
                <form method="post" action="">
                    <?php wp_nonce_field('pdm_test_connection', 'pdm_test_nonce'); ?>
                    <p class="submit">
                        <input type="submit" name="test_connection" class="button-secondary" value="Test Connection">
                    </p>
                </form>
            </div>
        </div>
        
        <div class="pdm-settings-sidebar">
            <div class="pdm-card">
                <h3>Getting Started</h3>
                <ol>
                    <li>Sign up for a Pathao merchant account</li>
                    <li>Get your API credentials from Pathao</li>
                    <li>Configure the settings on this page</li>
                    <li>Test the connection</li>
                    <li>Start creating deliveries!</li>
                </ol>
            </div>
            
            <div class="pdm-card">
                <h3>API Documentation</h3>
                <p>For more information about Pathao API:</p>
                <ul>
                    <li><a href="https://merchant.pathao.com" target="_blank">Merchant Portal</a></li>
                    <li><a href="#" target="_blank">API Documentation</a></li>
                    <li><a href="#" target="_blank">Support</a></li>
                </ul>
            </div>
            
            <div class="pdm-card">
                <h3>Plugin Features</h3>
                <ul>
                    <li>✓ Phone number tracking</li>
                    <li>✓ Order history management</li>
                    <li>✓ Real-time order tracking</li>
                    <li>✓ Fake order management</li>
                    <li>✓ Bulk delivery creation</li>
                    <li>✓ API configuration panel</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.pdm-settings-container {
    display: flex;
    gap: 20px;
    margin-top: 20px;
}

.pdm-settings-main {
    flex: 2;
}

.pdm-settings-sidebar {
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

.pdm-card ul, .pdm-card ol {
    margin-left: 20px;
}

.pdm-card a {
    text-decoration: none;
}

.pdm-card a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .pdm-settings-container {
        flex-direction: column;
    }
}
</style>

<?php
// Load cities for reference
$pathao_api = new PathaoAPI();
$cities_result = $pathao_api->get_cities();

if ($cities_result['success'] && !empty($cities_result['data']['data']['data'])) {
    echo '<script>';
    echo 'var pdmCities = ' . json_encode($cities_result['data']['data']['data']) . ';';
    echo '</script>';
}
?>