# Swoole 服务器

## TCP 服务器 [tcp.php]

什么是 TCP ?
```
TCP 即传输控制协议(Transmission Control Protocol)，是一个面向连接的协议，为用户进程提供可靠的全双工字节流。
TCP 套接字是一种流套接字。
TCP 关心"确认、超时和重传"之类的细节。
大多数因特网应用程序使用 TCP，TCP 既可以使用 IPv4，也可以使用 IPv6。
```

什么是 Swoole TCP Server ?
```
Swoole 底层实现的 TCP 协议的服务器，只能用于 cli 环境。
默认使用 SWOOLE_PROCESS 模式，因此除了 worker 进程外，会创建额外 master 和 manager 两个进程。
服务器启动后，通过 `kill 主进程id` 来结束所有工作进程。
```

Swoole TCP Server 特点
```
1. Swoole\Server 是异步服务器，所以是通过监听事件的方式来编写程序的。
2. 当有新的 TCP 连接进入时会执行 onConnect 事件回调，当某个连接向服务器发送数据时会回调 onReceive 事件回调，客户端断开会触发 onClose 事件回调。
3. $fd 是客户端连接的唯一标识。
4. 调用 $server->send() 向客户端连接发送数据。
5. 调用 $server->close() 可以强制关闭某个客户端连接。
```

Swoole TCP Server 参数
```
$host        监听的 ip 地址（支持IPv4 和 IPv6）
$port        监听的端口（监听小于1024端口需要root权限，0 表示随机）
$mode        运行模式（支持 SWOOLE_PROCESS 和 SWOOLE_BASE）
$socket_type Socket 的类型（$socket_type | SWOOLE_SSL 启用 SSL 加密，启用 SSL 后必须配置 ssl_key_file 和 ssl_cert_file）
```

更多信息@doc https://wiki.swoole.com/wiki/page/14.html

## UDP 服务器 [udp.php]

什么是 UDP ?
```
UDP 即用户数据报协议(User Datagram Protocol)，是一个无连接协议。
UDP 套接字是一种数据报套接字。
UDP 数据报不能保证最终到达它们的目的地，不保证各个数据报的先后顺序跨网络后保持不不变，也不保证每个数据报只到达一次。
UDP 可以是全双工的。
与 TCP 一样，UDP 既可以使用 IPv4，也可以使用 IPv6。
```

什么是 Swoole UDP Server ?
```
Swoole 底层实现的 UDP 协议的服务器，只能用于 cli 环境。
使用方式和 Swoole TCP Server 基本一致，根据 UDP 的特点，Server 相关操作的 API 不一样。
```

Swoole UDP Server 特点
```
1. Swoole\Server 是异步服务器，所以是通过监听事件的方式来编写程序的。
2. 与 TCP Server 不同，UDP 没有监听的概念，启动 Server 后，客户端无需 Connect，直接可以向 Server 监听的端口发送数据包，Server 对应事件为 onPacket。
3. $clientInfo 是客户端的相关信息，是一个数组，有客户端 IP 和端口等内容。
4. 调用 $server->sendto() 向客户端发送数据。
```

Swoole UDP Server 参数
```
参数和 Swoole TCP Server 基本一致，只需要 $socket_type 指定为 SWOOLE_SOCK_UDP 类型。
```

备注
```
在容器里暴露 udp 端口需要指定类型，否则默认是 tcp 类型：
$ docker run -it -p 7748:7748/udp -v /home/ubuntu:/usr/share/nginx/html ImageID bash

终端连接 udp 服务器可以用 netcat 或 nc：
$ netcat -u ip port
```

更多信息@doc https://wiki.swoole.com/wiki/page/14.html

## Server 四层生命周期 [lifecycle.php]

PHP 完整生命周期
```
执行PHP文件
    PHP扩展模块初始化（MINIT）
        PHP扩展请求初始化（RINIT）
        执行 PHP 逻辑
        PHP扩展请求结束（RSHUTDOWN）
        PHP脚本清理
    PHP扩展模块结束（MSHUTDOWN）
终止PHP
```

PHP 请求生命周期
```
如果是 cli 执行 PHP 脚本，那么会完整执行整个过程，因为存在进程创建。

如果是 php-fpm 请求响应阶段，那么会执行中间四步过程，等到 fpm 进程退出才执行扩展模块清理工作。
```

Swoole Server 四层生命周期

```
程序全局期：Server->start 之前创建的对象资源，持续驻留内存，worker共享。
            全局期代码在 Server 结束时才会释放，reload 无效。

进程全局期：Server 启动后创建多个进程，它们内存空间独立，非共享内存。
            worker 进程启动后（onWorkerStart）引入的代码在进程存活期有效，reload 会重新加载。

会话期：在 onConnect 或 第一次onReceive 时创建，onClose 时销毁。
        客户端连接后创建的对象会常驻内存，直到此客户端离开才销毁。

请求期：在 onReceive/onRequest 收到请求开始，直到发送 Response 返回。
        请求期创建的对象会在请求完成后销毁，和 fpm 程序中的对象一样。
```

## 全局配置选项详解 [setting.php]

Swoole\Server::set 用于设置 Server 运行时的各项参数，使用数组元素配置。

Swoole 的难点除了系统和网络外，相当一部分原因是由于配置选项繁多，未做拆分，不利于学习。

更多信息@doc https://wiki.swoole.com/wiki/page/274.html

## 事件回调函数详解 [callback.php]

Swoole\Server 是事件驱动模式，所有的业务逻辑代码必须写在事件回调函数中。当特定的网络事件发生后，底层会主动回调指定的 PHP 函数。

更多信息@doc https://wiki.swoole.com/wiki/page/41.html

事件执行顺序
```
所有事件回调均在 Server start 后发生。

服务器关闭终止时最后一次事件是 onShutdown。

服务器启动成功后，onStart / onManagerStart / onWorkerStart 会并发执行。

onReceive / onConnect / onClose 在 worker 进程中触发。

Worker / Task 进程启动和结束会分别调用 onWorkerStart / onWorkerStop。

onTask 事件仅在 task 进程中发生。

onFinish  事件仅在 worker 进程中发生。
```

## HTTP 服务器

Swoole\Http\Server

```
继承自 Swoole\Server，是一个 HTTP 服务器，父类提供的 API 都可使用，它支持同步和异步两种模式。
两种模式都支持维持大量 TCP 客户端连接，同步/异步 仅仅体现在对请求的处理方式上。

Swoole\Http\Server 对 Http 协议的支持不完整，一般用作应用服务器，使用 Nginx 作为代理。
```

Swoole\Http\Server 同步模式

```
等同于 php-fpm 模式，需要设置大量 worker 进程来完成并发请求处理。编程方式与普通 PHP Web 程序一致。

与 php-fpm 不同的是，服务器可以应对大量客户端并发连接，类似于 nginx。
```

Swoole\Http\Server 异步模式

```
这种模式下整个服务器是异步非阻塞的，服务器可以应对大量并发连接和并发请求，
但编程方式要使用异步 API，否则会退化为同步模式。

Swoole-4.3 版本已移除异步模块，建议使用 Coroutine 模式。
```

配置选项

```
除了可以设置 Server 相关选项外，可以设置 HTTP 服务器独有的选项。

upload_tmp_dir              上传文件临时目录，目录长度有限制。
http_parse_post             设置 POST 消息解析开关。
http_parse_cookie           关闭时将在 header 中保留原始 cookie 信息。
http_compression            启用压缩，默认开启。
document_root               配置静态文件根目录。
enable_static_handler       开启静态文件请求处理功能，配合 document_root
static_handler_locations    设置静态文件的路径。
```

回调函数

```
与 Swoole\Server->on 相同，使用 on 方法注册事件回调。

Swoole\Http\Server->on 不接受 onConnect / onReceive 回调设置。

Swoole\Http\Server->on 接受独有的 onRequest 事件回调。
```

## HTTP Server 参数接收响应

Swoole\Http\Request

```
Http 请求对象，保存了客户端请求相关信息。

属性 $header, $server, $get, $post, $cookie, $files

方法 rawContent() 获取原始的 POST 包体
     getData() 获取完整的原始 Http 请求报文

属性描述@doc https://wiki.swoole.com/wiki/page/328.html
```

Swoole\Http\Response

```
Http 响应对象，通过调用响应对象的方法来实现 Http 响应发送。

当 Response 对象销毁时，如果未调用 end 发送响应，底层会自动执行 end。

方法描述@doc https://wiki.swoole.com/wiki/page/329.html
```

## HTTP Server 常见问题

Chrome 产生两次请求

```
Chrome 浏览器会自动请求一次 favicon.ico，
可通过 $request->server 属性的 request_uri 键获取 URL 路径进行判断处理。
```

GET、POST 请求尺寸

```
GET 请求头有尺寸限制，不可更改，如果请求不是正确的 Http 请求，将会报错。

POST 请求尺寸受到 package_max_length 限制。
```

## WebSocket 服务器

Swoole\WebSocket\Server

```
继承自 Swoole\Http\Server，是实现了 WebSocket 协议的服务器，父类提供的 API 都可使用，
通过几行 PHP 代码就可以写出一个异步非阻塞多进程的 WebSocket 服务器。
```

有哪些 WebSocket 客户端

```
浏览器内置的 JavaScript WebSocket 客户端。

实现了 WebSocket 协议解析的程序都可以作为客户端。

非 WebSocket 客户端不能与 WebSocket 服务器通信。
```

回调函数

```
除了接收 Swoole\Server 和 Swoole\Http\Server 基类的回调函数外，额外增加三个回调函数设置。

onOpen      可选，客户端与服务器建立连接并完成握手时回调此函数

onHandShake 可选，建立连接后进行握手，不使用内置 handshake 时候设置

onMessage   必选，服务器收到客户端数据帧时回调此函数
            参数一是 Server 对象，参数二是 Swoole\WebSocket\Frame 对象
            Frame@doc https://wiki.swoole.com/wiki/page/987.html
```

方法列表

```
push            向 WebSocket 客户端连接发送数据
exist           判断 WebSocket 客户端是否存在
pack            打包 WebSocket 消息
unpack          解析 WebSocket 数据帧
disconnect      主动向 WebSocket 客户端发送关闭帧并关闭连接
isEstablished   检查连接是否为有效的 WebSocket 客户端连接，
                exist 仅判断是否为 TCP 连接，无法判断是否为已完成握手的 WebSocket 连接
```

预定义常量

```
WebSocket 数据帧类型
WEBSOCKET_OPCODE_TEXT = 0x1     UTF-8 文本字符串数据
WEBSOCKET_OPCODE_BINARY = 0x2   二进制数据
WEBSOCKET_OPCODE_PING = 0x9     ping类型数据

WebSocket 连接状态
WEBSOCKET_STATUS_CONNECTION = 1 连接进入等待握手
WEBSOCKET_STATUS_HANDSHAKE = 2  正在握手
WEBSOCKET_STATUS_FRAME = 3      已握手成功过等待客户端发送数据帧
```

配置选项

```
通过 Server::set 传入配置选项：

websocket_subprotocol   设置 WebSocket 子协议。
                        设置后握手响应的 Http 头会增加 Sec-WebSocket-Protocol: {$websocket_subprotocol}
                        具体使用参考 WebSocket 协议相关 RFC 文档

open_websocket_close_frame  启用 WebSocket 协议中关闭帧(opcode 为 0x08)，在 onMessage 回调中接收，默认 false。
                            开启后，可在 onMessage 回调中接收到 client 或 server 发送的关闭帧，可自行对其处理。
```

应用案例

聊天程序: https://github.com/farwish/PCP/tree/master/Project/Swoole


## Redis 服务器 [redis.php]

Swoole\Redis\Server

```
继承自 Swoole\Server，一个兼容 Redis 服务器端协议的 Server 程序。

Swoole\Redis\Server 不需要设置 onReceive 回调。
```

可用客户端

```
任意编程语言的 Redis 客户端，包括 PHP 的 Redis 扩展和库。

Swoole 扩展提供的异步 Redis 客户端。

Redis 提供的命令行工具，包括 redis-cli。
```

提供方法

```
父类的所有方法和以下新增：

setHandler      设置 Redis 命令字的处理器

format          格式化命令响应数据
```

提供常量

```
主要用于 format 函数打包 Redis 响应数据:

Server::NIL     返回 nil 数据
Server::ERROR   返回错误码
Server::STATUS  返回状态
Server::INT     返回整数
Server::STRING  返回字符串
Server::SET     返回列表（数组）
Server::MAP     返回 Map（关联数组）
```

## 毫秒定时器 [timer.php]

Swoole\Timer

```
毫秒精度的定时器。底层基于 epoll_wait（异步进程）和 settimer（同步进程）实现，
数据结构使用最小堆，可支持添加大量定时器。

底层不支持时间参数为 0 的定时器。
```

可用方法

```
tick        设置一个间隔时钟定时器，返回整数 id，会持续触发，直到调用 clear。
after       设置一个一次性定时器，返回整数 id，执行完后就销毁，是非阻塞的。
clear       使用定时器 id 来删除定时器，只作用于当前进程。
clearAll    清除所有的定时器（需要 swoole-4.4 及以上）
info        返回 timer 的信息（需要 swoole-4.4 及以上）
list        返回定时器迭代器，可用 foreach 遍历（需要 swoole-4.4 及以上）
stats       返回统计信息（需要 swoole-4.4 及以上）
```

@doc https://wiki.swoole.com/wiki/page/p-timer.html

## 执行异步任务 [task.php]

Swoole 异步任务

```
Server 程序中如果需要执行耗时的操作，worker 进程使用 $server->task() 向 task worker 投递任务，
使当前进程不阻塞，不影响当前请求的处理速度。
（必须设置了 task_worker_num 才能使用 task 回调函数）
```

注意事项

```
设置的 onTask 回调函数在 task 进程池内异步执行，执行完后使用 return 非null的变量
或者调用 $server->finish() 来返回结果。

return 和 $server->finish() 操作都是可选的，onTask 可以不返回任何结果。

onTask 返回结果才会触发 onFinish 回调，执行 onFinish 逻辑的 worker 进程和下发 task 任务的 worker 是同一进程。
```

回调原型

```
onTask(Swoole\Server $server, int $taskId, int $srcWorkerId, mix $data)

onTask(Swoole\Server $server, Swoole\Server\Task $task)
swoole-4.2.12起，开启了 task_enable_coroutine 之后的函数原型，信息存储在 $task 对象的属性上。

onFinish(Swoole\Server $server, int $taskId, string $data)
```

## 网络通信协议设计

通信协议解决的问题

```
TCP 协议是流式传输协议，应用需要处理分包和合包才能有效获取数据，比如 HTTP、FTP、SMTP、Redis、MySQL 等都是基于 TCP 的协议，
它们都实现了自己的数据解析方式，方便应用层进行使用。

Swoole 底层支持 2 种类型的自定义网络通信协议：EOF 结束符协议、固定包头加包体协议
```

EOF 结束符协议

```
原理是每个数据包结尾加一串自定义的特殊字符表示数据包的结束。
使用 EOF 协议，要确保数据包中间不会出现 EOF 字符，否则会分包错误。

$server->set([
    'open_eof_split' => true,
    'package_eof'    => '\r\n',
]);
```

固定包头加包体协议

```
原理是一个数据包总是由包头和包体两部分组成。
包头由一个字段 指定了包体或者整个包的长度，长度一般是使用 2 字节或 4 字节整数表示，
服务器收到包头后，根据长度值来控制需要再接收多少数据才是完整的数据包。

$server->set([
    'open_length_check' => true,
    'package_max_length' => 81920,
    'package_length_type' => 'n', // 和 pack 函数用法一致
    'package_length_offset' => 0,
    'package_body_offset' => 2,
]);
```

