<?php
/**
 * php-nohup
 * @version 1.0
 * @author Graftype (https://graftype.com)
 */

namespace graftype\nohup;

class OS
{
    public static function isWin()
    {
        return substr(strtoupper(PHP_OS), 0, 3) === 'WIN';
    }
}
