# Swoole 服务器

## TCP 服务器

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

## UDP 服务器

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

## Server 四层生命周期

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

## 全局配置选项详解

Swoole\Server::set 用于设置 Server 运行时的各项参数，使用数组元素配置。

Swoole 的难点除了系统和网络外，相当一部分原因是由于配置选项繁多，未做拆分，不利于学习。

更多信息@doc https://wiki.swoole.com/wiki/page/274.html

## 事件回调函数详解

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
继承自 Swoole\Server，是一个 HTTP 服务器，父类提供的 API 都可使用，它支持同步和异步两种模式。两种模式都支持维持大量 TCP 客户端连接，同步/异步 仅仅体现在对请求的处理方式上。

Swoole\Http\Server 对 Http 协议的支持不完整，一般用作应用服务器，使用 Nginx 作为代理。
```

Swoole\Http\Server 同步模式

```
等同于 php-fpm 模式，需要设置大量 worker 进程来完成并发请求处理。编程方式与普通 PHP Web 程序一致。

与 php-fpm 不同的是，服务器可以应对大量客户端并发连接，类似于 nginx。
```

Swoole\Http\Server 异步模式

```
这种模式下整个服务器是异步非阻塞的，服务器可以应对大量并发连接和并发请求，但编程方式要使用异步 API，否则会退化为同步模式。

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
Chrome 浏览器会自动请求一次 favicon.ico，可通过 $request->server 属性的 request_uri 键获取 URL 路径进行判断处理。
```

GET、POST 请求尺寸

```
GET 请求头有尺寸限制，不可更改，如果请求不是正确的 Http 请求，将会报错。

POST 请求尺寸受到 package_max_length 限制。
```

