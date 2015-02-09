<?php
/*
UPDATE tomaatnet SET `COL 3` = DATE_FORMAT(STR_TO_DATE(`COL 3`,"%d-%m-%Y" ),"%Y-%m-%d" );
UPDATE tomaatnet SET `COL 4` = DATE_FORMAT(STR_TO_DATE(`COL 4`,"%d-%m-%Y" ),"%Y-%m-%d" );
*/

include('./baseclass.php');
class tomaatnet extends baseclass {

	public $resultSet, $eventParams, $eventRow, $EventTypeIdentifier, $eventIdentifier, $customFields;
	public $theatherDate = array("februari" => "2014-02-09", "mei" => "2014-05-18", "september" => "2014-09-21", "november" => "2014-11-16");

	public function __construct($testMode = false) {
		echo "Start module: Tomaatnet Festivals \r\n";
		parent::baseclass($testMode);
		$this->fetch_event_type_id();
		$this->fetch_custom_fields();
		$this->resultSet = $this->dbAdapter->query("
			SELECT `seg`.`COL 1` as `seg_id`, `seg`.`COL 2` as `event_name`, `tn`.`COL 3` as `event_date`
			FROM `tomaatnet` as `tn`
			LEFT JOIN `segmenten` as `seg` ON `tn`.`COL 22` = `seg`.`COL 1`
			WHERE `tn`.`COL 22` IN (80,81,82,83)
			GROUP BY `tn`.`COL 22`
			UNION
			SELECT '84' as `seg_id`, 'Theater De Moed Febuari 2014' as `event_name`, '2014-02-01' as `event_date`
			UNION
			SELECT '84' as `seg_id`, 'Theater De Moed Mei 2014' as `event_name`, '2014-05-18' as `event_date`
			UNION
			SELECT '84' as `seg_id`, 'Theater De Moed September 2014' as `event_name`, '2014-09-21' as `event_date`
			UNION
			SELECT '84' as `seg_id`, 'Theater De Moed November 2014' as `event_name`, '2014-11-16' as `event_date`
		");
		$this->migreer();
		echo "Einde module: Tomaatnet Festivals \r\n";
	}

	public function fetch_event_type_id() {
		try {
			$cursus_group_value = civicrm_api3('OptionValue', 'getsingle', array('option_group_id' => 14, 'name' => 'Meeting'));
			$this->EventTypeIdentifier = $cursus_group_value['value'];
			echo "Event_type ophalen succesvol \r\n";
		} catch (Exception $e) {
			echo "Event_type ophalen mislukt \r\n";
			die($e);
		}
	}

	public function fetch_custom_fields() {
		try{
			$this->customFields 							= new Stdclass;
			try { $this->customFields->group			= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Kaartverkoop")); } catch (Exception $e) { die("CG Kaartverkoop niet gevonden"); }
			try { $this->customFields->volwassene	= civicrm_api3('CustomField', 'getsingle', array("name" => "volwassene", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF volwassene niet gevonden"); }
			try { $this->customFields->kind			= civicrm_api3('CustomField', 'getsingle', array("name" => "kind", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF kind niet gevonden"); }
		} catch (Exception $e) {
			die($e);
		}
	}

	public function migreer() {
		echo "Start migratie \r\n";
		while ($this->eventRow = $this->resultSet->fetch_assoc()) {
			$this->eventParams = array(
				'title' 					=> $this->eventRow['event_name'],
				'start_date' 				=> $this->eventRow['event_date'],
				'event_type_id' 			=> $this->EventTypeIdentifier,
				'is_public'					=> 0
			);
			if($this->CIVIAPI->Event->Create($this->eventParams)) {
				$this->eventIdentifier = $this->CIVIAPI->lastResult->id;
				$this->registerParticipants();
			} else {
				echo "Bijeenkomst: ".$this->eventRow['event_name']." aanmaken mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
		echo "Einde migratie \r\n";
	}

	public function registerParticipants() {
		if(stristr($this->eventRow['event_name'], "Theater De Moed")) {
			switch(date("m", strtotime($this->eventRow['event_date']))) {
				case "02":  $participants = $this->dbAdapter->query("SELECT * FROM `tomaatnet` WHERE `COL 22` = ".$this->eventRow['seg_id']." AND `COL 3` BETWEEN '2014-01-01' AND '2014-02-31'"); break;
				case "05":  $participants = $this->dbAdapter->query("SELECT * FROM `tomaatnet` WHERE `COL 22` = ".$this->eventRow['seg_id']." AND `COL 3` BETWEEN '2014-03-01' AND '2014-05-31'"); break;
				case "09":  $participants = $this->dbAdapter->query("SELECT * FROM `tomaatnet` WHERE `COL 22` = ".$this->eventRow['seg_id']." AND `COL 3` BETWEEN '2014-06-01' AND '2014-09-31'"); break;
				case "11":  $participants = $this->dbAdapter->query("SELECT * FROM `tomaatnet` WHERE `COL 22` = ".$this->eventRow['seg_id']." AND `COL 3` BETWEEN '2014-10-01' AND '2014-11-31'"); break;
			}
		} else {
			$participants = $this->dbAdapter->query("SELECT * FROM `tomaatnet` WHERE `COL 22` = ".$this->eventRow['seg_id']);
		}
		while($participant = $participants->fetch_assoc()) {
			if(!$this->CIVIAPI->Participant->create(array(
				'participant_contact_id' => $participant['COL 2'],
				'event_id' => $this->eventIdentifier,
				'participant_register_date' => $participant['COL 3'],
				'participant_status_id' => 1,
				'participant_role_id' => 1,
				'custom_'.$this->customFields->volwassene['id'] => $participant['COL 17'],
				'custom_'.$this->customFields->kind['id'] => $participant['COL 18']
			))) {
				echo "Inschrijving ".$participant['COL 1']." mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
	}

}
new tomaatnet(false);
?>
