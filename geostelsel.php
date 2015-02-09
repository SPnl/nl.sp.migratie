<?php
include ('./baseclass.php');
class geostelsel extends baseclass {

	public  $resultSet, $geoRow, $geoParams, $geoRegios = array(), $geoProvincies = array(), $contactTypes, $relationshipTypes, $relParams, $doubles = array(), $tags;

	public function __construct($testMode = false) {
		echo "Start module: Geostelsel\r\n";
        parent::baseclass($testMode);
		$this->resultSet = $this->dbAdapter->query("select `geo`.`COL 1` AS `COL 1`,`geo`.`COL 2` AS `COL 2`,`geo`.`COL 3` AS `COL 3`,`geo`.`COL 4` AS `COL 4`,`geo`.`COL 5` AS `COL 5`,`geo`.`COL 6` AS `COL 6`,`geo`.`COL 7` AS `COL 7`,`geo`.`COL 8` AS `COL 8`,`geo`.`COL 9` AS `COL 9`,`geo`.`COL 10` AS `COL 10`,`geo`.`COL 11` AS `COL 11`,`geo`.`COL 134` AS `COL 134`,`geo`.`COL 135` AS `COL 135`,`geo`.`COL 136` AS `COL 136`,`geo`.`COL 137` AS `COL 137`,`geo`.`COL 138` AS `COL 138`,`geo`.`COL 139` AS `COL 139`,`geo`.`COL 140` AS `COL 140`,`geo`.`COL 141` AS `COL 141`,`geo`.`COL 142` AS `COL 142`,`geo`.`COL 143` AS `COL 143` from `geo` where (`geo`.`COL 137` = 37) order by `col 134`, `col 4` desc");
		if(!$this->resultSet) die($this->dbAdapter->error);
		$this->fetchContactTypes();
		$this->fetchRelationshipTypes();
		$this->fetchTags();
        $this->migreerARP();
		$this->migreerLandelijk();
		$this->relatiesARP();
		$this->relatieLandelijk();
		echo "Einde module: Geostelsel\r\n";
    }

	public function fetchContactTypes () {
		try {
			$contactTypes = new stdClass();
			$contactTypes->Fractie 		= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Fractie"));
			$contactTypes->Afdeling 	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Afdeling"));
			$contactTypes->Regio 		= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Regio"));
			$contactTypes->Provincie	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Provincie"));
			$contactTypes->Landelijk	= civicrm_api3("ContactType", "getsingle", array("name" => "SP_Landelijk"));
			$this->contactTypes = $contactTypes;
		} catch (Exception $e) {
			die("Een of meerdere contact-types ontbreken");
		}
	}

	public function fetchRelationshipTypes () {
		try {
			$relationshipTypes = new stdClass();
			$relationshipTypes->FractieAfdeling		= civicrm_api3("RelationshipType", "getsingle", array("name_a_b" => "sprel_fractie_afdeling", "name_b_a" => "sprel_afdeling_fractie"));
			$relationshipTypes->AfdelingRegio 		= civicrm_api3("RelationshipType", "getsingle", array("name_a_b" => "sprel_afdeling_regio", "name_b_a" => "sprel_regio_afdeling"));
			$relationshipTypes->AfdelingioRegio 	= civicrm_api3("RelationshipType", "getsingle", array("name_a_b" => "sprel_afdelingio_regio", "name_b_a" => "sprel_regio_afdelingio"));
			$relationshipTypes->RegioProvincie 		= civicrm_api3("RelationshipType", "getsingle", array("name_a_b" => "sprel_regio_provincie", "name_b_a" => "sprel_provincie_regio"));
			$relationshipTypes->ProvincieLandelijk	= civicrm_api3("RelationshipType", "getsingle", array("name_a_b" => "sprel_provincie_landelijk", "name_b_a" => "sprel_landelijk_provincie"));
			$this->relationshipTypes = $relationshipTypes;
		} catch (Exception $e) {
			echo $e."\r\n";
			die("Een of meerdere relatie-types ontbreken");
		}
	}

	public function fetchTags() {
		$this->tags = new stdClass;
		try { $this->tags->io 			= civicrm_api3('tag','getsingle',array("name" => "In Oprichting")); } catch (Exception $e) { die("Tag IO ontbreekt"); }
		try { $this->tags->erkend 		= civicrm_api3('tag','getsingle',array("name" => "Erkend")); } catch (Exception $e) { die("Tag Erkend ontbreekt"); }
		try { $this->tags->opgeheven 	= civicrm_api3('tag','getsingle',array("name" => "Opgeheven")); } catch (Exception $e) { die("Tag Opgeheven ontbreekt"); }
	}
	
	public function migreerARP() {
		echo "=> Start Migratie ARP \r\n";
		while ($this->geoRow = $this->resultSet->fetch_assoc()) {
			if(in_array(ucfirst($this->geoRow['COL 134']), $this->doubles)) continue;
			echo "\r\n-> Start verwerking record: ".$this->geoRow['COL 1']."\r\n";
			$this->geoParams = array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Afdeling['name'],
				'organization_name' => "SP-afdeling " . $this->geoRow['COL 134']
			);
			$this->doubles[] = ucfirst($this->geoRow['COL 134']);
			if(!$this->CIVIAPI->Contact->Create($this->geoParams)){
				echo "-> Afdeling " . $this->geoRow['COL 134'] . " opslaan mislukt\r\n";
				echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
			} else {
				echo "-> Opslaan van SP-Afdeling ".$this->geoRow['COL 134']." succesvol\r\n";
				$afdelingsNummer = $this->CIVIAPI->lastResult->id;
				$this->registreerAdres($afdelingsNummer);
				$this->registreerTags($afdelingsNummer);
				if(!in_array($this->geoRow['COL 9'], $this->geoRegios)){
					echo "-> Start verwerking van SP-Regio ".$this->geoRow['COL 9']."\r\n";
					$this->geoParams = array(
						'contact_type' => 'organization',
						'contact_sub_type' => $this->contactTypes->Regio['name'],
						'organization_name' => "SP-regio " . $this->geoRow['COL 9']
					);
					if(!$this->CIVIAPI->Contact->Create($this->geoParams)){
						echo "-> SP Regio " . $this->geoRow['COL 9'] . " opslaan mislukt\r\n";
						echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
					} else {
						$this->geoRegios[] = $this->geoRow['COL 9'];
						echo "-> Verwerking van SP-Regio ".$this->geoRow['COL 9']." succesvol\r\n";
					}
				}
				if(!in_array($this->geoRow['COL 7'], $this->geoProvincies)){
					echo "-> Start verwerking van SP-Provincie ".$this->geoRow['COL 7']."\r\n";
					$this->geoParams = array(
						'contact_type' => 'organization',
						'contact_sub_type' => $this->contactTypes->Provincie['name'],
						'organization_name' => "SP-provincie " . $this->geoRow['COL 7']
					);
					if(!$this->CIVIAPI->Contact->Create($this->geoParams)){
						echo "-> SP Provincie " . $this->geoRow['COL 7'] . " opslaan mislukt\r\n";
						echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
					} else {
						$this->geoProvincies[] = $this->geoRow['COL 7'];
						echo "-> Verwerking van SP-Provincie ".$this->geoRow['COL 9']." succesvol\r\n";
					}
				}
			}
			echo "-> Verwerking record: ".$this->geoRow['COL 1']." afgerond\r\n";
		}
		echo "\r\n=> Stop Migratie ARP\r\n";
	}
	
	public function migreerLandelijk() {
		echo "=> Start Migratie Landelijk \r\n";
		$this->resultSet = $this->dbAdapter->query("SELECT * FROM  `geo` WHERE `COL 134` = 'Nederland' AND `COL 137` = 41");
		while ($this->geoRow = $this->resultSet->fetch_assoc()) {
			echo "\r\n-> Start verwerking record: ".$this->geoRow['COL 1']."\r\n";
			$this->geoParams = array(
				'contact_type' => 'organization',
				'contact_sub_type' => $this->contactTypes->Landelijk['name'],
				'organization_name' => "SP " . $this->geoRow['COL 134']
			);
			if(!$this->CIVIAPI->Contact->Create($this->geoParams)){
				echo "-> SP-landelijk " . $this->geoRow['COL 134'] . " opslaan mislukt\r\n";
				echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
			} else {
				echo "-> Verwerking record: ".$this->geoRow['COL 1']." succesvol afgerond\r\n";
			}
		}
		echo "\r\n=> Stop Migratie Landelijk \r\n";
	}

	public function relatiesARP() {
		echo "=> Start verwerking relatie ARP \r\n";
		$this->doubles = array();
		$this->resultSet = $this->dbAdapter->query("select `geo`.`COL 1` AS `COL 1`,`geo`.`COL 2` AS `COL 2`,`geo`.`COL 3` AS `COL 3`,`geo`.`COL 4` AS `COL 4`,`geo`.`COL 5` AS `COL 5`,`geo`.`COL 6` AS `COL 6`,`geo`.`COL 7` AS `COL 7`,`geo`.`COL 8` AS `COL 8`,`geo`.`COL 9` AS `COL 9`,`geo`.`COL 10` AS `COL 10`,`geo`.`COL 11` AS `COL 11`,`geo`.`COL 134` AS `COL 134`,`geo`.`COL 135` AS `COL 135`,`geo`.`COL 136` AS `COL 136`,`geo`.`COL 137` AS `COL 137`,`geo`.`COL 138` AS `COL 138`,`geo`.`COL 139` AS `COL 139`,`geo`.`COL 140` AS `COL 140`,`geo`.`COL 141` AS `COL 141`,`geo`.`COL 142` AS `COL 142`,`geo`.`COL 143` AS `COL 143` from `geo` where (`geo`.`COL 137` = 37) order by `col 134`, `col 4` desc");
		while ($this->geoRow = $this->resultSet->fetch_assoc()) {
			if(in_array(ucfirst(strtolower($this->geoRow['COL 134'])), $this->doubles)) continue;
			echo "\r\n-> Start verwerking voor ".$this->geoRow['COL 134']."\r\n";
			$this->doubles[] = ucfirst(strtolower($this->geoRow['COL 134']));
			try {
				$contactAfdeling = civicrm_api3("Contact","getsingle",array('contact_type' => 'organization', 'contact_sub_type' => $this->contactTypes->Afdeling['name'], 'organization_name' => "SP-afdeling " . ucfirst($this->geoRow['COL 134'])));
			} catch (Exception $e) {
				echo "-> Meerdere SP Afdelingen gevonden voor " . $this->geoRow['COL 134']."\r\n";
				echo "-> ".$e."\r\n";
			}
			try {
				$contactRegio = civicrm_api3("Contact","getsingle",array('contact_type' => 'organization', 'contact_sub_type' => $this->contactTypes->Regio['name'], 'organization_name' => "SP-regio " . $this->geoRow['COL 9']));
			} catch (Exception $e) {
				echo "-> Meerdere SP Regios gevonden voor " . $this->geoRow['COL 1']."\r\n";
				echo "-> ".$e."\r\n";
			}
			try {
				$contactProvincie = civicrm_api3("Contact","getsingle",array('contact_type' => 'organization', 'contact_sub_type' => $this->contactTypes->Provincie['name'], 'organization_name' => "SP-provincie " . $this->geoRow['COL 7']));
			} catch (Exception $e) {
				echo "-> Meerdere SP Provincies gevonden voor " . $this->geoRow['COL 7']."\r\n";
				echo "-> ".$e."\r\n";
			}
			$startDatum = ($this->geoRow['COL 3'] != '-  -') ? $this->geoRow['COL 3'] : NULL;
			$eindDatum = ($this->geoRow['COL 4'] != '2099-01-01') ? $this->geoRow['COL 4'] : NULL;
			$rstAfdelingRegio = ($this->geoRow['COL 141'] == "IO") ? $this->relationshipTypes->AfdelingioRegio['id'] : $this->relationshipTypes->AfdelingRegio['id'];
			$this->relParams = array(
			  'contact_id_a' => $contactAfdeling['id'],
			  'contact_id_b' => $contactRegio['id'],
			  'relationship_type_id' => $rstAfdelingRegio,
			  'start_date' => $startDatum,
			  'end_date' => $eindDatum,
			);
			if(!$this->CIVIAPI->Relationship->Create($this->relParams)){
				if($this->CIVIAPI->errorMsg() != "Relationship already exists"){
					echo "-> Relatie tussen afdeling ".$contactAfdeling['organization_name']." en regio ".$contactRegio['organization_name']." is mislukt\r\n";
					echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
				}
			} else {
				echo "-> Relatie tussen afdeling ".$contactAfdeling['organization_name']." en regio ".$contactRegio['organization_name']." succesvol aangemaakt\r\n";
				if(!$this->CIVIAPI->Relationship->getsingle(array('contact_id_a' => $contactRegio['id'], 'contact_id_b' => $contactProvincie['id'], 'relationship_type_id' =>  $this->relationshipTypes->RegioProvincie['id'], 'version' => 3))){
					$regioQuery = $this->dbAdapter->query("SELECT `COL 3`, `COL 4` FROM `geo` WHERE `COL 134` = '".$this->geoRow['COL 9']."' AND `COL 137` = 36 ORDER BY `COL 4` DESC LIMIT 1");
					if(is_object($regioQuery)){
						$regioResult = $regioQuery->fetch_assoc();
						$startDatum = ($regioResult['COL 3'] != '-  -') ? $regioResult['COL 3'] : NULL;
						$eindDatum = ($regioResult['COL 4'] != '2099-01-01') ? $regioResult['COL 4'] : NULL;
					} else {
						$startDatum = NULL;
						$eindDatum = NULL;
					}
					$this->relParams = array(
					  'contact_id_a' => $contactRegio['id'],
					  'contact_id_b' => $contactProvincie['id'],
					  'relationship_type_id' => $this->relationshipTypes->RegioProvincie['id'],
					  'start_date' => $startDatum,
					  'end_date' => $eindDatum,
					);
					if(!$this->CIVIAPI->Relationship->Create($this->relParams)){
						if($this->CIVIAPI->errorMsg() != "Relationship already exists"){
							echo "-> Relatie tussen regio ".$contactRegio['organization_name']." en provincie ".$contactProvincie['organization_name']." is mislukt\r\n";
							echo "-> ".$this->CIVIAPI->errorMsg() . "\r\n\r\n";
						} else {
							echo "-> Relatie tussen regio ".$contactRegio['organization_name']." en provincie ".$contactProvincie['organization_name']." succesvol aangemaakt\r\n";
						}
					}
				}
			}
			echo "-> Verwerking voor ".$this->geoRow['COL 134']." afgerond\r\n";
		}
		echo "\r\n=> Stop verwerking relatie ARP \r\n";
	}
	
	public function relatieLandelijk() {
		echo "=> Start Landelijke relaties \r\n";
		$contactLandelijk = civicrm_api3("Contact","getsingle",array('contact_type' => 'organization', 'contact_sub_type' => $this->contactTypes->Landelijk['name'], 'organization_name' => "SP Nederland"));
		$provincies = array('Overijssel', 'Zuid-Holland', 'Groningen', 'Friesland', 'Drenthe', 'Gelderland', 'Flevoland', 'Utrecht', 'Noord-Holland', 'Zeeland', 'Noord-Brabant', 'Limburg');
		foreach($provincies as $provincie) {
			echo "\r\n-> Start verwerking voor SP-Provincie ".$provincie." \r\n";
			$contactProvincie = civicrm_api3("Contact","getsingle",array('contact_type' => 'organization', 'contact_sub_type' => $this->contactTypes->Provincie['name'], 'organization_name' => "SP-provincie ".$provincie));
			if(!$this->CIVIAPI->Relationship->getsingle(array('contact_id_a' => $contactProvincie['id'], 'contact_id_b' => $contactLandelijk['id'], 'relationship_type_id' =>  $this->relationshipTypes->ProvincieLandelijk['id'], 'version' => 3))){
				$this->relParams = array(
				  'contact_id_a' => $contactProvincie['id'],
				  'contact_id_b' => $contactLandelijk['id'],
				  'relationship_type_id' => $this->relationshipTypes->ProvincieLandelijk['id']
				);
				if(!$this->CIVIAPI->Relationship->Create($this->relParams)){
					if($this->CIVIAPI->errorMsg() != "Relationship already exists"){
						echo "Relatie tussen SP-Provincie ".$provincie." en SP-Landelijk is mislukt\r\n";
						echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
					}
				} else {
					echo "-> Verwerking voor SP-Provincie ".$provincie." succesvol afgerond \r\n";
				}
			}
		}
		echo "\r\n=> Stop Landelijke relaties \r\n";
	}

	private function registreerAdres($civiContact_id) {
		$addressQuery = $this->dbAdapter->query("
			SELECT `h`.`COL 1`
			FROM `geokoppl` as `g`
			LEFT JOIN `his001` as `h` ON `h`.`COL 5` = `g`.`COL 5`
			WHERE `g`.`COL 4` = ".$this->geoRow['COL 2']."
			AND `h`.`COL 7` = 'AA'
			ORDER BY `g`.`COL 3` DESC
			LIMIT 1
		");
        if(is_object($addressQuery)){
    		$contact_id = $addressQuery->fetch_assoc();
    		try {
    			$address = civicrm_api3('Address', 'getsingle', array('contact_id' => $contact_id['COL 1']));
    			civicrm_api3('Address', 'create', array("contact_id" => $civiContact_id, "master_id" => $address['id'], "location_type_id" => 1));
    		} catch (Exception $e) {
    			echo "-> SP-Afdeling ".$this->geoRow['COL 134']." heeft geen kaderfunctie AA of het contact ".$contact_id['COL 1']." kan niet worden gevonden\r\n";
    		}
	   }
	}

	private function registreerTags($afdelingsNummer) {
		if($this->geoRow['COL 4'] != "2099-01-01") {
			try { civicrm_api3('EntityTag','create',array("entity_id" => $afdelingsNummer, "tag_id" => $this->tags->opgeheven['id'])); } Catch (Exception $e) { echo "Tag opgeheven koppeling mislukt \r\n"; }
		} else {
			if($this->geoRow['COL 141'] == "IO") {
				try { civicrm_api3('EntityTag','create',array("entity_id" => $afdelingsNummer, "tag_id" => $this->tags->io['id'])); } Catch (Exception $e) { echo "Tag 'In Oprichting' koppeling mislukt \r\n"; }
			} else if($this->geoRow['COL 141'] == "ER") {
				try { civicrm_api3('EntityTag','create',array("entity_id" => $afdelingsNummer, "tag_id" => $this->tags->erkend['id'])); } Catch (Exception $e) { echo "Tag Erkend koppeling mislukt \r\n"; }
			}
		}
	}

}
new geostelsel(false);