<?php
// APP_PATH 상수를 정의합니다.
defined('APP_PATH') or define('APP_PATH', __DIR__);
//BASEPATH 설정
define('BASEPATH', APP_PATH . '/../class/');
// public 디렉터리의 경로를 정의합니다.
defined('PUBLIC_PATH') or define('PUBLIC_PATH', realpath(APP_PATH . '/../public'));
defined('THIRDPARTY_PATH') or define('THIRDPARTY_PATH', realpath(APP_PATH . '/../third-party'));

require_once THIRDPARTY_PATH . '/vendor/autoload.php';

echo qb(['dbdriver' => 'sqlsrv', 'dbversion' => 12])->select('1+1', false)->from('dual')->limit(10, 10)->exec();