<?php
include ('./baseclass.php');
class documenthistorie extends baseclass {

	public $resultSet, $activityParams, $activity, $activityType;

	public function __construct($testMode = false, $arguments) {
		echo "Start module: Documenthistorie\r\n";
		parent::baseclass();
		$pointer = explode("=", $arguments[1]);
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `documenthistorie` LIMIT ".$pointer[1].", 2500");
		$this->fetchActivityType();
		$this->migreer();
		echo "Eind module: Documenthistorie\r\n";
	}
	
	public function fetchActivityType() {
		try {
			$this->activityType = civicrm_api3('OptionValue', 'getsingle', array("label" => "Documenthistorie", "option_group_id" => 2));
		} catch (Exception $e) {
			echo "Documenthistorie type kan niet worden gevonden \r\n";
			die($e);
		}
		echo "Activity-types opgehaald \r\n";
	}

	public function migreer() {
		echo "Start Migratie \r\n";
		while ($this->activity = $this->resultSet->fetch_assoc()) {
			$this->activityParams = array(
				'activity_status_id' => 2,
				'activity_subject' => $this->activity['COL 6'],
				'activity_date_time' => $this->activity['COL 4'],
				'activity_type_id' => $this->activityType['value'],
				'target_contact_id' => $this->activity['COL 2']
			);
			$this->setVerwerker();
			if (!$this->CIVIAPI->Activity->Create($this->activityParams)) {
				echo "Documenthistorie activiteit voor contact " . $this->activityParams['target_contact_id'] . " is gefaald.\r\n";
				echo $this->CIVIAPI->errorMsg() . "\r\n";
			}
		}
		echo "Einde Migratie \r\n";
	}
	
	public function setVerwerker() {
		switch($this->activity['COL 13']) {
			case "ARIE": 		$this->activityParams['source_contact_id'] 	= 56443; 	break;
			case "BRIGITTE": 	$this->activityParams['source_contact_id'] 	= 765471; 	break;
			case "EMULLER": 	$this->activityParams['source_contact_id'] 	= 791490; 	break;
			case "INGRID": 		$this->activityParams['source_contact_id'] 	= 707848; 	break;
			case "JOLANDA": 	$this->activityParams['source_contact_id'] 	= 103063; 	break;
			case "KEVIN": 		$this->activityParams['source_contact_id'] 	= 462535; 	break;
			case "MARGAB": 		$this->activityParams['source_contact_id'] 	= 34252; 	break;
			case "MATHIJS": 	$this->activityParams['source_contact_id'] 	= 37436; 	break;
			case "OANE": 		$this->activityParams['source_contact_id'] 	= 762754; 	break;
			case "PERRY": 		$this->activityParams['source_contact_id'] 	= 772402; 	break;
			case "PETER": 		$this->activityParams['source_contact_id'] 	= 796057; 	break;
			case "THOMAS": 		$this->activityParams['source_contact_id'] 	= 688436; 	break;
			case "TONY": 		$this->activityParams['source_contact_id'] 	= 760464; 	break;
			case "CORA": 		$this->activityParams['source_contact_id'] 	= 416126; 	break;
			case "CORRIE": 		$this->activityParams['source_contact_id'] 	= 756457; 	break;
			case "EPOSTMA": 	$this->activityParams['source_contact_id'] 	= 737356; 	break;
			case "GAZJBIN": 	$this->activityParams['source_contact_id'] 	= 789703; 	break;
			case "MARGAD": 		$this->activityParams['source_contact_id'] 	= 61587; 	break;
			case "SYLVANA": 	$this->activityParams['source_contact_id'] 	= 539562; 	break;
			case "TIM": 		$this->activityParams['source_contact_id']	= 835; 		break;
			case "WIM": 		$this->activityParams['source_contact_id'] 	= 46352; 	break;
			default:			$this->activityParams['source_contact_id'] 	= 1; 		break;
		}
	}

}

// Start
new documenthistorie(false, $argv);
?>