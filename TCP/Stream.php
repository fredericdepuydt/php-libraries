<?php namespace TCP;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

class Stream extends \Thread {

    // The socket connection
    private $socket;
    private $state;
    private $id;
    private $timeout;
    private $env;

    public const STATE_CREATED = 1;
    public const STATE_RUNNING = 2;
    public const STATE_SHUTDOWN = 3;
    public const STATE_CLOSED = 4;

    public const DEFAULT_TIMEOUT = 60;

    private $verbose;

    private $data = "";

    const BUFFER_SIZE = 204800;

    // Constructing TCP object
    function __construct($_socket, $_id = 0, $_verbose = false) {
        $this->state = self::STATE_CREATED;
        $this->socket = $_socket;
        $this->setId($_id);
        $this->setVerbose($_verbose);
        $this->setTimeout(self::DEFAULT_TIMEOUT);
        $this->process_init();
        try{
            $this->process_init();
        }catch(\Exception $e){            
            $this->error("Unknown error occured during 'process_init' function: ".$e->getMessage() . " (Error code: " . $e->getCode().")");
        }
        if($this->verbose){
            $this->echo("constructed.");
        }
    }

    // Destructing TCP object and closing connection
    function __destruct() {
        if($this->socket != NULL && $this->state != self::STATE_CREATED && $this->state != self::STATE_SHUTDOWN){
            $this->shutdown();
        }
        if($this->verbose){
            $this->echo("destructed.");
        }
    }

    public function setId($_id){
        if(is_int($_id)){
            if($_id >= 0){
                $this->id = $_id;
            }else{
                throw new \Exception('Invalid ID number');
            }
        }else{
            throw new \Exception('ID number is non-numerical');
        }
    }

    public function setVerbose($_verbose){
        if(is_bool($_verbose)){
            $this->verbose = $_verbose;
        }else{
            throw new \Exception('Verbose mode is non-boolean');
        }
    }

    public function setTimeout($_timeout){
        if(is_int($_timeout)){
            if($_timeout >= 0){
                $this->timeout = $_timeout;
                socket_set_option($this->socket,SOL_SOCKET,SO_RCVTIMEO,array("sec"=>$_timeout,"usec" => 0));
            }else{
                throw new \Exception('Invalid timeout number');
            }
        }else{
            throw new \Exception('Timeout number is non-numerical');
        }
    }

    public function setEnvironment($_env){
        $this->env = $_env;
    }

    public function run() {
        $this->state = self::STATE_RUNNING;
        if($this->verbose){
            $this->echo("running.");
        }
        do {
            if(($data = @socket_read($this->socket, self::BUFFER_SIZE, PHP_BINARY_READ)) != ""){
                $this->data .= $data;
                try{
                    $ret = $this->process_data();
                }catch(\Exception $e){
                    $this->error("Unknown error occured during 'process_data' function: ".$e->getMessage() . " (Error code: " . $e->getCode().")");
                    $ret = 1;
                }
                if($ret !== 0 && $ret === null){
                    $this->shutdown();
                    return;
                }
            }else{
                switch(socket_last_error($this->socket)){
                    case 0: // Disconnected
                        if($this->verbose){
                            $this->echo("disconnected.");
                        }
                        $this->shutdown();
                        return;
                    case 11: // Timeout
                        if($this->verbose){
                            $this->echo("timed out.");
                        }
                        $this->shutdown();
                        return;
                    case 104: // Connection reset by peer
                        if($this->verbose){
                            $this->echo("connection reset by peer.");
                        }
                        $this->shutdown();
                        return;
                    case 32: // Broken pipe
                        if($this->verbose){
                            $this->echo("broken pipe.");
                        }
                        $this->shutdown();
                        return;
                    default:
                        throw new \Exception("Socket read failed: " . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
                        break;
                }
            }
        } while (true);
    }
    public function process_init(){
        // DOES NOTHING, NEEDS TO BE EXTENDED;
    }
    public function process_data(){
        // DOES NOTHING, NEEDS TO BE EXTENDED;
    }
    public function process_abort(){
        // DOES NOTHING, NEEDS TO BE EXTENDED;
    }
    public function on_max_cycle(){
        // DOES NOTHING, NEEDS TO BE EXTENDED;
    }

    public function isRunning(){
        return ($this->state == self::STATE_RUNNING);
    }
    public function getState(){
        return $this->state;
    }

    public function shutdown(){
        try{
            $this->process_abort();
        }catch(\Exception $e){            
            $this->error("Unknown error occured during 'process_abort' function: ".$e->getMessage() . " (Error code: " . $e->getCode().")");
        }
        if($this->socket === false){
            throw new \Exception('Socket shutdown failed (false): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }elseif($this->socket === true){
            throw new \Exception('Socket shutdown failed (true): ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
        }else{
            if(@socket_shutdown($this->socket) === false){
                switch(socket_last_error($this->socket)){
                    case 107: // Shutdown failed
                        if($this->verbose){
                            $this->echo("shutting down: " . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
                        }
                        break;
                    default:
                        throw new \Exception('Socket shutdown failed: ' . socket_strerror(socket_last_error($this->socket))." [".socket_last_error($this->socket)."]");
                        return;
                }
            }
            $this->state = self::STATE_SHUTDOWN;
        }
    }

    public function echo(String $text){
        echo("Thread[". $this->id ."] " . $text . "\n");
        flush();
    }

    public function error(String $text){
        $this->echo( "\e[31mERROR\e[0m: ". $text);
    }

    public function warning(String $text){
        $this->echo( "\e[33mWARNING\e[0m: ". $text);
    }

    public function notice(String $text){
        $this->echo( "\e[32mNOTICE\e[0m: ". $text);
    }
}