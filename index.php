<?php
// Version
define('VERSION', '2.3.0.2');

// DB connection 
if (is_file('system/db_config.php')) { 
	require_once('system/db_config.php');
}

// Configuration
if (is_file('config.php')) {
	require_once('config.php');
}

// Install
if (!defined('DIR_APPLICATION')) {
	header('Location: install/index.php');
	exit;
}


// Startup
require_once(DIR_SYSTEM . 'startup.php');

<<<<<<< Updated upstream
start('catalog');
=======
start('catalog');


TEST TEST

BB
>>>>>>> Stashed changes
