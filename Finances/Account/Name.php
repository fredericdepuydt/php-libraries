<?php namespace Finances\Account;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

trait Name{
    private static $db;

    public function initDB(){
        self::$db = new \DB\MySQL($_ENV['DB_HOST'].":".$_ENV['DB_PORT'],$_ENV['DB_USERNAME'],$_ENV['DB_PASSWORD'],$_ENV['DB_DATABASE']);
    }

    public function nameInParser($_name, $_loc){
        if(isset($this->name)){
            return true;
        }
        $sql = "SELECT `name`, `location` FROM `parser_account_list` WHERE `match` = '" . self::$db->escape(trim($_name . " " . $_loc)) ."';";
        try{
            $row = self::$db->select_row($sql);
            $this->name = $row['name'];
            $this->location = $row['location'];
            return true;
        }catch(\Exception $e){
            $sql = "SELECT `regex`, `replace` FROM `parser_account_name_regex`;";
            $names = self::$db->select($sql);
            foreach($names as $name){
                if($match = preg_match("/".$name['regex']."/", $_name)){
                    $this->name = $name['replace'];
                    $sql = "SELECT `regex`, `replace` FROM `parser_account_loc_regex`;";
                    $locs = self::$db->select($sql);
                    foreach($locs as $loc){
                        if($_loc != "" && preg_match("/".$loc['regex']."/", $_loc)){
                            $loc1 = $loc['replace'];
                        }                        
                        if(count($match) == 1 && preg_match("/".$loc['regex']."/", $match[0])){
                            $loc2 = $loc['replace'];
                        }
                        if(isset($loc1) || isset($loc2)){
                            if(isset($loc1) && isset($loc2)){
                                if($loc1 == $loc2){
                                    $this->location = $loc1;
                                }else{
                                    throw new \Exception("Multiple locations: ".$loc1." & ".$loc2);
                                }
                            }elseif(isset($loc1)){
                                $this->location = $loc1;
                            }else{
                                $this->location = $loc2;
                            }
                            break;
                        }
                    }
                    $sql = "INSERT INTO `parser_account_list` (`match`, `name`, `location`) VALUES ('".self::$db->escape(trim($_name." ".$_loc))."','".self::$db->escape(trim($this->name))."','".self::$db->escape(trim($this->location))."');";
                    self::$db->query($sql);
                    return true;
                }
            }
        }
        return false;
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