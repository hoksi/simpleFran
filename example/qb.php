<?php
// APP_PATH 상수를 정의합니다.
defined('APP_PATH') or define('APP_PATH', __DIR__);
//BASEPATH 설정
define('BASEPATH', APP_PATH . '/../class/');
// public 디렉터리의 경로를 정의합니다.
defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(APP_PATH . '/../public'));
defined('THIRDPARTY_PATH') or define('THIRDPARTY_PATH', realpath(APP_PATH . '/../third-party'));

require_once THIRDPARTY_PATH . '/vendor/autoload.php';

ini_set('memory_limit', '4G');

function testPdo($offset = 0) {
    $offset = $offset * 10000;
    $pdo = new PDO('mysql:host=host.docker.internal;dbname=swoole;charset=utf8', 'swoole', 'swoole');
    $res = $pdo->query(qb()->select('*')->from('common_user')->limit(10000, $offset)->exec());

    while ($row = $res->fetch(PDO::FETCH_LAZY|PDO::FETCH_OBJ)) {
        $pdo->query(qb()
            ->set('last', date('Y-m-d H:i:s'))
            ->where('id', $row->id)
            ->update('common_user')
            ->exec()
        );
    }
};



Co::set(['stack_size' => '4096']);
Co\run(function () {
    for($i = 0; $i < 15; $i++) {
        go(function() use ($i) {
            testPdo($i);
        });
    }
});
