# Swoole 协程

## CSP 编程方式 [co.php]

CSP 编程

```
不同于传统的通过共享内存来通信，CSP 讲究以通信的方式来共享内存。

Swoole 使用到了 CSP 里面的部分概念，参考了 Golang 的实现，使用 go 协程作为执行体，
用 Chan 作为实体间通信的通道，Defer 在协程退出时执行。
```

Swoole 协程特点

```
应用层使用同步的编程方式，底层自动实现异步 IO 的效果和性能。

不需要在应用层使用 yield 关键字标识协程切换，易于使用。

默认开启了 enable_coroutine 选项，底层会在一些回调函数中自动创建一个协程，此时回调中使用协程 API。

使用 Coroutine::create 或者 go 方法来手动创建一个协程。

协程的切换是隐式发生的，所以协程切换前后不保证全局变量和静态变量的一致性（不安全）。
```

## 网络客户端一键协程 [runtime_co.php]

Swoole\Runtime

```
Swoole-4.1.0 版本新增，在运行时动态将 PHP Stream 实现的 扩展、网络客户端代码协程化。

底层替换了 ZendVM、Stream 的函数指针，所有使用 Stream 进行 socket 操作均变成协程调度的异步 IO。
```

开启方式

```
Swoole\Runtime::enableCoroutine(bool $enable = true, int $flags = SWOOLE_HOOK_ALL);

$enable 打开或关闭协程，$flags 选择要 Hook 的类型，仅在 $enable = true 时有效，默认全选。

@支持的选项 https://wiki.swoole.com/wiki/page/993.html
```

```
Swoole\Runtime::enableCoroutine(int $flags = SWOOLE_HOOK_ALL);
# Swoole-4.3.2

Coroutine::set(['hook_flags' => SWOOLE_HOOK_ALL]);
# Swoole-4.4
```

可用场合

```
redis 扩展

使用 mysqlnd 模式的 PDO、MySQLi 扩展

soap 扩展

stream_socket_client、stream_socket_server、stream_select ( 4.3.2以上 )

fsockopen

file_get_contents、fopen、fread/fgets、fwrite/fputs、unlink、mk(rm)dir

sleep、usleep
```

使用位置

```
调用后当前进程全局有效，一般放在整个项目最开头，只在 Swoole 协程中会被切换为协程模式，

在非协程中依然是同步阻塞的，不影响 PHP 原生环境使用。

( Swoole-4.4.0 中不再自动兼容协程内外环境，一旦开启，则一切阻塞操作必须在协程内调用 )

不建议放在 onRequest 等回调中，会多次调用造成不必要的开销。
```

## 协程编程须知

自动创建协程的回调方法

```
onWorkerStart
onConnect
onOpen
onReceive
redis_onReceive
onPacket
onRequest
onMessage
onPipeMessage
onFinish
onClose
tick/after 定时器

当 enable_coroutine 开启后，以上这些回调和功能会自动创建协程，其余情况可以使用 go() 或者 Coroutine::create() 创建。
@doc https://wiki.swoole.com/wiki/page/949.html
```

与 Go 协程的区别

```
Swoole4 的协程调度是单线程的，没有数据同步问题，协程间依次执行。
Go 协程调度器是多线程的，同一时间可能会有多个协程同时执行。

Swoole 禁止协程间公用 Socket 资源，底层会报错，Go 协程允许同时操作。

Swoole4 的 defer 设计为在协程退出时一起执行，在多层函数中嵌套的 defer 任务按照 先进后出 的顺序执行。
Go 的 defer 与函数绑定，函数退出时执行。
```

协程异常处理

```
在协程编程中可直接使用 try/catch 处理异常，但必须在协程内捕获，不能跨协程捕获异常。

Swoole-4.2.2 版本以上允许脚本(未创建HttpServer)在当前协程中 exit 退出。
```

协程编程范式

```
协程内部禁止使用全局变量。

协程使用 use 关键字引入外部变量时禁止使用引用(&)。

协程之间通讯必须使用 channel (IPC、Redis 等)。

多个协程公用一个连接、使用全局变量/类的静态变量保存上下文会出现错误。
```

## 协程执行流程 [order_co.php]

遵循原则

```
协程没有 IO 等待的执行 PHP 代码，不会产生执行流程切换。

协程遇到 IO 等待立即将控制权切换，IO 完成后，重新将执行流切回切出点。

协程并发，依次执行，其余同上。

协程嵌套执行，流程由外向内逐层进入，直到发生 IO，然后切到外层协程，
注意，父协程不会等待子协程结束。
```

## 并发调用

并发 shell_exec

```
在 PHP 程序中经常使用 shell_exec 执行外部命令，普通的 shell_exec 是阻塞的，导致进程阻塞，

Swoole 协程环境中可以使用 Co::exec 并发执行多个命令。
```

setDefer 机制

```
绝大部分协程组件都支持了 setDefer 特性，可以将请求响应式的接口拆分成为两个步骤，

使用此机制可以实现先发送数据，再并发收取响应结果。

@doc https://wiki.swoole.com/wiki/page/604.html
```

子协程与通道实现并发请求

```
主协程内创建一个 chan;

主协程内创建 2 个子协程分别进行 IO 请求，子协程使用 use 应用 chan;

主协程循环调用 chan->pop，等待子协程完成任务，进入挂起状态。

并发的两个子协程，完成请求的 调用 chan->push 将数据推送给主协程。

子协程完成请求后退出，主协程从挂起状态中恢复，继续向下执行。

@doc https://wiki.swoole.com/wiki/page/947.html
```

## WaitGroup 功能 [waitgroup_co.php]

WaitGroup 功能

```
在 Swoole4 中可以使用 channel 实现协程间的通信、依赖管理、协程同步。

简单来说，WaitGroup 就是主协程等待所有子协程结束后才退出的功能。

@address https://github.com/swoole/swoole-src/blob/v4.4.4/library/core/Coroutine/WaitGroup.php
```

