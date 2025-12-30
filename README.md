# JMVC

**A WordPress MVC Framework for Building Structured Applications**

JMVC brings the Model-View-Controller pattern to WordPress, enabling developers to build organized, maintainable applications with clean separation of concerns. Built with support for AJAX routing, Advanced Custom Fields integration, and hierarchical modular architecture (HMVC).

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Architecture Overview](#architecture-overview)
- [Core Components](#core-components)
  - [Controllers](#controllers)
  - [Models](#models)
  - [Views](#views)
  - [Libraries](#libraries)
  - [Configuration](#configuration)
- [Routing](#routing)
- [REST API](#rest-api)
- [Security Features](#security-features)
- [Environment Configuration](#environment-configuration)
- [Sample Application: Task Manager](#sample-application-task-manager)
- [Developer Tools](#developer-tools)
- [HMVC Modules](#hmvc-modules)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Migration Guide](#migration-guide)
- [Contributing](#contributing)
- [License](#license)

---

## Features

- **MVC Architecture** - Clean separation of Models, Views, and Controllers
- **WordPress Integration** - Native support for custom post types and WordPress APIs
- **ACF Integration** - Seamless Advanced Custom Fields support via traits
- **AJAX Routing** - Built-in URL routing through WordPress AJAX handlers
- **REST API Support** - Modern REST API endpoints alongside AJAX routing
- **HMVC Support** - Build modular, self-contained components
- **API Development** - Ready-to-use base controller for JSON APIs
- **Developer Tools** - Logging (JLog) and error alerting (DevAlert) built-in
- **Key-Value Store** - Redis or SQLite backend for caching and sessions
- **Flexible Configuration** - Centralized config management with nested access
- **Security Built-in** - Nonce verification, CSRF protection, input sanitization, and output escaping

---

## Requirements

- **PHP** 7.4 or higher (8.1+ recommended)
- **WordPress** 6.0 or higher
- **Composer** for dependency management

### Dependencies

- **Guzzle** ^7.0 - HTTP client
- **Predis** ^2.0 - Redis client (optional)

### Optional (Recommended)

- **Advanced Custom Fields (ACF)** - For model field management
- **Redis** or **SQLite** - For kvstore backend

---

## Installation

### 1. Clone or Download

```bash
# Clone into your WordPress theme or plugin directory
git clone https://github.com/your-repo/jmvc.git

# Or download and extract to your desired location
```

### 2. Install Dependencies

```bash
cd jmvc
composer install
```

### 3. Bootstrap the Framework

Include the bootstrap file in your theme's `functions.php` or your plugin's main file:

```php
// In functions.php or your plugin file
require_once get_template_directory() . '/jmvc/system/boot.php';
```

### 4. Configure Permalinks

JMVC uses WordPress AJAX for routing. Ensure your permalinks are set to anything other than "Plain" in:

**Settings → Permalinks**

### 5. Add Rewrite Rules (Optional - for clean URLs)

For SEO-friendly URLs, add these rewrite rules:

**Apache (.htaccess):**

```apache
# JMVC Controller Routing
RewriteRule ^controller/(.*)$ /wp-admin/admin-ajax.php?action=pub_controller&path=$1 [L,QSA]
RewriteRule ^admin_controller/(.*)$ /wp-admin/admin-ajax.php?action=admin_controller&path=$1 [L,QSA]
RewriteRule ^resource_controller/(.*)$ /wp-admin/admin-ajax.php?action=resource_controller&path=$1 [L,QSA]

# HMVC Module Routing
RewriteRule ^hmvc_controller/(.*)$ /wp-admin/admin-ajax.php?action=hmvc_controller&path=$1 [L,QSA]
```

**NGINX:**

```nginx
# JMVC Controller Routing
location /controller/ {
    rewrite ^/controller/(.*)$ /wp-admin/admin-ajax.php?action=pub_controller&path=$1 last;
}

location /admin_controller/ {
    rewrite ^/admin_controller/(.*)$ /wp-admin/admin-ajax.php?action=admin_controller&path=$1 last;
}

location /resource_controller/ {
    rewrite ^/resource_controller/(.*)$ /wp-admin/admin-ajax.php?action=resource_controller&path=$1 last;
}
```

---

## Quick Start

### Create Your First Controller

```php
<?php
// controllers/pub/HelloController.php

class HelloController {

    /**
     * Basic action - outputs text
     * URL: /controller/pub/Hello/index
     */
    public function index() {
        echo "Hello, JMVC!";
    }

    /**
     * Action with parameter
     * URL: /controller/pub/Hello/greet/John
     */
    public function greet($name) {
        echo "Hello, " . esc_html($name) . "!";
    }

    /**
     * Render a view
     * URL: /controller/pub/Hello/welcome
     */
    public function welcome() {
        $data = [
            'title' => 'Welcome to JMVC',
            'message' => 'Build WordPress apps with MVC!'
        ];
        JView::show('hello/welcome', $data);
    }
}
```

### Create a View

```php
<?php // views/hello/welcome.php ?>
<!DOCTYPE html>
<html>
<head>
    <title><?= esc_html($title) ?></title>
</head>
<body>
    <h1><?= esc_html($title) ?></h1>
    <p><?= esc_html($message) ?></p>
</body>
</html>
```

### Access Your Controller

Visit these URLs (adjust domain as needed):

- `https://yoursite.com/controller/pub/Hello/index`
- `https://yoursite.com/controller/pub/Hello/greet/World`
- `https://yoursite.com/controller/pub/Hello/welcome`

---

## Architecture Overview

### Directory Structure

```
jmvc/
├── config/                    # Configuration files
│   ├── devalert.php          # Alert settings (Slack/Email)
│   └── kvstore.php           # Key-value store config
├── controllers/               # MVC Controllers
│   ├── pub/                  # Public/frontend controllers
│   ├── admin/                # Admin-only controllers
│   └── resource/             # Resource/API controllers
├── models/                    # MVC Models
├── views/                     # MVC Views (templates)
├── libraries/                 # Reusable service classes
├── modules/                   # HMVC modules (optional)
│   └── {module}/
│       ├── controllers/
│       ├── models/
│       ├── views/
│       └── libraries/
├── assets/
│   └── js/global.js.php      # JavaScript helpers
├── system/                    # Core framework files
│   ├── boot.php              # Bootstrap
│   ├── JBag.php              # Service locator
│   ├── JConfig.php           # Configuration manager
│   ├── controller/
│   │   ├── JController.php   # Controller loader
│   │   └── JControllerAjax.php # AJAX routing
│   ├── model/
│   │   ├── JModel.php        # Model loader
│   │   ├── JModelBase.php    # Base model class
│   │   └── traits/
│   │       └── ACFModelTrait.php
│   ├── view/
│   │   └── JView.php         # View renderer
│   ├── library/
│   │   └── JLib.php          # Library loader
│   └── app/dev/
│       ├── JLog.php          # Logging
│       └── DevAlert.php      # Error alerts
└── vendor/                    # Composer dependencies
```

### Request Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        HTTP Request                              │
│         /controller/pub/Task/show/123                           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    WordPress AJAX Handler                        │
│              wp_ajax_pub_controller                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      JControllerAjax                             │
│           Parses URL: env=pub, controller=Task,                  │
│                    function=show, params=[123]                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        JController                               │
│              Loads controllers/pub/TaskController.php            │
│              Instantiates TaskController                         │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      TaskController                              │
│                    $controller->show(123)                        │
│                                                                  │
│    ┌──────────────┐    ┌──────────────┐    ┌──────────────┐    │
│    │    JModel    │    │    JView     │    │   Response   │    │
│    │  Load Task   │───▶│  Render HTML │───▶│   Output     │    │
│    │   Model      │    │  or JSON     │    │              │    │
│    └──────────────┘    └──────────────┘    └──────────────┘    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Core Components

### Controllers

Controllers handle incoming requests and coordinate between models and views.

#### Loading Controllers

```php
// Load a controller manually (rarely needed - routing handles this)
$controller = JController::load('Task', 'pub');
$controller->index();

// With HMVC module
$controller = JController::load('Task', 'pub', 'mymodule');
```

#### Controller Environments

| Environment | Directory | WordPress Hook | Use Case |
|-------------|-----------|----------------|----------|
| `pub` | `controllers/pub/` | `wp_ajax_pub_controller`, `wp_ajax_nopriv_pub_controller` | Public endpoints (logged in or not) |
| `admin` | `controllers/admin/` | `wp_ajax_admin_controller` | Admin-only endpoints |
| `resource` | `controllers/resource/` | `wp_ajax_resource_controller`, `wp_ajax_nopriv_resource_controller` | Static resources, files |

#### APIController

Extend `APIController` for JSON API endpoints:

```php
<?php
// controllers/pub/ProductAPIController.php

class ProductAPIController extends APIController {

    /**
     * Return JSON success response
     */
    public function list() {
        $products = Product::find(['posts_per_page' => 10]);

        $this->api_success([
            'products' => $products,
            'count' => count($products)
        ]);
    }

    /**
     * Return JSON error response
     */
    public function show($id) {
        $product = new Product($id);

        if (!$product->ID) {
            $this->api_die('Product not found', ['id' => $id]);
        }

        $this->api_success(['product' => $product]);
    }
}
```

**APIController Methods:**

| Method | Description |
|--------|-------------|
| `api_success($result)` | Output `{"success": true, "result": ...}` |
| `api_die($message, $data)` | Output `{"success": false, "error": ...}` and exit |
| `api_result($success, $result)` | Output custom success/fail response |
| `extractFields($data, $fields)` | Filter response to specific fields |
| `cleanParams($params)` | Convert string booleans, parse field lists |

---

### Models

Models represent your data and business logic, with built-in WordPress post type integration.

#### Basic Model

```php
<?php
// models/Product.php

class Product extends JModelBase {
    use ACFModelTrait;

    // Link to WordPress custom post type
    public static $post_type = 'product';

    /**
     * Custom business logic
     */
    public function isOnSale() {
        return $this->sale_price && $this->sale_price < $this->regular_price;
    }

    /**
     * Calculate discount percentage
     */
    public function getDiscountPercent() {
        if (!$this->isOnSale()) return 0;
        return round((1 - $this->sale_price / $this->regular_price) * 100);
    }
}
```

#### Loading Models

```php
// Load model class
JModel::load('Product');

// Create new instance
$product = new Product();

// Load existing by ID
$product = new Product(123);

// Get singleton instance
$product = JModel::load('Product', null, true);
```

#### Querying Records

```php
// Find all products
$products = Product::find();

// With WordPress query args
$products = Product::find([
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC',
    'meta_query' => [
        [
            'key' => 'featured',
            'value' => '1'
        ]
    ]
]);
```

#### Creating & Updating Records

```php
// Create new record
$product = new Product();
$product->post_title = 'New Product';
$product->post_content = 'Description here';
$product->regular_price = 99.99;
$product->sale_price = 79.99;
$id = $product->add();

// Update existing record
$product = new Product(123);
$product->regular_price = 89.99;
$product->save();

// Update specific fields
$product->update(['regular_price' => 89.99]);
```

#### Magic Properties

Models automatically map to WordPress post attributes and ACF fields:

```php
$product = new Product(123);

// WordPress post attributes
echo $product->post_title;      // Post title
echo $product->post_content;    // Post content
echo $product->post_date;       // Created date
echo $product->ID;              // Post ID

// ACF fields (via ACFModelTrait)
echo $product->regular_price;   // get_field('regular_price', 123)
echo $product->sale_price;      // get_field('sale_price', 123)
echo $product->product_image;   // get_field('product_image', 123)
```

---

### Views

Views are PHP templates for rendering HTML output.

#### Rendering Views

```php
// Output view directly
JView::show('products/list', [
    'products' => $products,
    'title' => 'Our Products'
]);

// Get view as string (for emails, AJAX, etc.)
$html = JView::get('emails/order-confirmation', [
    'order' => $order,
    'customer' => $customer
]);
```

#### View Template

```php
<?php // views/products/list.php ?>
<div class="product-listing">
    <h1><?= esc_html($title) ?></h1>

    <?php if (empty($products)): ?>
        <p>No products found.</p>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
                <?php JView::show('products/partials/card', ['product' => $product]); ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
```

#### View Partials

```php
<?php // views/products/partials/card.php ?>
<div class="product-card">
    <h3><?= esc_html($product->post_title) ?></h3>

    <?php if ($product->isOnSale()): ?>
        <span class="badge sale">Save <?= $product->getDiscountPercent() ?>%</span>
        <p class="price">
            <del>$<?= number_format($product->regular_price, 2) ?></del>
            <strong>$<?= number_format($product->sale_price, 2) ?></strong>
        </p>
    <?php else: ?>
        <p class="price">$<?= number_format($product->regular_price, 2) ?></p>
    <?php endif; ?>

    <a href="<?= get_permalink($product->ID) ?>">View Details</a>
</div>
```

---

### Libraries

Libraries are reusable service classes for shared functionality.

#### Creating a Library

```php
<?php
// libraries/EmailService.php

class EmailService {

    private $from_email;
    private $from_name;

    public function __construct() {
        $this->from_email = JConfig::get('email/from_address');
        $this->from_name = JConfig::get('email/from_name');
    }

    public function send($to, $subject, $template, $data = []) {
        $body = JView::get('emails/' . $template, $data);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$this->from_name} <{$this->from_email}>"
        ];

        return wp_mail($to, $subject, $body, $headers);
    }

    public function sendOrderConfirmation($order) {
        return $this->send(
            $order->customer_email,
            'Order Confirmation #' . $order->ID,
            'order-confirmation',
            ['order' => $order]
        );
    }
}
```

#### Loading Libraries

```php
// Load library (returns singleton)
$email = JLib::load('EmailService');
$email->sendOrderConfirmation($order);

// Load from subdirectory
$payment = JLib::load('payments/StripeService');

// Load from HMVC module
$service = JLib::load('CustomService', 'mymodule');
```

---

### Configuration

#### Creating Config Files

```php
<?php
// config/app.php

return [
    'name' => 'My Application',
    'version' => '1.0.0',
    'debug' => WP_DEBUG,

    'email' => [
        'from_address' => 'noreply@example.com',
        'from_name' => 'My App'
    ],

    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100
    ]
];
```

#### Accessing Configuration

```php
// Get single value
$appName = JConfig::get('app/name');

// Get nested value
$fromEmail = JConfig::get('app/email/from_address');

// Get with default
$perPage = JConfig::get('app/pagination/per_page') ?: 10;

// Set value at runtime
JConfig::set('app/debug', true);
```

#### Global Storage (JBag)

For runtime service storage:

```php
// Store a service
JBag::set('current_user', wp_get_current_user());

// Retrieve service
$user = JBag::get('current_user');

// Store kvstore instance
JBag::set('kvstore', new KVStore());
```

---

## Routing

### URL Structure

```
/controller/{env}/{controller}/{function}/{param1}/{param2}/...
```

| Segment | Description |
|---------|-------------|
| `env` | Environment: `pub`, `admin`, or `resource` |
| `controller` | Controller name (without "Controller" suffix) |
| `function` | Method name to call |
| `param1`, `param2`, ... | Method parameters |

### Examples

| URL | Controller | Method | Parameters |
|-----|------------|--------|------------|
| `/controller/pub/Task/index` | `TaskController` | `index()` | none |
| `/controller/pub/Task/show/42` | `TaskController` | `show(42)` | `$id = 42` |
| `/controller/pub/Task/list/pending/10` | `TaskController` | `list('pending', 10)` | `$status, $limit` |
| `/controller/admin/User/edit/5` | `UserController` | `edit(5)` | `$id = 5` |

### Generating URLs in PHP

```php
// Use the helper function
$url = controller_url('pub', 'Task', 'show', 42);
// Returns: /wp-admin/admin-ajax.php?action=pub_controller&path=Task/show/42

// With multiple parameters
$url = controller_url('pub', 'Task', 'list', 'pending', 10);
```

### JavaScript Helpers (JMVC Object)

Include the JavaScript helper:

```html
<script src="<?= get_template_directory_uri() ?>/jmvc/assets/js/global.js.php"></script>
```

The `JMVC` object provides URL helpers, security utilities, and AJAX wrappers:

#### URL Helpers

```javascript
// Site URL
JMVC.siteUrl('about-us')
// https://example.com/about-us

// Controller URL (legacy AJAX)
JMVC.controllerUrl('pub', 'Task', 'show', 42)
// https://example.com/controller/pub/Task/show/42

// REST API URL (recommended)
JMVC.restApiUrl('pub', 'Task', 'index')
// https://example.com/wp-json/jmvc/v1/pub/Task/index
```

#### AJAX with Automatic Nonce

```javascript
// GET request (nonce included automatically)
const response = await JMVC.get(JMVC.restApiUrl('pub', 'Task', 'index'));
const data = await response.json();

// POST JSON data
await JMVC.post(JMVC.restApiUrl('pub', 'Task', 'create'), {
    title: 'New Task',
    list_id: 1
});

// POST form data
await JMVC.postForm(JMVC.controllerUrl('pub', 'Task', 'create'), {
    title: 'New Task'
});
```

#### Security Helpers

```javascript
// Get current nonce
const nonce = JMVC.getNonce();

// Refresh nonce (for long-lived pages)
const newNonce = await JMVC.refreshNonce();
```

#### Query String Parser

```javascript
// URL: https://example.com/?page=2&sort=date
JMVC.qs('page')  // '2'
JMVC.qs('sort')  // 'date'
```

### HMVC Module Routes

For modules, use the HMVC route pattern:

```
/hmvc_controller/{env}/{module}/{controller}/{function}/{params}
```

Example:

```
/hmvc_controller/pub/blog/Post/show/42
```

This loads `modules/blog/controllers/pub/PostController.php` and calls `show(42)`.

---

## REST API

JMVC provides a modern REST API alongside the traditional AJAX routing system.

### Endpoint Format

```
/wp-json/jmvc/v1/{env}/{controller}/{action}/{params}
```

### Examples

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/wp-json/jmvc/v1/pub/Task/index` | List all tasks |
| GET | `/wp-json/jmvc/v1/pub/Task/show/42` | Get task by ID |
| POST | `/wp-json/jmvc/v1/pub/Task/create` | Create a task |
| POST | `/wp-json/jmvc/v1/admin/User/update/5` | Update user (admin only) |

### Authentication

For admin endpoints, requests require authentication. Use `JMVC.get()` or `JMVC.post()` which automatically include the nonce:

```javascript
// Automatic nonce handling with JMVC helpers
const response = await JMVC.get(JMVC.restApiUrl('admin', 'User', 'list'));
const users = await response.json();

// Or manual nonce with fetch
fetch('/wp-json/jmvc/v1/admin/User/list', {
    headers: {
        'X-WP-Nonce': JMVC.getNonce()
    }
});
```

### Using Both AJAX and REST API

The REST API uses the same controllers as AJAX routing:

```php
// controllers/pub/TaskController.php works for both:
// - AJAX: /wp-admin/admin-ajax.php?action=pub_controller&path=Task/index
// - REST: /wp-json/jmvc/v1/pub/Task/index
```

---

## Security Features

JMVC includes comprehensive security features to protect your application.

### Nonce Verification

All AJAX and REST requests can be protected with nonces:

```php
// In your controller
class TaskController extends APIController {

    public function create() {
        // Verify nonce for form submissions
        $nonce = sanitize_text_field($_REQUEST['_jmvc_nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'jmvc_ajax_nonce')) {
            $this->api_die('Security check failed');
        }

        // ... create task
    }
}
```

Generate nonces in your forms:

```php
<form method="POST">
    <input type="hidden" name="_jmvc_nonce" value="<?php echo wp_create_nonce('jmvc_ajax_nonce'); ?>">
    <!-- form fields -->
</form>
```

### Capability Checks

Always verify user capabilities before performing actions:

```php
public function delete($id) {
    if (!current_user_can('delete_posts')) {
        $this->api_die('Permission denied');
    }

    // ... delete logic
}
```

### Input Sanitization

JMVC automatically sanitizes path parameters. Always sanitize user input:

```php
// Text input
$title = sanitize_text_field($_POST['title']);

// Email
$email = sanitize_email($_POST['email']);

// File names
$filename = sanitize_file_name($_POST['filename']);

// Integer values
$id = absint($_POST['id']);

// Textarea/multiline
$content = sanitize_textarea_field($_POST['content']);
```

### Output Escaping

Always escape output to prevent XSS attacks:

```php
// In views
<h1><?php echo esc_html($title); ?></h1>
<a href="<?php echo esc_url($link); ?>">Link</a>
<input value="<?php echo esc_attr($value); ?>">
<?php echo wp_kses_post($html_content); ?>
```

### JSONP Callback Sanitization

JSONP callbacks are automatically sanitized to prevent injection:

```php
// Callback is sanitized to alphanumeric + $ + _ only
$callback = preg_replace('/[^a-zA-Z0-9_\$]/', '', $_REQUEST['callback']);
```

### Path Traversal Prevention

All file paths are validated to prevent directory traversal attacks:

```php
// Paths are validated using realpath()
$real_path = realpath($path);
$real_base = realpath($base_dir);

if ($real_path === false || strpos($real_path, $real_base) !== 0) {
    throw new Exception('Invalid path');
}
```

---

## Environment Configuration

JMVC supports environment variables for sensitive configuration.

### Available Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `JMVC_SLACK_WEBHOOK` | Slack webhook URL for DevAlert | (none) |
| `JMVC_DEVALERT_EMAIL` | Email address for DevAlert | (none) |
| `JMVC_SLACK_CHANNEL` | Slack channel for alerts | `#errors` |
| `JMVC_SLACK_USERNAME` | Slack bot username | `JMVC Alert` |

### Setting Environment Variables

**Using .env file** (with vlucas/phpdotenv):

```bash
JMVC_SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
JMVC_DEVALERT_EMAIL=developer@example.com
JMVC_SLACK_CHANNEL=#alerts
```

**Using wp-config.php**:

```php
define('JMVC_SLACK_WEBHOOK', 'https://hooks.slack.com/services/...');
define('JMVC_DEVALERT_EMAIL', 'developer@example.com');
```

**Using server environment**:

```bash
export JMVC_SLACK_WEBHOOK="https://hooks.slack.com/services/..."
```

### Accessing Configuration

```php
// Get config with fallback
$webhook = jmvc_get_config('JMVC_SLACK_WEBHOOK', 'default_value');

// Check if configured
if ($webhook = jmvc_get_config('JMVC_SLACK_WEBHOOK')) {
    // Send alert
}
```

---

## Sample Application: Task Manager

This complete example demonstrates all JMVC features: models, controllers, views, configuration, and JavaScript integration.

### What We're Building

A task management system with:
- Create, read, update, delete tasks
- Task lists/projects for organization
- Priority levels (low, medium, high)
- Due dates with overdue detection
- Status tracking (pending, in progress, completed)
- JSON API for frontend integration

### Data Model

```
Task
├── ID (WordPress post ID)
├── title (post_title)
├── description (post_content)
├── status: pending | in_progress | completed
├── priority: low | medium | high
├── due_date: date
├── task_list_id: int (reference to TaskList)
├── completed_at: datetime
└── created_at (post_date)

TaskList
├── ID (WordPress post ID)
├── name (post_title)
├── description (post_content)
└── color: hex color string
```

### Step 1: Register Post Types

```php
<?php
// In functions.php or a plugin file

add_action('init', function() {
    // Task post type
    register_post_type('task', [
        'label' => 'Tasks',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-list-view',
        'capability_type' => 'post'
    ]);

    // Task List post type
    register_post_type('task_list', [
        'label' => 'Task Lists',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-category',
        'capability_type' => 'post'
    ]);
});
```

### Step 2: Create the Task Model

```php
<?php
// models/Task.php

class Task extends JModelBase {
    use ACFModelTrait;

    public static $post_type = 'task';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    /**
     * Get all available statuses
     *
     * @return array
     */
    public static function getStatuses() {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }

    /**
     * Get all available priorities
     *
     * @return array
     */
    public static function getPriorities() {
        return [
            self::PRIORITY_LOW => ['label' => 'Low', 'color' => '#28a745'],
            self::PRIORITY_MEDIUM => ['label' => 'Medium', 'color' => '#ffc107'],
            self::PRIORITY_HIGH => ['label' => 'High', 'color' => '#dc3545']
        ];
    }

    /**
     * Find tasks by list ID
     *
     * @param int $list_id
     * @return array
     */
    public static function byList($list_id) {
        return self::find([
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'task_list_id',
                    'value' => $list_id,
                    'type' => 'NUMERIC'
                ]
            ]
        ]);
    }

    /**
     * Find tasks by status
     *
     * @param string $status
     * @return array
     */
    public static function byStatus($status) {
        return self::find([
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'status',
                    'value' => $status
                ]
            ]
        ]);
    }

    /**
     * Find overdue tasks
     *
     * @return array
     */
    public static function overdue() {
        return self::find([
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'due_date',
                    'value' => date('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'status',
                    'value' => self::STATUS_COMPLETED,
                    'compare' => '!='
                ]
            ]
        ]);
    }

    /**
     * Check if task is overdue
     *
     * @return bool
     */
    public function isOverdue() {
        if (!$this->due_date) {
            return false;
        }

        return strtotime($this->due_date) < strtotime('today')
            && $this->status !== self::STATUS_COMPLETED;
    }

    /**
     * Check if task is completed
     *
     * @return bool
     */
    public function isCompleted() {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Mark task as complete
     *
     * @return int|false Post ID on success, false on failure
     */
    public function markComplete() {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = current_time('mysql');
        return $this->save();
    }

    /**
     * Mark task as pending
     *
     * @return int|false
     */
    public function markPending() {
        $this->status = self::STATUS_PENDING;
        $this->completed_at = '';
        return $this->save();
    }

    /**
     * Toggle task completion status
     *
     * @return int|false
     */
    public function toggleComplete() {
        if ($this->isCompleted()) {
            return $this->markPending();
        }
        return $this->markComplete();
    }

    /**
     * Get formatted due date
     *
     * @param string $format Date format (default: 'M j, Y')
     * @return string|null
     */
    public function getDueDateFormatted($format = 'M j, Y') {
        if (!$this->due_date) {
            return null;
        }
        return date($format, strtotime($this->due_date));
    }

    /**
     * Get days until due (negative if overdue)
     *
     * @return int|null
     */
    public function getDaysUntilDue() {
        if (!$this->due_date) {
            return null;
        }

        $due = new DateTime($this->due_date);
        $today = new DateTime('today');
        $diff = $today->diff($due);

        return $diff->invert ? -$diff->days : $diff->days;
    }

    /**
     * Get priority configuration
     *
     * @return array ['label' => string, 'color' => string]
     */
    public function getPriorityConfig() {
        $priorities = self::getPriorities();
        return $priorities[$this->priority] ?? $priorities[self::PRIORITY_MEDIUM];
    }

    /**
     * Get the parent task list
     *
     * @return TaskList|null
     */
    public function getTaskList() {
        if (!$this->task_list_id) {
            return null;
        }

        JModel::load('TaskList');
        return new TaskList($this->task_list_id);
    }

    /**
     * Convert to array for API responses
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->ID,
            'title' => $this->post_title,
            'description' => $this->post_content,
            'status' => $this->status,
            'priority' => $this->priority,
            'priority_label' => $this->getPriorityConfig()['label'],
            'priority_color' => $this->getPriorityConfig()['color'],
            'due_date' => $this->due_date,
            'due_date_formatted' => $this->getDueDateFormatted(),
            'days_until_due' => $this->getDaysUntilDue(),
            'is_overdue' => $this->isOverdue(),
            'is_completed' => $this->isCompleted(),
            'task_list_id' => $this->task_list_id,
            'completed_at' => $this->completed_at,
            'created_at' => $this->post_date
        ];
    }
}
```

### Step 3: Create the TaskList Model

```php
<?php
// models/TaskList.php

class TaskList extends JModelBase {
    use ACFModelTrait;

    public static $post_type = 'task_list';

    // Default colors for task lists
    const DEFAULT_COLORS = [
        '#3498db', // Blue
        '#2ecc71', // Green
        '#9b59b6', // Purple
        '#e74c3c', // Red
        '#f39c12', // Orange
        '#1abc9c', // Teal
        '#34495e', // Dark Gray
        '#e91e63'  // Pink
    ];

    /**
     * Get all task lists with task counts
     *
     * @return array
     */
    public static function allWithCounts() {
        $lists = self::find(['posts_per_page' => -1]);

        JModel::load('Task');

        return array_map(function($list) {
            $tasks = Task::byList($list->ID);
            $list->task_count = count($tasks);
            $list->completed_count = count(array_filter($tasks, function($t) {
                return $t->status === Task::STATUS_COMPLETED;
            }));
            return $list;
        }, $lists);
    }

    /**
     * Get all tasks in this list
     *
     * @param array $args Additional query arguments
     * @return array
     */
    public function getTasks($args = []) {
        JModel::load('Task');
        return Task::byList($this->ID);
    }

    /**
     * Get task count for this list
     *
     * @return int
     */
    public function getTaskCount() {
        return count($this->getTasks());
    }

    /**
     * Get completed task count
     *
     * @return int
     */
    public function getCompletedCount() {
        $tasks = $this->getTasks();
        return count(array_filter($tasks, function($task) {
            return $task->status === Task::STATUS_COMPLETED;
        }));
    }

    /**
     * Get progress percentage
     *
     * @return int
     */
    public function getProgress() {
        $total = $this->getTaskCount();
        if ($total === 0) {
            return 0;
        }
        return round(($this->getCompletedCount() / $total) * 100);
    }

    /**
     * Get the list color or default
     *
     * @return string Hex color
     */
    public function getColor() {
        return $this->color ?: self::DEFAULT_COLORS[0];
    }

    /**
     * Convert to array for API responses
     *
     * @return array
     */
    public function toArray() {
        return [
            'id' => $this->ID,
            'name' => $this->post_title,
            'description' => $this->post_content,
            'color' => $this->getColor(),
            'task_count' => $this->getTaskCount(),
            'completed_count' => $this->getCompletedCount(),
            'progress' => $this->getProgress(),
            'created_at' => $this->post_date
        ];
    }
}
```

### Step 4: Create the Task Controller

```php
<?php
// controllers/pub/TaskController.php

class TaskController extends APIController {

    public function __construct() {
        // Load required models
        JModel::load('Task');
        JModel::load('TaskList');
    }

    /**
     * GET /controller/pub/Task/index
     * List all tasks with optional filters
     *
     * Query params:
     *   - list_id: Filter by task list
     *   - status: Filter by status
     *   - priority: Filter by priority
     *   - overdue: Set to 1 to show only overdue tasks
     */
    public function index() {
        $list_id = isset($_GET['list_id']) ? intval($_GET['list_id']) : null;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        $priority = isset($_GET['priority']) ? sanitize_text_field($_GET['priority']) : null;
        $overdue = isset($_GET['overdue']) ? (bool)$_GET['overdue'] : false;

        // Handle overdue filter separately
        if ($overdue) {
            $tasks = Task::overdue();
        } else {
            // Build query args
            $args = ['posts_per_page' => -1];
            $meta_query = [];

            if ($list_id) {
                $meta_query[] = [
                    'key' => 'task_list_id',
                    'value' => $list_id,
                    'type' => 'NUMERIC'
                ];
            }

            if ($status) {
                $meta_query[] = [
                    'key' => 'status',
                    'value' => $status
                ];
            }

            if ($priority) {
                $meta_query[] = [
                    'key' => 'priority',
                    'value' => $priority
                ];
            }

            if (!empty($meta_query)) {
                $args['meta_query'] = $meta_query;
            }

            $tasks = Task::find($args);
        }

        // Convert to arrays for response
        $result = array_map(function($task) {
            return $task->toArray();
        }, $tasks);

        $this->api_success([
            'tasks' => $result,
            'count' => count($result)
        ]);
    }

    /**
     * GET /controller/pub/Task/show/{id}
     * Get single task details
     *
     * @param int $id Task ID
     */
    public function show($id) {
        $id = intval($id);
        $task = new Task($id);

        if (!$task->ID) {
            $this->api_die('Task not found', ['id' => $id]);
        }

        $this->api_success([
            'task' => $task->toArray()
        ]);
    }

    /**
     * POST /controller/pub/Task/create
     * Create a new task
     *
     * POST params:
     *   - title: (required) Task title
     *   - description: Task description
     *   - priority: low|medium|high (default: medium)
     *   - due_date: YYYY-MM-DD format
     *   - task_list_id: Parent list ID
     */
    public function create() {
        // Validate title
        $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
        if (empty($title)) {
            $this->api_die('Title is required');
        }

        // Sanitize inputs
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $priority = isset($_POST['priority']) ? sanitize_text_field($_POST['priority']) : Task::PRIORITY_MEDIUM;
        $due_date = isset($_POST['due_date']) ? sanitize_text_field($_POST['due_date']) : '';
        $list_id = isset($_POST['task_list_id']) ? intval($_POST['task_list_id']) : 0;

        // Validate priority
        if (!in_array($priority, array_keys(Task::getPriorities()))) {
            $priority = Task::PRIORITY_MEDIUM;
        }

        // Validate due date format
        if ($due_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
            $this->api_die('Invalid date format. Use YYYY-MM-DD');
        }

        // Create task
        $task = new Task();
        $task->post_title = $title;
        $task->post_content = $description;
        $task->status = Task::STATUS_PENDING;
        $task->priority = $priority;
        $task->due_date = $due_date;
        $task->task_list_id = $list_id;

        $id = $task->add();

        if (!$id) {
            JLog::error('Failed to create task: ' . $title);
            $this->api_die('Failed to create task');
        }

        JLog::info('Task created: ' . $id);

        // Return the created task
        $task = new Task($id);
        $this->api_success([
            'id' => $id,
            'task' => $task->toArray(),
            'message' => 'Task created successfully'
        ]);
    }

    /**
     * POST /controller/pub/Task/update/{id}
     * Update an existing task
     *
     * @param int $id Task ID
     */
    public function update($id) {
        $id = intval($id);
        $task = new Task($id);

        if (!$task->ID) {
            $this->api_die('Task not found', ['id' => $id]);
        }

        // Update provided fields
        if (isset($_POST['title'])) {
            $title = sanitize_text_field($_POST['title']);
            if (empty($title)) {
                $this->api_die('Title cannot be empty');
            }
            $task->post_title = $title;
        }

        if (isset($_POST['description'])) {
            $task->post_content = sanitize_textarea_field($_POST['description']);
        }

        if (isset($_POST['status'])) {
            $status = sanitize_text_field($_POST['status']);
            if (in_array($status, array_keys(Task::getStatuses()))) {
                $task->status = $status;
                if ($status === Task::STATUS_COMPLETED) {
                    $task->completed_at = current_time('mysql');
                }
            }
        }

        if (isset($_POST['priority'])) {
            $priority = sanitize_text_field($_POST['priority']);
            if (in_array($priority, array_keys(Task::getPriorities()))) {
                $task->priority = $priority;
            }
        }

        if (isset($_POST['due_date'])) {
            $due_date = sanitize_text_field($_POST['due_date']);
            if ($due_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
                $this->api_die('Invalid date format. Use YYYY-MM-DD');
            }
            $task->due_date = $due_date;
        }

        if (isset($_POST['task_list_id'])) {
            $task->task_list_id = intval($_POST['task_list_id']);
        }

        $result = $task->save();

        if (!$result) {
            $this->api_die('Failed to update task');
        }

        JLog::info('Task updated: ' . $id);

        $this->api_success([
            'task' => $task->toArray(),
            'message' => 'Task updated successfully'
        ]);
    }

    /**
     * POST /controller/pub/Task/delete/{id}
     * Delete a task
     *
     * @param int $id Task ID
     */
    public function delete($id) {
        $id = intval($id);
        $task = new Task($id);

        if (!$task->ID) {
            $this->api_die('Task not found', ['id' => $id]);
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            $this->api_die('Failed to delete task');
        }

        JLog::info('Task deleted: ' . $id);

        $this->api_success([
            'message' => 'Task deleted successfully'
        ]);
    }

    /**
     * POST /controller/pub/Task/toggle/{id}
     * Toggle task completion status
     *
     * @param int $id Task ID
     */
    public function toggle($id) {
        $id = intval($id);
        $task = new Task($id);

        if (!$task->ID) {
            $this->api_die('Task not found', ['id' => $id]);
        }

        $task->toggleComplete();

        $this->api_success([
            'task' => $task->toArray(),
            'message' => $task->isCompleted() ? 'Task completed' : 'Task reopened'
        ]);
    }

    /**
     * GET /controller/pub/Task/stats
     * Get task statistics
     */
    public function stats() {
        $all = Task::find(['posts_per_page' => -1]);
        $overdue = Task::overdue();

        $by_status = [];
        $by_priority = [];

        foreach ($all as $task) {
            $status = $task->status ?: Task::STATUS_PENDING;
            $priority = $task->priority ?: Task::PRIORITY_MEDIUM;

            $by_status[$status] = ($by_status[$status] ?? 0) + 1;
            $by_priority[$priority] = ($by_priority[$priority] ?? 0) + 1;
        }

        $this->api_success([
            'total' => count($all),
            'overdue' => count($overdue),
            'by_status' => $by_status,
            'by_priority' => $by_priority
        ]);
    }
}
```

### Step 5: Create the TaskList Controller

```php
<?php
// controllers/pub/TaskListController.php

class TaskListController extends APIController {

    public function __construct() {
        JModel::load('Task');
        JModel::load('TaskList');
    }

    /**
     * GET /controller/pub/TaskList/index
     * List all task lists with statistics
     */
    public function index() {
        $lists = TaskList::allWithCounts();

        $result = array_map(function($list) {
            return $list->toArray();
        }, $lists);

        $this->api_success([
            'lists' => $result,
            'count' => count($result)
        ]);
    }

    /**
     * GET /controller/pub/TaskList/show/{id}
     * Get single task list with its tasks
     *
     * @param int $id List ID
     */
    public function show($id) {
        $id = intval($id);
        $list = new TaskList($id);

        if (!$list->ID) {
            $this->api_die('Task list not found', ['id' => $id]);
        }

        $tasks = $list->getTasks();

        $this->api_success([
            'list' => $list->toArray(),
            'tasks' => array_map(function($task) {
                return $task->toArray();
            }, $tasks)
        ]);
    }

    /**
     * POST /controller/pub/TaskList/create
     * Create a new task list
     */
    public function create() {
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        if (empty($name)) {
            $this->api_die('Name is required');
        }

        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $color = isset($_POST['color']) ? sanitize_hex_color($_POST['color']) : TaskList::DEFAULT_COLORS[0];

        $list = new TaskList();
        $list->post_title = $name;
        $list->post_content = $description;
        $list->color = $color;

        $id = $list->add();

        if (!$id) {
            $this->api_die('Failed to create task list');
        }

        $list = new TaskList($id);
        $this->api_success([
            'id' => $id,
            'list' => $list->toArray(),
            'message' => 'Task list created successfully'
        ]);
    }

    /**
     * POST /controller/pub/TaskList/update/{id}
     * Update a task list
     *
     * @param int $id List ID
     */
    public function update($id) {
        $id = intval($id);
        $list = new TaskList($id);

        if (!$list->ID) {
            $this->api_die('Task list not found', ['id' => $id]);
        }

        if (isset($_POST['name'])) {
            $name = sanitize_text_field($_POST['name']);
            if (empty($name)) {
                $this->api_die('Name cannot be empty');
            }
            $list->post_title = $name;
        }

        if (isset($_POST['description'])) {
            $list->post_content = sanitize_textarea_field($_POST['description']);
        }

        if (isset($_POST['color'])) {
            $list->color = sanitize_hex_color($_POST['color']);
        }

        $result = $list->save();

        if (!$result) {
            $this->api_die('Failed to update task list');
        }

        $this->api_success([
            'list' => $list->toArray(),
            'message' => 'Task list updated successfully'
        ]);
    }

    /**
     * POST /controller/pub/TaskList/delete/{id}
     * Delete a task list (and optionally its tasks)
     *
     * @param int $id List ID
     */
    public function delete($id) {
        $id = intval($id);
        $list = new TaskList($id);

        if (!$list->ID) {
            $this->api_die('Task list not found', ['id' => $id]);
        }

        $delete_tasks = isset($_POST['delete_tasks']) && $_POST['delete_tasks'];

        // Optionally delete associated tasks
        if ($delete_tasks) {
            $tasks = $list->getTasks();
            foreach ($tasks as $task) {
                wp_delete_post($task->ID, true);
            }
        }

        $result = wp_delete_post($id, true);

        if (!$result) {
            $this->api_die('Failed to delete task list');
        }

        $this->api_success([
            'message' => 'Task list deleted successfully'
        ]);
    }
}
```

### Step 6: Create the Views

**Task List Page View:**

```php
<?php
// views/tasks/index.php
// Receives: $tasks (array), $lists (array), $current_list (TaskList|null), $filters (array)
?>
<div class="task-manager">
    <header class="task-header">
        <h1><?= $current_list ? esc_html($current_list->post_title) : 'All Tasks' ?></h1>

        <?php if ($current_list): ?>
            <div class="list-progress">
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $current_list->getProgress() ?>%; background: <?= $current_list->getColor() ?>"></div>
                </div>
                <span><?= $current_list->getCompletedCount() ?> / <?= $current_list->getTaskCount() ?> completed</span>
            </div>
        <?php endif; ?>
    </header>

    <div class="task-toolbar">
        <div class="task-filters">
            <select id="filter-status" class="filter-select">
                <option value="">All Status</option>
                <?php foreach (Task::getStatuses() as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($filters['status'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= $label ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select id="filter-priority" class="filter-select">
                <option value="">All Priorities</option>
                <?php foreach (Task::getPriorities() as $value => $config): ?>
                    <option value="<?= $value ?>" <?= ($filters['priority'] ?? '') === $value ? 'selected' : '' ?>>
                        <?= $config['label'] ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label class="filter-checkbox">
                <input type="checkbox" id="filter-overdue" <?= !empty($filters['overdue']) ? 'checked' : '' ?>>
                Show overdue only
            </label>
        </div>

        <button type="button" class="btn btn-primary" id="add-task-btn">
            + Add Task
        </button>
    </div>

    <div class="task-list" id="task-list">
        <?php if (empty($tasks)): ?>
            <div class="empty-state">
                <p>No tasks found. Create your first task!</p>
            </div>
        <?php else: ?>
            <?php foreach ($tasks as $task): ?>
                <?php JView::show('tasks/partials/task-item', ['task' => $task]); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Task Modal -->
<div class="modal" id="task-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modal-title">Add Task</h2>
            <button type="button" class="modal-close" onclick="closeTaskModal()">&times;</button>
        </div>
        <form id="task-form">
            <input type="hidden" id="task-id" name="id" value="">

            <div class="form-group">
                <label for="task-title">Title *</label>
                <input type="text" id="task-title" name="title" required>
            </div>

            <div class="form-group">
                <label for="task-description">Description</label>
                <textarea id="task-description" name="description" rows="3"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="task-priority">Priority</label>
                    <select id="task-priority" name="priority">
                        <?php foreach (Task::getPriorities() as $value => $config): ?>
                            <option value="<?= $value ?>"><?= $config['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="task-due-date">Due Date</label>
                    <input type="date" id="task-due-date" name="due_date">
                </div>
            </div>

            <div class="form-group">
                <label for="task-list-select">Task List</label>
                <select id="task-list-select" name="task_list_id">
                    <option value="">No List</option>
                    <?php foreach ($lists as $list): ?>
                        <option value="<?= $list->ID ?>"><?= esc_html($list->post_title) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeTaskModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Task</button>
            </div>
        </form>
    </div>
</div>
```

**Task Item Partial:**

```php
<?php
// views/tasks/partials/task-item.php
// Receives: $task (Task)

$priority = $task->getPriorityConfig();
$classes = ['task-item'];
if ($task->isCompleted()) $classes[] = 'completed';
if ($task->isOverdue()) $classes[] = 'overdue';
?>
<div class="<?= implode(' ', $classes) ?>" data-task-id="<?= $task->ID ?>">
    <div class="task-checkbox">
        <input type="checkbox"
               id="task-check-<?= $task->ID ?>"
               <?= $task->isCompleted() ? 'checked' : '' ?>
               onchange="toggleTask(<?= $task->ID ?>)">
        <label for="task-check-<?= $task->ID ?>"></label>
    </div>

    <div class="task-content">
        <h4 class="task-title"><?= esc_html($task->post_title) ?></h4>

        <?php if ($task->post_content): ?>
            <p class="task-description"><?= esc_html(wp_trim_words($task->post_content, 20)) ?></p>
        <?php endif; ?>

        <div class="task-meta">
            <span class="task-priority" style="color: <?= $priority['color'] ?>">
                <span class="priority-dot" style="background: <?= $priority['color'] ?>"></span>
                <?= $priority['label'] ?>
            </span>

            <?php if ($task->due_date): ?>
                <span class="task-due-date <?= $task->isOverdue() ? 'overdue' : '' ?>">
                    <?php
                    $days = $task->getDaysUntilDue();
                    if ($days === 0) {
                        echo 'Due today';
                    } elseif ($days === 1) {
                        echo 'Due tomorrow';
                    } elseif ($days < 0) {
                        echo abs($days) . ' day' . (abs($days) > 1 ? 's' : '') . ' overdue';
                    } else {
                        echo 'Due ' . $task->getDueDateFormatted();
                    }
                    ?>
                </span>
            <?php endif; ?>

            <?php
            $list = $task->getTaskList();
            if ($list):
            ?>
                <span class="task-list-badge" style="background: <?= $list->getColor() ?>20; color: <?= $list->getColor() ?>">
                    <?= esc_html($list->post_title) ?>
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="task-actions">
        <button type="button" class="btn-icon" onclick="editTask(<?= $task->ID ?>)" title="Edit">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
        </button>
        <button type="button" class="btn-icon btn-danger" onclick="deleteTask(<?= $task->ID ?>)" title="Delete">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            </svg>
        </button>
    </div>
</div>
```

### Step 7: JavaScript Integration

```javascript
// assets/js/task-manager.js

(function() {
    'use strict';

    // API endpoints using JMVC helpers
    const API = {
        tasks: {
            list:   (params) => JMVC.restApiUrl('pub', 'Task', 'index') + (params ? '?' + new URLSearchParams(params) : ''),
            show:   (id) => JMVC.restApiUrl('pub', 'Task', 'show', id),
            create: () => JMVC.restApiUrl('pub', 'Task', 'create'),
            update: (id) => JMVC.restApiUrl('pub', 'Task', 'update', id),
            delete: (id) => JMVC.restApiUrl('pub', 'Task', 'delete', id),
            toggle: (id) => JMVC.restApiUrl('pub', 'Task', 'toggle', id),
            stats:  () => JMVC.restApiUrl('pub', 'Task', 'stats')
        },
        lists: {
            list:   () => JMVC.restApiUrl('pub', 'TaskList', 'index'),
            show:   (id) => JMVC.restApiUrl('pub', 'TaskList', 'show', id),
            create: () => JMVC.restApiUrl('pub', 'TaskList', 'create'),
            update: (id) => JMVC.restApiUrl('pub', 'TaskList', 'update', id),
            delete: (id) => JMVC.restApiUrl('pub', 'TaskList', 'delete', id)
        }
    };

    // Current state
    let currentListId = null;
    let currentFilters = {};

    /**
     * Load and display tasks
     */
    window.loadTasks = async function(filters = {}) {
        currentFilters = { ...filters };
        if (currentListId) {
            currentFilters.list_id = currentListId;
        }

        try {
            const response = await JMVC.get(API.tasks.list(currentFilters));
            const data = await response.json();

            if (data.success) {
                renderTasks(data.result.tasks);
                updateStats();
            } else {
                showError(data.error || 'Failed to load tasks');
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            showError('Network error. Please try again.');
        }
    };

    /**
     * Render tasks to the DOM
     */
    function renderTasks(tasks) {
        const container = document.getElementById('task-list');
        if (!container) return;

        if (tasks.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <p>No tasks found. Create your first task!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = tasks.map(task => `
            <div class="task-item ${task.is_completed ? 'completed' : ''} ${task.is_overdue ? 'overdue' : ''}"
                 data-task-id="${task.id}">
                <div class="task-checkbox">
                    <input type="checkbox"
                           id="task-check-${task.id}"
                           ${task.is_completed ? 'checked' : ''}
                           onchange="toggleTask(${task.id})">
                    <label for="task-check-${task.id}"></label>
                </div>
                <div class="task-content">
                    <h4 class="task-title">${escapeHtml(task.title)}</h4>
                    ${task.description ? `<p class="task-description">${escapeHtml(truncate(task.description, 100))}</p>` : ''}
                    <div class="task-meta">
                        <span class="task-priority" style="color: ${task.priority_color}">
                            <span class="priority-dot" style="background: ${task.priority_color}"></span>
                            ${task.priority_label}
                        </span>
                        ${task.due_date_formatted ? `
                            <span class="task-due-date ${task.is_overdue ? 'overdue' : ''}">
                                ${formatDueDate(task.days_until_due, task.due_date_formatted)}
                            </span>
                        ` : ''}
                    </div>
                </div>
                <div class="task-actions">
                    <button type="button" class="btn-icon" onclick="editTask(${task.id})" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                    </button>
                    <button type="button" class="btn-icon btn-danger" onclick="deleteTask(${task.id})" title="Delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Toggle task completion status
     */
    window.toggleTask = async function(id) {
        try {
            const response = await JMVC.postForm(API.tasks.toggle(id), {});
            const data = await response.json();

            if (data.success) {
                // Update UI immediately
                const taskEl = document.querySelector(`[data-task-id="${id}"]`);
                if (taskEl) {
                    taskEl.classList.toggle('completed', data.result.task.is_completed);
                    const checkbox = taskEl.querySelector('input[type="checkbox"]');
                    if (checkbox) checkbox.checked = data.result.task.is_completed;
                }
                updateStats();
            } else {
                showError(data.error || 'Failed to update task');
            }
        } catch (error) {
            console.error('Error toggling task:', error);
            showError('Network error. Please try again.');
        }
    };

    /**
     * Open task modal for editing
     */
    window.editTask = async function(id) {
        try {
            const response = await JMVC.get(API.tasks.show(id));
            const data = await response.json();

            if (data.success) {
                const task = data.result.task;
                document.getElementById('modal-title').textContent = 'Edit Task';
                document.getElementById('task-id').value = task.id;
                document.getElementById('task-title').value = task.title;
                document.getElementById('task-description').value = task.description || '';
                document.getElementById('task-priority').value = task.priority;
                document.getElementById('task-due-date').value = task.due_date || '';
                document.getElementById('task-list-select').value = task.task_list_id || '';
                openTaskModal();
            } else {
                showError(data.error || 'Failed to load task');
            }
        } catch (error) {
            console.error('Error loading task:', error);
            showError('Network error. Please try again.');
        }
    };

    /**
     * Delete a task
     */
    window.deleteTask = async function(id) {
        if (!confirm('Are you sure you want to delete this task?')) {
            return;
        }

        try {
            const response = await JMVC.postForm(API.tasks.delete(id), {});
            const data = await response.json();

            if (data.success) {
                // Remove from DOM
                const taskEl = document.querySelector(`[data-task-id="${id}"]`);
                if (taskEl) {
                    taskEl.remove();
                }
                updateStats();
                showSuccess('Task deleted');
            } else {
                showError(data.error || 'Failed to delete task');
            }
        } catch (error) {
            console.error('Error deleting task:', error);
            showError('Network error. Please try again.');
        }
    };

    /**
     * Handle task form submission
     */
    async function handleTaskSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);
        const id = formData.get('id');

        const url = id ? API.tasks.update(id) : API.tasks.create();

        try {
            const response = await JMVC.postForm(url, formData);
            const data = await response.json();

            if (data.success) {
                closeTaskModal();
                loadTasks(currentFilters);
                showSuccess(id ? 'Task updated' : 'Task created');
            } else {
                showError(data.error || 'Failed to save task');
            }
        } catch (error) {
            console.error('Error saving task:', error);
            showError('Network error. Please try again.');
        }
    }

    /**
     * Open task modal for new task
     */
    function openNewTaskModal() {
        document.getElementById('modal-title').textContent = 'Add Task';
        document.getElementById('task-form').reset();
        document.getElementById('task-id').value = '';
        document.getElementById('task-priority').value = 'medium';
        if (currentListId) {
            document.getElementById('task-list-select').value = currentListId;
        }
        openTaskModal();
    }

    function openTaskModal() {
        document.getElementById('task-modal').style.display = 'flex';
        document.getElementById('task-title').focus();
    }

    window.closeTaskModal = function() {
        document.getElementById('task-modal').style.display = 'none';
    };

    /**
     * Update task statistics
     */
    async function updateStats() {
        try {
            const response = await JMVC.get(API.tasks.stats());
            const data = await response.json();

            if (data.success) {
                // Update any stats displays in the UI
                const stats = data.result;
                // ... update UI elements
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    // Helper functions
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function truncate(str, length) {
        return str.length > length ? str.substring(0, length) + '...' : str;
    }

    function formatDueDate(daysUntil, formatted) {
        if (daysUntil === 0) return 'Due today';
        if (daysUntil === 1) return 'Due tomorrow';
        if (daysUntil < 0) return `${Math.abs(daysUntil)} day${Math.abs(daysUntil) > 1 ? 's' : ''} overdue`;
        return `Due ${formatted}`;
    }

    function showError(message) {
        // Implement your preferred notification method
        alert(message);
    }

    function showSuccess(message) {
        // Implement your preferred notification method
        console.log(message);
    }

    // Initialize on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        // Bind form submission
        const taskForm = document.getElementById('task-form');
        if (taskForm) {
            taskForm.addEventListener('submit', handleTaskSubmit);
        }

        // Bind add task button
        const addBtn = document.getElementById('add-task-btn');
        if (addBtn) {
            addBtn.addEventListener('click', openNewTaskModal);
        }

        // Bind filter changes
        const filterStatus = document.getElementById('filter-status');
        const filterPriority = document.getElementById('filter-priority');
        const filterOverdue = document.getElementById('filter-overdue');

        if (filterStatus) {
            filterStatus.addEventListener('change', function() {
                loadTasks({ ...currentFilters, status: this.value || undefined });
            });
        }

        if (filterPriority) {
            filterPriority.addEventListener('change', function() {
                loadTasks({ ...currentFilters, priority: this.value || undefined });
            });
        }

        if (filterOverdue) {
            filterOverdue.addEventListener('change', function() {
                loadTasks({ ...currentFilters, overdue: this.checked ? 1 : undefined });
            });
        }

        // Close modal on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeTaskModal();
            }
        });

        // Close modal on backdrop click
        const modal = document.getElementById('task-modal');
        if (modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeTaskModal();
                }
            });
        }

        // Initial load
        loadTasks();
    });

})(jQuery);
```

### Step 8: Configuration

```php
<?php
// config/tasks.php

return [
    // Default values
    'default_status' => 'pending',
    'default_priority' => 'medium',

    // Available options
    'statuses' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed'
    ],

    'priorities' => [
        'low' => ['label' => 'Low', 'color' => '#28a745'],
        'medium' => ['label' => 'Medium', 'color' => '#ffc107'],
        'high' => ['label' => 'High', 'color' => '#dc3545']
    ],

    // UI settings
    'items_per_page' => 20,
    'enable_due_dates' => true,
    'enable_lists' => true,
    'enable_descriptions' => true,

    // Notification settings
    'notify_on_overdue' => true,
    'overdue_check_interval' => 'daily'
];
```

### Using the Sample Application

1. **Register Post Types**: Add the post type registration code to your `functions.php`

2. **Create ACF Fields**: Set up ACF field groups for:
   - **Task Fields**: `status`, `priority`, `due_date`, `task_list_id`, `completed_at`
   - **TaskList Fields**: `color`

3. **Include Assets**: Add the JavaScript and CSS to your theme

4. **Create Page Template**: Use the views in a page template

5. **Test the API**: Visit these URLs:
   - `GET /controller/pub/Task/index` - List all tasks
   - `POST /controller/pub/Task/create` - Create a task
   - `GET /controller/pub/TaskList/index` - List all task lists

---

## Developer Tools

### JLog - Logging System

```php
// Log messages by level
JLog::info('User logged in: ' . $user_id);
JLog::warn('API rate limit approaching');
JLog::error('Database connection failed');
JLog::log('custom_category', 'Custom log message');

// View logs (stored in kvstore)
// Keys: Jlog/info, Jlog/warn, Jlog/error, Jlog/custom_category
```

### DevAlert - Error Notifications

Configure alerts in `config/devalert.php`:

```php
<?php
// config/devalert.php

return [
    'enabled' => true,

    // Slack configuration
    'slack' => [
        'enabled' => true,
        'webhook_url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
        'channel' => '#errors',
        'username' => 'JMVC Alert'
    ],

    // Email configuration
    'email' => [
        'enabled' => true,
        'to' => 'developer@example.com',
        'from' => 'alerts@example.com',
        'subject_prefix' => '[JMVC Error]'
    ]
];
```

Send alerts:

```php
// Send to Slack
DevAlert::slack('Critical error occurred', [
    'user_id' => $user_id,
    'action' => 'checkout',
    'error' => $exception->getMessage()
]);

// Send email
DevAlert::mail('Database Error', [
    'query' => $query,
    'error' => $error
]);

// Auto-detect and send
DevAlert::send('Something went wrong', $context);
```

---

## HMVC Modules

HMVC (Hierarchical Model-View-Controller) allows you to create self-contained modules.

### Module Structure

```
modules/
└── blog/
    ├── controllers/
    │   ├── pub/
    │   │   └── PostController.php
    │   └── admin/
    │       └── PostAdminController.php
    ├── models/
    │   ├── Post.php
    │   └── Category.php
    ├── views/
    │   ├── posts/
    │   │   ├── index.php
    │   │   └── single.php
    │   └── partials/
    │       └── post-card.php
    └── libraries/
        └── PostService.php
```

### Module Controller

```php
<?php
// modules/blog/controllers/pub/PostController.php

namespace blog\pub;

class PostController extends \APIController {

    public function index() {
        $posts = \JModel::load('Post', 'blog')::find();
        $this->api_success(['posts' => $posts]);
    }

    public function show($id) {
        \JModel::load('Post', 'blog');
        $post = new \blog\Post($id);
        \JView::show('posts/single', ['post' => $post], 'blog');
    }
}
```

### Module Routes

```
/hmvc_controller/pub/blog/Post/index
/hmvc_controller/pub/blog/Post/show/42
/hmvc_controller/admin/blog/PostAdmin/edit/42
```

### Loading Module Components

```php
// Load module model
JModel::load('Post', 'blog');
$post = new \blog\Post(123);

// Load module library
$service = JLib::load('PostService', 'blog');

// Render module view
JView::show('posts/single', ['post' => $post], 'blog');
```

---

## API Reference

### JController

| Method | Description |
|--------|-------------|
| `load($name, $env, $module = null)` | Load and instantiate a controller |

### JModel

| Method | Description |
|--------|-------------|
| `load($name, $module = null, $singleton = false)` | Load a model class |
| `exists($name, $module = null)` | Check if model exists |

### JModelBase

| Method | Description |
|--------|-------------|
| `find($args = [])` | Query posts (static) |
| `add()` | Create new post |
| `save()` | Save current instance |
| `update($fields)` | Update specific fields |

### JView

| Method | Description |
|--------|-------------|
| `show($template, $data = [], $module = null)` | Output view |
| `get($template, $data = [], $module = null)` | Return view as string |

### JLib

| Method | Description |
|--------|-------------|
| `load($name, $module = null)` | Load library (singleton) |

### JConfig

| Method | Description |
|--------|-------------|
| `get($path)` | Get config value by path |
| `set($key, $value)` | Set config value |

### JBag

| Method | Description |
|--------|-------------|
| `get($key)` | Get stored value |
| `set($key, $value)` | Store value |

### APIController

| Method | Description |
|--------|-------------|
| `api_success($result)` | Output success JSON |
| `api_die($message, $data = [])` | Output error JSON and exit |
| `api_result($success, $result)` | Output custom JSON |

### JLog

| Method | Description |
|--------|-------------|
| `info($message)` | Log info message |
| `warn($message)` | Log warning |
| `error($message)` | Log error |
| `log($category, $message)` | Log to custom category |

### DevAlert

| Method | Description |
|--------|-------------|
| `slack($message, $context = [])` | Send Slack alert |
| `mail($subject, $context = [])` | Send email alert |
| `send($message, $context = [])` | Send to all configured channels |

---

## Testing

JMVC includes a comprehensive PHPUnit test suite.

### Running Tests

```bash
# Install PHPUnit
composer require --dev phpunit/phpunit

# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Integration

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage/
```

### Test Structure

```
tests/
├── bootstrap.php              # Test bootstrap
├── Unit/
│   ├── Controller/
│   │   ├── JControllerTest.php
│   │   ├── JControllerAjaxTest.php
│   │   └── APIControllerTest.php
│   ├── Model/
│   │   ├── JModelBaseTest.php
│   │   └── ACFModelTraitTest.php
│   ├── View/
│   │   └── JViewTest.php
│   └── Service/
│       └── JBagTest.php
├── Integration/
│   └── SecurityTest.php
└── Mocks/
    ├── WordPressMocks.php
    └── ACFMocks.php
```

### Writing Tests

```php
use PHPUnit\Framework\TestCase;
use WP_Mock_Data;

class MyControllerTest extends TestCase
{
    protected function setUp(): void
    {
        WP_Mock_Data::reset();
    }

    public function testUserCanAccessResource(): void
    {
        WP_Mock_Data::setLoggedIn(1, ['edit_posts']);

        $this->assertTrue(current_user_can('edit_posts'));
    }
}
```

---

## Migration Guide

### Upgrading from Legacy JMVC

If upgrading from an older version of JMVC, note these breaking changes:

#### PHP Requirements

- **Old**: PHP 5.4+
- **New**: PHP 7.4+ (8.1+ recommended)

Update your code to use modern PHP features:

```php
// Old (PHP 5.4)
$value = isset($array['key']) ? $array['key'] : 'default';

// New (PHP 7.0+)
$value = $array['key'] ?? 'default';
```

#### WordPress Requirements

- **Old**: WordPress 4.0+
- **New**: WordPress 6.0+

#### Dependencies

Update your `composer.json`:

```json
{
    "require": {
        "php": ">=7.4",
        "guzzlehttp/guzzle": "^7.0",
        "predis/predis": "^2.0"
    }
}
```

Then run:

```bash
composer update
```

#### Security Changes

1. **Nonce Verification**: AJAX controllers now support nonce verification. Add nonces to your forms.

2. **Capability Checks**: Model `add()` and `update()` methods now check `edit_posts` capability.

3. **Input Sanitization**: All path parameters are now sanitized. Update any code that relied on raw input.

4. **Credentials**: Move any hardcoded credentials to environment variables.

#### REST API

New REST API endpoints are available at `/wp-json/jmvc/v1/`. You can use both AJAX and REST APIs - they share the same controllers.

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Setup

```bash
# Clone the repository
git clone https://github.com/your-repo/jmvc.git
cd jmvc

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit
```

---

## License

This project is open-sourced software. Please check with the repository owner for specific licensing terms.

---

**Built with care for WordPress developers who appreciate clean architecture.**
