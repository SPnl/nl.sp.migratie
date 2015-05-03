<?php
include('./baseclass.php');
class spanning extends baseclass {

	public $resultSet, $lmsParams, $lms, $mst, $optionValues, $pointer;
	
	public function __construct($testMode = false, $arguments) {
		echo "Start module: SPanning \r\n";
		parent::baseclass($testMode);
		$_mode = explode("=", $arguments[2]);
		$this->pointer = explode("=", $arguments[1]);
		$this->fetchLidmaatschapType();
		//if($_mode[1] == "betaald") $this->spanningBetaald();
		if($_mode[1] == "gratis") $this->spanningGratis();
		echo "Stop module: SPanning \r\n";
	}

	public function fetchLidmaatschapType() {
		$this->mst = new stdClass;
		try {
			$this->mst->betaald = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee SPanning Betaald"));
		} catch (Exception $e) {
			echo "SPanning lidmaatschap-type betaald niet gevonden \r\n";
			die($e);
		}
        try {
            $this->mst->gratis = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee SPanning Gratis"));
        } catch (Exception $e) {
            echo "SPanning lidmaatschap-type gratis niet gevonden \r\n";
            die($e);
        }
	}

	public function spanningBetaald() {
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `spanning` ORDER BY `COL 4` DESC LIMIT ".$this->pointer[1].", 500");
		echo "=> Start SPanning-Betaald \r\n";
		while ($this->lms = $this->resultSet->fetch_assoc()) {
			echo "\r\n-> Start verwerking van record: ".$this->lms['COL 1']." \r\n";
			$startDatum = ($this->lms['COL 8'] != "-  -") ? $this->lms['COL 8'] : $this->lms['COL 3'];
			$eindDatum = ($this->lms['COL 9'] != "-  -") ? $this->lms['COL 9'] : NULL;
			$this->lmsParams = array(
				'membership_contact_id' => $this->lms['COL 2'],
				'join_date' 			=> $startDatum,
				'membership_start_date' => $startDatum,
				'membership_end_date' 	=> $eindDatum,
				'membership_type_id'	=> $this->mst->betaald['id']
			);
			if(is_null($eindDatum)) {
				$kwartaalDatum = new DateTime('last day of march');
				$this->lmsParams['membership_end_date'] = $kwartaalDatum->format('Y-m-d');
			}
			if(!$this->CIVIAPI->Membership->Create($this->lmsParams)) {
				echo "-> SPanning-Betaald Abonnee ".$this->lms['COL 2']." opslaan mislukt!\r\n";
				echo "-> ".$this->CIVIAPI->errorMsg()."\r\n";
			} else {
				echo "-> SPanning-Betaald Abonnee ".$this->lms['COL 2']." opslaan gelukt!\r\n";
				$this->registreerBetaling($this->CIVIAPI->lastResult->id);
			}
		}
		echo "\r\n=> Stop SPanning-Betaald \r\n";
	}
	
	private function registreerBetaling($lmsNummer) {
		try {
			$amount = $this->lms['COL 10'] / 4;
			$_contribution = civicrm_api3('Contribution', 'create', array(
			  'financial_type_id' => 2,
			  'total_amount' => $amount,
			  'contact_id' => $this->lms['COL 2'],
			  'source' => "Migratie SPanning 2014",
			  'receive_date' => '2014-01-01 12:00:00'
			));
			civicrm_api3('MembershipPayment', 'create', array(
			  'membership_id' => $lmsNummer,
			  'contribution_id' => $_contribution['id']
			));
			echo "-> Aanmaken contributie ".$this->lms['COL 1']." is gelukt!\r\n";
		} catch (Exception $e){
			echo "-> Aanmaken contributie ".$this->lms['COL 1']." is mislukt!\r\n";
			echo "-> ".$e."\r\n";
		}	
	}

	public function spanningGratis() {
	    echo "\r\n=> Start SPanning-gratis \r\n";
	    $this->resultSet = $this->dbAdapter->query("
			SELECT `his001`.*, `contacten`.`COL_13` FROM `his001` 
			LEFT JOIN `contacten` ON `his001`.`COL 1` = `contacten`.`COL_1` 
			WHERE `COL 3` = '2099-01-01' 
			AND `COL 7` IN ('B','OS','V','AM','TK','EK','S','GS','EP','WH','YR','GL','OR','OD','VV','WN','RB','H','P_B','P_DH','P_R','VT','JV','OPV')
			AND `COL_13` = 0 
			GROUP BY `COL 1`
			LIMIT ".$this->pointer[1].", 500
		");
        while ($this->lms = $this->resultSet->fetch_assoc()) {
            $startDatum = ($this->lms['COL 2'] != "2099-01-01") ? $this->lms['COL 2'] : NULL;
            $eindDatum = ($this->lms['COL 3'] != "2099-01-01") ? $this->lms['COL 3'] : NULL;
            $this->lmsParams = array(
                'membership_contact_id' => $this->lms['COL 1'],
                'join_date'             => $startDatum,
                'membership_start_date' => $startDatum,
                'membership_type_id'    => $this->mst->gratis['id'],
				'source'				=> "Kaderfunctie: ".$this->lms['COL 7']
            );
			$kwartaalDatum = new DateTime('last day of december');
			$this->lmsParams['membership_end_date'] = $kwartaalDatum->format('Y-m-d');
            if(!$this->CIVIAPI->Membership->Create($this->lmsParams)) {
                echo "SPanning-Gratis Abonnee ".$this->lms['COL 1']." opslaan mislukt!\r\n";
                echo $this->CIVIAPI->errorMsg()."\r\n";
            } else {
				echo "SPanning-Gratis Abonnee ".$this->lms['COL 1']." opslaan gelukt!\r\n";
			}
        }
	    echo "\r\n=> Stop SPanning-gratis \r\n";
	}

}
new spanning(false, $argv);
?>