<?php

/*
Plugin Name: JbrBackup
Description: Allow scheduled and manual backups of DB and media files.
Version: 0.4.2
Author: Jim Rudolf
Author URI: http://www.jbrcodes.com
*/

defined('ABSPATH') or die();

require 'autoload.php';

define('JBR_BACKUP_VERSION', '0.4.2');

# Create singleton that also initializes everything
JbrBackup\JbrBackup::Get();

# These apparently have to be in the "main" plugin file
register_activation_hook(__FILE__, ['JbrBackup\JbrBackup', 'OnActivate']);
register_deactivation_hook(__FILE__, ['JbrBackup\JbrBackup', 'OnDeactivate']);
register_uninstall_hook(__FILE__, ['JbrBackup\JbrBackup', 'OnUninstall']);

#?>