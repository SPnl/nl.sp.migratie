<?php
include('./baseclass.php');
class bezorggebieden extends baseclass {

	public $resultSet, $bezorgRow, $bezorgParams, $customFields, $afdelingen;

	public function __construct($testMode = false) {
		echo "Start module: Bezorggebieden\r\n";
		parent::baseclass($testMode);
		$this->resultSet = $this->dbAdapter->query("SELECT `id`, `COL 13`, `COL 15`, `COL 17`, `COL 18`, `COL 19` FROM `bezorggebieden` GROUP BY `COL 15`, `COL 17` ORDER BY `COL 15`");
		if(!$this->resultSet) die("Query is mislukt");
		$this->fetch_custom_fields();
		$this->fetch_afdelingen();
		$this->migreerPerBezorger();
		$this->migreerPerAfdeling();
		echo "Einde module: Bezorggebieden\r\n";
	}
	
	public function fetch_custom_fields() {
		$this->customFields = new stdClass;
		try { $this->customFields->group			= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Bezorggebieden")); } catch (Exception $e) { die("CG Migratie_Contacten niet gevonden"); }
		try { $this->customFields->naam				= civicrm_api3('CustomField', 'getsingle', array("name" => "Bezorggebied_naam", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Bezorggebied_naam niet gevonden"); }
		try { $this->customFields->start_cijfer		= civicrm_api3('CustomField', 'getsingle', array("name" => "Start_cijfer_range", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Start_cijfer_range niet gevonden"); }
		try { $this->customFields->eind_cijfer		= civicrm_api3('CustomField', 'getsingle', array("name" => "Eind_cijfer_range", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Eind_cijfer_range niet gevonden"); }
		try { $this->customFields->start_letter		= civicrm_api3('CustomField', 'getsingle', array("name" => "Start_letter_range", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Start_letter_range niet gevonden"); }
		try { $this->customFields->eind_letter		= civicrm_api3('CustomField', 'getsingle', array("name" => "Eind_letter_range", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Eind_letter_range niet gevonden"); }
		try { $this->customFields->bezorging_per	= civicrm_api3('CustomField', 'getsingle', array("name" => "Bezorging_per", "custom_group_id" => $this->customFields->group['id'])); } catch (Exception $e) { die("CF Per_Post niet gevonden"); }
	}
	
	public function fetch_afdelingen() {
		$this->afdelingen = new stdClass;
		try { $this->afdelingen->{3001} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Amsterdam", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 3001 niet gevonden"); }
		try { $this->afdelingen->{5057} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Rotterdam", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 5057 niet gevonden"); }
		try { $this->afdelingen->{6066} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Utrecht", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 6066 niet gevonden"); }
		try { $this->afdelingen->{9052} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Oss", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 9052 niet gevonden"); }
		try { $this->afdelingen->{10020} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Eindhoven", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 10020 niet gevonden"); }
		try { $this->afdelingen->{10033} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Helmond", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 10033 niet gevonden"); }
		try { $this->afdelingen->{11031} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Heerlen", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 11031 niet gevonden"); }
		try { $this->afdelingen->{12050} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Nijmegen", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 12050 niet gevonden"); }
		try { $this->afdelingen->{13087} = civicrm_api3('Contact','getsingle',array("organization_name" => "SP-Afdeling Zwolle", "contact_type" => "organization", "contact_sub_type" => "SP_Afdeling")); } catch (Exception $e) { die("Afdeling 13087 niet gevonden"); }
	}
	
	public function migreerPerBezorger() {
		echo "Start migratie per bezorger\r\n";
		while ($this->bezorgRow = $this->resultSet->fetch_assoc()) {
			if(!empty($this->bezorgRow['COL 13']) && !empty($this->bezorgRow['COL 15']) && !empty($this->bezorgRow['COL 17']) && !empty($this->bezorgRow['COL 18'])) {
				try { $sp_afdeling = civicrm_api3('Contact','getsingle',array('contact_type' => 'Organization', 'contact_sub_type' => 'SP_Afdeling', 'organization_name' => 'SP-Afdeling '.$this->bezorgRow['COL 13'])); } catch (Exception $e) { echo "SP-Afdeling ".$this->bezorgRow['COL 13']." niet gevonden\r\n"; }
				if(isset($sp_afdeling['id'])) {
					$_startCijferRange = substr($this->bezorgRow['COL 15'], 0, 4);
					$_eindCijferRange = substr($this->bezorgRow['COL 17'], 0, 4);
					$_startLetterRange = strtoupper(substr(str_replace(" ", "", $this->bezorgRow['COL 15']), -2, 2));
					$_eindLetterRange = strtoupper(substr(str_replace(" ", "", $this->bezorgRow['COL 17']), -2, 2));
					$this->bezorgParams = array(
						'id' => $sp_afdeling['id'],
						'custom_'.$this->customFields->naam['id'] => $this->bezorgRow['COL 18'],
						'custom_'.$this->customFields->start_cijfer['id'] => $_startCijferRange,
						'custom_'.$this->customFields->eind_cijfer['id'] => $_eindCijferRange,
						'custom_'.$this->customFields->start_letter['id'] => $_startLetterRange,
						'custom_'.$this->customFields->eind_letter['id'] => $_eindLetterRange,
						'custom_'.$this->customFields->bezorging_per['id'] => 'Bezorger'
					);
					if(!$this->CIVIAPI->Contact->Create($this->bezorgParams)){
						echo "Bezorggebieden aanmaken voor SP-Afdeling ".$this->bezorgRow['COL 13']." is mislukt!\r\n";
						echo $this->CIVIAPI->errorMsg()."\r\n";
					}
				}
			}
		}
		echo "Einde migratie per bezorger\r\n";
	}
	
	public function migreerPerAfdeling() {
		echo "Start migratie per afdeling\r\n";
		$this->resultSet = $this->dbAdapter->query("SELECT `COL 1`, `COL 6`, SUBSTRING(`COL 8`, 1, 4) as `start_range`, SUBSTRING(`COL 9`, 1, 4) as `end_range` FROM `afdelingsnummers` ORDER BY `COL 6`");
		while ($this->bezorgRow = $this->resultSet->fetch_assoc()) {
			$this->bezorgParams = array(
				'id' => $this->afdelingen->{$this->bezorgRow['COL 6']}['id'],
				'custom_'.$this->customFields->naam['id'] => $this->bezorgRow['COL 6']."-".$this->bezorgRow['start_range']."-".$this->bezorgRow['end_range'],
				'custom_'.$this->customFields->start_cijfer['id'] => $this->bezorgRow['start_range'],
				'custom_'.$this->customFields->eind_cijfer['id'] => $this->bezorgRow['end_range'],
				'custom_'.$this->customFields->start_letter['id'] => "AA",
				'custom_'.$this->customFields->eind_letter['id'] => "ZZ",
				'custom_'.$this->customFields->bezorging_per['id'] => 'Afdeling'
			);
			if(!$this->CIVIAPI->Contact->Create($this->bezorgParams)){
				echo "Bezorggebieden aanmaken voor SP-Afdeling ".$this->bezorgRow['COL 6']." is mislukt!\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}	
		echo "Einde migratie per afdeling\r\n";
	}
	
}
new bezorggebieden(false);
?>