<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

require_once "include/classes/class.DB.php";

class USER {

    public $id = null;    //Integer : Stores the ID of the current user
    public $name = "";    //String : Stores the name of the current user
    public $company_id = null;
    public $permissions = [];  //Array : Stores the permissions for the user
    public $roles = [];   //Array : Stores the roles of the current user
    private $db;

    function __construct($user = null, $pass = null, $guestAllowed = false) {
        $this->db = new DB();
        if (isset($user)) {
            if (isset($pass)) {
                $this->name = $user;
                $this->id = intval($this->getUserIDbyPass($user, $pass));
            } else {
                if (is_numeric($user)) {
                    $this->id = intval($user);
                    $this->name = $this->getUserName($user);
                } else {
                    $this->name = $user;
                    $this->id = intval($this->getUserID($user));
                }
            }
        }

        if (isset($this->id)) {
            $this->roles = $this->getUserRoles(false);
            $this->buildUSER();
        } else if ($guestAllowed) {
            $this->id = 0;
            $this->name = "Guest";
            $this->roles = array(9);
            $this->buildUSER();
        }
    }

    function USER($user = null, $pass = null) {
        $this->__construct($user, $pass);
    }

    function getUserRoles($full_format = false) {
        if ($full_format) {
            $sql = "SELECT `USR_roles`.`id`,`USR_roles`.`role`
						FROM `USR_users_roles`
						INNER JOIN `USR_roles`
						ON `USR_users_roles`.`role_id` = `USR_roles`.`id`
						WHERE `user_id` = " . intval($this->id);
        } else {
            $sql = "SELECT `role_id` FROM `USR_users_roles` WHERE `user_id` = " . intval($this->id);
        }

        $result = $this->db->select($sql, TRUE);

        $resp = [];
        if ($result !== false) {
            foreach ($result as $row) {
                if ($full_format) {
                    $resp[] = array("id" => $row['id'], "role" => $row['role']);
                } else {
                    $resp[] = $row['role_id'];
                }
            }
        }
        return $resp;
    }

    function getAllRoles($full_format = false) {
        $result = $this->db->select("SELECT `id`, `role` FROM `USR_roles`", TRUE);
        $resp = [];
        foreach ($result as $row) {
            if ($full_format) {
                $resp[] = array("id" => $row['ID'], "role" => $row['role']);
            } else {
                $resp[] = $row['id'];
            }
        }
        return $resp;
    }

    function buildUSER() {
        $this->company_id = $this->db->select_value("SELECT `company_id` FROM `USR_users` WHERE `id` = ".$this->id." LIMIT 1", TRUE);
        // Get the rules for the user's role
        if (count($this->roles) > 0) {
            $this->getRolePermissions($this->roles);
        }
        // Get the individual user permissions
        $this->getUserPermissions($this->id);
    }

    function getPermKeyFromID($permission_id) {
        return $this->db->select_value("SELECT `id` FROM `USR_permissions` WHERE `ID` = " . intval($permission_id) . " LIMIT 1", TRUE);
    }

    function getPermNameFromID($permission_id) {
        return $this->db->select_value("SELECT `permission` FROM `USR_permissions` WHERE `id` = " . intval($permission_id) . " LIMIT 1", TRUE);
    }

    function getRoleFromID($role_id) {
        return $this->db->select_value("SELECT `role` FROM `USR_roles` WHERE `id` = " . intval($role_id) . " LIMIT 1", TRUE);
    }

    function getUserID($username) {
        return $this->db->select_value("SELECT `id` FROM `USR_users` WHERE `name` = '" . $this->db->escape($username) . "' LIMIT 1", TRUE);
    }

    function getUserIDbyPass($username, $password) {
        $result = $this->db->query("SELECT `id` FROM `USR_users` WHERE `name` = '" . $username . "' && `password` = '" . $password . "' LIMIT 1", TRUE);
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['id'];
        } else {
            return 0;
        }
    }

    function getUsername($user_id) {
        return $this->db->select_value("SELECT `name` FROM `USR_users` WHERE `id` = " . intval($user_id) . " LIMIT 1", TRUE);
    }

    function getRolePermissions($role_id) {
        if (is_array($role_id)) {
            $sql = "SELECT DISTINCT `USR_permissions`.`permission`
						FROM `USR_roles_permissions`
						INNER JOIN `USR_permissions`
						ON `USR_roles_permissions`.`permission_id` = `USR_permissions`.`id`
						WHERE `role_id` IN (" . implode(",", $role_id) . ")";
        } else {
            $sql = "SELECT `USR_permissions`.`permission`
						FROM `USR_roles_permissions`
						INNER JOIN `USR_permissions`
						ON `USR_roles_permissions`.`permission_id` = `USR_permissions`.`id`
						WHERE `role_id` = " . intval($role_id);
        }
        $result = $this->db->select($sql, TRUE);
        if ($result !== false) {
            foreach ($result as $row) {
                $this->permissions[$row['permission']] = true;
            }
        }
    }

    function getUserPermissions($user_id) {
        if (isset($user_id) && $user_id > 0) {
            $sql = "SELECT `USR_permissions`.`permission`, `USR_users_permissions`.`allowed`
                        FROM `USR_users_permissions`
                        INNER JOIN `USR_permissions`
                        ON `USR_users_permissions`.`permission_id` = `USR_permissions`.`id`
                        WHERE `user_id` = " . intval($user_id);
            $result = $this->db->select($sql, TRUE);
            if ($result !== false) {
                if (is_array($result)) {
                    foreach ($result as $row) {
                        $this->permissions[$row['permission']] = $row['allowed'];
                    }
                }
            }
        }
    }

    function getAllPerms() {
        $result = $this->db->select("SELECT * FROM `permissions` ORDER BY `type` ASC, `permissions` ASC", TRUE);
        foreach ($result as $row) {
            $resp[] = array('id' => $row['id'], 'permission' => $row['permission'], 'type' => $row['type']);
        }
        return $resp;
    }

    function hasPermission($permission) {
        if (array_key_exists($permission, $this->permissions)) {
            if ($this->permissions[$permission]) {
                return true;
            }
        }
        return false;
    }

}
