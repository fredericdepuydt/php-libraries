<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

require_once "include/classes/class.HTTPDigestAuthExtended.php";
require_once "include/classes/class.DB.php";
require_once "include/classes/class.PATH.php";

class WEB {

    protected $USER;
    protected $HTTP;
    protected $request;
    protected $path;
    protected $parameters;


    function __construct($_intern = false, $_request = null, $_USER = null) {
        $this->HTTP = new HTTP();
        $this->parameters['intern'] = $_intern;


        // TODO REPLACE SESSION
        session_start();
        // Getting stored USER
        if(isset($_SESSION["USER"])){
            $this->USER = $_SESSION["USER"];
        }else{
            $this->USER = new USER(null, null, true);
        }

        if($_intern == true || !isset($this->USER)){
            if($_USER == null){
                $this->USER = new USER(null, null, true);
            }else{
                $this->USER = $_USER;
            }
        }
        if (isset($_request)) {
            $request = $_request;
        } else {
            $request = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
            if (!$request) {
                $request = isset($_SERVER['ORIG_PATH_INFO']) ? $_SERVER['ORIG_PATH_INFO'] : '';
                $request = $request != $_SERVER['SCRIPT_NAME'] ? $request : '';
                if (!$request) {
                    $request = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
                }
            }
        }

        if(isset($_SERVER["HTTP_X_FORWARDED_PREFIX"])){
            $_SERVER['BASE'] = $_SERVER["HTTP_X_FORWARDED_PREFIX"] . $_SERVER['BASE'];
        }

        $_SERVER['WEBBASE'] = $_SERVER['BASE'] . "web/";

        // Trim request
        $request = trim($request, '/');
        $this->request = ($request !== "" ? explode('/', $request) : []);
    }

    public function extend() {
        $this->path = new PATH("web");
        if (count($this->request) == 0) {
            return $this->classload("home", true); // Get home.php
        } elseif ($this->get_file($this->path) !== false) {
            return $this->classload(null, true); // Get specific WEB file
        } else {
            $this->HTTP->setHTTP(404);
        }
    }

    private function get_file($prev_path, $offset = 0) {
        // Recursive function looking through API folder and files
        $path = clone $prev_path;
        if (count($this->request) >= 1 + $offset) {
            $path->appendfolder($this->request[$offset]);
            if (file_exists($path->full_folder())){
                if ($this->get_file($path, $offset + 1)) {
                    array_shift($this->request);
                    return true;
                }
            }
            $path = clone $prev_path;
            $path->file = $this->request[$offset];
            if (file_exists($path->full_file())) {
                array_shift($this->request);
                $this->path = $path;
                return true;
            }
            if($offset > 0){
                $path->file = $this->request[$offset-1];
                if (file_exists($path->full_file())) {
                    $this->path = $path;
                    return true;
                }

                $path->file = $this->request[$offset-1] . "_" . $this->request[$offset];
                if (file_exists($path->full_file())) {
                    array_shift($this->request);
                    $this->path = $path;
                    return true;
                }
            }
        }elseif(count($this->request) == $offset && $offset > 0){
            $path->file = $this->request[$offset-1];
            if (file_exists($path->full_file())) {
                $this->path = $path;
                return true;
            }
        }
        return false;
    }

    public function classload(string $suffix = null, bool $authorize = false) {
        if ($suffix !== null) {
            $this->path->file = strtolower($suffix);
        }
        $classname = 'Extended_WEB_' . $this->path->class_suffix();
        spl_autoload_register(array($this, 'autoload'));
        $Extended_REST = new $classname($this->HTTP, $this->USER, $this->request, $this->path, $this->parameters);
        if ($authorize) {
            $Extended_REST->authorize();
        }
        return $Extended_REST->extend();
    }

    public function autoload($class_name) {
        //if($class_name=="Extended_REST_"){
        require_once $this->path->full_file();
        spl_autoload_unregister(array($this, 'autoload'));
        /* }else{
          echo("Exception");
          } */
    }

}


abstract class Extended_WEB extends WEB {

    protected $request_method;

    function __construct($HTTP, $USER, $request, $path, $parameters) {
        $this->HTTP = $HTTP;
        $this->USER = $USER;
        $this->request = $request;
        $this->path = $path;
        $this->parameters = $parameters;

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $this->request_method = $_SERVER['REQUEST_METHOD'];
        } else {
            $this->request_method = "GET";
        }

        // Buffer echo's
        ob_start();

    }

    public function authorize() {
        $this->HTTP = new HTTPDigestAuthExtended();
        if (!isset($this->USER)){
            if(!$this->parameters['intern']) {
                $this->USER = $this->HTTP->authenticate();
            }else{
                $this->USER = new USER(null, null, true);
            }
        }
    }

    public function extend() {
        return $this;
    }

    public function execute() {
        $this->content['doctype'] = "<!doctype html>";
        $this->INITIALIZE();
        echo($this->content['doctype'].PHP_EOL);
        echo("<html>");
        echo("<head>");
        require 'include/web/header.php';
        $this->STYLE();
        echo("</head>");
        echo("<body>");

        if(!isset($this->menu) || $this->menu == true){
            echo('<div class="demo-layout mdl-layout mdl-js-layout mdl-layout--fixed-drawer">');
            require 'include/web/body/menu.php';
        }else{
            echo('<div class="demo-layout mdl-layout mdl-js-layout">');
        }
        if(!isset($this->title) || $this->title == true){
            $this->TITLE();
            require 'include/web/body/title.php';
        }
        $this->CONTENT();
        echo("</div>");
        echo("</body>");
        echo("</html>");
        @ob_flush();
        @flush();
    }

    protected function INITIALIZE() {

    }

    protected function STYLE() {

    }

    protected function TITLE() {

    }

    protected function CONTENT() {

    }

    public function SQL_QUERY(string $sql) {
        try {
            return $this->db->query($sql);
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text($sql));
            return null;
        }
    }

    public function SQL_MULTI_QUERY(string $sql) {
        try {
            $result = $this->db->multi_query($sql);
            while ($this->db->more_results() && $result !== false) {
                $result = $this->db->next_result();
            }
            return $result;
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text($sql));
            return null;
        }
    }

    public function SQL_STORE_RESULT() {
        try {
            return $this->db->store_result();
        } catch (Exception $e) {
            if ($e->getMessage() != 404) {
                $this->setJSONstatusCode($e->getMessage(), $this->db->error_text());
                return null;
            } else {
                return [];
            }
        }
    }

    public function SQL_STORE_RESULT_ROW() {
        try {
            return $this->db->store_result_row();
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text());
            return null;
        }
    }

    public function SQL_STORE_RESULT_VALUE() {
        try {
            return $this->db->store_result_value();
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text());
            return null;
        }
    }

    public function SQL_SELECT(string $sql) {
        try {
            return $this->db->select($sql);
        } catch (Exception $e) {
            if ($e->getMessage() != 404) {
                $this->setJSONstatusCode($e->getMessage(), $this->db->error_text($sql));
                return null;
            } else {
                return [];
            }
        }
    }

    public function SQL_SELECT_ROW(string $sql) {
        try {
            return $this->db->select_row($sql);
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text($sql));
            return null;
        }
    }

    public function SQL_SELECT_VALUE(string $sql) {
        try {
            return $this->db->select_value($sql);
        } catch (Exception $e) {
            $this->setJSONstatusCode($e->getMessage(), $this->db->error_text($sql));
            return null;
        }
    }

    //JSON related
    public function setJSONstatusCode($statusCode, $statusBody = false) {
        if (!isset($this->jsonbody["status"]["code"])) {
            $this->jsonbody["status"]["code"] = $statusCode;
            $this->jsonbody["status"]["message"] = $this->HTTP->getStatusMessage($statusCode);
            if ($statusCode != 200 && $statusBody !== false) {
                $this->jsonbody["status"]["body"] = $statusBody;
            }
        }
    }

    public function getJSONbody() {
        return $this->jsonbody;
    }

    //Additional functions
    public function GET_ACCEPT_LANGUAGE(): int {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            switch ($_SERVER['HTTP_ACCEPT_LANGUAGE']) {
                case "en-GB": return 1;
                case "nl-BE": return 2;
                case "nl-NL": return 2;
                case "fr-FR": return 3;
                case "fr-BE": return 3;
                default: return 1;
            }
        } else {
            return 1;
        }
    }

}
