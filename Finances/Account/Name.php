<?php namespace Finances\Account;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

trait Name{
    private static $db;

    public function initDB(){
        self::$db = new \Database\MySQL();
        self::$db->connect();
    }

    public function nameInParser($_name, $_location){
        $_name = self::$db->escape(trim($_name));
        $_location = self::$db->escape(trim($_location));
        
        if(isset($this->name)){
            return true;
        }

        $sql = "SELECT `n`.`name`
                FROM `parser_account_names` as `n`
                INNER JOIN `parser_account_name_matches` as `nm`
                ON `n`.`id` = `nm`.`name_id`
                WHERE `nm`.`match` = '".$_name."'
                AND `nm`.`approved` = 1;";
        try{
            $this->name = self::$db->select_value($sql);            
        }catch(\Database\MySQLException $e){     
            if ($e->getCode() == \Database\MySQLException::ER_TOO_FEW_ROWS){
                // Name not yet present in MATCH list
                $sql = "SELECT `id`, `name`, `regex`
                        FROM `parser_account_names`
                        WHERE `regex` != '';";
                $names = self::$db->select($sql);
                $matches = [];
                foreach($names as $name){
                    if(preg_match("/".$name['regex']."/", $_name)){
                        array_push($matches, $name);
                    }
                }
                $count = count($matches);
                if($count == 1) {
                        $this->name = $name['name'];
                        $sql = "INSERT INTO `parser_account_name_matches`
                                (`name_id`, `match`, `approved`) VALUES (".$name['id'].",'".$_name."', 1);";
                        self::$db->query($sql);
                }else{
                    if($count == 0){                  
                        // No MATCHES
                        echo("Name: ".$_name . "\n");
                        return true;
                    }else{
                        // TO MUCH MATCHES !! ERROR !!
                        return false;
                    }
                }
            }else{
                throw($e);
            }
        }
        // NAME WAS FOUND, now connect the location
        if($_location != ""){

            $sql = "SELECT `l`.`location`
                    FROM `parser_account_locations` as `l`
                    INNER JOIN `parser_account_location_matches` as `lm`
                    ON `l`.`id` = `lm`.`location_id`
                    WHERE `lm`.`match` = '".$_location."'
                    AND `lm`.`approved` = 1;";    
            try{
                $this->loc = self::$db->select_value($sql);            
            }catch(\Database\MySQLException $e){     
                if ($e->getCode() == \Database\MySQLException::ER_TOO_FEW_ROWS){
                    // Location not yet present in MATCH list
                    $sql = "SELECT `id`, `location`, `regex`
                            FROM `parser_account_locations`
                            WHERE `regex` != '';";
                    $locations = self::$db->select($sql);
                    $matches = [];
                    foreach($locations as $location){
                        if(preg_match("/".$location['regex']."/", $_location)){
                            array_push($matches, $location);
                        }
                    }
                    $count = count($matches);
                    if($count == 1) {
                            $this->location = $location['location'];
                            $sql = "INSERT INTO `parser_account_location_matches`
                                    (`location_id`, `match`, `approved`) VALUES (".$location['id'].",'".$_location."', 1);";
                            self::$db->query($sql);
                    }else{
                        if($count == 0){                  
                            // No MATCHES
                            echo("Location: " . $_location . "            Name: " . $this->name . "\n");
                            return true;
                        }else{
                            // TO MUCH MATCHES !! ERROR !!
                            return false;
                        }
                    }
                }else{
                    throw($e);
                }
            }
        }
        // NAME AND LOCATION WERE FOUND
        return true;
    }

    public function nameInFirefly($_name){
        $sql = "SELECT COUNT(`id`) FROM `accounts` WHERE `name` = '" . self::$db->escape($_name) ."';";
        if(self::$db->select_value($sql)){
            $this->name = $_name;
            return true;
        }else{
            return false;
        }
    }
}