<?php
/**
 * Created by PhpStorm.
 * User: LX
 * Date: 2019/3/6
 * Time: 9:27
 */
class Worker{
    //监听socket
    protected $socket = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;

    public function __construct($socket_address) {
        $this->socket = stream_socket_server(($socket_address));
    }

    public function run()
    {
        $this->pcntl();
    }

    public function pcntl(){
        $this->accept();
    }

    public function accept(){
        if ($this->socket){
            swoole_event_add($this->socket,function ($fd){
                $conn = stream_socket_accept($fd);
                if ($conn) {
                    call_user_func($this->onConnect, $conn);
                }
                swoole_event_add($conn,function ($fd){
                    $buffer = fread($fd, 1024);
                    if (strlen($buffer) == 0) {
                        fclose($fd);
                    } else {
                        $content = 'message：'.$fd;
                        call_user_func($this->onMessage, $fd, $content);
                    }
                });

            });
        }
    }
}

$worker = new Worker('tcp://0.0.0.0:9800');

$worker->onConnect = function ($fd) {
    echo $fd,PHP_EOL;
};
$worker->onMessage = function ($conn, $content) {
    //var_dump($conn,$content);

    $http_resonse = "HTTP/1.1 200 OK\r\n";
    $http_resonse .= "Content-Type: text/html;charset=UTF-8\r\n";
    $http_resonse .= "Connection: keep-alive\r\n";
    $http_resonse .= "Server: php socket server\r\n";
    $http_resonse .= "Content-length: ".strlen($content)."\r\n\r\n";
    $http_resonse .= $content;
    fwrite($conn, $http_resonse);
};
$worker->run();


