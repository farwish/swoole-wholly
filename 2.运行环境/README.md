
### 安装方式

1.Linux + PHP +  源码编译 Swoole / PECL 安装 Swoole

2.Linux + Docker + 镜像

两种方式都可以，不影响脚本的运行，编译安装更直观，Docker 更快速。

### Linux + Docker

1.安装 Docker  
    https://github.com/phvia/dkc#安装-docker

2.拉取镜像  
    docker pull phvia/php:7.3.9-fpm_swoole-4.3.5_web

3.运行进入容器  
    docker run -it -p 7749:7749 -v /home/ubuntu:/usr/share/nginx/html <ImageID> bash

