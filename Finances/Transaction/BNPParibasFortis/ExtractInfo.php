<?php namespace Finances\Transaction\BNPParibasFortis;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

trait ExtractInfo{

    function extractInfo(){
        // SOURCE ACCOUNT
        // $this->row[0]; // JAAR + REFERTE
        // $this->row[1]; // UITVOERINGSDATUM
        $this->date = $this->row[2]; // VALUTADATUM
        $this->amount = (float)str_replace(',','.',$this->row[3]); // BEDRAG
        // $this->row[4]; // MUNT V/D REKENING
        // $this->row[5]; // TEGENPARTIJ VAN DE VERRICHTING
        $this->str = $this->row[6]; // DETAILS
        $this->src_account->iban = trim($this->row[7]); // REKENINGNUMMER
        $this->src_account->findNameByIban();

        // ESSENTIAL CHECKS
        if(!$this->is_date($this->date,"dd/mm/yyyy")){
            throw new \Exception("Error in DATE from CSV column `VALUTADATUM`");
        }

        // HEADER REMOVING from STR
        $this->header = substr($this->str,0,63);
        $this->str = trim(substr($this->str,63));


        // MET KAART
        if($this->getinfo('card') === true){
            if(substr($this->str,0,10) == "MET KAART "){
                $this->card = substr($this->str,10,21);
                $this->str = trim(substr($this->str,32));
            }else{
                throw new \Exception("Missing: card");
            }
        }

        // IN CASH DEPOSIT MET KAART
        if($this->getinfo('cash')){
            if(substr($this->str,0,26) == "IN CASH DEPOSIT MET KAART "){
                $this->card = substr($this->str,26,21);
                $this->str = trim(substr($this->str,48));
            }else{
                throw new \Exception("Missing: cash");
            }
        }

        // EUROPESE DOMICILIERING VAN
        if($this->getinfo('domiciliering')){
            if(substr($this->str,0,27) == "EUROPESE DOMICILIERING VAN "){
                $this->str = trim(substr($this->str,27));
            }else{
                throw new \Exception("Missing: domiciliering");
            }
        }

        // VALUTADATUM
        $id = strrpos ($this->str, "VALUTADATUM : ");
        if($id !== false){
            $datum = substr($this->str, -10);
            if(strlen($this->str) - $id == 24 && $this->is_date($datum,"dd/mm/yyyy")){
                if($this->date == $datum){
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    throw new \Exception("Error `VALUTADATUM` doesn't match `Transaction->date`");
                }
            }else{
                echo("Details: " . $this->str . "\n");
                throw new \Exception("Error in VALUTADATUM");
            }
        }elseif($this->getinfo('valutadatum')){
            throw new \Exception("Missing: valutadatum");
        }

        // DATUM
        $id = strrpos ($this->str, "DATUM : ");
        if($id !== false){
            $datum = substr($this->str, -10);
            if(strlen($this->str) - $id == 18 && $this->is_date($datum,"dd/mm/yyyy")){
                $this->info['datum'] = $datum;
                $this->str = trim(substr($this->str, 0, $id));
            }else{
                throw new \Exception("Error in DATUM");
            }
        }elseif($this->getinfo('datum')){
            throw new \Exception("Missing: datum");
        }

        // TERMINAL
        if($this->getinfo('terminal')){
            $id = strrpos ($this->str, "TERMINAL NR.");
            if($id !== false){
                $terminal = substr($this->str,$id + 12);
                if(ctype_digit($terminal)){
                    $this->info['terminal'] = $terminal;
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    throw new \Exception("Nog data beschikbaar na TERMINAL");
                }
            }
        }

        // BANKREFERENTIE
            if($this->getinfo('bankreferentie')){
            $id = strrpos ($this->str, "BANKREFERENTIE : ");
            if($id !== false){
                $bankreferentie = substr($this->str,$id + 17);
                if(ctype_digit($bankreferentie)){
                    $this->info['bankreferentie'] = $bankreferentie;
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    echo("Details: " . $this->str . "\n");
                    throw new \Exception("Nog data beschikbaar na BANKREFERENTIE");
                }
            }
        }

        // UITGAVENSTAAT
        if($this->getinfo('uitgavenstaat')){
            $id = strrpos ($this->str, "UITGAVENSTAAT NUMMER ");
            if($id !== false){
                $uitgavenstaat = substr($this->str,$id + 21);
                if(ctype_digit($uitgavenstaat)){
                    $this->info['uitgavenstaat'] = $uitgavenstaat;
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    throw new \Exception("Nog data beschikbaar na UITGAVENSTAAT");
                }
            }
        }

        // VISA
        if($this->getinfo('visa')){
            $id = strrpos ($this->str, "INTERNE REKENING VISA : ");
            if($id !== false){
                $visa = substr($this->str,$id + 24);
                if(ctype_digit($visa)){
                    $this->info['visa'] = $visa;
                    $this->info['tegenpartij'] = "*VISA ". $visa . "*";
                    $this->info['plaats'] = "";
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    throw new \Exception("Nog data beschikbaar na VISA");
                }
            }
        }

        // UITGEVOERD OP
        $id = strrpos ($this->str, "UITGEVOERD OP ");
        if($id !== false){
            if(strlen($this->str) - $id == 19){
                $this->info['uitgevoerd_op'] = substr($this->str, -5);
                $this->str = trim(substr($this->str, 0, $id));
            }else{
                echo("Details: " . $this->str . "\n");
                throw new \Exception("Nog data beschikbaar na UITGEVOERD OP");
            }
        }elseif($this->getinfo('uitgevoerd_op')){
            throw new \Exception("Missing: uitgevoerd_op");
        }

        // MEDEDELING
        $id = strrpos ($this->str, "MEDEDELING : ");
        if($id !== false){
            $this->info['mededeling'] = ucfirst(trim(substr($this->str, $id + 12)));
            $this->str = trim(substr($this->str, 0, $id));
        }else{
            $id = strrpos ($this->str, "ZONDER MEDEDELING");
            if($id !== false){
                if(strlen($this->str) - $id == 17){
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    echo("Details: " . $this->str . "\n");
                    throw new \Exception("Nog data beschikbaar na ZONDER MEDEDELING");
                }
            }
        }

        // VIA ...
        if($this->getinfo('via')){
            $id = strrpos ($this->str, " VIA ");
            if($id !== false){
                $via = substr($this->str,$id + 5);
                // VIA SWITCH
                switch($via){
                    case "WEB BANKING":
                    case "MOBILE BANKING":
                    case "PC BANKING":
                    case "KANTOOR":
                    case "PC/WEB BANKING":
                        $this->info['via'] = ucfirst($via);
                        $this->str = trim(substr($this->str, 0, $id));
                        break;
                    default:
                        throw new \Exception("VIA onbekend");
                }
            }
        }

        // IN ...
        if($this->getinfo('in')){
            $id = strrpos ($this->str, "IN ");
            if($id !== false){
                if($id == 0){
                    $in = substr($this->str,$id + 3);
                    if(substr($in,0,11) == "HET KANTOOR"){
                        $this->info['tegenpartij'] = "KANTOOR " & trim($this->str,15);
                        $this->info['plaats'] = trim($this->str,15);
                        $this->str = "";
                    }else{
                        throw new \Exception("IN onbekend");
                    }
                }else{
                    throw new \Exception("Nog data beschikbaar voor IN");
                }
            }
        }

        // REFERTE
        if($this->getinfo('referte')){
            $id = strrpos ($this->str, "REFERTE : ");
            if($id !== false ){
                $this->info['referte'] = substr($this->str, $id + 10);
                $this->str = trim(substr($this->str, 0, $id));
            }
        }

        // MANDAAT
        if($this->getinfo('mandaat')){
            $id = strrpos ($this->str, "MANDAAT NUMMER : ");
            if($id !== false ){
                $this->info['mandaat'] = substr($this->str, $id + 17);
                $this->str = trim(substr($this->str, 0, $id));
            }
        }

        // REFERTE OPDRACHTGEVER
        if($this->getinfo('referte_opdrachtgever')){
            $id = strrpos ($this->str, "REFERTE OPDRACHTGEVER : ");
            if($id !== false){
                $this->info['referte_opdrachtgever'] = substr($this->str, $id + 24);
                $this->str = trim(substr($this->str, 0, $id));
            }
        }

        // EUR
        if($this->getinfo('eur')){
            $id = strrpos ($this->str, " EUR ");
            if($id !== false){
                $eur = (float)str_replace(',','.',trim(substr($this->str, $id + 5)));
                if($this->getinfo('eur-invert')){
                        $eur = -$eur;
                }
                $split = explode(",",$eur);
                if(is_numeric($eur)){
                    if($this->amount == $eur){
                        $this->str = trim(substr($this->str, 0, $id));
                    }else{
                        throw new \Exception("Error `EUR` doesn't match `Transaction->amount`");
                    }
                }else{
                    throw new \Exception("Nog data beschikbaar na EUR");
                }
            }
        }

        // TOEGEVOEGDE DATUM
        if($this->getinfo('toegevoegde_datum')){
            $datum = substr($this->str,-10);
            if($this->is_date($datum, "dd/mm/yyyy")){
                if($this->date == $datum){
                    $this->str = trim(substr($this->str, 0, strlen($this->str) - 11));
                }else{
                    throw new \Exception("Error toegevoegde datum doesn't match `Transaction->date`");
                }
            }else{
                throw new \Exception("Error in 'Toegevoegde datum'");
            }
        }

        // BIC & IBAN
        if($this->getinfo('bic')){
            $id = strrpos ($this->str, " BIC ");
            if($id !== false){
                if(strlen($this->str) - $id == 13){
                    $this->info['bic'] = trim(substr($this->str,-8));
                    $this->str = trim(substr($this->str, 0, $id));
                }elseif(strlen($this->str) - $id == 16){
                    $this->dst_account->bic = trim(substr($this->str,-11));
                    $this->str = trim(substr($this->str, 0, $id));
                }else{
                    throw new \Exception("Fout in BIC");
                }
                $id = 15;
                do{
                    $tmp = str_replace(" ","",substr($this->str,-$id));
                    $id = $id + 1;
                }while(strlen($this->str)>$id && strlen($tmp) < 33 && !$this->dst_account->setIban($tmp));

                if(strlen($tmp)<33){
                    $this->str = trim(substr($this->str, 0, strlen($this->str) - $id));
                }else{
                    echo("IBAN:    "  .$tmp."\n");
                    throw new \Exception("Fout in IBAN");
                }
            }
        }

        // TEGENPARTIJ EN PLAATS
        if($this->getinfo('tegenpartij-van')){
            if(substr($this->str,0,4) == "VAN "){
                $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,4)));
                $this->info['plaats'] = "";
                $this->str = "";
            }else{
                throw new \Exception("Fout in Tegenpartij (VAN)");
            }
        }
        if($this->getinfo('tegenpartij-alternatief')){
            $this->info['tegenpartij'] = $this->info['tegenpartij-alternatief'];
            $this->info['plaats'] = "";
        }
        if($this->getinfo('tegenpartij-direct')){
            $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim($this->str));
            $this->info['plaats'] = "";
            $this->str = "";
        }
        if($this->getinfo('tegenpartij-geldopname')){
            $this->info['tegenpartij'] = "GELDOPNAME AAN BANKAUTOMAAT" . (isset($this->card)?" MET KAART ".$this->card:"");
            $this->info['plaats'] = preg_replace('/\s\s+/', ' ',trim($this->str));
            $this->str = "";
        }
        if($this->getinfo('tegenpartij-contant')){
            $this->info['tegenpartij'] = "STORTING IN CONTANTEN" . (isset($this->card)?" MET KAART ".$this->card:"");
            $this->info['plaats'] = preg_replace('/\s\s+/', ' ',trim($this->str));
            $this->str = "";
        }
        if($this->getinfo('tegenpartij-per-18')){
            if(strlen($this->str)<=18){
                $id = strrpos($this->str, " ");
                if($id !== false){
                    $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,0,$id)));
                    $this->info['plaats'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,$id)));
                    $this->str = "";
                }else{
                    $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim($this->str));
                    $this->info['plaats'] = "";
                    $this->str = "";
                }
            }elseif(strlen($this->str)<=36){
                if(substr($this->str,17,1) == " "){
                    $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,0,18)));
                    $this->info['plaats'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,18)));
                    $this->str = "";
                }else{
                    $id = strpos($this->str, " ");
                    if($id !== false){
                        $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,0,$id)));
                        $this->info['plaats'] = preg_replace('/\s\s+/', ' ',trim(substr($this->str,$id)));
                        $this->str = "";
                    }else{
                        $this->info['tegenpartij'] = preg_replace('/\s\s+/', ' ',trim($this->str));
                        $this->info['plaats'] = "";
                        $this->str = "";
                    }
                }
            }else{
                throw new \Exception("Details too long");
            }
        }
        if(array_key_exists('tegenpartij',$this->info)){
            //if(!$this->dst_account->nameInFirefly($this->info['tegenpartij'])){
                if(!$this->dst_account->nameInParser($this->info['tegenpartij'],$this->info['plaats'])){
                    trigger_error("Destination Account unknown: " . $this->info['tegenpartij'], E_USER_WARNING);
                }else{
                    unset($this->info['tegenpartij']);
                    unset($this->info['plaats']);
                }
            /*}else{
                unset($this->info['tegenpartij']);
                unset($this->info['plaats']);
            }*/
        }else{
            trigger_error("Destination Account empty", E_USER_WARNING);
        }

        // REST to MEDEDELING
        if($this->getinfo('rest_mededeling')){
            if(strlen($this->str) > 0){
                $this->info['mededeling'] = $this->str;
                $this->str = "";
            }else{
                throw new \Exception("ERROR No rest for mededeling");
            }
        }

        //Creation of Description and Notes

        $this->description = $this->dst_account->name;
        $this->description .= (array_key_exists('mededeling',$this->info)?" - ".$this->info['mededeling'].";\n":"");
        $this->description = trim($this->description,";\n");

        $this->notes  = (array_key_exists('mededeling',$this->info)?"Mededeling:  ".$this->info['mededeling'].";\n":"");
        $this->notes .= (isset($this->card)?"Card: ".$this->card.";\n":"");
        $this->notes .= (array_key_exists('terminal',$this->info)?"Terminal Nr:  ".$this->info['terminal'].";\n":"");
        $this->notes .= (array_key_exists('bankreferentie',$this->info)?"Bankreferentie: ".$this->info['bankreferentie'].";\n":"");
        $this->notes .= (array_key_exists('uitgavenstaat',$this->info)?"Uitgavenstaat: ".$this->info['uitgavenstaat'].";\n":"");
        $this->notes .= (array_key_exists('via',$this->info)?"Via ".$this->info['via'].";\n":"");
        $this->notes .= (array_key_exists('in',$this->info)?"In: ".$this->info['in'].";\n":"");
        $this->notes .= (array_key_exists('mandaat',$this->info)?"Mandaat: ".$this->info['mandaat'].";\n":"");
        $this->notes .= (array_key_exists('referte',$this->info)?"Referte: ".$this->info['referte'].";\n":"");
        $this->notes .= (array_key_exists('referte_opdrachtgever',$this->info)?"Referte opdrachtgever: ".$this->info['referte_opdrachtgever'].";\n":"");
        $this->notes = trim($this->notes,";\n");

        if($this->str != ""){
            throw new \Exception("String not empty");
        }
    }
}