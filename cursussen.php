<?php
include('./baseclass.php');
class cursussen extends baseclass {

	public $resultSet, $cParams, $cRow, $cEventTypeIdentifier;

	public function __construct($testMode = false) {
		echo "Start module: Cursussen \r\n";
		parent::baseclass($testMode);
		$this->event_type_id();
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `cursussen`");
		//$this->migreer();
		$this->registerParticipants();
		echo "Eind module: Cursussen \r\n";
	}
	
	public function event_type_id() {
		try {
			$cursus_group_value = civicrm_api3('OptionValue', 'getsingle', array('option_group_id' => 14, 'name' => 'cursus'));
			$this->cEventTypeIdentifier = $cursus_group_value['value'];
			echo "Event_types ophalen succesvol \r\n";
		} catch (Exception $e) {
			echo "Event_types ophalen mislukt \r\n";
			die($e);
		}
	}
	
	public function migreer() {
		echo "Start migratie \r\n";
		while ($this->cRow = $this->resultSet->fetch_assoc()) {
			$this->cParams = array(
				'title' 					=> $this->cRow['COL 2'],
				'start_date' 				=> $this->cRow['COL 3'],
				'event_type_id' 			=> $this->cEventTypeIdentifier,
				'is_public'					=> 0
			);
			if($this->isPaidEvent()) $this->setEventPaymentParams();
			if($this->CIVIAPI->Event->Create($this->cParams)) {
				$this->registerParticipants();
			} else {
				echo "Cursus: ".$this->cRow['COL 1']." aanmaken mislukt! \r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
		echo "Einde migratie \r\n";
	}
	
	public function isPaidEvent() {
		$checkPayments = $this->dbAdapter->query("SELECT * FROM `cursus_deelname` WHERE `COL 9` = '".$this->cRow['COL 1']."' AND `COL 12` > 0");
		if(is_object($checkPayments) && $checkPayments->num_rows > 0) return true;
	}
	
	public function setEventPaymentParams() {
		$this->cParams['financial_type_id'] = 4;
		$this->cParams['is_monetary'] = 1;
		$this->cParams['fee_label'] = "Cursusgeld";
	}
	
	public function registerParticipants() {
		//$event_identifier = $this->CIVIAPI->lastResult->id;
		//$participants = $this->dbAdapter->query("SELECT * FROM `cursus_deelname` WHERE `COL 9` = '".$this->cRow['COL 1']."'");
		$participants = $this->dbAdapter->query("SELECT * FROM `cursus_deelname` WHERE `COL 1` = '19115'");
		while($participant = $participants->fetch_assoc()) {
			if($participant['COL 2'] > 0) {
				$register_date = ($participant['COL 7'] != "-  -\r\n") ? $participant['COL 7'] : $start_date;
				if(!$civiParticipant = $this->CIVIAPI->Participant->create(array(
					'participant_contact_id' => $participant['COL 2'],
					'event_id' => 620,
					'participant_register_date' => $register_date,
					'participant_status_id' => $this->presentation($participant['COL 8']),
					'participant_fee_amount' => round($participant['COL 12']),
					'participant_role_id' => 1,
					'role_id' => 1					
				))) {
					echo "MISLUKT BIJ ".$participant['COL 2']."!\r\n";
					var_dump($participant)."\r\n";
					var_dump($this->CIVIAPI->errorMsg());
				} else {
					//if($this->isPaidEvent()) $this->registerParticipantPayment($participant, $civiParticipant['id']);
				}
			}
		}
	}
	
	public function registerParticipantPayment($participant, $participant_id) {
		$receive_date = ($participant['COL 7'] != "-  -\r\n") ? $participant['COL 7'] : $this->cRow['COL 3'];
		if($this->CIVIAPI->Contribution->Create(array(
			'contact_id' => $participant['COL 2'],
			'total_amount' => round($participant['COL 12']),
			'fee_amount' => round($participant['COL 12']),
			'financial_type_id' => 4,
			'currency' => 'EUR',
			'source' => 'Cursusgeld voor: '.$this->cRow['COL 2'],
			'payment_instrument' => 3,
			'contribution_payment_instrument' => 3,
			'receive_date' => $receive_date
		))) {
			if($this->CIVIAPI->ParticipantPayment->Create(array(
				'participant_id' => $participant_id,
				'contribution_id' => $this->CIVIAPI->lastResult->id
			)));
		}
	}
	
	public function presentation($code) {
		switch($code) {
			case "AAN": return 2; break;
			case "AMK": return 4; break;
			case "AZK": return 3; break;
			case "UIT": return 4; break;
		}
	}
	
}
new cursussen(false);
?>