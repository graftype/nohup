<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

namespace nextposttech\nohup;

class Process
{
    protected $pid;
    protected $droplet;

    public function __construct($pid, $droplet = null)
    {
        $this->pid = $pid;
        $this->droplet = $droplet;
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function getDroplet()
    {
        return $this->droplet; 
    }

    /**
     * Check the process is already running via pid
     * @return bool
     */
    public function isRunning()
    {
        if (OS::isWin() && empty($this->droplet)) {
            $cmd = "wmic process get processid | find \"{$this->pid}\"";
            $res = array_filter(explode(" ", shell_exec($cmd)));
            return count($res) > 0 && $this->pid == reset($res);
        } else {
            if (class_exists('\Event') && !empty($this->droplet)) {
                return \Event::trigger("load_balancing.nohup.doctl.is_running", $this->droplet, $this->pid);
            }
            return !!posix_getsid($this->pid);
        }
    }

    /**
     * Stop the process via pid
     */
    public function stop()
    {
        if (OS::isWin() && empty($this->droplet)) {
            $cmd = "taskkill /pid {$this->pid} -t -f";
        } else {
            $cmd = "kill -9 {$this->pid}";
        }
        if (class_exists('\Event') && !empty($this->droplet)) {
            $cmd = \Event::trigger("load_balancing.nohup.doctl.command", $this->droplet, $cmd);
        }
        shell_exec($cmd);
    }

    public static function loadFromPid($pid)
    {
        return new static($pid);
    }
}
