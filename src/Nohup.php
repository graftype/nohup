<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

namespace nextposttech\nohup;

use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;

class Nohup
{
    protected $ssh;
    protected $ip = "";
    protected $port = 22;
    protected $username;
    protected $password;
    protected $privatekey;
    
    public static function run($commandLine, $outputFile = null, $errlogFile = null, $auth = [])
    {
        $command = new Command($commandLine, $outputFile, $errlogFile);
        return self::runCommand($command, $auth);
    }

    public static function runCommand(Command $command, $auth = [])
    {
        if (OS::isWin()) {
            $pid = self::runWindowsCommand($command, $auth);
        } else {
            $pid = self::runNixCommand($command, $auth);
        }
        return new Process($pid);
    }

    protected static function getWindowsRealPid($ppid)
    {
        $fetchCmd = "wmic process get parentprocessid, processid | find \"$ppid\"";
        $res = array_filter(explode(" ", shell_exec($fetchCmd)));
        array_pop($res);
        $pid = end($res);
        return (int) $pid;
    }

    protected static function getDescription(Command $command)
    {
        if ($command->getOutputFile()) {
            $stdoutPipe = ['file', $command->getOutputFile(), 'w'];
        } else {
            $stdoutPipe = fopen('NUL', 'c');
        }

        if ($command->getErrlogFile()) {
            $stderrPipe = ['file', $command->getErrlogFile(), 'w'];
        } else {
            $stderrPipe = fopen('NUL', 'c');
        }
        return [
            ['pipe', 'r'],
            $stdoutPipe,
            $stderrPipe
        ];
    }

    protected static function runWindowsCommand(Command $command, $auth = [])
    {
        if (!empty($auth)) {
            throw new \Exception('Nohup | SSH functionality for Nohup not supported on Windows platform yet.');
        }

        $commandLine = "START /b " . $command;
        $descriptions = self::getDescription($command);
        $handle = proc_open(
            $commandLine,
            $descriptions,
            $pipes,
            getcwd()
        );
        if (!is_resource($handle)) {
            throw new \Exception('Unable to launch a background process');
        }
        $processInfo = proc_get_status($handle);
        $ppid = $processInfo['pid'];
        proc_close($handle);
        return self::getWindowsRealPid($ppid);
    }

    protected static function runNixCommand(Command $command, $auth = [])
    {
        $output = ' >/dev/null';
        $error = ' 2>/dev/null';
        if ($command->getOutputFile()) {
            $output = ' >' . $command->getOutputFile();
        }
        if ($command->getErrlogFile()) {
            $error = ' 2>'. $command->getErrlogFile();
        }
        $commandLine = $command . $output . $error . "& echo $!";

        $ip = !empty($auth["ip"]) ? $auth["ip"] : "";
        $port = !empty($auth["port"]) ? $auth["port"] : 22;
        $username = !empty($auth["username"]) ? $auth["username"] : "root"; 
        $password = !empty($auth["port"]) ? $auth["port"] : "";
        $privatekey = !empty($auth["privatekey"]) ? $auth["privatekey"] : "";

        if (!empty($ip)) {
            $ssh = new SSH2($ip, $port);
            if (empty($privatekey)) {
                if (!$ssh->login($username, $password)) {
                    throw new \Exception(sprintf('Nohup | SSH authentication to server %s failed. Please try again or contact support.', $ip));
                }
            } else {
                $key = PublicKeyLoader::load($privatekey);
                if (!$ssh->login($username, $key)) {
                    throw new \Exception(sprintf('Nohup | SSH authentication via private key to server %s failed. Please try again or contact support.', $ip));
                }
            }
            $ssh->enableQuietMode();
        }

        if (!empty($ssh)) {
            return (int) $ssh->exec($commandLine);
        } else {
            return (int) shell_exec($commandLine);
        }
    }
}
