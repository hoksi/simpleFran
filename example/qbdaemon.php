<?php
// Swoole 확장을 로드합니다.
extension_loaded('swoole') or die('Swoole extension is not loaded.');

echo PHP_OS . PHP_EOL;

// APP_PATH 상수를 정의합니다.
defined('APP_PATH') or define('APP_PATH', __DIR__);
//BASEPATH 설정
define('BASEPATH', APP_PATH . '/../class/');
// public 디렉터리의 경로를 정의합니다.
defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(APP_PATH . '/../public'));
defined('THIRDPARTY_PATH') or define('THIRDPARTY_PATH', realpath(APP_PATH . '/../third-party'));

require_once THIRDPARTY_PATH . '/vendor/autoload.php';

// Swoole\Http\Server 객체를 생성합니다.
$server = new Swoole\Http\Server('0.0.0.0', 9501);

// 웹서버 설정을 합니다.
$server->set([
    'worker_num' => 10, // 워커 프로세스의 개수를 설정합니다.
    'daemonize' => false, // 데몬 모드로 실행할지 여부를 설정합니다.
    'max_request' => 100, // 워커 프로세스가 처리할 수 있는 최대 요청 수를 설정합니다.
    'dispatch_mode' => 1, // 요청을 워커 프로세스에 할당하는 방식을 설정합니다.
//    'log_file' => __DIR__ . '/daemon.log', // 로그 파일의 경로를 설정합니다.
]);

// 웹서버가 시작될 때 실행할 콜백 함수를 등록합니다.
$server->on('start', function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9501\n";
});

$server->on('shutdown', function ($server)
{
    echo "Server is shutting down.\n";
});

// 웹서버가 요청을 받았을 때 실행할 콜백 함수를 등록합니다.
$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($server) {
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->header('Content-Type', 'image/vnd.microsoft.icon');
        $response->sendfile(PUBLIC_PATH . '/favicon.ico');
        return;
    }

    if ($request->server['path_info'] == '/reload' || $request->server['request_uri'] == '/reload') {
        $response->end('reload');
        $sever->reload();
        echo 'reload' . PHP_EOL;
        return;
    }

    if ($request->server['path_info'] == '/shutdown' || $request->server['request_uri'] == '/shutdown') {
        $response->end('shutdown');
        $server->shutdown();
        echo 'shutdown' . PHP_EOL;
        return;
    }


    // 코루틴 스코프에서 비동기 작업을 수행합니다.
    go(function () use ($request, $response) {
        $__session_id__ = ($request->cookie['PHPSESSID'] ?? null);

        if($__session_id__ === null) {
            $__session_id__ = uniqid();
            $response->cookie('PHPSESSID', $__session_id__);
        }

        $pdo =  new PDO('mysql:host=host.docker.internal;dbname=swoole;charset=utf8', 'swoole', 'swoole');

        // index.php 파일을 실행합니다.
        try {
            ob_start();
            include PUBLIC_PATH . '/qbtest.php';
            $data = ob_get_clean();
        } catch (Throwable $e) {
            $data = '';
            echo sprintf("Caught exception: %s\n%s (line : %s)\n",  $e->getMessage(), $e->getFile(), $e->getLine());
        }

        // 응답 헤더를 설정합니다.
        $response->header("Content-Type", "text/plain; charset=utf-8");
        // 응답 본문을 설정합니다.
        $response->end($data);


    });
});


// 웹서버를 시작합니다.
$server->start();
