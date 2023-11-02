<?php
echo "Start\n";

Swoole\Coroutine\run(function () {
    for ($n = 1; $n <= 60; $n++) {
        if ($n % 2 == 0) {
            go(function () use ($n) {
                echo 'Child #' . $n . " go1 start and sleep {$n}s" . PHP_EOL;
                sleep(1);
                echo 'Child #' . $n . ' exit' . PHP_EOL;
            });
        } else {
            go(function () use ($n) {
                echo 'Child #' . $n . " go2 start and sleep {$n}s" . PHP_EOL;
                // sleep($n);
                echo 'Child #' . $n . ' exit' . PHP_EOL;
            });
        }
    }
});

echo 'finish';
