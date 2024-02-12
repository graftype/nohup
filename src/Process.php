<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

namespace nextposttech\nohup;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class Process
{
    protected $pid;
    protected $ssh;
    protected $ip = "";
    protected $port = 22;
    protected $username;
    protected $password;
    protected $privatekey;

    public function __construct($pid, $auth = [])
    {
        $this->pid = $pid;

        $this->ip = !empty($auth["ip"]) ? $auth["ip"] : "";
        $this->port = !empty($auth["port"]) ? $auth["port"] : 22;
        $this->username = !empty($auth["username"]) ? $auth["username"] : "root"; 
        $this->password = !empty($auth["port"]) ? $auth["port"] : "";
        $this->privatekey = !empty($auth["privatekey"]) ? $auth["privatekey"] : "";

        if (!empty($this->ip)) {
            $this->ssh = new SSH2($this->ip, $this->port);
            if (empty($this->privatekey)) {
                if (!$this->ssh->login($this->username, $this->password)) {
                    throw new \Exception(sprintf('Nohup | SSH authentication to server %s failed. Please try again or contact support.', $this->ip));
                }
            } else {
                $key = PublicKeyLoader::load($this->privatekey);
                if (!$this->ssh->login($this->username, $key)) {
                    throw new \Exception(sprintf('Nohup | SSH authentication via private key to server %s failed. Please try again or contact support.', $this->ip));
                }
            }
            $this->ssh->enableQuietMode();
        }
    }

    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Check the process is already running via pid
     * @return bool
     */
    public function isRunning()
    {
        if (OS::isWin()) {
            if (!empty($this->ssh)) {
                throw new \Exception('Nohup | SSH functionality for Nohup not supported on Windows platform yet.');
            }
            $cmd = "wmic process get processid | find \"{$this->pid}\"";
            $res = array_filter(explode(" ", shell_exec($cmd)));
            return count($res) > 0 && $this->pid == reset($res);
        } else {
            if (!empty($this->ssh)) {
                return (bool) $this->ssh->exec("[ -f /proc/{$this->pid}/status ] && echo 1 || echo 0");
            } else {
                return !!posix_getsid($this->pid);
            }
        }
    }

    /**
     * Stop the process via pid
     */
    public function stop()
    {
        if (OS::isWin()) {
            if (!empty($this->ssh)) {
                throw new \Exception('Nohup | SSH functionality for Nohup not supported on Windows platform yet.');
            }
            $cmd = "taskkill /pid {$this->pid} -t -f";
        } else {
            $cmd = "kill -9 {$this->pid}";
        }
        if (!empty($this->ssh)) {
            $this->ssh->exec($cmd);
        } else {
            shell_exec($cmd);
        }
    }

    public static function loadFromPid($pid, $auth = [])
    {
        return new static($pid, $auth);
    }
}
