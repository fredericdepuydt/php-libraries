<?php namespace Finances;

/************************************/
/*** Author: Frederic Depuydt     ***/
/*** Mail: f.depuydt@outlook.com  ***/
/************************************/

class Transactions {
    private $file;
    private $transactions;

    function __construct($_file){
        $this->file = $_file;
        $this->parse_file();
    }

    function parse_file(){
        //Open the file.
        $this->transactions = array();
        $fileHandle = fopen($this->file, "r");

        // TODO ADD CHECK FOR HANDLER

        //Loop through the CSV rows.
        if(($row = fgetcsv($fileHandle, 0, ";")) !== FALSE){
            while (($row = fgetcsv($fileHandle, 0, ";")) !== FALSE) {
                try{
                    $this->transactions[] = new Transaction\BNPParibasFortis($row);
                }catch(\UnderflowException $e){}; // continue
            }
        }
    }

    function log2file($_handler){
        foreach($this->transactions as $transaction){
            $transaction->log2file($_handler);
        }
    }

}

