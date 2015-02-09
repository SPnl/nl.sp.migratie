<?php
include('./baseclass.php');
class tribune extends baseclass {

    public $resultSet, $lmsObject, $lms, $lmsParams, $skipMS;

    public function __construct($testMode = false, $arguments) {
    	echo "Start module: Tribune \r\n";
        parent::baseclass($testMode);
        $pointer = explode("=", $arguments[1]);
        $this->resultSet = $this->dbAdapter->query("SELECT * FROM `lidmaatschappen` WHERE `COL 28` = 'ABOGR'");
        $this->fetchLidmaatschappen();
        $this->migreer();
		echo "Stop module: Tribune \r\n";
    }

    public function fetchLidmaatschappen() {
        $objLMS = new stdClass();
        try { $objLMS->tribBladGratis 	  = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Blad-Tribune Gratis")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Blad-Tribune Gratis niet gevonden \r\n"; }
        try { $objLMS->tribBladBetaald 	  = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Blad-Tribune Betaald")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Blad-Tribune Betaald niet gevonden \r\n"; }
        try { $objLMS->tribBladProef   	  = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Blad-Tribune Proef")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Blad-Tribune Proef niet gevonden \r\n"; }
		try { $objLMS->tribAudioBetaald   = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Audio-Tribune Betaald")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Audio-Tribune Betaald niet gevonden \r\n"; }
        $this->lmsObject = $objLMS;
    }

    public function migreer() {
    	echo "Start migratie \r\n";
        while ($this->lms = $this->resultSet->fetch_assoc()) {
         $this->skipMS = false;
			$startDatum       = ($this->lms['COL 12'] != '-  -') ? $this->lms['COL 12'] : NULL;
			$eindDatum        = (!empty($this->lms['COL 13'])) ? $this->lms['COL 13'] : date("Y-m-d",strtotime("last day of december"));
			$status			  = (empty($this->lms['COL 13'])) ? 2 : NULL;
            $this->lmsParams  = array(
                'membership_contact_id' => $this->lms['COL 2'],
                'join_date' => $startDatum,
                'membership_start_date' => $startDatum,
                'membership_end_date' => $eindDatum,
				'membership_type_id' => $this->lmsObject->tribBladGratis['id'],
				'status_id' => $status
            );
            //$this->setMST();
            if(!$this->skipMS) {
    			if(!$this->CIVIAPI->Membership->Create($this->lmsParams)) {
              echo "-> Registratie mislukt bij record: " . $this->lms['COL 1'] . "\r\n";
				print_r($this->lmsParams)."\r\n";
                 echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
                } else {
					echo "-> Registratie gelukt bij record: " . $this->lms['COL 1'] . "\r\n";
				}
            }
        }
		echo "Stop migratie \r\n";
    }

	public function setMST() {
        switch ($this->lms['COL 28']) {
            case "ABNAC":
            case "ABNIN":
            case "ABNPO":   if($this->lms['COL 11'] == "1") {
                                $this->lmsParams['membership_type_id'] = $this->lmsObject->tribBladBetaald['id'];
                            } else if ($this->lms['COL 11'] == "3") {
                                $this->lmsParams['membership_type_id'] = $this->lmsObject->tribAudioBetaald['id'];
                            } else if ($this->lms['COL 11'] == "4") {
							   $this->lmsParams['membership_type_id'] = $this->lmsObject->tribBladBetaald['id'];
							   $extraMembership = $this->lmsParams;
							   $extraMembership['membership_type_id'] = $this->lmsObject->tribAudioBetaald['id'];
									if($this->lms['COL 40'] > 0) {
										$_mandaatNummer = $this->registeerMandaten($this->lms['COL 2'], $this->lms['COL 40']);
										$this->lmsParams['custom_'.$this->mandaat_nr['id']] = $_mandaatNummer;
									}
								  if (!$this->CIVIAPI->Membership->Create($extraMembership)) {
									if((int)$this->lms['COL 21'] > 0) $this->registreerBetaling($this->CIVIAPI->lastResult->id, $this->lms['COL 2'], array($this->lms['COL 21']));
									  echo "-> Registratie mislukt bij record: " . $this->lms['COL 1'] . "\r\n";
									  echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
								  }
                            } else {
                                $this->skipMS = true;
                            } break;
            case "ABOGR":
            case "ABOKA":   if($this->lms['COL 11'] == "1") $this->lmsParams['membership_type_id'] = $this->lmsObject->tribBladGratis['id']; break;
            case "ABOPR":   if($this->lms['COL 11'] == "1") $this->lmsParams['membership_type_id'] = $this->lmsObject->tribBladProef['id']; break;
        }
    }

	private function registeerMandaten($contactID, $mandaatNummers) {
		$qMandaten = $this->dbAdapter->query("SELECT * FROM `mandaten` WHERE `COL 1` IN (".$mandaatNummers.")");
		if(!is_object($qMandaten)) return false;
		while($rMandaten = $qMandaten->fetch_assoc()) {
			$_mndDate = (!empty($rMandaten['COL 8']) && $rMandaten['COL 8'] != "-  -") ? $rMandaten['COL 10'] : NULL;
			$_expDate = (!empty($rMandaten['COL 10']) && $rMandaten['COL 10'] != "-  -") ? $rMandaten['COL 10'] : NULL;
			switch($rMandaten['COL 13']){
				case "NEW":  $_status = $this->optionValues->status_new['value']; break;
				case "FRST": $_status = $this->optionValues->status_frst['value']; break;
				case "RCUR": $_status = $this->optionValues->status_rcur['value']; break;
				case "OOFF": $_status = $this->optionValues->status_ooff['value']; break;
			}
			$_mandaatParams = array(
				'id' => $contactID,
				'custom_'.$this->customFields->status['id'] => $_status,
				'custom_'.$this->customFields->mandaat_nr['id'] => $rMandaten['COL 15'],
				'custom_'.$this->customFields->mandaat_datum['id'] => $_mndDate,
				'custom_'.$this->customFields->plaats['id'] => $rMandaten['COL 9'],
				'custom_'.$this->customFields->verval_datum['id'] => $_expDate,
				'custom_'.$this->customFields->IBAN['id'] => $rMandaten['COL 16'],
				'custom_'.$this->customFields->BIC['id'] => $rMandaten['COL 17']
			);
			if($this->CIVIAPI->Contact->Create($_mandaatParams)) {
				$this->debugString .= "-> Mandaat aangemaakt voor record: " . $rMandaten['COL 1'] . "\r\n";
				return $rMandaten['COL 15'];
			} else {
				$this->debugString .= "-> Aanmaken mandaat met mandaatnummer ".$rMandaten['COL 1']." is mislukt!\r\n";
				$this->debugString .= "=> ".$this->CIVIAPI->errorMsg()."\r\n";
			}
		}
	}

	private function registreerBetaling($lmsNummer, $contactID, $bedragen) {
		foreach($bedragen as $bedrag) {
			$amount = $bedrag / 4;
			try {
				$_contribution = civicrm_api3('Contribution', 'create', array(
				  'financial_type_id' => 2,
				  'total_amount' => $bedrag,
				  'contact_id' => $contactID
				));
				civicrm_api3('MembershipPayment', 'create', array(
				  'membership_id' => $lmsNummer,
				  'contribution_id' => $_contribution['id']
				));
				$this->debugString .= "-> Contributie en Payment succesvol vastgelegd \r\n";
			} catch (Exception $e){
				$this->debugString .= "-> Contributie en Payment aanmaken mislukt! \r\n";
				$this->debugString .= "-> ".$e."\r\n";
			}
		}
	}

}
new tribune(false, $argv);
?>
