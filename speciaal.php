<?php
include('./baseclass.php');
class speciaal extends baseclass {

	public $resultSet, $lmsParams, $lms, $smt;

	public function __construct($testMode = false) {
		echo "Start module: SPeciaal \r\n";
		parent::baseclass($testMode);
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `speciaal`");
		$this->fetchLidmaatschapType();
		$this->migreer();
		echo "Stop module: SPeciaal \r\n";
	}
	
	public function fetchLidmaatschapType() {
		try {
			$this->smt = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee SPeciaal"));
			echo "Lidmaatschap-type succesvol opgehaald \r\n";
		} catch (Exception $e) {
			echo "Lidmaatschap-type niet gevonden \r\n";
			die($e);
		}
	}

	public function migreer() {
		echo "Start migratie \r\n";
		while ($this->lms = $this->resultSet->fetch_assoc()) {
			$eindDatum = ($this->lms['COL 9'] != "-  -") ? $this->lms['COL 9'] : date("Y-m-d",strtotime("last day of march"));
			$this->lmsParams = array(
				'membership_contact_id' => $this->lms['COL 2'],
				'join_date' 			=> $this->lms['COL 3'],
				'membership_start_date' => $this->lms['COL 3'],
				'membership_end_date' 	=> $eindDatum,
				'membership_type_id'	=> $this->smt['id']
			);
			if(!$this->CIVIAPI->Membership->Create($this->lmsParams)) {
				echo "SPeciaal Abonnee ".$this->lms['COL 2']." opslaan mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
		echo "Stop migratie \r\n";
	}

}
// Start
new speciaal;
?>