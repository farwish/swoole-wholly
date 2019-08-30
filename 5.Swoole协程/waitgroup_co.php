<?php
/**
 * waitgroup_co.php
 *
 * github.com/farwish/swoole-wholly
 * github.com/swoole/swoole-src/blob/master/library/core/Coroutine/WaitGroup.php
 *
 * @author ercom
 */

namespace Swoole\Coroutine;
use BadMethodCallException;
use InvalidArgumentException;
class WaitGroup
{
    protected $chan;
    protected $count = 0;
    protected $waiting = false;
    public function __construct()
    {
        $this->chan = new Channel(1);
    }
    public function add(int $delta = 1): void
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: add called concurrently with wait');
        }
        $count = $this->count + $delta;
        if ($count < 0) {
            throw new InvalidArgumentException('negative WaitGroup counter');
        }
        $this->count = $count;
    }
    public function done(): void
    {
        $count = $this->count - 1;
        if ($count < 0) {
            throw new BadMethodCallException('negative WaitGroup counter');
        }
        $this->count = $count;
        if ($count === 0 && $this->waiting) {
            $this->chan->push(true);
        }
    }
    public function wait(float $timeout = -1): bool
    {
        if ($this->count > 0) {
            $this->waiting = true;
            $done = $this->chan->pop($timeout);
            $this->waiting = false;
            return $done;
        }
        return true;
    }
}

// 以上使用的是 swoole-4.4.4 library 的 WaitGroup.php

\Swoole\Runtime::enableCoroutine(true);

\Swoole\Coroutine::set([
    'max_coroutine' => 2000,
]);

go(function () {
    $wg = new WaitGroup;

    echo "main co start " . \co::getcid() . PHP_EOL;

    $wg->add();
    go(function () use ($wg) {
        echo "child co start " . \co::getcid() . PHP_EOL;

        sleep(2);

        echo "child co end " . \co::getcid() . PHP_EOL;

        $wg->done();
    });

    $wg->add();
    go(function () use ($wg) {
        echo "child co start " . \co::getcid() . PHP_EOL;

        sleep(1);

        echo "child co end " . \co::getcid() . PHP_EOL;

        $wg->done();
    });

    $wg->wait();
    echo "main co end " . \co::getcid() . PHP_EOL;
});

/* 未使用 waitgroup
main co start 1
child co start 2
child co start 3
main co end 1
child co end 3
child co end 2
 */

/* 使用 waitgroup
main co start 1
child co start 2
child co start 3
child co end 3
child co end 2
main co end 1
 */
