<div class="nav-tab-wrapper">
    <?php

    # These are tab filenames and labels
    $tabs = [
        'overview', 'Overview',
        'dbBackups', 'DB Backups',
        'filesBackups', 'File Backups',
        'log', 'Log',
        'ftpServer', 'FTP Server',
        'emails', 'Emails',
        'doc', 'Documentation',
        'debug', 'Debug'
    ];

    # Determine the active tab
    $uri = $_SERVER['REQUEST_URI'];
    if ( preg_match('!&tab=(\w+)!', $uri, $toks) )
        $activetab = $toks[1];
    else
        $activetab = $tabs[0];
    
    # Output tabs as links
    $baseuri = preg_replace('!&.*!', '', $uri);
    for ($i=0; $i<count($tabs); $i+=2) {
        $cl = ($tabs[$i] == $activetab) ? ' nav-tab-active' : '';
        printf('<a href="%s&amp;tab=%s" class="nav-tab%s">%s</a>',
              $baseuri, $tabs[$i], $cl, $tabs[$i+1]);
    }
    
    ?>

</div>