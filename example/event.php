<?php
use Swoole\Coroutine;
use Swoole\Database\PDOConfig;
use Swoole\Database\PDOPool;
use Swoole\Runtime;

// APP_PATH 상수를 정의합니다.
if (strchr(PHP_OS, 'CYGWIN')) {
    defined('APP_PATH') or define('APP_PATH', __DIR__ . '/app');
} else {
    defined('APP_PATH') or define('APP_PATH', __DIR__);
}
// public 디렉터리의 경로를 정의합니다.
defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(APP_PATH . '/../public'));
defined('THIRDPARTY_PATH') or define('THIRDPARTY_PATH', realpath(APP_PATH . '/../third-party'));

require_once THIRDPARTY_PATH . '/vendor/autoload.php';

$events = new \Laminas\EventManager\EventManager();
$events->attach('test', function ($e) {
    $event = $e->getName();
    $params = $e->getParams();
    printf(
        'Handled event "%s", with parameters %s' . PHP_EOL,
        $event,
        json_encode($params)
    );

    return ['params' => $params, 'result' => 'ok'];
});

Coroutine\run(function () use ($events) {
    $pool = new PDOPool((new PDOConfig)
        ->withHost('host.docker.internal')
        ->withPort(3306)
        ->withDbName('swoole')
        ->withCharset('utf8mb4')
        ->withUsername('swoole')
        ->withPassword('swoole')
    );

    var_dump($pool->get());

    go(function () use ($events) {
        sleep(1);
        $res = $events->trigger('test', null, ['1', 'bar', microtime(true)]);
    });

    go(function () use ($events) {
        $res = $events->trigger('test', null, ['2', 'bar', microtime(true)]);
    });

    go(function () use ($events) {
        $res = $events->trigger('test', null, ['3', 'bar', microtime(true)]);
    });
});