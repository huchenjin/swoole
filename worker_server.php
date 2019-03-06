<?php
/**
 * Created by PhpStorm.
 * User: LX
 * Date: 2019/3/6
 * Time: 10:45
 */
class Worker{
    //监听socket
    protected $socket = NULL;
    //连接事件回调
    public $onConnect = NULL;
    //接收消息事件回调
    public $onMessage = NULL;
    //开启进程数
    public $onWorkerNum  = 4;

    public $addr;

    public function __construct($socket_address) {
        $this->addr = $socket_address;
    }

    public function run()
    {
        $this->pcntl();
    }

    public function pcntl(){
        for ($i =0; $i < $this->onWorkerNum; $i++){
            $pid = pcntl_fork();
            if($pid < 0){
                echo 'fork child failed'.PHP_EOL;
            } elseif ($pid > 0) {
                //echo 'fork child success'.PHP_EOL;
            } else {
                $this->accept();
                exit;
            }
        }
        for ($j = 0; $j < $this->onWorkerNum; $j++){
            $status = 0;
            pcntl_wait($status);
        }
    }

    public function accept(){
        $context_option = [
            'socket' => array(
                'backlog' => '10240',
            ),
        ];
        $this->_context = stream_context_create($context_option);
        $flags = STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $errno = 0;
        $errmsg = '';
        stream_context_set_option($this->_context, 'socket', 'so_reuseport', 1);
        $this->socket = stream_socket_server($this->addr,$errno, $errmsg, $flags, $this->_context);
        if ($this->socket){
            swoole_event_add($this->socket,function ($fd){
                $pid = posix_getpid();
                var_dump($pid);
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
