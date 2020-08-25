<?php

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

class PATH {

    const ext = ".php";
    private $root;

    public $file = "";
    public $prefix = "";
    public $folder = "";

    public function __construct($root){
        $this->root = $root;
    }

    public function full_file() {
        return $this->full_folder() . $this->file . self::ext;
    }

    public function full_folder() {
        return $this->root . $this->folder . "/";
    }

    public function class_suffix() {
        $exploded_folder = explode('/', $this->folder);
        $last_folder = end($exploded_folder);
        if ($last_folder == $this->file) {
            return trim(strtoupper(str_replace('/', '_', $this->folder)), '_');
        } elseif ($last_folder.'_' == substr($this->file,0,strlen($last_folder)+1)) {
            return trim(strtoupper(str_replace('/', '_', $this->folder) . "_" . (substr($this->file,strlen($last_folder)+1))), '_');
        }else{
            return trim(strtoupper(str_replace('/', '_', $this->folder) . "_" . $this->file), '_');
        }
    }
    public function setfile($file) {
        $this->file = $file;
    }

    public function appendfolder($value) {
        $this->folder .= "/" . $value;
        $this->prefix = $value;
    }
}