<?php

#
# Class: AdminController
#
# This class takes care of everything related to rendering and/or receiving
# events/input on the admin side of things.
#

namespace JbrBackup;

defined('ABSPATH') or die();

use JbrBackup\Ftp;
use JbrBackup\JobLog;

class AdminController {

    # -------------------------------------------------------------------------
    # instance variables
    # -------------------------------------------------------------------------
    
    private $_jbrbu = null;
    
    # -------------------------------------------------------------------------
    # constructor
    # -------------------------------------------------------------------------
    
    public function __construct($jbrbu) {
        $this->_jbrbu = $jbrbu;
        
        # Add a menu item to the admin Settings menu
        add_action('admin_menu', [$this, 'onAdminMenu'] );
        
        # Enqueue our JS and CSS files
		add_action('init', [$this, 'onEnqueueFiles']);
        
        # Register our method to handle AJAX events
        add_action('wp_ajax_jbrbu_ajax_event', [$this, 'handleAjaxEvent']);
    }
    
    # -------------------------------------------------------------------------
    # WordPress callbacks
    # -------------------------------------------------------------------------
    
    #
    # Callback to add menu item to admin Tools menu
    #
    
    public function onAdminMenu() {
        add_submenu_page(
            'tools.php',
            'JbrBackup',  # <title>
            'JbrBackup', 
            'manage_options', 
            'JbrBackup', 
            [$this, 'onRenderToolsPage']
        );
    }
    
    #
    # Callback to render the Tools page
    #
    
    public function onRenderToolsPage() {
        $tab = array_key_exists('tab', $_GET) ? $_GET['tab'] : 'overview';
        $vars = [ 'tab' => $tab ];
        $this->_render('base', $vars);
    }
    
    #
    # Callback to include our JS and CSS files
    #
    
	public function onEnqueueFiles() {
        $dir = WP_PLUGIN_URL . '/JbrBackup';  # (not best practice)
        wp_register_script('JbrBackup', $dir . '/JbrBackup.js');
        wp_enqueue_script('JbrBackup', $in_footer=true);
        wp_enqueue_style('JbrBackup', $dir . '/style.css');
	}
    
    # -------------------------------------------------------------------------
    # main event handler
    # -------------------------------------------------------------------------
    
    public function handleAjaxEvent() {
        $cmd = $_POST['cmd'];
        switch ($cmd) {
            case 'backupDbNow':
                Cron::ScheduleJobNow('jbrbu_db');  # magic string
                $msg = 'DB backup scheduled for now';
                break;
                
            case 'backupFilesNow':
                Cron::ScheduleJobNow('jbrbu_files');  # magic string
                $msg = 'Files backup scheduled for now';
                break;
                
            case 'cleanUp':
                JobLog::CleanUp($_POST['saveHowLong']);
                $msg = 'cleaned up';
                break;
                
            case 'dumpSettingsAndData':
                $msg = $this->_dumpSettingsAndData();
                $msg = str_replace("\n", '<br>', $msg);
                $msg = str_replace("\t", '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $msg);
                break;
                
            case 'findBinaries':
                $msg = $this->_jbrbu->findBinaries();
                break;
                
            case 'findFiles':
                $relPathArr = $this->_jbrbu->findFiles($_POST['subdir'], $_POST['newerThan']);
                $count = count($relPathArr);
                $msg = "$count file(s) found:<br><br>" . join('<br>', $relPathArr);
                break;
                
                # The "new" generic way for simple settings
            case 'saveSetting':
                $this->_jbrbu->saveSetting($_POST['key'], $_POST[ $_POST['key'] ]);
                $msg = 'Setting saved';
                break;
                
            case 'saveSettings':
                $this->_jbrbu->saveSettings($_POST['keys'], $_POST);
                $msg = 'Settings saved';
                break;
                
            case 'saveFtpInfo':
                $this->_jbrbu->saveFtpSettings($_POST);
                $msg = 'Remote server info saved';
                break;
                
            case 'sendEmail':
                $P = $_POST;
                if ( !trim($P['emailTo']) ) {
                    $msg = 'No recipient(s) defined';
                } else if ( !trim($P['emailSubject']) && !trim($P['emailBody']) ) {
                    $msg = 'Please supply a subject or body!';
                } else {
                    $msg = $this->_jbrbu->sendEmail(
                        $P['emailTo'], $P['emailSubject'], $P['emailBody']);
                    if (!$msg)
                        $msg = 'Email sent';
                }

                break;
                
            case 'testFtpConn':
                $ftp = new Ftp();
                $info = $this->_jbrbu->getSettings()['ftpInfo'];
                $msg = $ftp->testConnection($info);
                break;
                
            case 'saveLastFilesBackup':
                if ( !preg_match('!^\s*(\d{4}-\d\d-\d\d)\s*$!', $_POST['lastFilesBackup'], $toks) ) {
                    $msg = 'Illegal date format';
                    break;
                }
                $stamp = strtotime($toks[1]);
                $this->_jbrbu->saveData('lastFilesBackup', $stamp);
                $msg = 'Last file backup date saved';
                break;
                
            case 'test':
                $str1 = 'mary27';
                $encstr = $this->_jbrbu->encrypt($str1);
                $str2 = $this->_jbrbu->decrypt($encstr);
                $msg = "str1 ($str1)<br>enc ($encstr)<br> str2 ($str2)";
                break;
                
            case 'testPwd':
                $msg = getcwd();
                break;
                
                # --- cron ---
                
            case 'getNextScheduledJob':
                $stamp = Cron::GetNextScheduledJob('foobar');
                if ($stamp)
                    $msg = "'foobar' is scheduled for " . date('Y-m-d H:i:s', $stamp);
                else
                    $msg = "'foobar' is not scheduled";
                break;
                
            case 'getBackupSchedule':
                $sched = $this->_jbrbu->getBackupSchedule();
                $msg = sprintf('DB (%s)<br>files (%s)', $sched['dbFrequency'],
                              $sched['filesFrequency']);
                break;
            
            default:
                $msg = 'unknown command: ' . $cmd;
                break;
        }
        echo $msg;
        
        wp_die();  # ???
    }
    
    # -------------------------------------------------------------------------
    # rendering
    # -------------------------------------------------------------------------
    
    private function _render($templateName, $vars = []) {
        $tdir = WP_PLUGIN_DIR . '/JbrBackup/templates/';
        $path = $tdir . $templateName . '.php';
        include $path;
    }
    
    private function _renderMenu($id, $labels, $values=[], $selected='') {
        if (!$values)
            $values = $labels;
        $tag = sprintf('<select id="%s" name="%s">', $id, $id);
        for ($i=0; $i<count($labels); $i++) {
            $tag .= sprintf('<option value="%s"', $values[$i]);
            if ($values[$i] === $selected)
                $tag .= ' selected';
            $tag .= sprintf('>%s</option>', $labels[$i]);
        }
        $tag .= '</select>';
        
        return $tag;
    }
    
    # -------------------------------------------------------------------------
    # private controller helpers
    # -------------------------------------------------------------------------
    
    private function _dumpSettingsAndData() {
        $settings = $this->_jbrbu->getSettings();
        $out = $this->_myDumpDict('settings', $settings);
        $out .= $this->_myDumpDict('data', $this->_jbrbu->getData());

        return $out;
    }
    
    private function _myDumpDict($name, $dict, $indent='') {
        asort($dict);
        $out = $indent . '=== ' . $name . " ===\n";
        foreach ($dict as $k => $v) {
            if ( gettype($v) == 'array' )
                $out .= $this->_myDumpDict($k, $v, "\t$indent");
            else
                $out .= sprintf("%s%s: (%s)\n", $indent, $k, $v);
        }

        return $out;
    }
    
    # -------------------------------------------------------------------------
    # Helpers for templates
    # -------------------------------------------------------------------------
    
    public function renderCheckbox($id, $value, $checked=false) {
        $tag = sprintf('<input id="%s" name="%s" type="checkbox" value="%s"', 
                       $id, $id, $value);
        if ($checked)
            $tag .= ' checked';
        $tag .= '>';
        
        return $tag;
    }
    
    public function stampToDate($stamp, $showTime=true) {
        if ($stamp) {
            $fmt = ($showTime) ? 'Y-m-d H:i:s' : 'Y-m-d';
            $d = date($fmt, $stamp);
        } else
            $d = '--';
        
        return $d;
    }
    
    public function renderMenu($id, $selected) {
        switch ($id) {
            case 'dbFrequency':
            case 'filesFrequency':
                $lbls = ['never', 'hourly', 'daily', 'weekly', 'biweekly'];
                $vals = $lbls;
                break;
                
            case 'sendEmailWhen':
                $lbls = ['always', 'errors', 'never'];
                $vals = $lbls;
                break;
        }
        
        return $this->_renderMenu($id, $lbls, $vals, $selected);
    }
    
    public function getData() {
        return $this->_jbrbu->getData();
    }
    
    public function getSettings() {
        return $this->_jbrbu->getSettings();
    }
    
    public function getSummaryInfo() {
        return $this->_jbrbu->getSummaryInfo();
    }
    
    # IN DEVELOPMENT
    public function getNextSchedules() {
        return $this->_jbrbu->getNextSchedules();
    }
    
    public function includeMarkdown($mdFile) {
        $dir = WP_PLUGIN_DIR . '/JbrBackup/doc/';
        $path = $dir . $mdFile;
        $md = file_get_contents($path);
        #$Parsedown = new \Parsedown();
        
        return $md;  # $Parsedown->text($md);
    }
    
}

#?>
