# Swoole 多进程

## 创建子进程 (process.php)

最新可用镜像

```
拉取镜像： docker pull phvia/php:7.3.9-fpm_swoole-4.3.5_web

运行并进入容器：docker run -it -p 7749:7749 –v <YourDir>:/usr/share/nginx/html <ImageID> bash
```

Swoole\Process

```
Swoole 的进程管理模块，可以作为 PHP pcntl 的易用版本。

与 pcntl 相比的几点优势：

    * 集成了进程间通信的 API。

    * 支持重定向标准输入输出。

    * 面向对象的操作 API 易于使用。
```

创建子进程

```
Swoole\Process::__construct(callable $function, bool $redirect_stdin_stdout = false, int $pipe_type = SOCK_DGRAM, bool $enable_coroutine = false);

$function               子进程创建成功后要执行的函数。
$redirect_stdin_stdout  是否重定向子进程的标准输入和输出
$pipe_type              管道类型，启用第二个参数后，值将被忽略 强制为1。
$enable_coroutine       默认为 false, (4.3.0)开启后可以在 callback 中使用协程API。
```

启动进程

```
Process->start(): int|bool

创建成功返回子进程的 PID，创建失败返回 false。可以使用 swoole_errno( )、swoole_strerror(int $errno) 获取当前的错误码和错误信息。

$process->pid   子进程的 PID
$process->pipe  管道的文件描述符
```

修改进程名称

```
Process->name(`php worker`)

可以修改主进程名，修改子进程名是在 start 之后的子进程回调函数中使用；此方法是 swoole_set_process_name 的别名。
```

执行一个外部程序

```
Process->exec(string $execfile, array $args): bool

执行成功后，当前进程的代码段将会被新程序替换。子进程空间变成另外一套程序。作用类似 pcntl_exec.

$execfile   可执行文件的绝对路径，如 /bin/echo
$args       参数列表，如 [`AAA`]
```

退出子进程

```
Process->exit(int $status = 0): int

$status 退出进程的状态码，如果为 0 表示正常结束，会继续执行清理工作。
包括：PHP 的 shutdown_function；对象析构 __destruct；其他扩展的 RSHUTDOWN 函数。

如果 $status 不为 0，表示异常退出，会立即终止进程，不再执行清理工作。
```

设置 CPU 亲和性

```
Process::setAffinity(array $cpu_set)

可以将进程绑定到特定的 CPU 核上，作用是让进程只在某几个 CPU 核上运行，让出某些 CPU 资源执行更重要的程序。
接受一个数组绑定哪些 CPU 核，如 [0, 2, 3] 表示绑定 CPU0、CPU2、CPU3。

使用 swoole_cpu_num( ) 可以得到当前服务器的 CPU 核数。
```

代码库

```
可用镜像版本    hub.docker.com/r/phvia/php/tags

镜像构建的代码  https://github.com/phvia/dkc
```

## 管道数据读写 (write_read.php)

向管道内写入数据

```
Process->write(string $data) int | bool;

在子进程内调用 write，父进程可以调用 read 接收此数据
在父进程内调用 write，子进程可以调用 read 接收此数据
```

从管道中读取数据

```
Process->read(int $buffer_size = 8192): string

$buffer_size 是缓冲区的大小，默认为 8192，最大不超过 64K。
管道类型为 DGRAM 数据时，read 可以读取完整的一个数据包。
管道类型为 STREAM 时，read 是流式的，需要自行处理完整性问题。
读取成功返回二进制数据字符串，读取失败返回 false。
```

关闭创建好的管道

```
Process->close(int $which = 0)

$which 指定关闭哪一个管道。
默认为 0 表示同时关闭读和写，1 关闭读，2 关闭写。
```

设置管道读写操作的超时时间

```
Process->setTimeout(double $timeout): bool

$timeout 单位为秒，支持浮点型；设置成功返回 true，设置失败返回 false。
设置成功后，调用 recv 和 write 在规定时间内未读取或写入成功，将返回 false。
```

设置管道是否为阻塞模式

```
Process->setBlocking(bool $blocking = true)

$blocking 默认为 true 同步阻塞，设置为 false 时管道为非阻塞模式。
```

将管道导出为 Coroutine\Socket 对象

```
Process->exportSocket( ): Swoole\Coroutine\Socket

多次调用此方法，返回的对象是同一个。
进程未创建管道，操作失败，返回 false。
```

## 消息队列通信 (push_pop.php)

启用消息队列作为进程间通信

```
Process->useQueue(int $msgKey = 0, int $mode = 2, int $capacity = 8192): bool

$msgKey 是消息队列的 key，默认会使用 ftok(__FILE__, 1) 作为 KEY。
$mode 通信模式，默认为 2，表示争抢模式，所有子进程都会从队列中取数据。
$capacity 单个消息长度，长度受限于操作系统内核参数的限制，默认为 8192，最大不超过 65536。
```

查看消息队列状态

```
Process->statQueue(): array

返回的数组中包括 2 项，queue_num 队列中的任务数量，queue_bytes 队列数据的总字节数。
```

删除队列

```
Process->freeQueue( )

此方法与 useQueue 成对使用，useQueue 创建，freeQueue 销毁，销毁队列后，队列中的数据会被清空。
如果只调用 useQueue, 未调用 freeQueue，在程序结束时并不会清除数据，重新运行程序可以继续读取上次运行时留下的数据。
```

投递数据到消息队列中

```
Process->push(string $data): bool

$data 要投递的数据，长度受限于操作系统内核参数的限制。
默认阻塞模式，如果队列已满，push 方法会阻塞等待。
非阻塞模式下，如果队列已满，push 方法会立即返回 false。
```

从队列中提取数据

```
Process->pop(int $maxsize = 8192): string|bool

$maxsize 表示获取数据的最大尺寸，默认为 8192
操作成功会返回提取到的数据内容，失败返回 false
默认阻塞模式下，如果队列中没有数据，pop 方法会阻塞等待
非阻塞模式下，如果队列中没有数据，pop 方法会立即返回 false，并设置错误码为 ENOMSG
```

## 守护进程化 (daemon.php)

使当前进程蜕变为一个守护进程

```
Process::daemon(bool $nochdir = true, bool $noclose = false)

$nochdir 为 true 表示不要切换当前目录到根目录
$noclose 为 true 表示不要关闭标准输入输出文件描述符

蜕变为守护进程时，进程 PID 将发生变化，可以使用 getmypid( ) 获取当前PID。
```

## 信号监听 (signal.php)

设置异步信号监听

```
Process::signal(int $signo, callable $callback): bool

此方法基于 signalfd 和 eventloop，是异步 IO，不能用于同步程序中。
同步阻塞的程序可以使用 pcntl 扩展提供的 pcntl_signal。
$callback 如果为 null，表示移除信号监听。
如果已设置信号回调函数，重新设置时会覆盖历史设置。
```

回收结束运行的子进程

```
Process::wait(bool $blocking = true): array|bool

子进程结束必须要执行 wait 进程回收，否则子进程会变成僵尸进程。

$blocking 参数可以指定是否阻塞等待，默认为阻塞。

操作成功会返回一个数组包含子进程的 PID、退出状态码、被哪种信号 kill，
如：['pid' => 15001, 'code' => 0, 'signal' => 15]，失败返回 false。
```

向指定 PID 进程发送信号

```
Process::kill($pid, $signo = SIGTERM): bool

默认的信号为 SIGTERM，表示终止进程

$signo = 0 可以检测进程是否存在，不会发送信号。
```

高进度定时器

```
Process::alarm(int $interval_usec, int $type = 0): bool

定时器会触发信号，需要与 Process::signal 或 pcntl_signal 配合使用。

$interval_usec 定时器间隔时间，单位为微秒，如果为负数表示清除定时器。

$type 定时器类型，
0 表示为真实时间，触发 SIGALAM 信号；
1 表示用户态 CPU 时间，触发 SIGVTALAM 信号；
2 表示用户态 + 内核态时间，触发 SIGPROF 信号。
```

## 进程池 (pool.php)

Swoole\Process\Pool

```
进程池，基于 Server 的 Manager 模块实现，可管理多个工作进程。
相比 Process 实现多进程，Process\Pool 更加简单，封装层次更高，开发者无需编写过多代码即可实现进程管理功能。

SWOOLE_IPC_MSGQUEUE     系统消息队列通信
SWOOLE_IPC_SOCKET       SOCKET通信
SWOOLE_IPC_UNIXSOCK     Unix Socket 通信
```

创建进程池

```
Process\Pool->__construct(int $worker_num, int $ipc_type = 0, int $msgqueue_key = 0, bool $enable_coroutine = false)

$worker_num         指定工作进程的数量
$ipc_type           进程间通信的模式，默认为 0 表示不使用任何进程间通信特性
$msgqueue_key       使用消息队列通信模式时，可设置消息队列的键
$enable_coroutine   (4.4版本)启用协程
```

设置进程池回调函数

```
Process\Pool->on(string $event, callable $function)

onWorkerStart(Process\Pool $pool, int $workerId)	子进程启动(必须设置)
onWorkerStop(Process\Pool $pool, int $workerId)	子进程结束
onMessage(Process\Pool $pool, string $data)		消息接收
```

监听 SOCKET

```
Process\Pool->listen(string $host, int $port = 0, int $backlog = 2048): bool

$host    监听的地址，支持 TCP、UnixSocket 类型
$port    监听的端口，TCP 模式下指定
$backlog 监听的队列长度
```

向对端写入数据

```
Process\Pool->write(string $data)

$data   写入的数据内容。

多次调用 write，底层会在 onMessage 函数退出后将全部数据写入 socket 中，并 close 连接。发送操作是同步阻塞的。内存操作，无 IO 消耗。
```

启动工作进程

```
Process\Pool->start(): bool

启动成功，当前进程进入 wait 装填，管理工作进程
启动失败，返回 false，可使用 swoole_errno 获取错误码
```

获取当前工作进程对象

```
Process\Pool->getProcess($worker_id): Process

$worker_id  可选参数，指定获取 worker，默认当前 worker

必须在 start 之后，在工作进程的 onWorkerStart 或其他回调函数中调用，返回的 Process 对象是单例模式，在工作进程中重复调用 getProcess() 将返回一个对象。
```
