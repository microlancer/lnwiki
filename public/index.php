<?php 
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', false);
ini_set('log_errors', true);
ini_set("error_log", __DIR__ . '/../logs/app.log');
ini_set('ignore_user_abort', true);
$startTime = microtime(true);

require_once '../app/Util/Autoloader.php';
require_once '../app/Util/Di.php';

use App\Util\Autoloader;
use App\Util\Di;
use App\Util\Logger;
use App\Util\Route;
use App\Util\View;
use App\Util\Config;
use App\Util\HeaderParams as Headers;

Di::getInstance()->get(Autoloader::class)->register();
Di::getInstance()->initApcu();

/** @var Config $config */
$config = Di::getInstance()->get(Config::class);
if (in_array($config->get('env'), ['dev', 'stage'])) {
    ini_set('display_errors', true);    
}

if ($config->get('env') !== 'dev') {
    // this breaks cookies when doing command-line curl calls, not sure why
    ini_set('session.cookie_domain', $config->get('session.cookie_domain'));
}

/** @var Logger $logger */
$logger = Di::getInstance()->get(Logger::class);

/** @var Route $route */
$route = Di::getInstance()->get(Route::class);

try {
    $route->dispatch($_REQUEST);
    $logger->logTimerEnd("End of request", $startTime);
} catch (\Exception $e) {
    /* @var $headers Headers */
    $headers = Di::getInstance()->get(Headers::class);
    $headers->setResponseCode(500);
    
    if ($headers->getContentType() == Headers::CONTENT_TYPE_JSON) {
        if ($config->get('env') == 'dev') {
            $arr = ['exception' => (string)$e];
        } else {
            $arr = ['exception' => 'Internal Server Error'];
        }
        echo json_encode($arr, JSON_PRETTY_PRINT);
        return;
    }
    
    Di::getInstance()->get(View::class)->render('error');
    Di::getInstance()->get(Logger::class)->log((string)$e, Logger::ERROR);
    throw $e;
}
