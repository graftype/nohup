<?php
/**
 * php-nohup
 * @version 1.0
 * @author Nextpost.tech (https://nextpost.tech)
 */

namespace nextposttech\nohup;

class OS
{
    public static function isWin()
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }
}
