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

    public $reads = array();
    public $writes = array();

    public function __construct($socket_address) {
        $this->socket = stream_socket_server(($socket_address));
        $this->reads[(int)$this->socket] = $this->socket;
        stream_set_blocking($this->socket,0);
    }

    public function run()
    {
        if ($this->socket) {
            while (true){
                $write = $this->writes;
                $read = $this->reads;
                $except = null;
                if(stream_select($read, $write, $except, 100) > 0){
                    foreach ($read as $key => $val) {

                        if($this->socket === $val){
                            $conn = stream_socket_accept($val);
                            if($conn){
                                $this->reads[(int)$conn] = $conn;
                                //unset($this->reads[$key]);
                                //$this->writes[(int)$conn] = $conn;
                                call_user_func($this->onConnect, $conn);
                            }
                        }else{
                            $buffer = fread($val, 1024);

                            if (strlen($buffer) == 0 ) {
                                fclose($val);
                                echo $key,'closed',PHP_EOL;
                                unset($this->reads[$key]);
                                //unset($this->writes[$key]);
                                var_dump($this->reads);
                            }else {
                                $content = 'message'.$key;
                                call_user_func($this->onMessage, $val, $content);
                            }
                        }
                    }
                }
            }
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


