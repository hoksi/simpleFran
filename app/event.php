<?php
Swoole\Coroutine\run(function() {
    $fp = stream_socket_client("tcp://www.qq.com:80", $errno, $errstr, 30);
    fwrite($fp,"GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n");

    Swoole\Event::add($fp, function($fp) {
        $resp = fread($fp, 8192);
        Swoole\Event::del($fp);
        fclose($fp);
        print_r($resp);
    });
});
echo "Finish\n";

