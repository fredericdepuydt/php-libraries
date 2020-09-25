<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

require_once "include/classes/class.HTTPDigestAuthExtended.php";
require_once "include/classes/class.DB.php";
require_once "include/classes/class.PATH.php";

class REST {

    protected $USER;
    protected $HTTP;
    protected $request;
    protected $path;
    protected $parameters;
    protected $HTTP_request_body = null;

    function __construct($_intern = false, $_request = null, $_USER = null) {
        $this->parameters['intern'] = $_intern;
        if($_intern == true){
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
            $_SERVER['REQUEST_URI'] = $_SERVER["HTTP_X_FORWARDED_PREFIX"] . $_SERVER['REQUEST_URI'];
            unset($_SERVER["HTTP_X_FORWARDED_PREFIX"]);
        }

        // Trim request
        $request = trim($request, '/');
        $this->request = ($request !== "" ? explode('/', $request) : []);
    }

    public function extend() {
        $this->path = new PATH("api");
        if (count($this->request) == 0) {
            return $this->classload("api", true); // Get api.php
        } elseif ($this->get_file($this->path) !== false) {
            return $this->classload(null, true); // Get specific API file
        } else {
            return $this->classload("unsupported", true); // Get unsupported.php
        }
    }

    private function get_file($prev_path, $offset = 0) {
        // Recursive function looking through API folder and files
        $path = clone $prev_path;
        if (count($this->request) >= 1 + $offset) {
            $path->appendfolder($this->request[$offset]);
            //echo($path->full_folder().PHP_EOL);
            if (file_exists($path->full_folder())){
                if ($this->get_file($path, $offset + 1)) {
                    array_shift($this->request);
                    return true;
                }
            }
            $path = clone $prev_path;
            $path->file = $this->request[$offset];
            //echo($path->full_file().PHP_EOL);
            if (file_exists($path->full_file())) {
                array_shift($this->request);
                $this->path = $path;
                return true;
            }
            if($offset > 0){
                $path->file = $this->request[$offset-1];
                //echo($path->full_file().PHP_EOL);
                if (file_exists($path->full_file())) {
                    $this->path = $path;
                    return true;
                }

                $path->file = $this->request[$offset-1] . "_" . $this->request[$offset];
                //echo($path->full_file().PHP_EOL);
                if (file_exists($path->full_file())) {
                    array_shift($this->request);
                    $this->path = $path;
                    return true;
                }
            }
        }elseif(count($this->request) == $offset && $offset > 0){
            $path->file = $this->request[$offset-1];
            //echo($path->full_file().PHP_EOL);
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
        $classname = 'Extended_REST_' . $this->path->class_suffix();
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

abstract class Extended_REST extends REST {

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
        if (!$this->parameters['intern']) {
            $this->HTTP = new HTTPDigestAuthExtended();
            $this->USER = $this->HTTP->authenticate();
        } else {
            $this->HTTP = new HTTPDigestAuthExtended();
            if (!isset($this->USER)) {
                $this->USER = new USER(null, null, true);
            }
        }
    }

    public function extend() {
        return $this;
    }

    public function execute() {
        if ($this->USER->hasPermission('HTTP_' . strtolower($this->request_method)) || $this->request_method == "UNSUPPORTED") {
            switch ($this->request_method) {
                case "GET":
                    $this->INITIALIZE();
                    $this->REST_GET();
                    break;
                case "PUT":
                    $this->INITIALIZE();
                    $this->REST_PUT();
                    break;
                case "POST":
                    $this->INITIALIZE();
                    $this->REST_POST();
                    break;
                case "DELETE":
                    $this->INITIALIZE();
                    $this->REST_DELETE();
                    break;
                default:
                    $this->UNSUPPORTED();
                    break;
            }
        } else {
            $this->HTTP->setHTTP(403, 'Missing permission: HTTP_' . strtolower($this->request_method));
        }
        if (!$this->parameters['intern']) {
            $this->HTTP->setHeaders(200, false);
            $this->FINALIZE();
            $this->LOGGING();
        }
    }

    protected function INITIALIZE() {
        $this->HTTP->addHeader("Content-Type", "application/json; charset=utf-8");
        $this->jsonheader['type'] = 'Questionnaire';
        $this->db = new DB;
    }

    protected function PUSH(bool $close = TRUE) {
        ob_implicit_flush(true);
        @ob_end_flush();
        $this->FINALIZE();
        $length = ob_get_length();
        if($close){
            $this->HTTP->addHeader("Content-Length",$length);
            $this->HTTP->addHeader("Connection","close");
        }
        $this->HTTP->setHeaders(200, false);
        if($length<4096){
            echo str_pad('',4096-$length);
        }
        @ob_flush();
        @flush();
    }

    protected function FINALIZE() {
        //Creating JSON
        if (isset($this->jsonbody)) {
            $this->setJSONstatusCode(200);
            if (isset($this->jsonheader)) {
                $json = $this->jsonheader;
            } else {
                $json = [];
            }
            //Generating metadata
            $metadata["request"] = $this->request_method;
            $metadata["title"] = "EDUCAT API";
            $metadata["url"] = $_SERVER['REQUEST_URI'];
            $metadata["php"]["version"] = PHP_VERSION;
            $metadata["php"]["class"]["name"] = get_class($this);
            $metadata["php"]["class"]["file"] = $this->path->full_file();
            $json["metadata"] = $metadata;
            if (!empty($this->jsonbody)) {
                $json += $this->jsonbody;
            }
            try {
                echo(json_encode($json, JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR));
            } catch (Exception $e) {
                ob_start();
                var_dump($this->jsonheader);
                $this->HTTP->setHTTP(500, $e->getMessage() . " " . ob_get_clean());
            }
        } else {
            $this->HTTP->setHTTP(500, "JSON Response Body isn't set");
        }
    }

    protected function LOGGING() {
        if (property_exists($this, "db") && isset($this->db)) {
            $db = $this->db;
        } else {
            $db = new DB();
        }
        $date = round(microtime(true) * 1000);
        $api = $_SERVER['REQUEST_URI'];
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $HTTP_method = $_SERVER['REQUEST_METHOD'];
        } else {
            $HTTP_method = "GET";
        }
        $HTTP_status_code = http_response_code();
        if (isset($this->jsonbody["status"]["code"])) {
            $JSON_status_code = $this->jsonbody["status"]["code"];
        } else {
            $JSON_status_code = null;
        }
        if (isset($this->USER)) {
            $user_id = $this->USER->id;
        } else {
            $user_id = null;
        }
        $request = $db->escape(file_get_contents('php://input'));
        $response = $db->escape(ob_get_contents());

        $sql = "INSERT INTO `LOG_api` (`date`, `api`, `HTTP_method`, `HTTP_status_code`, `JSON_status_code`, `user_id`, `request`, `response`)
						VALUES (" . $date . ",'" . $api . "','" . $HTTP_method . "'," . $HTTP_status_code . "," . $JSON_status_code . "," . $user_id . ",'" . $request . "','" . $response . "')";
        $db->query($sql, TRUE);
    }

    public function setHTTPrequestbody($body) {
        $this->HTTP_request_body = $body;
    }

    protected function getJSONfromBody($tag) {
        if($this->HTTP_request_body == null){
            $this->HTTP_request_body  = file_get_contents('php://input');
        }
        // Check HTTP Body
        if (empty($this->HTTP_request_body)) {
            $this->setJSONstatusCode(510, "No content in HTTP body");
            return false;
        }
        $json_decoded = json_decode($this->HTTP_request_body, TRUE);
        // Check JSON Content
        if (!isset($json_decoded[$tag])) {
            $this->setJSONstatusCode(510, "Wrong JSON content in HTTP body");
            return false;
        } else {
            return $json_decoded[$tag];
        }
    }

    protected function REST_GET() {
        $this->setJSONstatusCode(405);
    }

    protected function REST_PUT() {
        $this->setJSONstatusCode(405);
    }

    protected function REST_POST() {
        $this->setJSONstatusCode(405);
    }

    protected function REST_UPDATE() {
        $this->setJSONstatusCode(405);
    }

    protected function REST_DELETE() {
        $this->setJSONstatusCode(405);
    }

    protected function UNSUPPORTED() {
        $this->HTTP->addHeader("Content-Type", "application/json");
        $this->setJSONstatusCode(409, "Unsupported API parameters");
    }

    protected function REST_FORBIDDEN(string $permission = '') {
        $this->setJSONstatusCode(403, $permission);
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
