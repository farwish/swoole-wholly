<?php
/**
 * daemon.php
 *
 * github.com/farwish/swoole-wholly
 *
 * @author ercom
 */

// 只需要加这一行即可变为守护进程程序
Swoole\Process::daemon();

for ($i = 0; $i < 5; $i++) {
    $p = new Swoole\Process(function (Swoole\Process $p) {
        $p->name('process child');

        while (true) {
            // 阻塞读
            $msg = $p->pop();

            if ($msg === false) {
                break;
            }

            //echo "\n $p->pid received msg {$msg} \n";
        }
    });

    if ($p->useQueue() === false) {
        throw new \Exception('use queue failed');
    }

    $p->name('process master');
    $p->start();
}

sleep(1);

while (true) {
    // 单批次
    //echo "\n =========== \n";
    foreach (['a', 'b', 'c', 'd', 'e'] as $message) {
        $p->push($message);
    }
    sleep(2);
}

sleep(10);
