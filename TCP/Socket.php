<?php namespace TCP;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

/**
 * TCP Socket Class.
 * Opens a TCP socket on a specific port
 * When a socket is accepted a stream is created in a separate thread (using pthread)
 */
class Socket {

    // The socket connection
    private $socket;
    private $state;

    private $address;
    private $port;

    private $verbose;
    private $timeout;
    private $stream_timeout;

    private $next_id = 0;

    private $streamclass;

    public const STATE_CREATED = 1;
    public const STATE_BOUND = 2;
    public const STATE_LISTENING = 3;
    public const STATE_SHUTDOWN = 4;
    public const STATE_CLOSED = 5;


    public const DEFAULT_TIMEOUT = 0;
    public const DEFAULT_STREAM_TIMEOUT = 60;

    private $streams = [];
    private $max_streams = 4;

    /**
     * Constructor for a TCP Socket object.
     * @param string $_address IP-address on which the socket needs to be opened
     * @param string $_port TCP Port on which the socket needs to be opened
     * @return void
     */
    function __construct($_address = "127.0.0.1", $_port = "0") {
        $this->setAddress($_address);
        $this->setPort($_port);
        $this->setTimeout(self::DEFAULT_TIMEOUT, self::DEFAULT_STREAM_TIMEOUT);
        $this->setStreamClass("\TCP\Stream");
        $this->verbose = false;
    }

    /**
     * Destructor for a TCP Socket object.
     * @return void
     */
    function __destruct() {
        if($this->socket !== NULL){
            $this->shutdown();
            $this->close();
        }
    }

    public function setAddress($_address){
        $this->address = $_address;
    }

    public function setPort($_port){
        if(is_int($_port)){
            if($_port >= 0 && $_port <= 65535){
                $this->port = $_port;
            }else{
                throw new \Exception('Invalid port number');
            }
        }else{
            throw new \Exception('Port number is non-numerical');
        }
    }

    public function setVerbose($_verbose){
        if(is_bool($_verbose)){
            $this->verbose = $_verbose;
        }else{
            throw new \Exception('Verbose mode is non-boolean');
        }
    }

    public function setTimeout($_timeout, $_stream_timeout = 60){
        if(is_int($_timeout)){
            if($_timeout >= 0){
                $this->timeout = $_timeout;
                if($this->socket != null && !@socket_set_option($this->socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>$_timeout,"usec" => 0))){
                    switch(socket_last_error($this->socket)){
                        default:
                            throw new \Exception("Socket read failed: " . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
                    }
                }
            }else{
                throw new \Exception('Invalid timeout number');
            }
        }else{
            throw new \Exception('Timeout number is non-numerical');
        }
        if(is_int($_stream_timeout)){
            if($_stream_timeout >= 0){
                $this->stream_timeout = $_stream_timeout;
                foreach($this->streams as $stream){
                    $stream->setTimeout($_stream_timeout);
                }
            }else{
                throw new \Exception('Invalid stream timeout number');
            }
        }else{
            throw new \Exception('Stream timeout number is non-numerical');
        }
    }

    public function setStreamClass($class){
        $this->streamclass = $class;
    }

    public function getState(){
        return $this->state;
    }


    public function init(){
        $this->create();
        $this->bind();
        $this->listen();
    }

    public function create(){
        if(($this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false){
            throw new \Exception('Socket create failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            $this->state = self::STATE_CREATED;
            if($this->verbose){
                echo("Socket created.\n");
                flush();
            }
            $this->setTimeout($this->timeout, $this->stream_timeout);
        }
    }

    public function bind(){
        if(socket_bind($this->socket, $this->address, $this->port) === false){
            throw new \Exception('Socket bind failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            $this->state = self::STATE_BOUND;
            if($this->verbose){
                echo("Socket bound.\n");
                flush();
            }
        }
    }

    public function listen(){
        if(@socket_listen($this->socket, 0) === false){
            throw new \Exception('Socket bind failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            $this->state = self::STATE_LISTENING;
            if($this->verbose){
                echo("Socket listening.\n");
                flush();
            }
        }
    }

    public function run(){
        if($this->verbose){
            echo("Socket started accepting (max:".$this->max_streams.").\n");
            flush();
        }
        while($this->accept());
        if($this->verbose){
            echo("Socket stopped accepting.\n");
            flush();
        }
        do{
            $tot_streams = 0;
            foreach($this->streams as $key=>$stream){
                if($stream != NULL && $stream->isRunning()){
                    $tot_streams++;
                }else{
                    unset($this->streams[$key]);
                }
            }
            if($tot_streams > 0){
                echo("Waiting for opened sockets.\n");
                sleep(60);
            }
        }while($tot_streams > 0);
    }

    public function accept(){
        if(($socket = @socket_accept($this->socket)) !== false){
            $this->next_id++;
            $new_stream = new $this->streamclass($socket, $this->next_id, $this->verbose);
            $tot_streams = 0;
            foreach($this->streams as $key=>$stream){
                if($stream != NULL && $stream->isRunning()){
                    $tot_streams++;
                }else{
                    unset($this->streams[$key]);
                }
            }
            if($tot_streams < $this->max_streams){
                if($this->verbose){
                    echo("Socket accepted (".($tot_streams + 1)." of ".$this->max_streams.").\n");
                    flush();
                }
                $new_stream->setTimeout($this->stream_timeout);
                $new_stream->setEnvironment($_ENV);
                $new_stream->start();
                array_push($this->streams,$new_stream);
            }else{
                if($this->verbose){
                    echo("Socket denied (max number of streams reached:".$this->max_streams.").\n");
                    flush();
                }
                $new_stream->on_max_cycle();
                $new_stream->shutdown();
            }
            return true;
        }else{
            switch(socket_last_error($this->socket)){
                case 0:
                    $this->shutdown();
                    return false;
                default:
                    throw new \Exception('Socket accept failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
            }
        }
    }
    public function shutdown() {
        if($this->socket === false){
            throw new \Exception('Socket shutdown failed (false): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }elseif($this->socket === true){
            throw new \Exception('Socket shutdown failed (true): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            if(@socket_shutdown($this->socket) === false){
                if(socket_last_error($this->socket) != 107){
                    throw new \Exception('Socket shutdown failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
                    return;
                }
            }
            if($this->verbose){
                echo("Socket shutdown.\n");
                flush();
            }
            $this->state = self::STATE_SHUTDOWN;
        }
    }

    public function close() {
        if($this->socket === false){
            throw new \Exception('Socket close failed (false): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }elseif($this->socket === true){
            throw new \Exception('Socket close failed (true): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            if(@socket_close($this->socket) === false){
                throw new \Exception('Socket close failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
            }
            if($this->verbose){
                echo("Socket closed.\n");
                flush();
            }
            $this->socket = NULL;
            $this->state = self::STATE_CLOSED;
        }
    }
}