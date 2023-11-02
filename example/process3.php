<?php
use function Swoole\Coroutine\run;

// create a new child process
$p = new Swoole\Process(function () {
    run(function () {
        swoole_timer_after(1000, function () {
            echo "hello world\n";
        });
    });
});

run(function () {
    swoole_timer_tick(1000, function () {
        echo "parent timer\n";
    });
});

$p->start();