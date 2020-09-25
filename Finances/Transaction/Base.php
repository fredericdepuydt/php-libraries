<?php namespace Finances\Transaction;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

class Base {

    public $src_account;
    public $dst_account;
    public $amount;
    public $category;
    public $date;
    public $tags;
    public $description;
    public $notes;


    function __construct(){
        $this->src_account = new \Finances\Account();
        $this->dst_account = new \Finances\Account();
    }

    function is_bankaccount($_var){
        return true;
    }

    function is_date($_date, $_format = null){
        switch(strtolower($_format)){
            case 'dd-mm-yyyy':
                $regex = "^(?:0?[1-9]|[12][0-9]|3[01])\-(?:0?[1-9]|1[012])\-\d{4}$";
                break;
            case 'dd/mm/yyyy':
                $regex = "^(?:0?[1-9]|[12][0-9]|3[01])\/(?:0?[1-9]|1[012])\/\d{4}$";
                break;
            default:
                $regex = $_format;
        }

        preg_match('('.$regex.')', $_date, $matches);
        return (count($matches)==1);
    }
}