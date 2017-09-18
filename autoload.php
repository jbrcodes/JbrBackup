<?php

spl_autoload_register(function($className) {
    #error_log("autoload ($className)");
    $fwdSlashClass = str_replace('\\', '/', $className);
	$abspath = sprintf('%s/JbrBackup/%s.php', WP_PLUGIN_DIR, $fwdSlashClass);
	if ( file_exists($abspath) )
		include $abspath;
    #else
    #    error_log("Could not find/load ($className) -> ($abspath)");
});

#?>