<?php

namespace App\Util;

use App\Controller\Helper\Session\Vars;
use App\Util\Mysql;
use App\Util\Logger;

/**
 * @codeCoverageIgnore
 */
class Session implements SharedObject
{
    private $logger;
    private $config;
    private $mysql;
    
    public function __construct(Logger $logger, Config $config, Mysql $mysql)
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->mysql = $mysql;
    }
            
    const SECONDS_PER_DAY = 86400;
    
    public function start()
    {
        $this->logger->log('Starting session');        
        ini_set('session.gc_maxlifetime', 30 * self::SECONDS_PER_DAY);
        session_name($this->config->get('sessionName','PHPSESSID')); 
        
        $this->logger->log('Setting up session save handler');
        
        // Set handler to overide SESSION  
        session_set_save_handler(  
            array($this, "_open"),  
            array($this, "_close"),  
            array($this, "_read"),  
            array($this, "_write"),  
            array($this, "_destroy"),  
            array($this, "_gc")  
        );
        
        // the following prevents unexpected effects 
        // when using objects as save handlers
        // @see http://php.net/manual/en/function.session-set-save-handler.php 
        register_shutdown_function('session_write_close');
        
        session_start();
    }

    public function end()
    {
        session_destroy();
    }

    public function regenerate()
    {
        session_regenerate_id(true);
    }

    public function set($var, $value)
    {
        if (!$this->isStarted()) {
            throw new \Exception('Cannot set session variable until session is started.');
        }
        
        $_SESSION[$var] = $value;
    }

    public function get($var)
    {
        if (isset($_SESSION[$var])) {
            return $_SESSION[$var];
        }
        return null;
    }
    
    public function unset($var)
    {
        unset($_SESSION[$var]);
    }

    public function getOnce($var)
    {
        if (isset($_SESSION[$var])) {
            if (is_object($_SESSION[$var])) {
                $val = clone $_SESSION[$var];
            } else {
                $val = $_SESSION[$var];
            }
            unset($_SESSION[$var]);
            return $val;
        }
        return null;
    }

    public function delete($var)
    {
        unset($_SESSION[$var]);
    }

    public function getSessionId()
    {
        return session_id();
    }

    public function getRequestId()
    {
        $fields = $_SERVER['REMOTE_ADDR'] .
            $_SERVER['REQUEST_TIME'] .
            $_SERVER['REMOTE_PORT'];

        return sprintf("%08x", abs(crc32($fields)));
    }

    public function getAll()
    {
        return isset($_SESSION) ? $_SESSION : [];
    }
    
    public function getBasic()
    {
        if (isset($_SESSION)) {
            $basic = $_SESSION;
            unset($basic['csrfTokens']);
            unset($basic['hmacKeys']);
            return $basic;
        }
        return [];
    }

    public function isStarted()
    {
        return session_status() == PHP_SESSION_ACTIVE;
    }
    
    public function _open()
    {
        
        $this->logger->log('Session::_open()');
        
        return true;
    }
    
    public function _close()
    {  
        $this->logger->log('Session::_close()');
        $this->semRelease();
        return true;
    }  
    
    /**
     * Why do we need semAcquire()?
     * 
     * PHP sessions that use files will benefit from file locking. The file 
     * locking system also prevents simultaneous READS of the session data, so
     * even if multiple incoming Apache requests are arriving, only one will
     * read for the duration of the request, making each request essentially
     * synchronous.
     * 
     * Now, since we've switched to DB sessions, we lose that locking mechanism.
     * We could implement locks with MySQL but that means dealing with some
     * deadlocks. Instead, we'll use semaphore-based locking.
     * 
     * For some requests that are not going to ever write to the session, we
     * can preemptively close the session (such as in EventsController). This
     * is very important, because long-lived requests like EventSource calls
     * would otherwise end up locking the session indefinitely, which prevents
     * any other site requests (for the same session) to come through.
     * 
     * Since many requests are API XHR requests and in many cases multiple ones
     * are sent simultaneously, we need to lock in order to keep the session
     * data consistent. Otherwise, an earlier read can end up writing stale data.
     */
    private function semAcquire($id)
    {
        $semInt = intval(substr(md5($id), 0, 8), 16);
        $this->sem = sem_get($semInt, 1, 0777);
        $this->logger->log("Acquiring lock for sessionId=$id, int=$semInt");
        sem_acquire($this->sem);
        $this->logger->log("Acquired successfully.");
    }
    
    private function semRelease()
    {
        $this->logger->log("Releasing lock for session.");
        sem_release($this->sem);
    }
    
    public function _read($id) 
    {
        $this->logger->log('Reading sessionId=' . $id);
        
        $this->semAcquire($id);
        
        // Set query  
        $query = 'select data from sessions where sessionId = ?';

        // Bind the Id  
        $types = 's';
        $params = [$id];
        
        $rows = $this->mysql->query($query, $types, $params);
        
        if (isset($rows[0]) && isset($rows[0]['data'])) {
            return $rows[0]['data'];
        }
        
        $this->logger->log('Missing sessionId=' . $id);
        return '';
    }
    
    // Sometimes we want to pre-emptively close for long-running PHP requests
    // such as the EventSource.
    public function close()
    {
        $this->logger->log('Pre-emptively closing the session');
    }
    
    public function _write($id, $data) 
    {
        $this->logger->log('Updating sessionId=' . $id);
        
        // Create time stamp  
        $access = time();
        
        // Set query  
        $query = 'replace into sessions (sessionId, access, data, activeLock) values (?, ?, ?, ?)';
        
        $types = 'sisi';
        $params = [$id, $access, $data, 1];
        
        $updated = $this->mysql->query($query, $types, $params);
        
        // Why 1? Replace = if record doesn't exist, only insert happens.
        // Why 2? Replace = a call to delete happens, then a call to insert.
        if ($updated != 1 && $updated != 2) {
            // Unable to update session
            $this->logger->log(json_encode([$id, $access, $data]));
            $this->logger->log('Unable to update sessionId=' . $id);
            throw new \Exception('Unable to update session');
            return false;
        }
        
        return true;
    }
    
    public function _destroy($id) {
        
        $this->logger->log('Deleting sessionId=' . $id);
        
        $query = 'delete from sessions where sessionId = ?';

        $types = 's';
        $params = [$id];
        
        $deleted = $this->mysql->query($query, $types, $params);
        
        // Sometimes the session is already deleted.
        if ($deleted != 0 && $deleted != 1) {
            $this->logger->log('Unable to delete sessionId=' . $id);
            return false;
        }
        
        return true;
    }
    
    public function _gc($max) 
    {
        // Let's keep sessions for 1 year
        $max = 31556952;
        
        $this->logger->log('Running session garbage collect');
        // Calculate what is to be deemed old  
        $old = time() - $max;

        // Set query  
        $query = 'delete from sessions where access < ?';
        
        $types = 'i';
        $params = [$old];
        
        $this->mysql->query($query, $types, $params);
        
        // Can be 0 or more deleted records, so just return true.
        return true;
    }
}