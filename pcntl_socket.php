<?php
/**
 * Created by PhpStorm.
 * User: Hugh
 * Date: 2019/2/27
 * Time: 9:38
 */
class Worker{
    //监听socket
    protected $socket = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;
    //开启进程数
    public $onWorkerNum  = 2;

    public function __construct($socket_address) {
        $this->socket = stream_socket_server(($socket_address));
    }

    public function run() {
        $this->pcntl();
    }

    public function pcntl(){
        $ppid = posix_getpid();
        echo $ppid.PHP_EOL;
        for ($i =0; $i < $this->onWorkerNum; $i++){
            $pid = pcntl_fork();
            if($pid < 0){
                echo 'fork child failed'.PHP_EOL;
            } elseif ($pid > 0) {
                echo 'fork child success'.PHP_EOL;
            } else {
                $pid = posix_getpid();
                echo $pid.PHP_EOL;
                $this->accept();
            }
        }
        $status = 0;
        pcntl_wait($status);

    }

    public function accept(){
        if ($this->socket){
            while (true){
                $conn = stream_socket_accept($this->socket);
                if(!empty($conn) && is_callable($this->onConnect)){
                    call_user_func($this->onConnect,$conn);
                }
                $buffer = fread($conn,65535);
                //var_dump($buffer);
                if(!empty($buffer) && is_callable($this->onMessage)){
                    $content = 'my socket server';
                    call_user_func($this->onMessage,$conn,$content);
                }
                fclose($conn);
            }
        }
    }
}

$worker = new Worker('tcp://0.0.0.0:9800');

$worker->onConnect = function ($fd) {
    echo '新的连接来了',$fd,PHP_EOL;
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


