<?php
/**
 * order_co.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

Swoole\Runtime::enableCoroutine(true);

Swoole\Coroutine::set([
    'max_coroutine' => 2000,
]);

go(function () {

    echo "main co start " . co::getcid() . PHP_EOL;

    go(function () {
        echo "child co start " . co::getcid() . PHP_EOL;

        sleep(2);

        echo "child co end " . co::getcid() . PHP_EOL;
    });

    go(function () {
        echo "child co start " . co::getcid() . PHP_EOL;

        sleep(1);

        echo "child co end " . co::getcid() . PHP_EOL;
    });

    echo "main co end " . co::getcid() . PHP_EOL;
});

echo "end" . PHP_EOL;

/*
main co start 1
child co start 2
child co start 3
main co end 1
end
child co end 3
child co end 2
 */
