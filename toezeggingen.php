<?php
include('./baseclass.php');
class toezeggingen extends baseclass {

	public $resultSet, $donatieRow, $donatieParams, $membershipType, $customFields, $optionValues, $financialTypes;

	public function __construct($testMode = false) {
		echo "Start module: Toezeggingen\r\n";
		parent::baseclass($testMode);
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `toezeggingen`");
		if(!$this->resultSet) die("Query mislukt, script gestopt.");
		$this->fetch_membership_type();
		$this->fetch_custom_fields();
		$this->fetch_financial_types();
		$this->migreer();
		echo "Einde module: Toezeggingen\r\n";
	}

	public function fetch_membership_type() {
		try { $this->membershipType = civicrm_api3('MembershipType','getsingle',array('name' => 'SP Donateur')); } catch (Exception $e) { die("Donateur membership type niet gevonden. Script gestopt."); }
	}

	public function fetch_custom_fields() {
		$this->customFields 									= new Stdclass;
		try { $this->customFields->group					= civicrm_api3('CustomGroup', 'getsingle', array("name" => "SEPA_Mandaat")); } catch (Exception $e) { die("CG SEPA_Mandaat niet gevonden"); }
		try { $this->customFields->status				= civicrm_api3('CustomField', 'getsingle', array("name" => "status", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF status niet gevonden"); }
		try { $this->customFields->mandaat_nr			= civicrm_api3('CustomField', 'getsingle', array("name" => "mandaat_nr", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF mandaat_nr niet gevonden"); }
		try { $this->customFields->mandaat_datum		= civicrm_api3('CustomField', 'getsingle', array("name" => "mandaat_datum", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF mandaat_datum niet gevonden"); }
		try { $this->customFields->plaats				= civicrm_api3('CustomField', 'getsingle', array("name" => "plaats", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF plaats niet gevonden"); }
		try { $this->customFields->verval_datum		= civicrm_api3('CustomField', 'getsingle', array("name" => "verval_datum", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF verval_datum niet gevonden"); }
		try { $this->customFields->IBAN					= civicrm_api3('CustomField', 'getsingle', array("name" => "IBAN", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF IBAN niet gevonden"); }
		try { $this->customFields->BIC					= civicrm_api3('CustomField', 'getsingle', array("name" => "BIC", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF BIC niet gevonden"); }
		try { $this->customFields->groupMembership	= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Membership_SEPA_Mandaat")); } catch (Exception $e) { die("CG SEPA_Mandaat niet gevonden"); }
		try { $this->customFields->member_mandaat_id	= civicrm_api3('CustomField', 'getsingle', array("name" => "mandaat_id", "custom_group_id" => $this->customFields->groupMembership['id'])); } catch (Exception $e) { die("CF mandaat_id niet gevonden"); }
		$this->optionValues 									= new Stdclass;
		try { $this->optionValues->status_group 		= civicrm_api3('OptionGroup', 'getsingle', array("name" => "sepa_mandaat_status")); } catch (Exception $e) { die("OG sepa_mandaat_status niet gevonden."); }
		try { $this->optionValues->status_new 			= civicrm_api3('OptionValue', 'getsingle', array("name" => "NEW", "option_group_id" => $this->optionValues->status_group['id'])); } catch (Exception $e) { die("OV NEW niet gevonden."); }
		try { $this->optionValues->status_frst 		= civicrm_api3('OptionValue', 'getsingle', array("name" => "FRST", "option_group_id" => $this->optionValues->status_group['id'])); } catch (Exception $e) { die("OV FRST niet gevonden."); }
		try { $this->optionValues->status_rcur 		= civicrm_api3('OptionValue', 'getsingle', array("name" => "RCUR", "option_group_id" => $this->optionValues->status_group['id'])); } catch (Exception $e) { die("OV RCUR niet gevonden."); }
		try { $this->optionValues->status_ooff 		= civicrm_api3('OptionValue', 'getsingle', array("name" => "OOFF", "option_group_id" => $this->optionValues->status_group['id'])); } catch (Exception $e) { die("OV OOFF niet gevonden."); }
		try { $this->optionValues->pid_ac	 			= civicrm_api3('OptionValue', 'getsingle', array("name" => "sp_acceptgiro", "option_group_id" => 10)); } catch (Exception $e) { die("PID AC niet gevonden."); }
		try { $this->optionValues->pid_po	 			= civicrm_api3('OptionValue', 'getsingle', array("name" => "Periodieke overboeking", "option_group_id" => 10)); } catch (Exception $e) { die("PID PO niet gevonden."); }
		try { $this->optionValues->pid_in	 			= civicrm_api3('OptionValue', 'getsingle', array("name" => "sp_automatischincasse", "option_group_id" => 10)); } catch (Exception $e) { die("PID IN niet gevonden."); }
	}

	public function fetch_financial_types() {
		$financialTypes 					= new stdClass();
		$financialTypes->donatie 		= civicrm_api3('financialType', 'getsingle', array("name" => "Donatie"));
		$this->financialTypes 			= $financialTypes;
	}

	public function migreer() {
		echo "Start migratie \r\n";
		while ($this->donatieRow = $this->resultSet->fetch_assoc()) {
			if($this->donatieRow['COL 3'] < 0) continue;
			$this->debugString = "Start verwerking voor record: ".$this->donatieRow['COL 1']."\r\n";
			if($this->donatieRow['COL 29'] > 0) $mandaatIdentifier = $this->generateMandaat();
			$_mandaatIdentifier = (isset($mandaatIdentifier)) ? $mandaatIdentifier : NULL;
			$_stopDate = ($this->donatieRow['COL 5'] != "-  -") ? $this->donatieRow['COL 5'] : date("Y-m-d 12:00:00", strtotime("last day of march"));
			$this->donatieParams = (array(
				'contact_id' => $this->donatieRow['COL 2'],
				'membership_type_id' => $this->membershipType['id'],
				'join_date' => $this->donatieRow['COL 4'],
				'start_date' => $this->donatieRow['COL 4'],
				'end_date' => $_stopDate,
				'custom_'.$this->customFields->member_mandaat_id['id']  => $_mandaatIdentifier
			));
			if(!$this->CIVIAPI->Membership->Create($this->donatieParams)){
				$this->debugString .= "-> Lidmaatschap Donateur aanmaken mislukt voor contact: ".$this->donatieRow['COL 2']."\r\n";
				$this->debugString = $this->CIVIAPI->errorMsg()."\r\n";
			} else {
				$this->debugString .= "-> Lidmaatschap Donateur aanmaken gelukt voor contact: ".$this->donatieRow['COL 2']."\r\n";
				$this->registreerBetaling($this->CIVIAPI->lastResult->id);
			}
			echo $this->debugString."\r\n\r\n";
		}
		echo "Einde migratie \r\n";
	}

	public function generateMandaat() {
		$_mandaten = $this->dbAdapter->query("SELECT * FROM `mandaten` WHERE `COL 1` = ".$this->donatieRow['COL 29']);
		while($_mandaat = $_mandaten->fetch_assoc()) {
			$_expDate = (!empty($_mandaat['COL 10']) && $_mandaat['COL 10'] != "-  -") ? $_mandaat['COL 10'] : NULL;
			switch($_mandaat['COL 13']){
				case "NEW": 	$_status = $this->optionValues->status_new['value']; break;
				case "FRST": 	$_status = $this->optionValues->status_frst['value']; break;
				case "RCUR": 	$_status = $this->optionValues->status_rcur['value']; break;
				case "OOFF": 	$_status = $this->optionValues->status_ooff['value']; break;
			}
			$_mandaatParams = array(
				'id' 																=> $this->donatieRow['COL 2'],
				'custom_'.$this->customFields->status['id'] 			=> $_status,
				'custom_'.$this->customFields->mandaat_nr['id'] 	=> $_mandaat['COL 15'],
				'custom_'.$this->customFields->mandaat_datum['id'] => $_mandaat['COL 8'],
				'custom_'.$this->customFields->plaats['id'] 			=> $_mandaat['COL 9'],
				'custom_'.$this->customFields->verval_datum['id'] 	=> $_expDate,
				'custom_'.$this->customFields->IBAN['id'] 			=> $_mandaat['COL 16'],
				'custom_'.$this->customFields->BIC['id'] 				=> $_mandaat['COL 17']
			);
			if($this->CIVIAPI->Contact->Create($_mandaatParams)) {
				$this->debugString .= "-> Aanmaken voor mandaatnummer ".$this->donatieRow['COL 29']." is gelukt\r\n";
				return $_mandaat['COL 15'];
			} else {
				$this->debugString .= "-> Aanmaken voor mandaatnummer ".$this->donatieRow['COL 29']." is mislukt\r\n";
				$this->debugString .= $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
	}

	private function registreerBetaling($lmsIdentifier) {
		try {
			switch($this->donatieRow['COL 7']) {
				case "AC": $_pid = $this->optionValues->pid_ac['value']; break;
				case "PO": $_pid = $this->optionValues->pid_po['value']; break;
				case "IN": $_pid = $this->optionValues->pid_in['value']; break;
				default  : $_pid = $this->optionValues->pid_ac['value']; break;
			}
			$_contribution = civicrm_api3('Contribution', 'create', array(
				'financial_type_id' 			=> $this->financialTypes->donatie['id'],
				'total_amount' 				=> $this->donatieRow['COL 3'],
				'contact_id' 					=> $this->donatieRow['COL 2'],
				'source' 						=> "Migratie Toezeggingen 2015",
				'receive_date' 				=> '2015-01-01 12:00:00',
				'payment_instrument_id' 	=> $_pid
			));
			civicrm_api3('MembershipPayment', 'create', array(
				'membership_id' 				=> $lmsIdentifier,
				'contribution_id' 			=> $_contribution['id']
			));
			$this->debugString .= "-> Contributie en Payment succesvol vastgelegd \r\n";
		} catch (Exception $e){
			$this->debugString .= "-> Contributie en Payment aanmaken mislukt! \r\n";
			$this->debugString .= "-> ".$e."\r\n";
		}
	}

}
new toezeggingen(false);
?>
