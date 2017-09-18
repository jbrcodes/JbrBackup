<?php

namespace JbrBackup;

defined('ABSPATH') or die();

class Ftp {
    
    # -------------------------------------------------------------------------
    # instance variables
    # -------------------------------------------------------------------------
    
    private $_connId = null;
    private $_logFile = '';
    
    # -------------------------------------------------------------------------
    # constructor
    # -------------------------------------------------------------------------
    
    public function __construct($logFile='') {
        $this->_logFile = $logFile;
    }
    
    # -------------------------------------------------------------------------
    # public methods
    # -------------------------------------------------------------------------
 
    public function testConnection($creds) {
        $this->_connId = ftp_connect($creds['host']);
        if (!$this->_connId)
            return 'Error: could not connect to host';
        
        $msg = 'Server connection succeeded';
        try {
            
            if ( !ftp_login($this->_connId, $creds['user'], $creds['password']) ) {
                throw new \Exception('login failed');
            }
            
            if ( $creds['passiveMode'] == 'y' ) {
                 if ( !ftp_pasv($this->_connId, true) ) {
                     throw new \Exception('set passive mode failed');
                 }       
            } 
            
            if ( !ftp_chdir($this->_connId, $creds['remoteDbDir']) ) {
                throw new \Exception('remote DB directory not found');
            }

            if ( !ftp_chdir($this->_connId, $creds['remoteFilesDir']) ) {
                throw new \Exception('remote files directory not found');
            }
            
        } catch (\Exception $e) {
            $msg = 'Error: ' . $e->getMessage();
        } finally {
            ftp_close($this->_connId);
        }

        return $msg;       
    }
    
    #
    # Transfer a file to a remote FTP server. Returns '' on success
    #
    
    public function transferDbBackup($creds, $localPath) {
        $this->_log("Starting transfer of " . basename($localPath));
        $this->_connId = ftp_connect($creds['host']);
        if (!$this->_connId) {
            $msg = 'could not connect to host';
            $this->_log($msg);
            return $msg;
        }
        
        $msg = $sysmsg = '';
        try {
            
            if ( !ftp_login($this->_connId, $creds['user'], $creds['password']) ) {
                throw new \Exception('login failed');
            }
            
            if ( $creds['passiveMode'] == 'y' ) {
                 if ( !ftp_pasv($this->_connId, true) ) {
                     throw new \Exception('set passive mode failed');
                 }       
            } 

            $fileName = basename($localPath);
            $remotePath = $creds['remoteDbDir'] . '/' . $fileName;
            if ( !ftp_put($this->_connId, $remotePath, $localPath, FTP_BINARY) ) {
                throw new \Exception('PUT failed');
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $sysmsg = error_get_last()['message'];
        } finally {
            ftp_close($this->_connId);
        }
        
        if ($msg)
            $this->_log("$msg: $sysmsg");

        return $msg;
    }
    
    public function transferFiles($creds, $filesArr, $localUploadDir) {
        $this->_log("Starting transfer of files");
        $this->_connId = ftp_connect($creds['host']);
        if (!$this->_connId) {
            $msg = 'could not connect to host';
            $this->_log($msg);
            return $msg;
        }
        
        $msg = $sysmsg = '';
        try {
            
            if ( !ftp_login($this->_connId, $creds['user'], $creds['password']) ) {
                throw new \Exception('login failed');
            }
            
            if ( $creds['passiveMode'] == 'y' ) {
                 if ( !ftp_pasv($this->_connId, true) ) {
                     throw new \Exception('set passive mode failed');
                 }       
            } 

            foreach ($filesArr as $localPath) {
                $locAbs = $localUploadDir . $localPath;
                $remAbs = $creds['remoteFilesDir'] . $localPath;
                $parts = pathinfo($remAbs);
                if ( !@ftp_chdir($this->_connId, $parts['dirname']) ) {
                    $errmsg = $this->_makeDirs($parts['dirname']);
                    if ($errmsg)
                        throw new \Exception($errmsg);
                }

                $this->_log("ftp_put($locAbs)");  # DEBUG (or is it?)
                if ( !ftp_put($this->_connId, $remAbs, $locAbs, FTP_BINARY) ) {
                    throw new \Exception("PUT failed for '$locAbs'");
                }
            }

        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $sysmsg = error_get_last()['message'];
        } finally {
            ftp_close($this->_connId);
        }
        
        if ($msg)
            $this->_log("$msg: $sysmsg");

        return $msg;
    }
    
    # -------------------------------------------------------------------------
    # Private methods
    # -------------------------------------------------------------------------
    
    private function _log($msg) {
        if ($this->_logFile)
            error_log("Ftp: $msg\n", 3, $this->_logFile);
    }
    
    private function _makeDirs($dir) {
        $errmsg = '';
        
        # Find how many subdirs we have to make
        $dirsToMake = [];
        while ( !@ftp_chdir($this->_connId, $dir) ) {
            preg_match('!(.*)/([^/]+)$!', $dir, $toks);
            $dirsToMake[] = $toks[2];
            $dir = $toks[1];
        }
        
        # Make them!
        $dirsToMake = array_reverse($dirsToMake);
        foreach ($dirsToMake as $d) {
            $dir .= "/$d";
            $this->_log("ftp_mkdir($dir)");  # DEBUG (or is it?)
            if ( !ftp_mkdir($this->_connId, $dir) ) {
                $errmsg = "ftp_mkdir('$dir') failed";
                $this->_log($errmsg);
                break;
            }
        }
        
        return $errmsg;
    }
}

#?>
































