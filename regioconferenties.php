<?php
include('./baseclass.php');
class regioconferenties extends baseclass {

	public $resultSet, $eventParams, $eventRow, $EventTypeIdentifier, $eventIdentifier;

	public function __construct($testMode = false) {
		echo "Start module: Regioconferenties \r\n";
		parent::baseclass($testMode);
		$this->fetch_event_type_id();
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `regioconferenties`");
		$this->migreer();
		echo "Einde module: Regioconferenties \r\n";
	}
	
	public function fetch_event_type_id() {
		try {
			$cursus_group_value = civicrm_api3('OptionValue', 'getsingle', array('option_group_id' => 14, 'name' => 'Regioconferentie'));
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
				'title' 					=> $this->eventRow['COL 2'],
				'start_date' 				=> date("d-m-Y",$this->eventRow['COL 3']),
				'event_type_id' 			=> $this->EventTypeIdentifier,
				'is_public'					=> 0
			);
			if($this->CIVIAPI->Event->Create($this->eventParams)) {
				$this->eventIdentifier = $this->CIVIAPI->lastResult->id;
				$this->registerParticipants();
			} else {
				echo "Regioconferentie: ".$this->eventRow['COL 2']." aanmaken mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
		echo "Einde migratie \r\n";
	}
	
	public function registerParticipants() {
		$participants = $this->dbAdapter->query("SELECT * FROM `regioconferentie_deelnemers` WHERE `COL 2` = '".$this->eventRow['COL 1']."'");
		while($participant = $participants->fetch_assoc()) {
			if(!$this->CIVIAPI->Participant->create(array(
				'participant_contact_id' => $participant['COL 4'],
				'event_id' => $this->eventIdentifier,
				'participant_register_date' => date("d-m-Y",$this->eventRow['COL 3']),
				'participant_status_id' => 1,
				'participant_role_id' => 1,
				'participant_note' => ucfirst($participant['COL 5'])
			))) {
				echo "Inschrijving ".$participant['COL 1']." mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
	}
	
}
new regioconferenties(false);
?>