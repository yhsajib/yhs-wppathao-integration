<?php

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if ($_POST) {
    if (isset($_POST['pdm_create_bulk_delivery']) && wp_verify_nonce($_POST['pdm_nonce'], 'pdm_create_bulk_delivery')) {
        $results = pdm_process_bulk_delivery($_POST);
        
        if ($results['success'] > 0) {
            echo '<div class="notice notice-success is-dismissible"><p>Successfully created ' . $results['success'] . ' deliveries out of ' . $results['total'] . ' attempts.</p></div>';
        }
        
        if ($results['failed'] > 0) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . $results['failed'] . ' deliveries failed to create. Check the results below for details.</p></div>';
        }
        
        if (!empty($results['errors'])) {
            echo '<div class="notice notice-error is-dismissible"><p>Errors encountered: ' . implode(', ', array_slice($results['errors'], 0, 3)) . '</p></div>';
        }
    }
}

?>

<div class="wrap">
    <h1>Bulk Delivery Creation</h1>
    <p>Create multiple Pathao deliveries at once using CSV upload or manual entry. This feature helps you process large numbers of orders efficiently.</p>
    
    <div class="pdm-bulk-delivery-container">
        <!-- Bulk Creation Methods -->
        <div class="pdm-card">
            <h2>Choose Bulk Creation Method</h2>
            
            <div class="pdm-bulk-tabs">
                <button class="pdm-tab-btn active" data-tab="csv">CSV Upload</button>
                <button class="pdm-tab-btn" data-tab="manual">Manual Entry</button>
                <button class="pdm-tab-btn" data-tab="template">Download Template</button>
            </div>
            
            <!-- CSV Upload Tab -->
            <div id="pdm-bulk-csv" class="pdm-tab-content active">
                <h3>Upload CSV File</h3>
                <p>Upload a CSV file containing delivery information. Make sure your CSV follows the required format.</p>
                
                <form method="post" enctype="multipart/form-data" class="pdm-bulk-form">
                    <?php wp_nonce_field('pdm_create_bulk_delivery', 'pdm_nonce'); ?>
                    <input type="hidden" name="pdm_create_bulk_delivery" value="1">
                    <input type="hidden" name="bulk_method" value="csv">
                    
                    <div class="pdm-form-group">
                        <label for="csv_file">CSV File:</label>
                        <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
                        <p class="description">Select a CSV file with delivery information. Maximum file size: 2MB</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="csv_has_header">File has header row:</label>
                        <input type="checkbox" id="csv_has_header" name="csv_has_header" value="1" checked>
                        <p class="description">Check if your CSV file has column headers in the first row</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="test_mode">Test Mode:</label>
                        <input type="checkbox" id="test_mode" name="test_mode" value="1">
                        <p class="description">Enable test mode to validate data without creating actual deliveries</p>
                    </div>
                    
                    <div class="pdm-form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-upload"></span>
                            Process CSV File
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Manual Entry Tab -->
            <div id="pdm-bulk-manual" class="pdm-tab-content">
                <h3>Manual Entry</h3>
                <p>Enter delivery information manually, one per line. Use the format shown in the example below.</p>
                
                <form method="post" class="pdm-bulk-form">
                    <?php wp_nonce_field('pdm_create_bulk_delivery', 'pdm_nonce'); ?>
                    <input type="hidden" name="pdm_create_bulk_delivery" value="1">
                    <input type="hidden" name="bulk_method" value="manual">
                    
                    <div class="pdm-form-group">
                        <label for="manual_data">Delivery Data (one per line):</label>
                        <textarea id="manual_data" name="manual_data" rows="10" class="large-text" placeholder="Customer Name, Phone, Address, Item Description, Weight, Amount&#10;John Doe, 01712345678, House 123 Road 5 Dhanmondi Dhaka, Mobile Phone, 0.5, 1500&#10;Jane Smith, 01812345679, Flat 4B Building 7 Gulshan 2 Dhaka, Laptop, 2.0, 3000" required></textarea>
                        <p class="description">Format: Customer Name, Phone, Address, Item Description, Weight (kg), Amount to Collect (BDT)</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="manual_test_mode">Test Mode:</label>
                        <input type="checkbox" id="manual_test_mode" name="test_mode" value="1">
                        <p class="description">Enable test mode to validate data without creating actual deliveries</p>
                    </div>
                    
                    <div class="pdm-form-actions">
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-admin-page"></span>
                            Process Manual Data
                        </button>
                        <button type="button" id="pdm-add-sample-data" class="button button-secondary">
                            <span class="dashicons dashicons-randomize"></span>
                            Add Sample Data
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Template Download Tab -->
            <div id="pdm-bulk-template" class="pdm-tab-content">
                <h3>CSV Template</h3>
                <p>Download a CSV template with the correct format and sample data to get started quickly.</p>
                
                <div class="pdm-template-info">
                    <h4>Required Columns:</h4>
                    <ul>
                        <li><strong>customer_name</strong> - Full name of the customer</li>
                        <li><strong>phone_number</strong> - Customer phone number (01XXXXXXXXX format)</li>
                        <li><strong>delivery_address</strong> - Complete delivery address</li>
                        <li><strong>item_description</strong> - Description of the item to be delivered</li>
                        <li><strong>item_weight</strong> - Weight of the item in kg</li>
                        <li><strong>amount_to_collect</strong> - Amount to collect in BDT (0 for prepaid)</li>
                    </ul>
                    
                    <h4>Optional Columns:</h4>
                    <ul>
                        <li><strong>delivery_type</strong> - normal, express (default: normal)</li>
                        <li><strong>item_type</strong> - document, parcel, fragile (default: parcel)</li>
                        <li><strong>item_quantity</strong> - Number of items (default: 1)</li>
                        <li><strong>special_instruction</strong> - Special delivery instructions</li>
                    </ul>
                </div>
                
                <div class="pdm-template-actions">
                    <a href="<?php echo admin_url('admin-ajax.php?action=pdm_download_template&nonce=' . wp_create_nonce('pdm_nonce')); ?>" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        Download CSV Template
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=pdm_download_sample&nonce=' . wp_create_nonce('pdm_nonce')); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        Download Sample Data
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Bulk Creation Settings -->
        <div class="pdm-card">
            <h2>Bulk Creation Settings</h2>
            <p>Configure default settings for bulk delivery creation.</p>
            
            <form id="pdm-bulk-settings-form">
                <div class="pdm-settings-grid">
                    <div class="pdm-form-group">
                        <label for="default_delivery_type">Default Delivery Type:</label>
                        <select id="default_delivery_type" name="default_delivery_type" class="regular-text">
                            <?php foreach (pdm_get_delivery_type_options() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="default_item_type">Default Item Type:</label>
                        <select id="default_item_type" name="default_item_type" class="regular-text">
                            <?php foreach (pdm_get_item_type_options() as $key => $label): ?>
                                <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="batch_size">Batch Size:</label>
                        <input type="number" id="batch_size" name="batch_size" class="regular-text" value="10" min="1" max="50">
                        <p class="description">Number of deliveries to process at once (1-50)</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="delay_between_batches">Delay Between Batches (seconds):</label>
                        <input type="number" id="delay_between_batches" name="delay_between_batches" class="regular-text" value="2" min="1" max="10">
                        <p class="description">Delay to prevent API rate limiting</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="auto_retry_failed">Auto Retry Failed:</label>
                        <input type="checkbox" id="auto_retry_failed" name="auto_retry_failed" value="1">
                        <p class="description">Automatically retry failed deliveries once</p>
                    </div>
                    
                    <div class="pdm-form-group">
                        <label for="send_notifications">Send Notifications:</label>
                        <input type="checkbox" id="send_notifications" name="send_notifications" value="1" checked>
                        <p class="description">Send email notifications when bulk creation is complete</p>
                    </div>
                </div>
                
                <div class="pdm-form-actions">
                    <button type="button" id="pdm-save-settings" class="button button-secondary">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Recent Bulk Operations -->
        <div class="pdm-card">
            <h2>Recent Bulk Operations</h2>
            <div id="pdm-recent-operations">
                <p>Loading recent operations...</p>
            </div>
        </div>
        
        <!-- Bulk Creation Progress -->
        <div id="pdm-bulk-progress" class="pdm-card" style="display: none;">
            <h2>Bulk Creation Progress</h2>
            <div class="pdm-progress-container">
                <div class="pdm-progress-bar">
                    <div class="pdm-progress-fill" style="width: 0%"></div>
                </div>
                <div class="pdm-progress-text">Preparing...</div>
            </div>
            
            <div class="pdm-progress-stats">
                <div class="pdm-stat-item">
                    <span class="pdm-stat-label">Total:</span>
                    <span class="pdm-stat-value" id="pdm-total-count">0</span>
                </div>
                <div class="pdm-stat-item">
                    <span class="pdm-stat-label">Processed:</span>
                    <span class="pdm-stat-value" id="pdm-processed-count">0</span>
                </div>
                <div class="pdm-stat-item">
                    <span class="pdm-stat-label">Success:</span>
                    <span class="pdm-stat-value" id="pdm-success-count">0</span>
                </div>
                <div class="pdm-stat-item">
                    <span class="pdm-stat-label">Failed:</span>
                    <span class="pdm-stat-value" id="pdm-failed-count">0</span>
                </div>
            </div>
            
            <div class="pdm-progress-actions">
                <button type="button" id="pdm-cancel-bulk" class="button button-secondary">
                    <span class="dashicons dashicons-no"></span>
                    Cancel Operation
                </button>
            </div>
        </div>
        
        <!-- Bulk Results -->
        <div id="pdm-bulk-results" class="pdm-card" style="display: none;">
            <h2>Bulk Creation Results</h2>
            <div id="pdm-results-content"></div>
        </div>
    </div>
</div>

<style>
.pdm-bulk-delivery-container {
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

.pdm-bulk-tabs {
    display: flex;
    margin-bottom: 20px;
    border-bottom: 1px solid #ddd;
}

.pdm-tab-btn {
    padding: 12px 24px;
    background: none;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    font-weight: bold;
    color: #666;
    transition: all 0.3s ease;
}

.pdm-tab-btn.active {
    color: #0073aa;
    border-bottom-color: #0073aa;
}

.pdm-tab-btn:hover {
    color: #0073aa;
    background: #f7f7f7;
}

.pdm-tab-content {
    display: none;
    animation: fadeIn 0.3s ease-in;
}

.pdm-tab-content.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.pdm-bulk-form {
    margin-top: 20px;
}

.pdm-form-group {
    margin-bottom: 20px;
}

.pdm-form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #333;
}

.pdm-form-group input,
.pdm-form-group select,
.pdm-form-group textarea {
    width: 100%;
    max-width: 600px;
}

.pdm-form-group .description {
    margin-top: 5px;
    font-style: italic;
    color: #666;
    font-size: 13px;
}

.pdm-form-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.pdm-settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.pdm-template-info {
    background: #f7f7f7;
    padding: 20px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.pdm-template-info h4 {
    margin-top: 0;
    color: #333;
}

.pdm-template-info ul {
    margin: 10px 0;
    padding-left: 20px;
}

.pdm-template-info li {
    margin-bottom: 5px;
}

.pdm-template-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.pdm-progress-container {
    margin-bottom: 20px;
}

.pdm-progress-bar {
    width: 100%;
    height: 24px;
    background: #f0f0f0;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 10px;
    position: relative;
}

.pdm-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0073aa, #005a87);
    transition: width 0.3s ease;
    border-radius: 12px;
}

.pdm-progress-text {
    text-align: center;
    font-weight: bold;
    color: #333;
}

.pdm-progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
}

.pdm-stat-item {
    text-align: center;
}

.pdm-stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    margin-bottom: 5px;
}

.pdm-stat-value {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.pdm-progress-actions {
    text-align: center;
}

.pdm-results-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.pdm-results-table th,
.pdm-results-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.pdm-results-table th {
    background-color: #f7f7f7;
    font-weight: bold;
}

.pdm-results-table tr:hover {
    background-color: #f9f9f9;
}

.pdm-status-success {
    color: #28a745;
    font-weight: bold;
}

.pdm-status-failed {
    color: #dc3545;
    font-weight: bold;
}

.pdm-status-pending {
    color: #ffc107;
    font-weight: bold;
}

.pdm-recent-operation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
    background: #f9f9f9;
}

.pdm-operation-info {
    flex: 1;
}

.pdm-operation-title {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.pdm-operation-details {
    font-size: 13px;
    color: #666;
}

.pdm-operation-stats {
    display: flex;
    gap: 15px;
    align-items: center;
}

.pdm-operation-stat {
    text-align: center;
}

.pdm-operation-stat-value {
    display: block;
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.pdm-operation-stat-label {
    display: block;
    font-size: 11px;
    color: #666;
    text-transform: uppercase;
}

@media (max-width: 768px) {
    .pdm-bulk-tabs {
        flex-direction: column;
    }
    
    .pdm-tab-btn {
        border-bottom: none;
        border-left: 3px solid transparent;
    }
    
    .pdm-tab-btn.active {
        border-left-color: #0073aa;
        border-bottom-color: transparent;
    }
    
    .pdm-settings-grid {
        grid-template-columns: 1fr;
    }
    
    .pdm-form-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .pdm-template-actions {
        flex-direction: column;
    }
    
    .pdm-progress-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .pdm-recent-operation {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .pdm-operation-stats {
        width: 100%;
        justify-content: space-around;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    var bulkInProgress = false;
    var bulkCancelled = false;
    
    // Load settings and recent operations
    loadBulkSettings();
    loadRecentOperations();
    
    // Tab switching
    $('.pdm-tab-btn').on('click', function() {
        var tab = $(this).data('tab');
        
        $('.pdm-tab-btn').removeClass('active');
        $('.pdm-tab-content').removeClass('active');
        
        $(this).addClass('active');
        $('#pdm-bulk-' + tab).addClass('active');
    });
    
    // Add sample data
    $('#pdm-add-sample-data').on('click', function() {
        var sampleData = [
            'John Doe, 01712345678, House 123 Road 5 Dhanmondi Dhaka, Mobile Phone, 0.5, 1500',
            'Jane Smith, 01812345679, Flat 4B Building 7 Gulshan 2 Dhaka, Laptop, 2.0, 3000',
            'Ahmed Rahman, 01912345680, Village Rampur Post Savar Dhaka, Books, 1.0, 500',
            'Fatima Khan, 01612345681, House 45 Sector 3 Uttara Dhaka, Clothes, 0.8, 1200',
            'Mohammad Ali, 01512345682, Apartment 2C Green Road Dhaka, Medicine, 0.3, 800'
        ];
        
        $('#manual_data').val(sampleData.join('\n'));
    });
    
    // Save settings
    $('#pdm-save-settings').on('click', function() {
        var settings = {
            default_delivery_type: $('#default_delivery_type').val(),
            default_item_type: $('#default_item_type').val(),
            batch_size: $('#batch_size').val(),
            delay_between_batches: $('#delay_between_batches').val(),
            auto_retry_failed: $('#auto_retry_failed').is(':checked'),
            send_notifications: $('#send_notifications').is(':checked')
        };
        
        $.post(ajaxurl, {
            action: 'pdm_save_bulk_settings',
            settings: settings,
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                alert('Settings saved successfully!');
            } else {
                alert('Error saving settings: ' + response.data);
            }
        });
    });
    
    // Handle bulk form submissions
    $('.pdm-bulk-form').on('submit', function(e) {
        if (bulkInProgress) {
            e.preventDefault();
            alert('A bulk operation is already in progress. Please wait for it to complete.');
            return;
        }
        
        var method = $(this).find('input[name="bulk_method"]').val();
        var testMode = $(this).find('input[name="test_mode"]').is(':checked');
        
        if (!testMode && !confirm('Are you sure you want to create these deliveries? This action cannot be undone.')) {
            e.preventDefault();
            return;
        }
        
        // Show progress for AJAX submission
        if (method === 'csv' || method === 'manual') {
            e.preventDefault();
            processBulkDelivery($(this));
        }
    });
    
    // Cancel bulk operation
    $('#pdm-cancel-bulk').on('click', function() {
        if (confirm('Are you sure you want to cancel the bulk operation?')) {
            bulkCancelled = true;
            $(this).prop('disabled', true).text('Cancelling...');
        }
    });
    
    function processBulkDelivery($form) {
        bulkInProgress = true;
        bulkCancelled = false;
        
        var formData = new FormData($form[0]);
        formData.append('action', 'pdm_process_bulk_delivery_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce('pdm_nonce'); ?>');
        
        // Show progress
        $('#pdm-bulk-progress').show();
        $('#pdm-bulk-results').hide();
        
        // Reset progress
        updateProgress(0, 'Preparing bulk delivery...');
        resetStats();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    startBulkProcessing(response.data);
                } else {
                    showError('Error preparing bulk delivery: ' + response.data);
                    bulkInProgress = false;
                }
            },
            error: function() {
                showError('An error occurred while preparing the bulk delivery.');
                bulkInProgress = false;
            }
        });
    }
    
    function startBulkProcessing(data) {
        var deliveries = data.deliveries;
        var batchSize = parseInt(data.settings.batch_size) || 10;
        var delay = parseInt(data.settings.delay_between_batches) * 1000 || 2000;
        
        var totalCount = deliveries.length;
        var processedCount = 0;
        var successCount = 0;
        var failedCount = 0;
        var results = [];
        
        $('#pdm-total-count').text(totalCount);
        
        function processBatch(startIndex) {
            if (bulkCancelled || startIndex >= totalCount) {
                completeBulkProcessing(results, successCount, failedCount);
                return;
            }
            
            var endIndex = Math.min(startIndex + batchSize, totalCount);
            var batch = deliveries.slice(startIndex, endIndex);
            
            updateProgress(
                (startIndex / totalCount) * 100,
                'Processing batch ' + Math.ceil((startIndex + 1) / batchSize) + '...'
            );
            
            $.post(ajaxurl, {
                action: 'pdm_process_delivery_batch',
                batch: batch,
                nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    var batchResults = response.data.results;
                    
                    batchResults.forEach(function(result) {
                        processedCount++;
                        if (result.success) {
                            successCount++;
                        } else {
                            failedCount++;
                        }
                        results.push(result);
                    });
                    
                    updateStats(processedCount, successCount, failedCount);
                    
                    // Process next batch after delay
                    setTimeout(function() {
                        processBatch(endIndex);
                    }, delay);
                } else {
                    // Handle batch failure
                    for (var i = startIndex; i < endIndex; i++) {
                        processedCount++;
                        failedCount++;
                        results.push({
                            success: false,
                            data: deliveries[i],
                            error: 'Batch processing failed'
                        });
                    }
                    
                    updateStats(processedCount, successCount, failedCount);
                    
                    setTimeout(function() {
                        processBatch(endIndex);
                    }, delay);
                }
            }).fail(function() {
                // Handle request failure
                for (var i = startIndex; i < endIndex; i++) {
                    processedCount++;
                    failedCount++;
                    results.push({
                        success: false,
                        data: deliveries[i],
                        error: 'Request failed'
                    });
                }
                
                updateStats(processedCount, successCount, failedCount);
                
                setTimeout(function() {
                    processBatch(endIndex);
                }, delay);
            });
        }
        
        processBatch(0);
    }
    
    function completeBulkProcessing(results, successCount, failedCount) {
        bulkInProgress = false;
        
        updateProgress(100, bulkCancelled ? 'Operation cancelled' : 'Bulk delivery completed!');
        
        // Show results
        displayBulkResults(results, successCount, failedCount);
        
        // Reload recent operations
        loadRecentOperations();
        
        // Reset cancel button
        $('#pdm-cancel-bulk').prop('disabled', false).html('<span class="dashicons dashicons-no"></span> Cancel Operation');
    }
    
    function displayBulkResults(results, successCount, failedCount) {
        var $results = $('#pdm-bulk-results');
        var $content = $('#pdm-results-content');
        
        var html = '<div class="pdm-results-summary">';
        html += '<h3>Summary</h3>';
        html += '<p><strong>' + successCount + '</strong> deliveries created successfully, <strong>' + failedCount + '</strong> failed.</p>';
        html += '</div>';
        
        if (results.length > 0) {
            html += '<h3>Detailed Results</h3>';
            html += '<table class="pdm-results-table">';
            html += '<thead><tr><th>Customer</th><th>Phone</th><th>Status</th><th>Consignment ID</th><th>Error</th></tr></thead><tbody>';
            
            results.forEach(function(result) {
                html += '<tr>';
                html += '<td>' + escapeHtml(result.data.customer_name || 'N/A') + '</td>';
                html += '<td>' + escapeHtml(result.data.phone_number || 'N/A') + '</td>';
                
                if (result.success) {
                    html += '<td><span class="pdm-status-success">SUCCESS</span></td>';
                    html += '<td>' + escapeHtml(result.consignment_id || 'N/A') + '</td>';
                    html += '<td>-</td>';
                } else {
                    html += '<td><span class="pdm-status-failed">FAILED</span></td>';
                    html += '<td>-</td>';
                    html += '<td>' + escapeHtml(result.error || 'Unknown error') + '</td>';
                }
                
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        $content.html(html);
        $results.show();
    }
    
    function updateProgress(percentage, text) {
        $('.pdm-progress-fill').css('width', percentage + '%');
        $('.pdm-progress-text').text(text);
    }
    
    function updateStats(processed, success, failed) {
        $('#pdm-processed-count').text(processed);
        $('#pdm-success-count').text(success);
        $('#pdm-failed-count').text(failed);
    }
    
    function resetStats() {
        $('#pdm-processed-count').text('0');
        $('#pdm-success-count').text('0');
        $('#pdm-failed-count').text('0');
    }
    
    function loadBulkSettings() {
        $.post(ajaxurl, {
            action: 'pdm_get_bulk_settings',
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                var settings = response.data;
                $('#default_delivery_type').val(settings.default_delivery_type || 'normal');
                $('#default_item_type').val(settings.default_item_type || 'parcel');
                $('#batch_size').val(settings.batch_size || 10);
                $('#delay_between_batches').val(settings.delay_between_batches || 2);
                $('#auto_retry_failed').prop('checked', settings.auto_retry_failed || false);
                $('#send_notifications').prop('checked', settings.send_notifications !== false);
            }
        });
    }
    
    function loadRecentOperations() {
        $.post(ajaxurl, {
            action: 'pdm_get_recent_bulk_operations',
            nonce: '<?php echo wp_create_nonce('pdm_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                displayRecentOperations(response.data);
            } else {
                $('#pdm-recent-operations').html('<p>No recent operations found.</p>');
            }
        });
    }
    
    function displayRecentOperations(operations) {
        var $container = $('#pdm-recent-operations');
        
        if (operations.length === 0) {
            $container.html('<p>No recent bulk operations found.</p>');
            return;
        }
        
        var html = '';
        operations.forEach(function(operation) {
            html += '<div class="pdm-recent-operation">';
            html += '<div class="pdm-operation-info">';
            html += '<div class="pdm-operation-title">' + escapeHtml(operation.title) + '</div>';
            html += '<div class="pdm-operation-details">' + escapeHtml(operation.details) + '</div>';
            html += '</div>';
            html += '<div class="pdm-operation-stats">';
            html += '<div class="pdm-operation-stat">';
            html += '<span class="pdm-operation-stat-value">' + operation.total + '</span>';
            html += '<span class="pdm-operation-stat-label">Total</span>';
            html += '</div>';
            html += '<div class="pdm-operation-stat">';
            html += '<span class="pdm-operation-stat-value">' + operation.success + '</span>';
            html += '<span class="pdm-operation-stat-label">Success</span>';
            html += '</div>';
            html += '<div class="pdm-operation-stat">';
            html += '<span class="pdm-operation-stat-value">' + operation.failed + '</span>';
            html += '<span class="pdm-operation-stat-label">Failed</span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });
        
        $container.html(html);
    }
    
    function showError(message) {
        $('#pdm-bulk-progress').hide();
        alert(message);
    }
    
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
});
</script>

<?php

// Helper function to process bulk delivery
function pdm_process_bulk_delivery($data) {
    $results = [
        'total' => 0,
        'success' => 0,
        'failed' => 0,
        'errors' => []
    ];
    
    $method = $data['bulk_method'];
    $test_mode = isset($data['test_mode']);
    
    if ($method === 'csv') {
        // Process CSV file
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $results['errors'][] = 'CSV file upload failed';
            return $results;
        }
        
        $csv_data = file_get_contents($_FILES['csv_file']['tmp_name']);
        $lines = str_getcsv($csv_data, "\n");
        
        $has_header = isset($data['csv_has_header']);
        if ($has_header && count($lines) > 0) {
            array_shift($lines); // Remove header
        }
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $fields = str_getcsv($line);
            if (count($fields) < 6) {
                $results['errors'][] = 'Invalid CSV format: ' . $line;
                $results['failed']++;
                continue;
            }
            
            $delivery_data = [
                'customer_name' => $fields[0],
                'phone_number' => $fields[1],
                'delivery_address' => $fields[2],
                'item_description' => $fields[3],
                'item_weight' => floatval($fields[4]),
                'amount_to_collect' => floatval($fields[5])
            ];
            
            $results['total']++;
            
            if (!$test_mode) {
                $result = pdm_create_pathao_delivery($delivery_data);
                if ($result) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = 'Failed to create delivery for: ' . $delivery_data['customer_name'];
                }
            } else {
                $results['success']++; // In test mode, assume success
            }
        }
    } elseif ($method === 'manual') {
        // Process manual data
        $manual_data = $data['manual_data'];
        $lines = explode("\n", $manual_data);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            $fields = array_map('trim', explode(',', $line));
            if (count($fields) < 6) {
                $results['errors'][] = 'Invalid format: ' . $line;
                $results['failed']++;
                continue;
            }
            
            $delivery_data = [
                'customer_name' => $fields[0],
                'phone_number' => $fields[1],
                'delivery_address' => $fields[2],
                'item_description' => $fields[3],
                'item_weight' => floatval($fields[4]),
                'amount_to_collect' => floatval($fields[5])
            ];
            
            $results['total']++;
            
            if (!$test_mode) {
                $result = pdm_create_pathao_delivery($delivery_data);
                if ($result) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = 'Failed to create delivery for: ' . $delivery_data['customer_name'];
                }
            } else {
                $results['success']++; // In test mode, assume success
            }
        }
    }
    
    return $results;
}

// Helper function to create Pathao delivery
function pdm_create_pathao_delivery($data) {
    // This would integrate with the Pathao API
    // For now, we'll create a fake order in the database
    return pdm_create_fake_order($data);
}

?>