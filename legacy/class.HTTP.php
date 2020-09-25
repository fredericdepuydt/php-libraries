<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

class HTTP {

    protected $version;
    private $headers = [];

    function __construct(string $version = "HTTP/1.1") {
        $this->version = $version;
    }

    public function addHeader(string $key, string $value = null) {
        $this->headers[$key] = $value;
    }

    public function setHeaders(int $statusCode, bool $die = TRUE) {
        if(!headers_sent()){
            header($this->version . ' ' . $statusCode . ' ' . $this->getStatusMessage($statusCode));
            foreach ($this->headers as $key => $value) {
                if ($value != "") {
                    header($key . ": " . $value);
                } else {
                    header($key);
                }
            }
        }
        if ($die) {
            die();
        }
    }

    protected function setBody(int $statusCode, string $message = "", bool $die = TRUE) {
        echo('  <!DOCTYPE HTML>
                <html>
					<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>Error</title></head>
					<body><h1>' . $statusCode . ' ' . $this->getStatusMessage($statusCode) . '</h1><p>' . htmlspecialchars($message) . '</p></body>
                </HTML>');
        if ($die) {
            die();
        }
    }

    public function setHTTP(int $statusCode, string $message = "", bool $die = TRUE) {
        $this->setHeaders($statusCode, FALSE);
        $this->setBody($statusCode, $message, FALSE);
        if ($die) {
            die();
        }
    }

    public function getStatusMessage(int $statusCode): string {
        $statusMessage = array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I\'m a teapot',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            440 => 'Login Time-out',
            449 => 'Retry With',
            451 => 'Unavailable For Legal Reasons',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            510 => 'Not Extended',
            511 => 'Network Authentication Required');
        return ($statusMessage[$statusCode]) ? $statusMessage[$statusCode] : $statusMessage[500];
    }
}

