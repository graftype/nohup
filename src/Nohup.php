<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

namespace nextposttech\nohup;

use nextposttech\nohup\exceptions\RuntimeException;

class Nohup
{
    public static function run($commandLine, $droplet = null, $outputFile = null, $errlogFile = null)
    {
        $command = new Command($commandLine, $outputFile, $errlogFile);
        return self::runCommand($command, $droplet);
    }

    public static function runCommand(Command $command, $droplet = null)
    {
        if (OS::isWin() && empty($droplet)) {
            $pid = self::runWindowsCommand($command);
        } else {
            $pid = self::runNixCommand($command, $droplet);
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

    protected static function runWindowsCommand(Command $command)
    {
        $commandLine = "START /b " . $command;
        $descriptions = self::getDescription($command);
        $handle = proc_open(
            $commandLine,
            $descriptions,
            $pipes,
            getcwd()
        );
        if (!is_resource($handle)) {
            throw new RuntimeException('Unable to launch a background process');
        }
        $processInfo = proc_get_status($handle);
        $ppid = $processInfo['pid'];
        proc_close($handle);
        return self::getWindowsRealPid($ppid);
    }

    protected static function runNixCommand(Command $command, $droplet = null)
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
        if (class_exists('\Event') && !empty($droplet)) {
            $commandLine = \Event::trigger("load_balancing.nohup.doctl.command", $droplet, $commandLine);
        }
        return (int) shell_exec($commandLine);
    }
}
