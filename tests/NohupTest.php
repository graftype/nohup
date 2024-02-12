<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

class NohupTest extends \PHPUnit_Framework_TestCase
{
    protected $errorScript;

    public function setUp()
    {
        parent::setUp();
        $script = \nextposttech\nohup\OS::isWin() ? 'error.bat' : 'error';
        $this->errorScript = __DIR__ . DIRECTORY_SEPARATOR . "fixtures" .DIRECTORY_SEPARATOR . $script;

    }

    public function testProccessRunningStatus()
    {
        $cmd = 'php -r "sleep(2);"';
        $process = \nextposttech\nohup\Nohup::run($cmd);
        $this->assertTrue($process->isRunning());
        while ($process->isRunning()) {
            sleep(1);
        }
        $this->assertFalse($process->isRunning());
    }

    public function testProcessCanBeStop()
    {
        $cmd = 'php -r "sleep(5);"';
        $process = \nextposttech\nohup\Nohup::run($cmd);
        $this->assertTrue($process->isRunning());
        $process->stop();
        $this->assertFalse($process->isRunning());
    }

    public function testOutputCanBeWriteToFile()
    {
        $cmd = 'php -r "echo \"OK\";"';
        $file = $this->tempfile();
        $process = \nextposttech\nohup\Nohup::run($cmd, $file);
        while ($process->isRunning()) {
            sleep(1);
        }
        $string = file_get_contents($file);
        unlink($file);
        $this->assertEquals('OK', $string);
    }

    public function testErrCanBeWriteToFile()
    {
        $file = $this->tempfile();
        $process = \nextposttech\nohup\Nohup::run($this->errorScript, '', $file);
        while ($process->isRunning()) {
            sleep(1);
        }
        $string = file_get_contents($file);
        unlink($file);
        $this->assertEquals('error', $string);
    }

    protected function tempfile()
    {
        return tempnam(sys_get_temp_dir(), mt_rand(1000000, 9000000) . '.nohup_test');
    }
}
