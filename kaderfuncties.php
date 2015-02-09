<?php
include('./baseclass.php');
class kaderfuncties extends baseclass
{
	private  $resultSet, $geoResultSet, $relRow, $geoKopRow, $relParams, $contactTypes, $relationshipTypes, $contactA, $contactB, $skipRel = false;

	public function __construct($testMode = false, $arguments) {
		//echo "Start module: Kaderfuncties\r\n";
		parent::baseclass($testMode);
		$pointer = explode("=", $arguments[1]);
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM `his001` LIMIT ".$pointer[1].", 500");
		$this->fetchContactTypes();
		$this->createContacts();
		$this->fetchRelationshipTypes();
		$this->relaties();
		//echo "Einde module: Kaderfuncties\r\n";
   }

	private function fetchContactTypes () {
		$contactTypes 					= new stdClass();
		$contactTypes->Afdeling 	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Afdeling"));
		$contactTypes->Regio 		= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Regio"));
		$contactTypes->Provincie	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Provincie"));
		$contactTypes->Landelijk	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Landelijk"));
		$this->contactTypes 			= $contactTypes;
	}

	private function createContacts() {
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Afdeling['name'],
			'organization_name' => "SP-afdeling Onbekend"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Afdeling['name'],
				'organization_name' => "SP-afdeling Onbekend"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Regio['name'],
			'organization_name' => "SP-regio Onbekend"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Regio['name'],
				'organization_name' => "SP-regio Onbekend"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Provincie['name'],
			'organization_name' => "SP-provincie Onbekend"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Provincie['name'],
				'organization_name' => "SP-provincie Onbekend"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Landelijk['name'],
			'organization_name' => "Partijbureau SP"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "Partijbureau SP"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Landelijk['name'],
			'organization_name' => "ROOD"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "ROOD"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Landelijk['name'],
			'organization_name' => "Tweede Kamerfractie SP"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "Tweede Kamerfractie SP"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Landelijk['name'],
			'organization_name' => "Eerste Kamerfractie SP"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "Eerste Kamerfractie SP"
			));
		}
		if(!$this->CIVIAPI->Contact->getSingle(array(
			'contact_type' => 'organization',
			'contact_sub_type' => $this->contactTypes->Landelijk['name'],
			'organization_name' => "Europese Kamerfractie SP"
		))){
			$this->CIVIAPI->Contact->Create(array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "Europese Kamerfractie SP"
			));
		}
	}

	private function fetchRelationshipTypes () {
		$relationshipTypes = new stdClass();
		try { $relationshipTypes->voorzitter 											= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_voorzitter_afdeling','name_b_a' => 'sprel_afdeling_voorzitter')); } catch (Exception $e) { echo "Ophalen van relatietype: voorzitter mislukt! \r\n"; die($e); }
		try { $relationshipTypes->vervangend_voorzitter 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_vervangendvoorzitter_afdeling', 'name_b_a' => 'sprel_afdeling_vervangendvoorzitter', 'version' => 3)); } catch (Exception $e) { echo "Ophalen van relatietype: vervangend voorzitter mislukt! \r\n"; die($e); }
		try { $relationshipTypes->organisatiesecretaris 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_organisatiesecretaris_afdeling','name_b_a' => 'sprel_afdeling_organisatiesecretaris')); } catch (Exception $e) { echo "Ophalen van relatietype: organisatiesecretaris mislukt! \r\n"; die($e); }
		try { $relationshipTypes->penningmeester 										= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_penningmeester_afdeling','name_b_a' => 'sprel_afdeling_penningmeester')); } catch (Exception $e) { echo "Ophalen van relatietype: penningmeester mislukt! \r\n"; die($e); }
		try { $relationshipTypes->bestuurslid 											= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_bestuurslid_afdeling','name_b_a' => 'sprel_afdeling_bestuurslid')); } catch (Exception $e) { echo "Ophalen van relatietype: bestuurslid mislukt! \r\n"; die($e); }
		try { $relationshipTypes->kaderlid 												= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_kaderlid_afdeling','name_b_a' => 'sprel_afdeling_kaderlid')); } catch (Exception $e) { echo "Ophalen van relatietype: kaderlid mislukt! \r\n"; die($e); }
		try { $relationshipTypes->rood_contactpersoon 								= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_ROOD_Contactpersoon_afdeling','name_b_a' => 'sprel_afdeling_ROOD_Contactpersoon')); } catch (Exception $e) { echo "Ophalen van relatietype: rood_contactpersoon mislukt! \r\n"; die($e); }
		try { $relationshipTypes->scholingsverantwoordelijke 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_scholingsverantwoordelijke_afdeling','name_b_a' => 'sprel_afdeling_scholingsverantwoordelijke')); } catch (Exception $e) { echo "Ophalen van relatietype: scholingsverantwoordelijke mislukt! \r\n"; die($e); }
		try { $relationshipTypes->opnaartweehonderd 									= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_opnaartweehonderd_afdeling','name_b_a' => 'sprel_afdeling_opnaartweehonderd')); } catch (Exception $e) { echo "Ophalen van relatietype: opnaartweehonderd mislukt! \r\n"; die($e); }
		try { $relationshipTypes->webmaster 											= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_webmaster_afdeling','name_b_a' => 'sprel_afdeling_webmaster')); } catch (Exception $e) { echo "Ophalen van relatietype: webmaster mislukt! \r\n"; die($e); }
		try { $relationshipTypes->hulpedienstmedewerker 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_hulpdienstmedewerker_afdeling','name_b_a' => 'sprel_afdeling_hulpdienstmedewerker')); } catch (Exception $e) { echo "Ophalen van relatietype: hulpedienstmedewerker mislukt! \r\n"; die($e); }
		try { $relationshipTypes->vwledenadmin 										= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_verantwoordelijke_ledenadministratie_afdeling','name_b_a' => 'sprel_afdeling_verantwoordelijke_ledenadministratie')); } catch (Exception $e) { echo "Ophalen van relatietype: vwledenadmin mislukt! \r\n"; die($e); }
		try { $relationshipTypes->bestelpersoon_afdeling 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_bestelpersoon_afdeling','name_b_a' => 'sprel_afdeling_bestelpersoon')); } catch (Exception $e) { echo "Ophalen van relatietype: bestelpersoon_afdeling mislukt! \r\n"; die($e); }
		try { $relationshipTypes->bestelpersoon_provincie 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_bestelpersoon_provincie','name_b_a' => 'sprel_provincie_bestelpersoon')); } catch (Exception $e) { echo "Ophalen van relatietype: bestelpersoon_provincie mislukt! \r\n"; die($e); }
		try { $relationshipTypes->bestelpersoon_landelijk 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_bestelpersoon_landelijk','name_b_a' => 'sprel_landelijk_bestelpersoon')); } catch (Exception $e) { echo "Ophalen van relatietype: bestelpersoon_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->fractievoorzitter_afdeling 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_fractievoorzitter_afdeling','name_b_a' => 'sprel_afdeling_fractievoorzitter')); } catch (Exception $e) { echo "Ophalen van relatietype: fractievoorzitter_afdeling mislukt! \r\n"; die($e); }
		try { $relationshipTypes->fractievoorzitter_provincie						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_fractievoorzitter_provincie','name_b_a' => 'sprel_provincie_fractievoorzitter')); } catch (Exception $e) { echo "Ophalen van relatietype: fractievoorzitter_provincie mislukt! \r\n"; die($e); }
		try { $relationshipTypes->fractievoorzitter_landelijk						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_fractievoorzitter_landelijk','name_b_a' => 'sprel_landelijk_fractievoorzitter')); } catch (Exception $e) { echo "Ophalen van relatietype: fractievoorzitter_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->fractieraadslid_afdeling 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_fractieraadslid_afdeling','name_b_a' => 'sprel_afdeling_fractieraadslid')); } catch (Exception $e) { echo "Ophalen van relatietype: fractieraadslid_afdeling mislukt! \r\n"; die($e); }
		try { $relationshipTypes->deelraadslid_afdeling 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_deelraadslid_afdeling','name_b_a' => 'sprel_afdeling_deelraadslid')); } catch (Exception $e) { echo "Ophalen van relatietype: deelraadslid_afdeling mislukt! \r\n"; die($e); }
		try { $relationshipTypes->wethouder_afdeling 								= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_wethouder_afdeling','name_b_a' => 'sprel_afdeling_wethouder')); } catch (Exception $e) { echo "Ophalen van relatietype: wethouder_afdeling mislukt! \r\n"; die($e); }
		try { $relationshipTypes->statenlid_provincie 								= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_statenlid_provincie','name_b_a' => 'sprel_provincie_statenlid')); } catch (Exception $e) { echo "Ophalen van relatietype: statenlid_provincie mislukt! \r\n"; die($e); }
		try { $relationshipTypes->gedeputeerde_provincie 							= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_gedeputeerde_provincie','name_b_a' => 'sprel_provincie_gedeputeerde')); } catch (Exception $e) { echo "Ophalen van relatietype: gedeputeerde_provincie mislukt! \r\n"; die($e); }
		try { $relationshipTypes->tweede_kamerlid_landelijk 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_tweede_kamerlid_landelijk','name_b_a' => 'sprel_landelijk_tweede_kamerlid')); } catch (Exception $e) { echo "Ophalen van relatietype: tweede_kamerlid_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->eerste_kamerlid_landelijk 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_eerste_kamerlid_landelijk','name_b_a' => 'sprel_landelijk_eerste_kamerlid')); } catch (Exception $e) { echo "Ophalen van relatietype: eerste_kamerlid_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->europarlementarier_landelijk 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_europarlementarier_landelijk','name_b_a' => 'sprel_landelijk_europarlementarier')); } catch (Exception $e) { echo "Ophalen van relatietype: europarlementarier_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->partijbestuurslid_landelijk 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_partijbestuurslid_landelijk','name_b_a' => 'sprel_landelijk_partijbestuurslid')); } catch (Exception $e) { echo "Ophalen van relatietype: partijbestuurslid_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->liddagelijksbestuur_landelijk 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_liddagelijksbestuur_landelijk','name_b_a' => 'sprel_landelijk_liddagelijksbestuur')); } catch (Exception $e) { echo "Ophalen van relatietype: liddagelijksbestuur_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->regiobestuurder 									= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_regiobestuurder_landelijk','name_b_a' => 'sprel_landelijk_regiobestuurder')); } catch (Exception $e) { echo "Ophalen van relatietype: regiobestuurder mislukt! \r\n"; die($e); }
		try { $relationshipTypes->personeelslid_amersfoort_landelijk			= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_personeelslid_amersfoort_landelijk','name_b_a' => 'sprel_landelijk_personeelslid_amersfoort')); } catch (Exception $e) { echo "Ophalen van relatietype: personeelslid_amersfoort_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->personeelslid_denhaag_landelijk 				= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_personeelslid_denhaag_landelijk','name_b_a' => 'sprel_landelijk_personeelslid_denhaag')); } catch (Exception $e) { echo "Ophalen van relatietype: personeelslid_denhaag_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->personeelslid_brussel_landelijk 				= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_personeelslid_brussel_landelijk','name_b_a' => 'sprel_landelijk_personeelslid_brussel')); } catch (Exception $e) { echo "Ophalen van relatietype: personeelslid_brussel_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->lidberoepscomissie_landelijk 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_lidberoepscomissie_landelijk','name_b_a' => 'sprel_landelijk_lidberoepscomissie')); } catch (Exception $e) { echo "Ophalen van relatietype: lidberoepscomissie_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->lidfinancielecontrolecomissie_landelijk 		= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_lidfinancielecontrolecomissie_landelijk','name_b_a' => 'sprel_landelijk_lidfinancielecontrolecomissie')); } catch (Exception $e) { echo "Ophalen van relatietype: lidfinancielecontrolecomissie_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->lidvteam_landelijk 								= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_lidvteam_landelijk','name_b_a' => 'sprel_landelijk_lidvteam')); } catch (Exception $e) { echo "Ophalen van relatietype: lidvteam_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->bestuurslidrood_landelijk 						= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_bestuurslidrood_landelijk','name_b_a' => 'sprel_landelijk_bestuurslidrood')); } catch (Exception $e) { echo "Ophalen van relatietype: bestuurslidrood_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->actiefroodlandelijk_landelijk 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_actiefroodlandelijk_landelijk', 'name_b_a' => 'sprel_landelijk_actiefroodlandelijk')); } catch (Exception $e) { echo "Ophalen van relatietype: actiefroodlandelijk_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->gebiedscomissielid_afdeling 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_gebiedscomissielid_afd', 'name_b_a' => 'sprel_afd_gebiedscomissielid')); } catch (Exception $e) { echo "Ophalen van relatietype: actiefroodlandelijk_landelijk mislukt! \r\n"; die($e); }
		try { $relationshipTypes->gebiedscomissievoorzitter_afdeling 					= civicrm_api3("RelationshipType", "getsingle", array('name_a_b' => 'sprel_gebiedscomissievoorzitter_afd', 'name_b_a' => 'sprel_afd_gebiedscomissievoorzitter')); } catch (Exception $e) { echo "Ophalen van relatietype: actiefroodlandelijk_landelijk mislukt! \r\n"; die($e); }
		$this->relationshipTypes = $relationshipTypes;
	}

	private function relaties() {
		while ($this->relRow = $this->resultSet->fetch_assoc()) {
			$this->skipRel = false;
			try {
				$this->contactA = civicrm_api3("Contact", "getsingle", array("id" => $this->relRow['COL 1']));
			} catch (Exception $e) {
				echo "Contact ".$this->relRow['COL 1']." niet gevonden.\r\n";
				continue;
			}
			if(!$this->skipRel) {
				$this->geoResultSet = $this->dbAdapter->query("
					SELECT *
					FROM `geokoppl`
					WHERE `geokoppl`.`COL 5` = ".$this->relRow['COL 5']."
					AND (
						DATEDIFF(`geokoppl`.`COL 3`,`geokoppl`.`COL 2`) > 2
							OR
						DATEDIFF(`geokoppl`.`COL 3`,`geokoppl`.`COL 2`) < -2
					)
					GROUP BY `COL 1`
				");
				if($this->geoResultSet->num_rows > 0 && is_object($this->geoResultSet)) {
					while($this->geoKopRow = $this->geoResultSet->fetch_assoc()) {
						$newestLocationQuery = $this->dbAdapter->query("SELECT `COL 7`, `COL 9`, `COL 11`, `COL 134`, `COL 137` FROM `geo` WHERE `COL 2` = ".$this->geoKopRow['COL 4']." ORDER BY `COL 4` DESC LIMIT 1");
						if($newestLocationQuery->num_rows > 0 && is_object($newestLocationQuery)) {
							$mergeArray = $newestLocationQuery->fetch_assoc();
							$this->geoKopRow = array_merge($this->geoKopRow, $mergeArray);
						}
						$startDate = ($this->geoKopRow['COL 2'] != "2099-01-01") ? $this->geoKopRow['COL 2'] : NULL;
						if(!in_array($this->relRow['COL 7'], array("OPV", "TK", "EK", "EP", "OD", "DB", "OR", "P_R", "P_DH", "P_B", "BC", "CB", "Y", "FC", "VT", "RB", "ROOD", "F", "CB"))) {
							$endDate = ($this->geoKopRow['COL 3'] != "2099-01-01") ? $this->geoKopRow['COL 3'] : NULL;
						} else {
							$endDate = ($this->relRow['COL 3'] != "2099-01-01") ? $this->relRow['COL 3'] : NULL;
						}
						$this->relParams = array('contact_id_a' => $this->contactA['id'], 'start_date' => $startDate, 'end_date' => $endDate);
						$this->determineRelationshipType();
						if(!$this->skipRel) {
							$this->determineBContact(true);
							if(!$this->CIVIAPI->Relationship->Create($this->relParams)){
								echo "Relatie tussen ".$this->contactA['display_name']." en ".$this->contactB['display_name']." is mislukt\r\n";
								var_dump($this->relRow);
								echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
							}
						}
					}
				} else {
					$startDate = ($this->relRow['COL 2'] != "2099-01-01") ? $this->relRow['COL 2'] : NULL;
					$endDate = ($this->relRow['COL 3'] != "2099-01-01") ? $this->relRow['COL 3'] : NULL;
					$this->relParams = array('contact_id_a' => $this->contactA['id'], 'start_date' => $startDate, 'end_date' => $endDate);
					$this->determineRelationshipType();
					if(!$this->skipRel) {
						$this->determineBContact(false);
						if(!$this->CIVIAPI->Relationship->Create($this->relParams)){
							echo "Relatie tussen ".$this->contactA['display_name']." en ".$this->contactB['display_name']." is mislukt\r\n";
							echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
						}
					}
				}
			}
		}
	}

	private function determineRelationshipType() {
		switch($this->relRow['COL 7']) {
			case "V"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->voorzitter['id']; break;
			case "VV"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->vervangend_voorzitter['id']; break;
			case "OS"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->organisatiesecretaris['id']; break;
			case "PM"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->penningmeester['id']; break;
			case "B"		:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->bestuurslid['id']; break;
			case "AM"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->kaderlid['id']; break;
			case "JV"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->rood_contactpersoon['id']; break;
			case "LS"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->scholingsverantwoordelijke['id']; break;
			case "OPV"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->opnaartweehonderd['id']; break;
			case "WM"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->webmaster['id']; break;
			case "H"		:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->hulpedienstmedewerker['id']; break;
			case "MM"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->vwledenadmin['id']; break;
			case "BP"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->bestelpersoon_afdeling['id']; break;
			case "FMB"	: 	switch($this->geoKopRow['COL 137']) {
									case "35": $this->relParams['relationship_type_id'] = $this->relationshipTypes->bestelpersoon_provincie['id']; break;
									case "41": $this->relParams['relationship_type_id'] = $this->relationshipTypes->bestelpersoon_landelijk['id']; break;
									default:   $this->relParams['relationship_type_id'] = $this->relationshipTypes->bestelpersoon_landelijk['id']; break;
								} break;
			case "FG"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->fractievoorzitter_afdeling['id']; break;
			case "FP"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->fractievoorzitter_provincie['id']; break;
			case "F"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->fractievoorzitter_landelijk['id']; break;
			case "YR"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->fractieraadslid_afdeling['id']; break;
			case "YD"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->deelraadslid_afdeling['id']; break;
			case "WH"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->wethouder_afdeling['id']; break;
			case "S"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->statenlid_provincie['id']; break;
			case "GS"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->gedeputeerde_provincie['id']; break;
			case "TK"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->tweede_kamerlid_landelijk['id']; break;
			case "EK"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->eerste_kamerlid_landelijk['id']; break;
			case "EP"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->europarlementarier_landelijk['id']; break;
			case "OD"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->partijbestuurslid_landelijk['id']; break;
			case "DB"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->liddagelijksbestuur_landelijk['id']; break;
			case "OR"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->regiobestuurder['id']; break;
			case "P_R"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->personeelslid_amersfoort_landelijk['id']; break;
			case "P_DH"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->personeelslid_denhaag_landelijk['id']; break;
			case "P_B"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->personeelslid_brussel_landelijk['id']; break;
			case "BC"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->lidberoepscomissie_landelijk['id']; break;
			case "FC"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->lidfinancielecontrolecomissie_landelijk['id']; break;
			case "VT"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->lidvteam_landelijk['id']; break;
			case "RB"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->bestuurslidrood_landelijk['id']; break;
			case "ROOD"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->actiefroodlandelijk_landelijk['id']; break;
			case "GL"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->gebiedscomissielid_afdeling['id']; break;
			case "GV"	:	$this->relParams['relationship_type_id'] = $this->relationshipTypes->gebiedscomissievoorzitter_afdeling['id']; break;
			default		:	$this->skipRel = true;
		}
	}

	private function determineBContact($geoKnown) {
		if(in_array($this->relRow['COL 7'], array("F","TK","EK","EP","OD","DB","LS","P_R","P_DH","P_B","BC","FC","VT","RB","ROOD","OPV"))){
			if(in_array($this->relRow['COL 7'], array("P_R","DB","BC","FC","VT"))){
				$contactBParams = array("organization_name" => "Partijbureau SP");
			} else if(in_array($this->relRow['COL 7'], array("RB","ROOD"))){
				$contactBParams = array("organization_name" => "ROOD");
			} else if(in_array($this->relRow['COL 7'], array("TK","P_DH"))){
				$contactBParams = array("organization_name" => "Tweede Kamerfractie SP");
			} else if(in_array($this->relRow['COL 7'], array("EK","F"))){
				$contactBParams = array("organization_name" => "Eerste Kamerfractie SP");
			} else if(in_array($this->relRow['COL 7'], array("EP","P_B"))){
				$contactBParams = array("organization_name" => "Europese Kamerfractie SP");
			} else if(in_array($this->relRow['COL 7'], array("LS", "OD", "OPV"))) {
				$contactBParams = array("organization_name" => "SP Nederland");
			}
		} else {
			if($geoKnown) {
				if(in_array($this->relRow['COL 7'], array("V","VV","OS","PM","B","AM","JV","OPV","WM","H","MM","BP","GV","GL"))){
					$contactBParams = array("organization_name" => "SP-afdeling " . $this->geoKopRow['COL 134']);
				} else if(in_array($this->relRow['COL 7'], array("FG","YR","YD","WH"))){
					if($this->geoKopRow['COL 137'] == 38) {
						$contactBParams = array("organization_name" => "SP-afdeling " . $this->geoKopRow['COL 11']);
					} else {
						$contactBParams = array("organization_name" => "SP-afdeling " . $this->geoKopRow['COL 134']);
					}
				} else if(in_array($this->relRow['COL 7'], array("OR"))){
					$contactBParams = array("organization_name" => "SP-regio " . $this->geoKopRow['COL 9']);
				} else if(in_array($this->relRow['COL 7'], array("FP", "S", "GS", "FMB"))){
					$contactBParams = array("organization_name" => "SP-provincie " . $this->geoKopRow['COL 134']);
				}
			} else {
				if(in_array($this->relRow['COL 7'], array("V","VV","OS","PM","B","AM","JV","OPV","WM","H","MM","BP","FG","YR","YD","WH","GV","GL"))){
					$contactBParams = array("organization_name" => "SP-afdeling Onbekend");
				} else if(in_array($this->relRow['COL 7'], array("OR"))){
					$contactBParams = array("organization_name" => "SP-regio Onbekend");
				} else if(in_array($this->relRow['COL 7'], array("FP", "S", "GS"))){
					$contactBParams = array("organization_name" => "SP-provincie Onbekend");
				} else if(in_array($this->relRow['COL 7'], array("FMB"))) {
					$contactBParams = array("organization_name" => "Tweede Kamerfractie SP");
				}
			}
		}
		try {
			$this->contactB = civicrm_api3("Contact", "getsingle", $contactBParams);
			$this->relParams['contact_id_b'] = $this->contactB['contact_id'];
		} catch (Exception $e) {
			echo "Contact ".$contactBParams['organization_name']." niet gevonden.\r\n";
			echo $e."\r\n";
		}
	}

}

new kaderfuncties(false, $argv);
