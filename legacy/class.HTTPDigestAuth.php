<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

require_once "include/classes/class.HTTP.php";

abstract class HTTPDigestAuth extends HTTP {

    ////////////////////////////////////////////////////////////////////////
    // @public
    //
    // @return an authenticated user object on success, null otherwise.
    function __construct($version = "HTTP/1.1") {
        $this->version = $version;
    }

    public function authenticate() {
        if (!empty($_SERVER['PHP_AUTH_DIGEST'])) {
            return $this->authenticate_DIGEST();
        } else if (!empty($_SERVER['PHP_AUTH_USER'])) {
            return $this->authenticate_BASIC();
        } else {
            $this->addHeaderUnauthorized();
            $this->setHTTP(401);
        }
    }

    protected function authenticate_BASIC() {
        // Check for user and password
        $username = $_SERVER['PHP_AUTH_USER'];
        $password = md5($_SERVER['PHP_AUTH_PW']);
        if (!$this->userExists($username, $password)) {
            $this->setHTTP(400);
        }
        // Check if user is allowed to use HTTP BASIC AUTH
        if (!$this->userAllowedHTTPbasic($username)) {
            $this->setHTTP(500);
        }
        // Authentication successful
        return $this->getUser($username);
    }

    protected function authenticate_DIGEST() {
        $authClientData = new HTTPDigestAuthClientData($_SERVER['PHP_AUTH_DIGEST']);

        // Check for user
        if (!$this->userExists($authClientData->username)) {
            $this->setHTTP(400);
        }
        // Check for wrong nonce
        if ($this->isWrongNonce($authClientData->nonce)) {
            $this->setHTTP(400);
        }
        // Check for stale nonce
        if ($this->isStaleNonce($authClientData->nonce)) {
            $this->addHeaderUnauthorized(TRUE);
            $this->setHTTP(401);
        }
        // Check for correct nonce count
        $nc = hexdec($authClientData->nc);
        if ($nc < $this->getNonceCount($authClientData->nonce) + 1) {
            $this->deleteNonce($authClientData->nonce);
            $this->addHeaderUnauthorized();
            $this->setHTTP(400);
        }
        $this->setNonceCount($authClientData->nonce, $nc);


        // Check request URI is the same as the auth digest uri
        if ($authClientData->uri != $_SERVER['REQUEST_URI']) {
            echo($authClientData->uri . " ");
            echo($_SERVER['REQUEST_URI']);
            //$this->addHeaderUnauthorized(FALSE,$authClientData->nonce);
            //$this->addHeaderUnauthorized();
            $this->setHTTP(405);
        }
        // Check opaque is correct
        if ($authClientData->opaque != $this->getOpaque()) {
            //$this->addHeaderUnauthorized(FALSE,$authClientData->nonce);
            //$this->addHeaderUnauthorized();
            $this->setHTTP(400);
        }

        // Getting A1 hash
        $HA1 = $this->getHA1ForUser($authClientData->username);
        // Generate A2 hash
        if ($authClientData->qop == 'auth-int') {
            $A2 = $_SERVER['REQUEST_METHOD'] . ':' . stripslashes($_SERVER['REQUEST_URI']) . ':' . file_get_contents('php://input');
            $HA2 = md5($A2);
        } else {
            $A2 = $_SERVER['REQUEST_METHOD'] . ':' . stripslashes($_SERVER['REQUEST_URI']);
            $HA2 = md5($A2);
        }
        // Generate the expected response
        if ($authClientData->qop == 'auth' || $authClientData->qop == 'auth-int') {
            $expectedResponse = md5($HA1 . ':' . $authClientData->nonce . ':' . $authClientData->nc . ':' . $authClientData->cnonce . ':' . $authClientData->qop . ':' . $HA2);
        } else {
            $expectedResponse = md5($HA1 . ':' . $authClientData->nonce . ':' . $HA2);
        }
        // Check request contained the expected response
        if ($authClientData->response != $expectedResponse) {
            //$this->addHeaderUnauthorized();
            $this->setHTTP(400, 'Wrong response');
        }

        // Authentication successful
        return $this->getUser($authClientData->username);
    }

    ////////////////////////////////////////////////////////////////////////
    // @private
    //
    private function addHeaderUnauthorized($stale = FALSE, $nonce = null) {
        if (isset($nonce)) {
            //remove
            $authHeader = 'WWW-Authenticate: Digest realm="' . $this->getAuthRealm() . '",qop="auth-int, auth",algorithm="MD5",nonce="' . $nonce . '",opaque="' . $this->getOpaque() . '"';
        } else {
            $authHeader = 'WWW-Authenticate: Digest realm="' . $this->getAuthRealm() . '",qop="auth-int, auth",algorithm="MD5",nonce="' . $this->createNonce() . '",opaque="' . $this->getOpaque() . '"';
        }
        if ($stale) {
            $authHeader .= ',stale=TRUE';
        }
        $this->addHeader($authHeader);
    }

    ////////////////////////////////////////////////////////////////////////
    // @required

    /**
     * Gets the authentication realm for this class
     *
     * @return String
     */
    abstract protected function getAuthRealm();

    /**
     * Gets the opaque for this class
     *
     * @return String
     */
    abstract protected function getOpaque();

    /**
     * Creates a new nonce to send to the client
     *
     * @return String
     */
    abstract protected function createNonce();

    /**
     * Creates a new nonce to send to the client
     *
     * @return String
     */
    abstract protected function deleteNonce($nonce);

    /**
     * Returns whether or not this nonce has expired. Should return true for
     * non existent nonce.
     *
     * @param String $nonce
     * @return Boolean
     */
    abstract protected function isWrongNonce($nonce);

    /**
     * Returns whether or not this nonce has expired. Should return true for
     * non existent nonce.
     *
     * @param String $nonce
     * @return Boolean
     */
    abstract protected function isStaleNonce($nonce);

    /**
     * Gets the current request count for a particular nonce
     *
     * @param String $nonce The nonce to get the count of
     * @return uint The current nonce count
     */
    abstract protected function getNonceCount($nonce);

    /**
     * Increments the nonce count by 1
     *
     * @param String $nonce The nonce to increment
     */
    abstract protected function setNonceCount($nonce, $nc);

    /**
     * Returns a boolean indicating whether or not a user with the specified
     * username exists.
     *
     * @param String $username
     * @return Boolean
     */
    abstract protected function userExists($username, $password = null);

    abstract protected function userAllowedHTTPbasic($username);

    /**
     * Returns the A1 hash for the specified user.
     * i.e. return md5('username:realm:password')
     *
     * @param String $username
     * @return String
     */
    abstract protected function getHA1ForUser($username);

    /**
     * Returns a user instance that belongs to the user with the username
     * provided.
     *
     * @param String $username
     * @return ???
     */
    abstract protected function getUser($username);
}

/**
 * @private
 */
class HTTPDigestAuthClientData {

    public $username;
    public $nonce;
    public $nc;
    public $cnonce;
    public $qop;
    public $uri;
    public $response;
    public $opaque;

    public function __construct($header) {
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response|opaque)=[\'"]?([^\'",]+)@', $header, $t);
        $data = array_combine($t[1], $t[2]);
        $this->username = $data['username'];
        $this->nonce = $data['nonce'];
        $this->nc = $data['nc'];
        $this->cnonce = $data['cnonce'];
        $this->qop = $data['qop'];
        $this->uri = $data['uri'];
        $this->response = $data['response'];
        $this->opaque = $data['opaque'];
    }

}
