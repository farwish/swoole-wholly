
Swoole 与 php-fpm 在 HTTP 方面的差异：

```
Nginx   -> PHP-FPM          -> 加载框架，同步阻塞执行（返回结果）  
[Nginx] -> SwooleHttpServer -> 同步阻塞/非阻塞/协程执行（返回结果）  
```

PHP-FPM 是后台多进程模型，但是只用来解析PHP脚本，没有Web服务器支持无法处理HTTP请求；  
SwooleHttpServer 实现了HTTP协议解析，C语言实现，应用常驻内存，性能很高，并且支持了很多其它高级特性。

