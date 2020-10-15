<?php namespace Database;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

class MySQL {
    // The database connection
    protected $connection;

    protected $db_port;
    protected $db_host;
    protected $db_user;
    protected $db_pass;
    protected $db_name;

    const CONN_ENV = 1;
    const CONN_FILE = 2;
    const CONN_VARS = 3;

    protected static $method = self::CONN_ENV;

    public static function setMethod($_method){
        switch($_method){
            case self::CONN_ENV:
                self::$method = self::CONN_ENV;
                break;
            case self::CONN_FILE:
                self::$method = self::CONN_FILE;
                break;
            case self::CONN_VARS:
                self::$method = self::CONN_VARS;
                break;
            default:
                throw new \InvalidArgumentException("MySQL constructor method unknown: ". $_method);
                break;
        }
    }

    // Constructing DB and gathering config data
    function __construct(){
        switch(self::$method){
            case self::CONN_ENV: // enviroment variables
                $this->db_host = (isset($_ENV['DB_HOST'])?$_ENV['DB_HOST']:"127.0.0.1");
                $this->db_port = (isset($_ENV['DB_PORT'])?$_ENV['DB_PORT']:3306);
                $this->db_user = (isset($_ENV['DB_USER'])?$_ENV['DB_USER']:"");
                $this->db_pass = (isset($_ENV['DB_PASS'])?$_ENV['DB_PASS']:"");
                $this->db_name = (isset($_ENV['DB_NAME'])?$_ENV['DB_NAME']:"");
                if(func_num_args()>0){
                    trigger_error("MySQL constructor with `environment variables` needs no arguments", E_USER_WARNING);
                }
                break;
            case self::CONN_FILE: // file variables
                if(func_num_args()==1){
                    $file = func_get_args()[1];
                }else{
                    $file = "include/db_config.ini";
                }
                $ini = parse_ini_file($file, true);
                if($ini !== false){
                    $this->db_host = (isset($ini['database']['DB_HOST'])?$ini['database']['DB_HOST']:"127.0.0.1");
                    $this->db_port = (isset($ini['database']['DB_PORT'])?$ini['database']['DB_PORT']:3306);
                    $this->db_user = (isset($ini['database']['DB_USER'])?$ini['database']['DB_USER']:"");
                    $this->db_pass = (isset($ini['database']['DB_PASS'])?$ini['database']['DB_PASS']:"");
                    $this->db_name = (isset($ini['database']['DB_NAME'])?$ini['database']['DB_NAME']:"");
                }else{
                    throw new \Exception("File `".$file."` not found");
                }
                if(func_num_args()>1){
                    trigger_error("MySQL constructor with `file variables` needs 0 or 1 argument", E_USER_WARNING);
                }
                break;
            case self::CONN_VARS: // custom variables
                if(func_num_args()==5){
                    $this->db_host = $argv[0];
                    $this->db_port = $argv[1];
                    $this->db_user = $argv[2];
                    $this->db_pass = $argv[3];
                    $this->db_name = $argv[4];
                }else{
                    throw new \InvalidArgumentException("MySQL constructor with `custom variables` needs 5 input arguments (db_host, db_port, db_user, db_pass, db_name)");
                }
            default:
                throw new \InvalidArgumentException("MySQL constructor method unknown: ". $method);
                break;
        }
    }

    // Destructing DB and clearing config data
    function __destruct() {
        $this->close();
    }

    // Connect to the database
    // @return bool false on failure / mysqli MySQLi object instance on success
    public function connect() {
        // Try and connect to the database
        if (!isset($this->connection)) {
            $this->connection = @(new \mysqli($this->db_host.":".$this->db_port,
                                              $this->db_user,
                                              $this->db_pass,
                                              $this->db_name));
        }else{
            \trigger_error("Connection was already established!",E_USER_WARNING);
        }
        // If connection was not successful, handle the error
        if ($this->connection === false || $this->connection->connect_errno) {
            // Handle error - notify administrator, log to a file, show an error screen, etc.
            throw new MySQLException($this->connection->connect_error,$this->connection->connect_errno);
        }
        return true;
    }

    // Reconnect to the database
    // @return bool false on failure / mysqli MySQLi object instance on success
    public function reconnect() {
        // Try and reconnect to the database
        if (!isset($this->connection)) {
            \trigger_error("Connection had to be reestablished!", E_USER_WARNING);
            $this->connection = @(new \mysqli($this->db_host.":".$this->db_port,
                                              $this->db_user,
                                              $this->db_pass,
                                              $this->db_name));
        }
        // If connection was not successful, handle the error
        if ($this->connection === false || $this->connection->connect_errno) {
            // Handle error - notify administrator, log to a file, show an error screen, etc.
            throw new MySQLException($this->connection->connect_error,$this->connection->connect_errno);
        }
        return true;
    }

    // Close the database connection
    // @return mixed The result of the mysqli::close() function
    public function close() {
        // Try and close the database
        if(isset($this->connection)) {
            try{
                $this->connection->close();
                unset($this->connection);
                return true;
            }catch(\Exception $e) {
                return false;
            }
        }else{
            return true;
        }
    }

    // Query the database
    // @param $query The query string
    // @return mixed The result of the mysqli::query() function
    public function query($query, $suppress = false) {
        // Reconnect to the database
        $this->reconnect();
        // Query the database
        $result = $this->connection->query($query);
        
        if ($result === false && !$suppress) {
            if($this->connection->errno == 1064){
                throw new MySQLException($this->connection->error . " (Full SQL Statement: *** ". $sql ." ***)", $this->connection->errno);
            }else{
                throw new MySQLException($this->connection->error, $this->connection->errno);
            }
        } else {
            return $result;
        }
        
    }

    // Query the database
    // @param $query The query string
    // @return mixed The result of the mysqli::query() function
    public function multi_query($query, $suppress = false) {
        // Reconnect to the database
        $this->reconnect();
        // Query the database
        $result = $this->connection->multi_query($query);        
        if ($result === false && !$suppress) {
            throw new MySQLException($this->connection->error,$this->connection->errno);
        } else {
            return $result;
        }
    }

    public function multi_select($query, $suppress = false) {
        $result = $this->multi_query($query, $suppress);
        if ($result === false) {
            if ($suppress) {
                return false;
            } else {
                throw new MySQLException($this->connection->error,$this->connection->errno);
            }            
        } else {
            while($this->more_results()){
                $this->next_result();
            }
            $result = $this->store_result();
            if (!$suppress && $result->num_rows == 0) {
                $result->free();
                unset($result);
                throw new MySQLException("Result doesn't consist of any row", MySQLException::ER_TOO_FEW_ROWS);
            } else {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                    unset($row);
                }
                $result->free();
                unset($result);
                return $rows;
            }
        }

        $this->reconnect();
        // Query the database
        $result = $this->connection->multi_query($query);        
        if ($result === false && !$suppress) {
            throw new MySQLException($this->connection->error,$this->connection->errno);
        } else {
            return $result;
        }
    }
    public function store_result($suppress = false) {
        try {
            // Reconnect to the database
            $this->reconnect();
            // Query the database
            $result = $this->connection->store_result();
            if ($result === false && !$suppress) {
                throw new MySQLException($this->connection->error,$this->connection->errno);
            } else {
                return $result;
            }
        } catch (\Exception $e) {
            throw new MySQLException("Store result error: ".$this->connection->error,$this->connection->errno);
        }
    }
    public function store_result_row($suppress = false) {
        
        $result = $this->store_result();
        // Catch errors
        if ($result->num_rows != 1) {
            if ($suppress) {
                return null;
            } else {
                if ($result->num_rows == 0) {
                    throw new MySQLException("Result doesn't consist of any row", MySQLException::ER_TOO_FEW_ROWS);
                } else {
                    throw new MySQLException("Result consisted of more than one row", MySQLException::ER_TOO_MANY_ROWS);
                }
            }
        }
        try{
            return $result->fetch_assoc();
        } catch (\Exception $e) {
            throw new MySQLException("Fetch assoc error: ".$this->connection->error,$this->connection->errno);
        }
    }
    public function store_result_value($suppress = false) {
        $result = $this->store_result_row($suppress);
        if (count($result) != 1) {
            if ($suppress) {
                return null;
            } else {
                if (count($result) == 0) {
                    throw new MySQLException("Too few columns", MySQLException::ER_TOO_FEW_FIELDS);
                } else {
                    throw new MySQLException("Too many columns", MySQLException::ER_TOO_MANY_FIELDS);
                }
            }
        }
        return reset($result);
    }

    public function next_result($suppress = false) {
        // Reconnect to the database
        $this->reconnect();
        // Query the database
        $result = $this->connection->next_result();
        if ($result === false && !$suppress) {
            throw new MySQLException($this->connection->error,$this->connection->errno);
        } else {
            return $result;
        }
    }

    public function more_results($suppress = false) {
        // Reconnect to the database
        $this->reconnect();
        // Query the database
        $result = $this->connection->more_results();
        if ($result === false && $suppress) {
            throw new MySQLException($this->connection->error,$this->connection->errno);
        } else {
            return $result;
        }
    }
    // Fetch rows from the database (SELECT query)
    // @param $query The query string
    // @return bool False on failure / array Database rows on success
    public function select($query, $suppress = false) {
        $result = $this->query($query, $suppress);
        if ($result === false) {
            $result->free();
            unset($result);
            if ($suppress) {
                return false;
            } else {
                throw new MySQLException($this->connection->error,$this->connection->errno);
            }
        } else {
            if (!$suppress && $result->num_rows == 0) {
                $result->free();
                unset($result);
                throw new MySQLException("Result doesn't consist of any row", MySQLException::ER_TOO_FEW_ROWS);
            } else {
                $rows = [];
                while ($row = $result->fetch_assoc()) {
                    $rows[] = $row;
                    unset($row);
                }
                $result->free();
                unset($result);
                return $rows;
            }
        }
    }
    // Fetch rows from the database (SELECT query)
    // @param $query The query string
    // @return bool False on failure / array Database rows on success
    public function select_row($query, $suppress = false) {
        $result = $this->select($query, $suppress);
        if ($result == null || count($result) != 1) {
            if ($suppress) {
                return null;
            } else {
                if (count($result) == 0) {
                    throw new MySQLException("Result doesn't consist of any row", MySQLException::ER_TOO_FEW_ROWS);
                } else {
                    throw new MySQLException("Result consisted of more than one row", MySQLException::ER_TOO_MANY_ROWS);
                }
            }
        }
        return $result[0];
    }
    // Fetch rows from the database (SELECT query)
    // @param $query The query string
    // @return bool False on failure / array Database rows on success
    public function select_value($query, $suppress = false) {
        $result = $this->select_row($query, $suppress);
        if ($result == null || count($result) != 1) {
            if ($suppress) {
                return null;
            } else {
                if (count($result) == 0) {
                    throw new MySQLException("Too few columns", MySQLException::ER_TOO_FEW_FIELDS);
                } else {
                    throw new MySQLException("Too many columns", MySQLException::ER_TOO_MANY_FIELDS);
                }
            }
        }
        return reset($result);
    }
    // Fetch rows from the database (SELECT query)
    // @param $query The query string
    // @return bool False on failure / array Database rows on success
    public function has_rows($query) {
        $result = $this->query($query);
        return ($result->num_rows >= 1);
    }
    public function has_row($query) {
        $result = $this->query($query);
        return ($result->num_rows == 1);
    }

    // Fetch the last error from the database
    // @return string Database error message
    public function error() {
        // Reconnect to the database
        $this->reconnect();
        return $this->connection->error;
    }
    // Fetch the last error from the database
    // @return string Database error message
    public function error_text($sql = false) {
        // Reconnect to the database
        $this->reconnect();
        $error = $this->connection->error;
        if ($error != "") {
            $error = "SQL Error: " . $error;
        } else {
            $error = "Unknown SQL Error";
        }
        if ($sql === false) {
            return $error;
        } else {
            return $error . " (" . preg_replace('/\s+/S', " ", $sql) . ")";
        }
    }
    // Quote and escape value for use in a database query
    // @param string $value The value to be quoted and escaped
    // @return string The quoted and escaped string
    public function escape($value) {
        // Reconnect to the database
        $this->reconnect();
        // Escaping the value
        return $this->connection->real_escape_string($value);
    }
    // Quote and escape value for use in a database query
    // @param string $value The value to be quoted and escaped
    // @return string The quoted and escaped string
    public function quote($value, $quotes = "'") {
        return $quotes . $this->escape($value) . $quotes;
    }
}

class MySQLException extends \Exception{
    const ER_TOO_MANY_FIELDS = 1117;
    const ER_TOO_MANY_ROWS = 1172;
    const ER_TOO_FEW_FIELDS = 3117; // Custom created errno
    const ER_TOO_FEW_ROWS = 3172; // Custom created errno
    const ER_DUP_ENTRY = 1062;
}