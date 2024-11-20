<?php
// Swoole 확장을 로드합니다.
extension_loaded('swoole') or die('Swoole extension is not loaded.');

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

// Swoole\Http\Server 객체를 생성합니다.
$server = new Swoole\Http\Server('127.0.0.1', 9501);

// 웹서버 설정을 합니다.
$server->set([
    'worker_num' => 2, // 워커 프로세스의 개수를 설정합니다.
    'daemonize' => false, // 데몬 모드로 실행할지 여부를 설정합니다.
    'max_request' => 10000, // 워커 프로세스가 처리할 수 있는 최대 요청 수를 설정합니다.
    'dispatch_mode' => 1 // 요청을 워커 프로세스에 할당하는 방식을 설정합니다.
]);

// 웹서버가 시작될 때 실행할 콜백 함수를 등록합니다.
$server->on('start', function ($server) {
    echo "Swoole http server is started at http://127.0.0.1:9502\n";
});

$imgMaker = new Intervention\Image\ImageManager(['driver' => 'imagick']);

// 웹서버가 요청을 받았을 때 실행할 콜백 함수를 등록합니다.
$server->on('request', function (Swoole\Http\Request $request, Swoole\Http\Response $response) use ($imgMaker) {
    if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
        $response->header('Content-Type', 'image/vnd.microsoft.icon');
        $response->sendfile('public/favicon.ico');
        return;
    }

    // 코루틴 스코프에서 비동기 작업을 수행합니다.
    go(function () use ($request, $response, $imgMaker) {
        $params = explode('/', trim($request->server['request_uri'], '/'));

        if (empty($params[0]) === false) {
            $cacheFile = 'cache/images' . $params[0];
            if (file_exists($cacheFile) === false) {
                $size = explode('x', $params[0]);

                // 이미지를 생성합니다.
                $img = $imgMaker->make('public/test.png')->resize($size[0], ($size[1] ?? null));
                $img->save($cacheFile, null, 'webp');
            }

            $response->header("Content-Type", "content-type: image/webp");
            $response->sendfile($cacheFile);
            return;
        }


        // 응답 헤더를 설정합니다.
        $response->header("Content-Type", "content-type: image/webp");
        // 응답 본문을 설정합니다.
        $response->sendfile('public/test.png');
    });
});

$server->on('WorkerExit', function (Swoole\Server $serv, $worker_id) {
    echo "WorkerExit: {$worker_id}\n";
});

// 웹서버를 시작합니다.
$server->start();