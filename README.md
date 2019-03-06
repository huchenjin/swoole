# swoole
swoole socket
单线程，阻塞socket

pcntl_socket.php 多线程模式

select_server.php 单进程非阻塞，轮询监听模式

epoll_server.php epoll 单进程-监听模式，不限制描述符

worker_server.php 多进程，并实现多个进程监听同一个端口
