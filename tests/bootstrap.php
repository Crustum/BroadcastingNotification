<?php
declare(strict_types=1);

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\TestSuite\Fixture\SchemaLoader;

define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');
define('TESTS', ROOT . DS . 'tests' . DS);
define('TMP', sys_get_temp_dir() . DS);
define('CONFIG', ROOT . DS . 'config' . DS);

require_once ROOT . '/vendor/autoload.php';

Configure::write('debug', true);
Configure::write('App', [
    'namespace' => 'Crustum\Notification\Broadcasting',
    'paths' => [
        'templates' => [ROOT . DS . 'templates' . DS],
    ],
]);

Cache::setConfig([
    '_cake_core_' => [
        'engine' => 'File',
        'prefix' => 'cake_core_',
        'serialize' => true,
    ],
]);

if (!getenv('DB_DSN')) {
    putenv('DB_DSN=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
    'url' => getenv('DB_DSN'),
    'timezone' => 'UTC',
]);

ConnectionManager::alias('test', 'default');

Configure::write('Authentication', [
    'unauthenticatedRedirect' => false,
    'queryParam' => 'redirect',
]);

$loader = new SchemaLoader();
$loader->loadInternalFile(TESTS . 'schema.php');

Log::setConfig([
    'debug' => [
        'engine' => 'Cake\Log\Engine\FileLog',
        'levels' => ['notice', 'info', 'debug'],
        'file' => 'debug',
        'path' => TMP,
    ],
    'error' => [
        'engine' => 'Cake\Log\Engine\FileLog',
        'levels' => ['warning', 'error', 'critical', 'alert', 'emergency'],
        'file' => 'error',
        'path' => TMP,
    ],
]);
