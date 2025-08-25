# Pathao Delivery Manager WordPress Plugin

A comprehensive WordPress plugin for managing Pathao delivery services with advanced tracking, bulk operations, and testing capabilities.

## Features

### 🔍 **Phone Number Tracking**
- Search orders by customer phone number
- View complete order history for any phone number
- Recent search history with quick access
- Advanced phone number validation for Bangladesh numbers

### 📦 **Order Tracking**
- Track individual orders by Order ID or Pathao Consignment ID
- Bulk order tracking via CSV upload or manual entry
- Real-time status updates from Pathao API
- Detailed tracking timeline with status history

### 🧪 **Fake Order Management**
- Create fake orders for testing purposes
- Bulk fake order generation with sample data
- Complete CRUD operations for test orders
- Export fake orders for analysis

### 🚚 **Bulk Delivery Creation**
- Create multiple deliveries at once via CSV upload
- Manual bulk entry with comma-separated data
- Test mode for safe bulk operations
- Batch processing with configurable delays
- Detailed success/failure reporting

### ⚙️ **Pathao API Configuration**
- Easy setup with sandbox and production modes
- Secure credential management
- API connection testing
- Default sender information configuration

### 📊 **Dashboard & Analytics**
- Order statistics and status distribution
- Recent orders overview
- Quick action buttons for common tasks
- System information and API status

## Installation

### Method 1: Manual Installation

1. **Download the Plugin**
   - Download the plugin files to your computer
   - Extract the ZIP file if necessary

2. **Upload to WordPress**
   ```
   wp-content/plugins/pathao-delivery-manager/
   ```

3. **Activate the Plugin**
   - Go to WordPress Admin → Plugins
   - Find "Pathao Delivery Manager"
   - Click "Activate"

### Method 2: WordPress Admin Upload

1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Choose the plugin ZIP file
4. Click "Install Now" then "Activate"

## Configuration

### 1. API Settings

1. **Navigate to Settings**
   - Go to WordPress Admin → Pathao Delivery → Settings

2. **Configure API Credentials**
   ```
   Sandbox Mode: Enable for testing
   Client ID: Your Pathao API Client ID
   Client Secret: Your Pathao API Client Secret
   Username: Your Pathao merchant username
   Password: Your Pathao merchant password
   Store ID: Your Pathao store ID
   ```

3. **Set Default Sender Information**
   ```
   Sender Name: Your business name
   Sender Phone: Your contact number
   Sender Address: Your pickup address
   ```

4. **Test Connection**
   - Click "Test API Connection" to verify settings
   - Ensure you see "API connection successful"

### 2. Database Setup

The plugin automatically creates necessary database tables:
- `wp_pdm_orders` - Stores order information
- `wp_pdm_tracking_history` - Stores tracking updates

## Usage Guide

### Phone Number Tracking

1. **Search by Phone**
   - Go to Pathao Delivery → Phone Tracking
   - Enter phone number (supports +88, 88, or 01 formats)
   - Click "Search Orders"
   - View results with order details and actions

2. **Recent Searches**
   - Access previously searched phone numbers
   - Click any recent search to repeat the query
   - Clear search history when needed

### Order Tracking

1. **Single Order Tracking**
   - Go to Pathao Delivery → Order Tracking
   - Enter Order ID or Pathao Consignment ID
   - Click "Track Order"
   - View detailed tracking timeline

2. **Bulk Order Tracking**
   - Use the "Bulk Tracking" tab
   - Upload CSV file or enter order IDs manually
   - Process multiple orders simultaneously
   - Download results for record keeping

### Fake Order Management

1. **Create Single Fake Order**
   - Go to Pathao Delivery → Fake Orders
   - Fill in customer details
   - Select order status
   - Click "Create Fake Order"

2. **Bulk Fake Order Creation**
   - Use "Bulk Creation" section
   - Specify number of orders (1-100)
   - Click "Create Bulk Orders"
   - System generates random sample data

3. **Manage Fake Orders**
   - View all fake orders in the list
   - Edit, delete, or view individual orders
   - Clear all fake orders when needed
   - Export fake orders for analysis

### Bulk Delivery Creation

1. **Prepare Data**
   
   **CSV Format:**
   ```csv
   Customer Name,Phone Number,Address,Item Description,Weight,Amount
   John Doe,01712345678,"House 123, Road 5, Dhaka",Mobile Phone,0.5,1500
   Jane Smith,01812345679,"Flat 4B, Building 7, Dhaka",Laptop,2.0,3000
   ```
   
   **Manual Format:**
   ```
   John Doe, 01712345678, House 123 Road 5 Dhaka, Mobile Phone, 0.5, 1500
   Jane Smith, 01812345679, Flat 4B Building 7 Dhaka, Laptop, 2.0, 3000
   ```

2. **Process Deliveries**
   - Go to Pathao Delivery → Bulk Delivery
   - Choose CSV upload or manual entry
   - Enable "Test Mode" for safe testing
   - Configure batch settings if needed
   - Click "Process Deliveries"

3. **Review Results**
   - Check success/failure statistics
   - Review detailed results table
   - Note any errors for correction
   - Save consignment IDs for tracking

## Shortcodes

### Order Tracking Shortcode

Add order tracking functionality to any page or post:

```php
[pdm_tracking]
```

**Attributes:**
- `title` - Custom title (default: "Track Your Order")
- `placeholder` - Input placeholder text
- `button_text` - Submit button text

**Example:**
```php
[pdm_tracking title="Track Your Delivery" placeholder="Enter Order ID" button_text="Track Now"]
```

## API Integration

### Pathao API Endpoints Used

- **Authentication:** `/aladdin/api/v1/issue-token`
- **Cities:** `/aladdin/api/v1/countries/1/city-list`
- **Zones:** `/aladdin/api/v1/cities/{city_id}/zone-list`
- **Areas:** `/aladdin/api/v1/zones/{zone_id}/area-list`
- **Create Order:** `/aladdin/api/v1/orders`
- **Order Details:** `/aladdin/api/v1/orders/{consignment_id}`
- **Price Calculation:** `/aladdin/api/v1/merchant/price-plan`

### Error Handling

The plugin includes comprehensive error handling:
- API connection failures
- Invalid credentials
- Rate limiting
- Network timeouts
- Data validation errors

## Troubleshooting

### Common Issues

1. **API Connection Failed**
   - Verify API credentials in settings
   - Check if sandbox mode is correctly set
   - Ensure internet connectivity
   - Contact Pathao support for API access

2. **Phone Number Not Found**
   - Verify phone number format (+8801XXXXXXXXX)
   - Check if orders exist in database
   - Ensure orders were created through the plugin

3. **Bulk Upload Errors**
   - Verify CSV format matches requirements
   - Check for special characters in data
   - Ensure file size is under 2MB
   - Validate phone numbers in data

4. **Database Errors**
   - Check WordPress database permissions
   - Verify table creation during activation
   - Contact hosting provider if issues persist

### Debug Mode

Enable WordPress debug mode for detailed error logs:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check logs at: `wp-content/debug.log`

## Security Features

- **Nonce Verification:** All AJAX requests are protected
- **Data Sanitization:** All inputs are sanitized and validated
- **Capability Checks:** Admin-only access to sensitive functions
- **SQL Injection Prevention:** Prepared statements for all queries
- **XSS Protection:** Output escaping for all displayed data

## Performance Optimization

- **Caching:** API responses cached for improved performance
- **Batch Processing:** Large operations split into manageable chunks
- **Database Indexing:** Optimized queries with proper indexes
- **Asset Minification:** CSS/JS files optimized for production

## Hooks and Filters

### Actions

```php
// Fired when an order is created
do_action('pdm_order_created', $order_id, $order_data);

// Fired when tracking is updated
do_action('pdm_tracking_updated', $order_id, $tracking_data);

// Fired during bulk processing
do_action('pdm_bulk_process_start', $total_items);
do_action('pdm_bulk_process_complete', $results);
```

### Filters

```php
// Modify order data before creation
$order_data = apply_filters('pdm_order_data', $order_data);

// Customize API request parameters
$api_params = apply_filters('pdm_api_params', $api_params, $endpoint);

// Modify tracking display
$tracking_html = apply_filters('pdm_tracking_html', $html, $tracking_data);
```

## Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **cURL:** Required for API communication
- **JSON:** Required for data processing

## Support

For support and questions:

1. **Documentation:** Check this README file
2. **WordPress Admin:** Use the built-in help sections
3. **Debug Logs:** Enable WordPress debugging
4. **API Issues:** Contact Pathao support directly

## Changelog

### Version 1.0.0
- Initial release
- Phone number tracking functionality
- Order tracking with Pathao API integration
- Fake order management system
- Bulk delivery creation
- Admin dashboard and settings
- Comprehensive AJAX handling
- Security and performance optimizations

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed for WordPress integration with Pathao Delivery Services API.

---

**Note:** This plugin requires valid Pathao API credentials. Contact Pathao to obtain merchant account and API access.