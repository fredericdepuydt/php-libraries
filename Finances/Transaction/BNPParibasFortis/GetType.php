<?php namespace Finances\Transaction\BNPParibasFortis;

/*******************************************/
/*** Author: Frederic Depuydt            ***/
/*** Mail: frederic.depuydt@outlook.com  ***/
/*******************************************/

trait GetType{

    function getType(){
        $str1 = $this->row[6];
        $str2 = $this->row[5];

        if(substr($str1,0,23) == "BETALING MET BANKKAART " && $str2 == "BETALING MET BANKKAART"){
            // BETALING MET BANKKAART
            $this->setinfo(array('card','bankreferentie','via','referte_opdrachtgever','eur','toegevoegde_datum','tegenpartij-per-18','eur-invert'));

        }elseif(substr($str1,0,28) == "EUROPESE OVERSCHRIJVING VAN " && $str2 == "EUROPESE OVERSCHRIJVING VAN"){
            // EUROPESE OVERSCHRIJVING VAN
            $this->setinfo(array('bankreferentie','via','referte_opdrachtgever','bic','tegenpartij-direct'));

        }elseif(substr($str1,0,28) == "EUROPESE OVERSCHRIJVING VAN " && $this->is_bankaccount($str2)){
            // EUROPESE OVERSCHRIJVING VAN
            $this->setinfo(array('bankreferentie','via','referte_opdrachtgever','bic','tegenpartij-direct'));

        }elseif(substr($str1,0,29) == "EUROPESE OVERSCHRIJVING NAAR " && $str2 == "EUROPESE OVERSCHRIJVING NAAR"){
            // EUROPESE OVERSCHRIJVING NAAR
            $this->setinfo(array('bankreferentie','via','referte_opdrachtgever','bic','tegenpartij-direct'));

        }elseif(substr($str1,0,27) == "EUROPESE DOMICILIERING VAN " && $str2 == "EUROPESE DOMICILIERING VAN"){
            // EUROPESE DOMICILIERING VAN
            $this->setinfo(array('bankreferentie','via','referte','mandaat','referte_opdrachtgever','tegenpartij-direct'));

        }elseif(substr($str1,0,32) == "GELDOPNAME AAN ANDERE AUTOMATEN " && $str2 == "GELDOPNAME AAN ANDERE AUTOMATEN"){
            // GELDOPNAME AAN ANDERE AUTOMATEN
            $this->setinfo(array('card','bankreferentie','via','referte_opdrachtgever','eur','toegevoegde_datum','tegenpartij-geldopname'));

        }elseif(substr($str1,0,30) == "GELDOPNAME AAN ONZE AUTOMATEN " && $str2 == "GELDOPNAME AAN ONZE AUTOMATEN"){
            // GELDOPNAME AAN ONZE AUTOMATEN
            $this->setinfo(array('card','bankreferentie','via','referte_opdrachtgever','eur','toegevoegde_datum','tegenpartij-geldopname'));

        }elseif(substr($str1,0,19) == "STORTING CONTANTEN " && $str2 == "STORTING CONTANTEN"){
            // STORTING CONTANTEN
            $this->setinfo(array('cash','via','referte_opdrachtgever','tegenpartij-contant'));

        }elseif(substr($str1,0,22) == "STORTING IN CONTANTEN " && $str2 == "STORTING IN CONTANTEN"){
            // STORTING IN CONTANTEN
            $this->setinfo(array('via','referte_opdrachtgever','tegenpartij-contant'));

        }elseif(substr($str1,0,29) == "UW DOORLOPENDE OPDRACHT NAAR " && $str2 == "UW DOORLOPENDE OPDRACHT NAAR"){
            // UW DOORLOPENDE OPDRACHT NAAR
            $this->setinfo(array('via','referte_opdrachtgever','bic','tegenpartij-direct'));

        }elseif(substr($str1,0,27) == "EERSTE INVORDERING VAN EEN " && $str2 == "EERSTE INVORDERING VAN EEN"){
            // EERSTE INVORDERING VAN EEN
            $this->setinfo(array('domiciliering','bankreferentie','via','referte','mandaat','referte_opdrachtgever','tegenpartij-direct'));

        }elseif(substr($str1,0,24) == "ANNULERING BETALING VAN " && substr($str2,0,24) == "ANNULERING BETALING VAN "){
            // ANNULERING BETALING VAN
            $this->setinfo(array('card','via','referte_opdrachtgever','eur','tegenpartij-direct'));

		}elseif(substr($str1,0,31) == "BETALING AAN BANK CARD COMPANY " && $str2 == "BETALING AAN BANK CARD COMPANY"){
            // BETALING AAN BANK CARD COMPANY
            $this->setinfo(array('bankreferentie','visa','uitgavenstaat'));

		}elseif(substr($str1,0,35) == "INSCHRIJVING OP BELGISCHE EFFECTEN " && $str2 == "INSCHRIJVING OP BELGISCHE EFFECT"){
            // INSCHRIJVING OP BELGISCHE EFFECTEN
            $this->setinfo(array('bankreferentie','referte','mandaat','tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*INSCHRIJVING OP BELGISCHE EFFECTEN*";

		}elseif(substr($str1,0,36) == "OVERSCHRIJVING NAAR PROXIMUS PAY&GO " && $str2 == "OVERSCHRIJVING NAAR PROXIMUS PAY"){
            // OVERSCHRIJVING NAAR PROXIMUS PAY&GO
            $this->setinfo(array('tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*OVERSCHRIJVING NAAR PROXIMUS PAY&GO*";

		}elseif(substr($str1,0,31) == "MAANDELIJKSE EQUIPERINGSKOSTEN " && $str2 == "MAANDELIJKSE EQUIPERINGSKOSTEN"){
            // MAANDELIJKSE EQUIPERINGSKOSTEN
            $this->setinfo(array('bankreferentie','rest_mededeling','tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*MAANDELIJKSE EQUIPERINGSKOSTEN*";

		}elseif(substr($str1,0,17) == "NETTO INTERESTEN " && substr($str2,0,16) == "NETTO INTERESTEN"){
            // NETTO INTERESTEN
            $this->setinfo(array('tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*NETTO INTERESTEN*";

		}elseif(substr($str1,0,27) == "OVERSCHRIJVING UWEN GUNSTE " && $str2 == "OVERSCHRIJVING UWEN GUNSTE"){
            // OVERSCHRIJVING UWEN GUNSTE
            $this->setinfo(array('tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*OVERSCHRIJVING UWEN GUNSTE*";

        }elseif(substr($str1,0,11) == "PORTKOSTEN " && $str2 == "PORTKOSTEN"){
            // PORTKOSTEN
            $this->setinfo(array('tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*PORTKOSTEN*";

        }elseif(substr($str1,0,22) == "MAANDELIJKSE BIJDRAGE " && $str2 == "MAANDELIJKSE BIJDRAGE"){
            // MAANDELIJKSE BIJDRAGE
            $this->setinfo(array('bankreferentie','rest_mededeling','tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*MAANDELIJKSE BIJDRAGE*";

        }elseif(substr($str1,0,28) == "OVERSCHRIJVING IN EURO NAAR " && $str2 == "OVERSCHRIJVING IN EURO NAAR"){
            // OVERSCHRIJVING IN EURO NAAR
            $this->setinfo(array('bankreferentie','via','referte_opdrachtgever','bic','tegenpartij-direct'));

        }elseif(substr($str1,0,33) == "GLOBALISATIE 1 VERRICHTINGEN POS " && $str2 == "GLOBALISATIE 1 VERRICHTINGEN POS"){
            // GLOBALISATIE 1 VERRICHTINGEN POS
            $this->setinfo(array('datum','terminal','tegenpartij-alternatief'));
            $this->info['tegenpartij-alternatief'] = "*GLOBALISATIE*";

        }elseif(substr($str1,0,30) == "UW GELDOPVRAGING IN CONTANTEN " && $str2 == "UW GELDOPVRAGING IN CONTANTEN"){
            // UW GELDOPVRAGING IN CONTANTEN
            $this->setinfo(array('in'));

		}elseif(substr($str1,0,23) == "OVERSCHRIJVING VAN REK "){
			// OVERSCHRIJVING VAN REK
            $str1 = explode("  ", $str1)[0];
			if($str2 == substr($str1,23,strlen($str1)-23)){
                $this->setinfo(array('via','tegenpartij-van'));
            }else{
                throw new \Exception("Error in transaction type (OVERSCHRIJVING VAN REK)");
            }
		}elseif(substr($str1,0,23) == "OVERSCHRIJVING OP REK. "){
			// OVERSCHRIJVING OP REK
            $str1 = explode("  ", $str1)[0];
            if($str2 == substr($str1,23,strlen($str1)-23)){
                $this->setinfo(array('via','tegenpartij-van'));
            }else{
                throw new \Exception("Error in transaction type (OVERSCHRIJVING OP REK)");
            }
        }else{
            throw new \Exception("Error in transaction type (None)");
        }
    }
}