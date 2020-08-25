<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

require_once "include/classes/class.HTTPDigestAuth.php";
require_once "include/classes/class.DB.php";
require_once "include/classes/class.USER.php";

class HTTPDigestAuthExtended extends HTTPDigestAuth {

    ////////////////////////////////////////////////////////////////////////
    // @variables
    //
    private $db;
    private $USER;

    ////////////////////////////////////////////////////////////////////////
    // @public
    //
    // @return an authenticated user object on success, null otherwise.
    function __construct() {
        parent::__construct();
        $this->db = new DB();
    }

    ////////////////////////////////////////////////////////////////////////
    // @extension
    // Gets the authentication realm for this class
    protected function getAuthRealm() {
        return "USER_AUTH";
    }

    // Gets the opaque for this class
    protected function getOpaque() {
        return md5("USER_AUTH");
    }

    // Creates a new nonce to send to the client
    protected function createNonce() {
        //do{
        $nonce = md5(uniqid());
        $id = 0;
        if (isset($this->USER)) {
            $id = $this->USER->id;
            $this->db->query("DELETE FROM `AUTH_nonces` WHERE `user_id` = " . $id . ";", TRUE);
        }
        $result = $this->db->query("INSERT INTO `AUTH_nonces` (`user_id`, `nonce`, `nc`, `expires`) VALUES (" . $id . ",'" . $nonce . "', 0, NOW());", TRUE);
        //} while($result == false);
        return $nonce;
    }

    // Delete a new nonce
    protected function deleteNonce($nonce) {
        return $this->db->query("DELETE FROM `AUTH_nonces` WHERE `nonce` = '" . $this->db->escape($nonce) . "'", TRUE);
    }

    // Returns whether or not this nonce is correct. Should return true for incorrect nonce
    protected function isWrongNonce($nonce) {
        $nonce = $this->db->escape($nonce);
        $this->db->query("DELETE FROM `AUTH_nonces` WHERE `expires` < NOW() - INTERVAL 1 MONTH;", TRUE);
        if (isset($this->USER) && isset($this->USER->id)) {
            if ($this->db->has_rows("SELECT 1 FROM `AUTH_nonces` WHERE (`user_id` = " . $this->USER->id . " || `user_id` = 0) AND `nonce` = '" . $nonce . "';", TRUE)) {
                $this->db->query("UPDATE `AUTH_nonces` SET `user_id` = " . $this->USER->id . " WHERE `user_id` = 0 AND `nonce` = '" . $nonce . "';", TRUE);
                $this->db->query("DELETE FROM `AUTH_nonces` WHERE (`user_id` = " . $this->USER->id . " AND `nonce` != '" . $nonce . "') OR (`user_id` = 0 AND `nonce` = '" . $nonce . "');");
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    // Returns whether or not this nonce has expired. Should return true for non existent nonce
    protected function isStaleNonce($nonce) {
        $nonce = $this->db->escape($nonce);
        return !$this->db->has_rows("SELECT 1 FROM `AUTH_nonces` WHERE `user_id` = " . $this->USER->id . " AND `nonce` = '" . $nonce . "' AND `expires` >= NOW() - INTERVAL 12 HOUR;");
    }

    // Gets the current request count for a particular nonce
    protected function getNonceCount($nonce) {
        return $this->db->select_value("SELECT `nc` FROM `AUTH_nonces` WHERE `nonce` = '" . $this->db->escape($nonce) . "';", TRUE);
    }

    protected function setNonceCount($nonce, $nc) {
        $this->db->query("UPDATE `AUTH_nonces` SET `nc` = '" . $this->db->escape($nc) . "' WHERE `nonce`='" . $this->db->escape($nonce) . "';", TRUE);
    }

    // Returns a boolean indicating whether or not a user with the specified
    protected function userExists($username, $password = null) {
        $this->USER = new USER($username, $password);
        return isset($this->USER->id) && $this->USER->id > 0;
    }

    // Returns a boolean indicating whether or not a user with the specified
    protected function userAllowedHTTPbasic($username) {
        if (isset($this->USER) && $this->USER->name == $username) {
            return $this->USER->hasPermission('LOGIN_AUTH_BASIC');
        } else {
            return false;
        }
    }

    // Returns the A1 hash for the specified user
    protected function getHA1ForUser($username) {
        $result = $this->db->select("SELECT `HA1_USER_AUTH` FROM `USR_users` WHERE `name` = '" . $username . "';", TRUE);
        if (sizeof($result) == 1) {
            return $result[0]['HA1_USER_AUTH'];
        } else {
            return false;
        }
    }

    // Returns a user instance that belongs to the user with the username provided.
    protected function getUser($username) {
        if (isset($this->USER) && $this->USER->name == $username) {
            return $this->USER;
        } else {
            return false;
        }
    }

}
