<?php

namespace JbrBackup;

defined('ABSPATH') or die();

#
# "Encapsulate" everything related to documentation for a backup job
#

class JobLog {
    
    # -------------------------------------------------------------------------
    # class variables
    # -------------------------------------------------------------------------
    
    private static $_BaseDir = WP_CONTENT_DIR . '/jbrbackup';
    private static $_BaseDirUrl = WP_CONTENT_URL . '/jbrbackup';
    
    # -------------------------------------------------------------------------
    # WordPress callbacks
    # -------------------------------------------------------------------------
    
    #
    # Since WP doesn't have an OnInstall hook, we have to catch OnActivate and
    # see if there are traces of JobLog already.
    #
    
    public static function OnActivate() {
        global $wpdb;
        
        error_log('JobLog::OnActivate');
        
        $var = $wpdb->get_var( "SHOW TABLES LIKE 'jbrbu_job_log'" );
        if (!$var) 
            self::OnInstall();
    }
    
    #
    # Create DB table and wp-content/jbrbackup dir
    #
    
    public static function OnInstall() {
        global $wpdb;

        error_log('JobLog::OnInstall');
        
        # Create DB table
        $path = WP_PLUGIN_DIR . '/JbrBackup/schema.sql';
        $sql = file_get_contents($path);
        $wpdb->query($sql);
        
        # Create job dir
        if ( !file_exists(self::$_BaseDir) )
            mkdir(self::$_BaseDir);
    }
    
    #
    # Drop DB table, delete wp-content/jbrbackup dir
    #
    
    public static function OnUninstall() {
        global $wpdb;
        
        error_log('JobLog::OnUninstall');
        
        # Drop our only table
        $sql = "DROP TABLE IF EXISTS jbrbu_job_log";
        $wpdb->query($sql);
        
        # Remove our job dir
        # (Is this better or worse than: rm -r ?)
        self::_RecursiveRmdir(self::$_BaseDir);
    }
    
    # -------------------------------------------------------------------------
    # Class methods
    # -------------------------------------------------------------------------
    
    public static function GetRecent() {
        global $wpdb;
        
        $sql = 'SELECT * FROM jbrbu_job_log ORDER BY id DESC';
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        # Add a link to each log file
        $baseUrl = preg_replace('!.*(/wp-content)!', '\1', self::$_BaseDir);
        for ($i=0; $i<count($results); $i++) {
            $url = sprintf('%s/%s/log.txt', $baseUrl, $results[$i]['dirName']);
            $results[$i]['logFileUrl'] = $url;
            $url = sprintf('%s/%s/files.txt', $baseUrl, $results[$i]['dirName']);
            $results[$i]['fileListUrl'] = $url;
        }
        
        return $results;
    }
    
    public static function GetLastOne($what) {
        global $wpdb;
        
        $sql = "SELECT * FROM jbrbu_job_log" .
            " WHERE what = '$what' ORDER BY id DESC LIMIT 1";
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results ? $results[0] : null;
    }
    
    #
    # Clean up the job logs, deleting DB table entries and directories
    # older than $saveHowLong. We aren't too worried about being
    # exact. :-}
    #
    
    public static function CleanUp($saveHowLong='2 months') {
        global $wpdb;
        
        # DB
        preg_match('!(\d+)\s+(\w+?)s?\b!', $saveHowLong, $toks);
        $where = sprintf('startedStamp < DATE_SUB(NOW(), INTERVAL %d %s)',
                         $toks[1], $toks[2]);
        $sql = 'DELETE FROM jbrbu_job_log WHERE ' . $where;
        $wpdb->query($sql);
        
        # Files & dirs
        # 'find' magic!
        # find $dir -maxdepth 1 ! -newermt '-1 week' -exec rm -r \{} \;
        $cmd = sprintf('find %s -maxdepth 1 ! -newermt \'-%s\' -exec rm -r \{} \;',
                      self::$_BaseDir, $saveHowLong);
        $rc = shell_exec($cmd);
        # and if there's an error ???
    }
    
    #
    # Recursive directory delete (like rm -r). (Not written by me.)
    #
    
    private static function _RecursiveRmdir($dir) {
        if ( !is_dir($dir) || is_link($dir) )
            return unlink($dir);
        
        if ( is_dir($dir) ) {
            $objs = scandir($dir);
            foreach ($objs as $obj) {
                if ($obj == '.' || $obj == '..')
                    continue;
                $path = "$dir/$obj";
                if (filetype($path) == 'dir') 
                    self::_RecursiveRmdir($path); 
                else 
                    unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    # -------------------------------------------------------------------------
    # instance variables
    # -------------------------------------------------------------------------
    
    private $_jobDir = '';
    private $_logFile = '';
    private $_dbRecId = 0;
    
    # -------------------------------------------------------------------------
    # constructor
    # -------------------------------------------------------------------------
    
    public function __construct() {

    }
    
    # -------------------------------------------------------------------------
    # [top level]
    # -------------------------------------------------------------------------
    
    public function startJob($what, $how) {
        $dirName = date('Ymd_His') . '_' . $what;
        $this->_jobDir = sprintf('%s/%s', self::$_BaseDir, $dirName);
        $ok = mkdir($this->_jobDir);  # DID IT SUCCEED???
        $this->_logFile = $this->_jobDir . '/log.txt';
        $this->log('Job started');
        $this->_insertDb($dirName, $what, $how);
    }
    
    public function endJob($result) {
        $this->log("Result: $result");
        $this->log('Job ended');
        $this->_endDb($result);
    }
    
    public function getFileListFile() {
        return $this->_jobDir . '/files.txt';
    }
    
    public function getFileListFileUrl() {
        # FIX/IMPROVE ME
        $dir = self::$_BaseDir;
        $url = self::$_BaseDirUrl;
        return preg_replace("!^$dir!", $url, $this->getFileListFile());
    }
    
    public function getJobDir() {
        return $this->_jobDir;
    }
    
    public function getLogFile() {
        return $this->_logFile;
    }
    
    public function getLogFileUrl() {
        # FIX/IMPROVE ME
        $dir = self::$_BaseDir;
        $url = self::$_BaseDirUrl;
        return preg_replace("!^$dir!", $url, $this->_logFile);
    }
    
    # -------------------------------------------------------------------------
    # logging
    # -------------------------------------------------------------------------
    
    public function log($msg) {
        $now = date('Y-m-d H:i:s');
        error_log(
            sprintf("[%s] %s\n", $now, $msg), 3, $this->_logFile
        );
    }

    # -------------------------------------------------------------------------
    # DB stuff
    # -------------------------------------------------------------------------
    
    private function _insertDb($dirName, $what, $how) {
        global $wpdb;
        
        $fmt = 'INSERT INTO jbrbu_job_log' .
            ' (dirName, what, how)' .
            " VALUES ('%s', '%s', '%s')";
        $sql = sprintf($fmt, $dirName, $what, $how);
        $wpdb->query($sql);
        $this->_dbRecId = $wpdb->insert_id;
    }
    
    private function _endDb($result) {
        global $wpdb;
        
        $fmt = 'UPDATE jbrbu_job_log' .
            " SET endedStamp = NOW(), result = '%s'" .
            " WHERE id = %d";
        $sql = sprintf($fmt, addslashes($result), $this->_dbRecId);
        $wpdb->query($sql);
    }

    
}

#?>