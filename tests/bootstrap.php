<?php
/**
 * JMVC Test Suite Bootstrap
 *
 * Sets up the testing environment with WordPress mocks.
 *
 * @package JMVC\Tests
 */

// Define JMVC path first (before any includes)
if (!defined('JMVC')) {
    define('JMVC', dirname(__DIR__) . '/');
}

// Define WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

// Define WordPress output constants
if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('ARRAY_N')) {
    define('ARRAY_N', 'ARRAY_N');
}

// Load mock functions
require_once __DIR__ . '/Mocks/WordPressMocks.php';
require_once __DIR__ . '/Mocks/ACFMocks.php';

// Autoload Composer dependencies
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Load core JMVC classes that are needed for tests
require_once JMVC . 'system/JBag.php';
require_once JMVC . 'system/config/JConfig.php';
require_once JMVC . 'system/view/JView.php';
require_once JMVC . 'system/controller/JController.php';
require_once JMVC . 'system/controller/JControllerAjax.php';
require_once JMVC . 'system/model/JModel.php';
require_once JMVC . 'system/model/JModelBase.php';
require_once JMVC . 'system/model/traits/ACFModelTrait.php';
require_once JMVC . 'system/library/JLib.php';
require_once JMVC . 'system/app/dev/JLog.php';
require_once JMVC . 'system/app/dev/DevAlert.php';
