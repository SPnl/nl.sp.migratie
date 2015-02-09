<?php
include('./baseclass.php');
class tribune extends baseclass {

    public $resultSet, $lmsObject, $lms, $lmsParams, $skipMS;

    public function __construct($testMode = false, $arguments) {
    	echo "Start module: Tribune \r\n";
        parent::baseclass($testMode);
        $pointer = explode("=", $arguments[1]);
        $this->resultSet = $this->dbAdapter->query("
			SELECT *  FROM `lidmaatschappen` 
			WHERE `COL 11` IN (3,4)
		");
        $this->fetchLidmaatschappen();
        $this->migreer();
		echo "Stop module: Tribune \r\n";
    }

    public function fetchLidmaatschappen() {
        $objLMS = new stdClass();
		try { $objLMS->tribAudioGratis   = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Audio-Tribune Gratis")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Audio-Tribune Gratis niet gevonden \r\n"; }
		try { $objLMS->tribAudioBetaald   = civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Audio-Tribune Betaald")); } catch (Exception $e) { echo "Lidmaatschap-type Abonnee Audio-Tribune Betaald niet gevonden \r\n"; }
        $this->lmsObject = $objLMS;
    }

    public function migreer() {
    	echo "Start migratie \r\n";
        while ($this->lms = $this->resultSet->fetch_assoc()) {
			$membership_type_id = (stristr($this->lms['COL 28'], "ABN")) ? $this->lmsObject->tribAudioBetaald['id'] : $this->lmsObject->tribAudioGratis['id'];
			$startDatum       = ($this->lms['COL 12'] != '-  -') ? $this->lms['COL 12'] : NULL;
			$eindDatum        = (!empty($this->lms['COL 13'])) ? $this->lms['COL 13'] : date("Y-m-d",strtotime("last day of december"));
			$status			  = (empty($this->lms['COL 13'])) ? 2 : NULL;
            $this->lmsParams  = array(
                'membership_contact_id' => $this->lms['COL 2'],
                'join_date' => $startDatum,
                'membership_start_date' => $startDatum,
                'membership_end_date' => $eindDatum,
				'membership_type_id' => $membership_type_id,
				'status_id' => $status
            );
			if(!$this->CIVIAPI->Membership->Create($this->lmsParams)){
				echo $this->CIVIAPI->errorMsg();
			}
        }
		echo "Stop migratie \r\n";
    }

}
new tribune(false, $argv);
?>
