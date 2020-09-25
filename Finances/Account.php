<?php namespace Finances;

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

class Account {
    public $name;
    public $iban;
    public $bic;
    public $notes;

    function __construct(){
        $this->initDB();
    }

    use Account\Iban;
    use Account\Name;
}

