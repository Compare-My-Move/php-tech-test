<?php
namespace App\Logger;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerFactory
{
    public static function create(): Logger
    {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler(__DIR__ . '/../../logs/app.log', Logger::WARNING));
        return $logger;
    }
}