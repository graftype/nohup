# php-nohup

A library to run a command in the background, it will return the process's pid, and get it's is running status anytime in the another process, and can be stoped anytime. 

# Navigation

- [Installation](#installation)
- [Usage](#usage)
  - [Run a script in background](#run-a-script-in-background)
  - [Create process from known pid ($pid)](#create-process-from-known-pid-pid)

# Installation

Using composer:  

```console
composer require nextpost-tech/nohup
```

# Usage

## Run a script in background

```php
use nextposttech\nohup;

$process = Nohup::run('sleep 5');
```

It will be running in the background for 5 seconds. 

But, it can be stoped any time:

```php
//...
$process->stop();
```
It stoped now!

Get the pid : `$process->getPid()`, it will return the real pid in both window and *inx system.

Get it's running status with the function `$process->isRunning()`:

```php
use nextposttech\nohup\Nohup;

$process = Nohup::run('sleep 5');
while ($process->isRunning()) {
    echo '.';
    sleep(1);
}
echo "Done.\n";

```
*output*: `.....Done.`   

## Create process from known pid ($pid)

```php
use nextposttech\nohup\Process;

$process = Process::loadFromPid($pid);  
// or
$process = new Process($pid); 

if ($process->isRunning()) {
    $process->stop();
}
```

### Method:
`Nohup::run($commandLine, $outputFile, $errorFile)`  
- `$commandLine`: string, the command will be run.  
- `$outputFile`: string, the file path where to save output content.  
- `$errlogFile`: string, the file path where to save error message.  
