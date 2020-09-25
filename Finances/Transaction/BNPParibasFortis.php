<?php namespace Finances\Transaction;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

class BNPParibasFortis extends Base {

    private $row;
    private $str;

    private $info;
    private $hasinfo;

    function __construct($_row){
        parent::__construct();
        $this->row = $_row;
        $this->info = array();

        if(count($this->row) == 1 && $this->row[0] == ""){
            throw new \UnderflowException("Empty transaction");
        }

        try{
            $this->getType();
            $this->extractInfo();
        }catch(\Exception $e){
            echo("HEADER:  " . $this->header . "\n");
            echo("FULL:  " . $this->row[6] . "\n");
            echo("DETAILS: " . $this->str . "\n");
            echo("ERROR:  " . $e->getMessage() . "\n");
            echo("\n");
        }
    }

    use BNPParibasFortis\GetType;
    use BNPParibasFortis\ExtractInfo;

    function setinfo($_arr){
        if(is_array($_arr)){
            foreach($_arr as $_var){
                $this->hasinfo[$_var] = true;
            }
        }else{
            $this->hasinfo[$_arr] = true;
        }
    }

    function getinfo($_var){
        return isset($this->hasinfo[$_var]) && ($this->hasinfo[$_var] === true);
    }

    function quote($_str){
        $_str = str_replace("\\\\","\\",$_str);
        $_str = str_replace("\\\"","\"",$_str);
        return "\"" . $_str . "\"";
    }

    function log2file($_handler){
        fwrite($_handler, $this->date . ",");
        fwrite($_handler, str_replace(",",".",$this->amount) . ",");
        fwrite($_handler, $this->quote($this->src_account->name).",");
        fwrite($_handler, str_replace(",",".",$this->src_account->iban) . ",");
        fwrite($_handler, str_replace(",",".",$this->src_account->bic) . ",");
        fwrite($_handler, $this->quote($this->dst_account->name).",");
        fwrite($_handler, str_replace(",",".",$this->dst_account->iban) . ",");
        fwrite($_handler, str_replace(",",".",$this->dst_account->bic) . ",");
        fwrite($_handler, str_replace(",",".",$this->dst_account->notes) . ",");
        fwrite($_handler, $this->quote($this->description).",");
        fwrite($_handler, $this->quote($this->notes).",");
        fwrite($_handler, str_replace(",",".",$this->row[5]) . ",");
        fwrite($_handler, str_replace(",",".",$this->row[6]) . ",");
        fwrite($_handler, str_replace(",",".",$this->row[7]) . ",");
        if(is_array($this->info)){
            $tegenpartij = str_replace(",",".",(array_key_exists('tegenpartij',$this->info)?$this->info['tegenpartij']:""));
            $plaats = str_replace(",",".",(array_key_exists('plaats',$this->info)?$this->info['plaats']:""));
            fwrite($_handler, $tegenpartij.",");
            fwrite($_handler, $plaats.",");
            fwrite($_handler, $tegenpartij." ".$plaats.",");
            fwrite($_handler, str_replace(",",".",(array_key_exists('mededeling',$this->info)?$this->info['mededeling']:"")) . "\r\n");
        }else{
            fwrite($_handler,",,,\r\n");
        }
    }

}