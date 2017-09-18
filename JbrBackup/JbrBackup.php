<?php

namespace JbrBackup;

defined('ABSPATH') or die();

use JbrBackup\AdminController;
use JbrBackup\Cron;
use JbrBackup\Ftp;
use JbrBackup\JobLog;

class JbrBackup {
    
    # -------------------------------------------------------------------------
    # class variables
    # -------------------------------------------------------------------------
    
    private static $_encryptKey = 'kjwhf7823$@*hfwH38&8;l';
    private static $_encryptMethod = 'aes-256-ctr';
    private static $_settingsKey = 'JbrBackup_settings';
    private static $_dataKey = 'JbrBackup_data';
    private static $_dbBackupActionName = 'jbrbu_db';
    private static $_filesBackupActionName = 'jbrbu_files';
    private static $_cleanupActionName = 'jbrbu_cleanup';
    private static $_cleanupFrequency = 'weekly';
    
    # -------------------------------------------------------------------------
    # WordPress callbacks & helpers (class)
    # -------------------------------------------------------------------------

    public static function OnActivate() {
        error_log('JbrBackup::OnActivate');
        JobLog::OnActivate();
        self::_ActivateCronJobs();
    }
    
    public static function OnDeactivate() {
        error_log('JbrBackup::OnDeactivate');
        self::_DeactivateCronJobs();
    }
    
    #
    # Delete recs from wp_options
    #
    
    public static function OnUninstall() {
        global $wpdb;
        
        error_log('JbrBackup::OnUninstall');

        # Delete records from wp_options
        $sql = "DELETE FROM wp_options WHERE option_name LIKE 'JbrBackup%'";
        $wpdb->query($sql);
        
        JobLog::OnUninstall();
    }
    
    private static function _ActivateCronJobs() {
        $jbrbu = JbrBackup::Get();
        $freq = $jbrbu->_settings['dbFrequency'];
        if ($freq != 'never') {
            Cron::ScheduleRecurringJob($freq, self::$_dbBackupActionName);           
        }

        $freq = $jbrbu->_settings['filesFrequency'];
        if ($freq != 'never') {
            Cron::ScheduleRecurringJob($freq, self::$_filesBackupActionName);
        }
        
        Cron::ScheduleRecurringJob(self::$_cleanupFrequency, self::$_cleanupActionName);
    }
    
    private static function _DeactivateCronJobs() {
        $jbrbu = JbrBackup::Get();
        $freq = $jbrbu->_settings['dbFrequency'];
        if ($freq != 'never') {
            Cron::ClearEvent(self::$_dbBackupActionName);
        }

        $freq = $jbrbu->_settings['filesFrequency'];
        if ($freq != 'never') {
            Cron::ClearEvent(self::$_filesBackupActionName);
        }
        
        Cron::ClearEvent(self::$_cleanupActionName);
    }
    
    # -------------------------------------------------------------------------
    # Methods called by WP Cron (class)
    # -------------------------------------------------------------------------
    
    public static function CronDoDbBackup($how, $args) {
        error_log("JbrBackup::CronDoDbBackup()");
        $jbrbu = self::Get();
        $jbrbu->_backUpDb($how);
    }
    
    public static function CronDoFilesBackup($how, $args) {
        error_log('JbrBackup::CronDoFilesBackup()');
        $jbrbu = self::Get();
        $jbrbu->_backUpFiles($how); 
    }
    
    public static function CronDoCleanup($how, $args) {
        error_log('JbrBackup::CronDoCleanup()');
        $jbrbu = self::Get();
        $saveLogsFor = $jbrbu->getSetting('saveLogsFor');
        JobLog::CleanUp($saveLogsFor);
    }
    
    # -------------------------------------------------------------------------
    # Other class methods
    # -------------------------------------------------------------------------
    
    #
    # Treat us as a singleton
    #
    
    public static function Get() {
        if ( !array_key_exists('JBR_BACKUP', $GLOBALS) ) {
            $GLOBALS['JBR_BACKUP'] = new JbrBackup();
        }
        
        return $GLOBALS['JBR_BACKUP'];
    }
    
    # -------------------------------------------------------------------------
    # instance variables
    # -------------------------------------------------------------------------
    
    private $_data = [];
    private $_settings = [];
    
    # -------------------------------------------------------------------------
    # constructor
    # -------------------------------------------------------------------------
    
    public function __construct() {
        $this->_loadSettings();
        $this->_loadData();
        
        # The timezone is correctly shown by phpinfo(), but for some reason
        # time() only considers it if I include this. So get it from WP.
        $wptz = get_option('timezone_string');
        if ($wptz)
            date_default_timezone_set($wptz);
        
        if ( is_admin() )
            new AdminController($this);
        
        Cron::ListenToEvent(self::$_dbBackupActionName, 
                                ['JbrBackup\JbrBackup', 'CronDoDbBackup']);
        Cron::ListenToEvent(self::$_filesBackupActionName, 
                                ['JbrBackup\JbrBackup', 'CronDoFilesBackup']);
        Cron::ListenToEvent(self::$_cleanupActionName, 
                                ['JbrBackup\JbrBackup', 'CronDoCleanup']);
    }

    # -------------------------------------------------------------------------
    # settings
    # -------------------------------------------------------------------------
    
    public function getSettings() {
        $s = $this->_settings;
        $s['ftpInfo']['password'] = $this->decrypt($s['ftpInfo']['password']);
        
        return $s;
    }
    
    public function getSetting($key) {
        return $this->_settings[$key];
    }
    
    #
    # Save a setting. Return true if value changed, false otherwise.
    #
    
    public function saveSetting($key, $value) {
        if ($this->_settings[$key] == $value)
            return false;
        
        $this->_settings[$key] = $value;
        # Save all settings to wp_options
        $this->_saveSettings();
        
        # Changing settings can have side effects...
        switch ($key) {
            case 'dbFrequency':
                if ($value == 'never')
                    Cron::UnscheduleRecurringJob(self::$_dbBackupActionName);
                else
                    Cron::ScheduleRecurringJob($value, self::$_dbBackupActionName);
                break;
                
            case 'filesFrequency':
                if ($value == 'never') {
                    Cron::UnscheduleRecurringJob(self::$_filesBackupActionName);
                } else {
                    Cron::ScheduleRecurringJob($value, self::$_filesBackupActionName);
                } 
                break;
        }

        return true;
    }
    
    public function saveSettings($keysStr, $dict) {
        $keys = explode(',', $keysStr);
        foreach ($keys as $k) {
            $this->saveSetting($k, $dict[$k]);
        }
     
        return true;  # ??
    }
    
    #
    # Kindofa special case (maybe obsolete with saveSettings())
    #
    
    public function saveFtpSettings($dict) {
        $keys = ['host', 'user', 'password', 'passiveMode', 'remoteDbDir', 'remoteFilesDir'];
        $info = [];
        foreach ($keys as $k) {
            $info[$k] = array_key_exists($k, $dict) ? $dict[$k] : '';
        }
        $info['password'] = $this->encrypt($info['password']);
        if ($info['passiveMode'] !== 'y')
            $info['passiveMode'] = 'n';
        $this->_settings['ftpInfo'] = $info;
        $this->_saveSettings();
    }
    
    private function _loadSettings() {
        $s = get_option(self::$_settingsKey);
        if ($s) {
            $this->_settings = $s;
        } else {
            $this->_settings = [
                'ftpInfo' => [
                    'host' => '',
                    'user' => '',
                    'password' => '',
                    'passiveMode' => 'n',
                    'remoteDbDir' => '',
                    'remoteFilesDir' => ''
                ],
                'dbFrequency' => 'weekly',
                'filesFrequency' => 'weekly',
                'saveLogsFor' => '2 months',
                'sendEmailTo' => '',
                'sendEmailWhen' => ''
            ];
        }
    }
    
    private function _saveSettings() {
        update_option(self::$_settingsKey, $this->_settings);     
    }
    
    # -------------------------------------------------------------------------
    # (System-generated) data, distinct from user-defined settings
    # -------------------------------------------------------------------------
    
    public function getData() {
        return $this->_data;
    }
    
    public function saveData($key, $value) {
        if ($this->_data[$key] == $value)
            return false;
        
        $this->_data[$key] = $value;
        $this->_saveData();
        
        return true;
    }
    
    private function _loadData() {
        $d = get_option(self::$_dataKey);
        if ($d) {
            $this->_data = $d;
        } else {
            $this->_data = [
                'lastDbBackup' => '',
                'lastFilesBackup' => ''
            ];
        }
    }
    
    private function _saveData() {
        update_option(self::$_dataKey, $this->_data);
    }

    
    # Find a home for me
    
    public function getSummaryInfo() {
        $next = $this->getNextSchedules();

        $info = [
            'dbFrequency' => $this->_settings['dbFrequency'],
            'filesFrequency' => $this->_settings['filesFrequency'],
            
            'lastDbBackup' => '',
            'lastFilesBackup' => '',
            
            'nextDbBackup' => $next['nextDbBackup'],
            'nextFilesBackup' => $next['nextFilesBackup'],
            
            'lastDbResult' => '--',
            'lastFilesResult' => '--',
        ];
        
        $lastDb = JobLog::GetLastOne('db');
        $lastFiles = JobLog::GetLastOne('files');
        if ($lastDb) {
            $info['lastDbBackup'] = strtotime($lastDb['endedStamp']);
            $info['lastDbResult'] = $lastDb['result'];
        }
        if ($lastFiles) {
            $info['lastFilesBackup'] = strtotime($lastFiles['endedStamp']);
            $info['lastFilesResult'] = $lastFiles['result'];
        }
        
        return $info;
    }
    
    # -------------------------------------------------------------------------
    # fun with files
    # -------------------------------------------------------------------------
    
    #
    # Return an array of RELATIVE paths to the files
    #
    
    public function findFiles($jl, $subDir='', $newerThan='') {
        $upDir = WP_CONTENT_DIR . '/uploads';
        
        if ($newerThan)
            $nt = '-newermt ' . $newerThan;
        else
            $nt = '';
        
        if ($subDir)
            $dir = $upDir . $subDir;
        else
            $dir = $upDir;
        
        $findListFile = $jl->getFileListFile();
        $cmd = sprintf('find %s -type f %s | sort > %s 2>> %s',
                      $dir, $nt, $findListFile, $jl->getLogFile());
        $rc = shell_exec($cmd);
        if ($rc)
            return 'find error: see logfile for details';
        
        $pathstr = trim( file_get_contents($findListFile) );
        if ($pathstr == '')
            return [];
        
        $pathstr = preg_replace("!$upDir!", '', $pathstr);
        $pathArr = explode("\n", $pathstr);
        
        return $pathArr;
    }
    
    # -------------------------------------------------------------------------
    # Backups
    # -------------------------------------------------------------------------
    
    #
    # (Doesn't return anything; initiated by WP Cron)
    #
    
    private function _backUpDb($how) {
        $jl = new JobLog();
        $jl->startJob('db', $how);
        
        $jobDir = $jl->getJobDir();
        $logFile = $jl->getLogFile();
        $result = 'OK';
        
        #
        # Try to do a number of things, any of which can fail
        #
        
        try {
            
            # Dump the DB
            $jl->log( sprintf("Dump database '%s'...", DB_NAME) );
            preg_match('!(\d{8}_\d{6})!', $jobDir, $toks);
            $now = $toks[1];
            $sqlFile = sprintf('%s/%s_%s.sql', $jobDir, DB_NAME, $now);
            $cmd = sprintf('%s --user=%s --password="%s" %s > %s 2>> %s',
                          'mysqldump', DB_USER, DB_PASSWORD, DB_NAME, 
                           $sqlFile, $logFile);
            $cmdnopass = preg_replace('!(password=")[^"]+"!', '\1*"', $cmd);
            $jl->log($cmdnopass);
            $rc = shell_exec($cmd);
            if ($rc)
                throw new \Exception('mysqldump error');

            # Compress it
            $jl->log("Gzip file...");
            $cmd = 'gzip ' . $sqlFile . ' 2>> ' . $logFile;
            $jl->log($cmd);
            $rc = shell_exec($cmd);
            if ($rc)
                throw new \Exception('gzip error');
        
            # FTP it
            $jl->log("FTP file...");
            $gzFile = $sqlFile . '.gz';
            $ftp = new Ftp($logFile);
            $ftpInfo = $this->_settings['ftpInfo'];
            $ftpInfo['password'] = $this->decrypt($ftpInfo['password']);
            $errmsg = $ftp->transferDbBackup($ftpInfo, $gzFile);
            if ($errmsg)
                throw new \Exception($errmsg);
            
        } catch (\Exception $e) {
            $jl->log("Error: " . $e->getMessage());
            $result = 'error';
        }

        if ($result == 'OK') {
            $this->saveData('lastDbBackup', time());
        }
        
        if ($how == 'scheduled') {
            $this->_maybeSendEmail($jl, 'DB', $result); 
        }
        
        $jl->endJob($result);
    }
    
    private function _backUpFiles($how) {
        $jl = new JobLog();
        $jl->startJob('files', $how);
        $result = 'OK';
        
        $lastBackup = $this->getData()['lastFilesBackup'];  # schmutzig...
        $then = ($lastBackup) ? date('Y-m-d', $lastBackup) : '';
        
        # TO DO: How would I detect an error in findFiles()?
        $jl->log("Find files newer than $then...");
        $filesArr = $this->findFiles($jl, '', $then);
        $jl->log( sprintf('%d file(s) found', count($filesArr)) );
        
        # If no files found, clean up and return
        if (!$filesArr) {
            $this->saveData('lastFilesBackup', time());
            $result .= ' (0 file(s) backed up)';
            $this->_maybeSendEmail($jl, 'Files', $result);
            $jl->endJob($result);
            return;
        }
        
        #
        # FTP the files
        #
        
        $upDir = WP_CONTENT_DIR . '/uploads'; 
        
        try {
            # FTP it
            $jl->log("FTP files...");
            $logFile = $jl->getLogFile();
            $ftp = new Ftp($logFile);
            $ftpInfo = $this->_settings['ftpInfo'];
            $ftpInfo['password'] = $this->decrypt($ftpInfo['password']);
            $errmsg = $ftp->transferFiles($ftpInfo, $filesArr, $upDir); 
        } catch (\Exception $e) {
            $jl->log("Error: " . $e->getMessage());
            $result = 'error';
        }

        if ($result == 'OK') {
            $this->saveData('lastFilesBackup', time());
            $result .= sprintf(' (%d file(s) backed up)', count($filesArr));
        }
        
        if ($how == 'scheduled') {
            $this->_maybeSendEmail($jl, 'Files', $result); 
        }
        
        $jl->endJob($result);
    }
    
    # -------------------------------------------------------------------------
    # cron
    # -------------------------------------------------------------------------
    
    
    #
    # Return timestamps for the next scheduled backups
    #
    
    public function getNextSchedules() {
        $foo = [
            'nextDbBackup' => Cron::GetNextScheduledJob(self::$_dbBackupActionName),
            'nextFilesBackup' => Cron::GetNextScheduledJob(self::$_filesBackupActionName)
        ];
        
        return $foo;
    }
    
    # -------------------------------------------------------------------------
    # Email
    # -------------------------------------------------------------------------
    
    public function sendEmail($to, $subject, $body) {
        if ( defined('JBR_DEV') ) {
            $email = "=== Begin Email:\nTo: $to\n" .
                "Subject: $subject\n\n" .
                $body . "\n" .
                "=== End Email";
            error_log($email);
            $errmsg = 'Mail written to server logfile';
        } else {
            $ok = mail($to, $subject, $body);
            $errmsg = ($ok) ? '' : error_get_last()['message'];
        }
        
        return $errmsg;
    }
    
    private function _maybeSendEmail($jl, $what, $result) {
        if (trim($this->_settings['sendEmailTo']) == '')
            return '';
        
        $when = $this->_settings['sendEmailWhen'];
        if ($when == 'never')
            return '';
        if ($when == 'error' && preg_match('!^OK\b!', $result))
            return '';
        
        $to = $this->_settings['sendEmailTo'];
        $subject = sprintf("%s backup report for site '%s'", 
            $what, get_bloginfo('name'));
        $body = 'Result: ' . $result . "\nLog File: " . $jl->getLogFileUrl();
        $errmsg = $this->sendEmail($to, $subject, $body);
        if ($errmsg) {
            error_log("JbrBackup sendEmail() errmsg ($errmsg) to ($to)" .
                      " subject ($subject) body ($body)");
        }

        return $errmsg;
    }
    
    # -------------------------------------------------------------------------
    # utilities
    # -------------------------------------------------------------------------
    
    public function encrypt($plaintxt) {
        $ivSize = openssl_cipher_iv_length(self::$_encryptMethod);
        $iv = openssl_random_pseudo_bytes($ivSize);
        $enctxt = openssl_encrypt($plaintxt, self::$_encryptMethod, self::$_encryptKey,
                                 OPENSSL_RAW_DATA, $iv);
        
        return base64_encode($iv . $enctxt);
    }
    
    public function decrypt($b64ivenctxt) {
        $ivenctxt = base64_decode($b64ivenctxt, true);
        $ivSize = openssl_cipher_iv_length(self::$_encryptMethod);
        $iv = mb_substr($ivenctxt, 0, $ivSize, '8bit');
        $enctxt = mb_substr($ivenctxt, $ivSize, null, '8bit');
        $plaintxt = openssl_decrypt($enctxt, self::$_encryptMethod, self::$_encryptKey,
                                   OPENSSL_RAW_DATA, $iv);
        
        return $plaintxt;
    }
    
    public function findBinaries() {
        $bins = ['sort', 'find', 'gzip', 'mysqldump'];
        $paths = [];
        $paths[] = 'shell: ' . shell_exec('echo $0');
        foreach ($bins as $bin) {
            $resp = shell_exec("which $bin");
            $paths[] = "$bin: $resp";
        }
        
        return join('<br>', $paths);
    }

}

#?>