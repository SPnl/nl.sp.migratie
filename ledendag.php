<?php
include('./baseclass.php');
class ledendag extends baseclass {

	public $resultSet, $eventParams, $eventRow, $EventTypeIdentifier, $eventIdentifier, $missingCounter;
	
	public $eventDate = array("2010" => "2010-11-20", "2011" => "2011-11-19", "2012" => "2012-11-10", "2013" => "2013-11-16", "2014" => "2014-11-08");
	
	public function __construct($testMode = false) {
		echo "Start module: Nieuwe Ledendagen \r\n";
		parent::baseclass($testMode);
		$this->missingCounter = 0;
		$this->fetch_event_type_id();
		$this->resultSet = $this->dbAdapter->query("SELECT DISTINCT YEAR(`COL_3`) as `jaar` FROM ledendag");
		$this->migreer();
		echo "Einde module: Nieuwe Ledendagen \r\n";
	}
	
	public function fetch_event_type_id() {
		try {
			$cursus_group_value = civicrm_api3('OptionValue', 'getsingle', array('option_group_id' => 14, 'name' => 'Nieuwe Ledendag'));
			$this->EventTypeIdentifier = $cursus_group_value['value'];
			echo "Event_type ophalen succesvol \r\n";
		} catch (Exception $e) {
			echo "Event_type ophalen mislukt \r\n";
			die($e);
		}
	}
	
	public function migreer() {
		echo "Start migratie \r\n";
		while ($this->eventRow = $this->resultSet->fetch_assoc()) {
			$this->eventParams = array(
				'title' 					=> "Nieuwe Ledendag ".$this->eventRow['jaar'],
				'start_date' 				=> $this->eventDate[$this->eventRow['jaar']],
				'event_type_id' 			=> $this->EventTypeIdentifier,
				'is_public'					=> 0
			);
			if($this->CIVIAPI->Event->Create($this->eventParams)) {
				$this->eventIdentifier = $this->CIVIAPI->lastResult->id;
				$this->registerParticipants();
			} else {
				echo "Regioconferentie: "."Nieuw Ledendag ".$this->eventRow['jaar']." aanmaken mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
		echo "Einde migratie \r\n";
	}
	
	public function registerParticipants() {
		$participants = $this->dbAdapter->query("SELECT * FROM `ledendag` WHERE YEAR(`COL_3`) = ".$this->eventRow['jaar']);
		while($participant = $participants->fetch_assoc()) {
			$status_id = ($participant['COL_10'] == "T") ? 2 : 3;
			if(!$this->CIVIAPI->Participant->create(array(
				'participant_contact_id' => $participant['COL_2'],
				'event_id' => $this->eventIdentifier,
				'participant_register_date' => $participant['COL_3'],
				'participant_status_id' => $status_id,
				'participant_role_id' => 1
			))) {
				echo "Inschrijving ".$participant['COL_1']." mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
				$this->missingCounter++;
			}
		}
	}
	
}
new ledendag(false);
?>