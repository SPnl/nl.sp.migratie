<?php
include('./baseclass.php');
class contacten extends baseclass {

	public $resultSet, $contactRow, $contactParameters, $addressParameters, $ltBezoekAdres, $cfComVoorkeurPV, $catAanBestandToegevoegd, $cfBronOpties, $cfBron, $cfOptions;
	public $bronWebsite = array(8, 11, 12, 66, 104, 164, 204, 218, 220, 240, 353, 400, 666), $bronVerkiezingen = array(101, 132, 194, 196, 208, 229, 236, 294, 300, 301, 304, 350, 351), $bronActieLandelijk = array(105, 109, 110, 111, 112, 115, 133, 134, 135, 137, 138, 140, 141, 142, 167, 175, 176, 181, 183, 193, 243, 245, 246, 292, 302, 307, 343, 344), $bronAfdeling = array(108, 118, 216, 221, 228, 233, 234, 244, 308), $bronTelefonisch = array(100, 117, 160, 205, 217, 219, 232, 271), $bronZOKrant = array(297), $bronOuderenkrant = array(237, 241, 266, 268, 303, 306), $bronSolidair = array(128, 130, 144, 285), $bronROOD = array(58, 131, 139, 174, 177, 178, 179, 255, 256, 320, 321, 322, 323, 324, 325, 329, 330, 331, 332, 333, 334, 336, 337, 338, 339, 341, 342), $bronTribuneBon = array(114, 121, 198, 207, 239, 345), $bronDrukwerkOverig = array(107, 113, 116, 119, 120, 122, 127, 149, 154, 155, 156, 157, 158, 161, 162, 163, 165, 166, 168, 172, 173, 180, 184, 185, 186, 187, 188, 189, 190, 191, 192, 212, 227, 235, 247, 248, 260, 262, 273, 275, 277, 288, 298, 299, 335, 347, 352), $bronLedenwerfcapagne = array(106, 123, 136, 150, 151, 152, 153, 195, 197, 200, 201, 202, 203, 211, 213, 214, 230, 231, 249, 250, 265, 267, 276, 278, 291, 354, 355), $bronLidWerftLid = array(124, 126, 182, 242, 280, 346), $bronTerugWerving = array(251, 289, 444, 500), $Infoaanvraag = array(102, 103, 129, 146, 159, 170, 171, 199, 209, 210, 215, 223, 226, 305), $bronEvenement = array(77, 79, 80, 81, 82, 83, 169, 270, 310, 2000), $bronOverig = array(0, 50, 51, 52, 53, 54, 55, 56, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 98, 99, 125, 143, 145, 147, 148, 206, 222, 224, 225, 238, 261, 272, 274, 290, 309, 311, 313, 340, 377, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1034, 6001, 6002, 6003, 9000, 9001);

	public function contacten($testMode = false, $arguments) {

		parent::baseclass($testMode);

		$pointer         = explode("=", $arguments[1]);

		$this->resultSet = $this->dbAdapter->query("
			SELECT
			`COL_1` as `regnumber`,
			`COL_2` as `lastname`,
			`COL_3` as `middlename`,
			`COL_4` as `nameletters`,
			`COL_5` as `firstname`,
			`COL_6` as `street`,
			`COL_7` as `housenumber`,
			`COL_8` as `housenumber_extra`,
			`COL_9` as `city`,
			`COL_10` as `postalcode`,
			`COL_11` as `country`,
			`COL_16` AS `gender`,
			`COL_13` AS `status`,
			`COL_14` AS `join_date`,
			`COL_15` AS `dateofbirth`,
			`COL_29` AS `bLand`,
			`COL_30` AS `bStraat`,
			`COL_31` AS `bNummer`,
			`COL_33` AS `bPostcode`,
			`COL_34` AS `bWoonplaats`,
			`COL_36` as `second_last_name`,
			`COL_40` as `bronVeldValue`,
			`COL_53` as `tavAchternaam`,
			`COL_54` as `tavNaam`,
			`COL_57` as `tavRelatie`,
			`COL_58` as `contact_type`,
			`COL_65` as `tav`,
			`COL_66` as `comVoorkeur`,
			`COL_104` as `nietbellen`
			FROM `contacten`
			ORDER BY `COL_1` ASC
			LIMIT " . $pointer[1] . ", 500
		");

		if(!$this->resultSet) die("Query is mislukt");

		$this->fetchCustomFields();
		$this->fetchTags();
		$this->migreer();

	}

	public function fetchCustomFields() {
		/* Fetch all custom fields and values */
		try { $this->ltBezoekAdres 				= civicrm_api3('LocationType','getsingle', array("name" => "Bezoekadres")); } catch (Exception $e) { die("CF Bezoekadres niet gevonden"); }
		try { $this->cfVoorletters				= civicrm_api3('CustomField', 'getsingle', array("name" => "voorletters")); } catch (Exception $e) { die("CF Voorletters niet gevonden"); }
		try { $this->cfRetourpost				= civicrm_api3('CustomField', 'getsingle', array("name" => "retourpost")); } catch (Exception $e) { die("CF Retourpost niet gevonden"); }
		try { $this->cfComVoorkeurPV			= civicrm_api3('OptionValue', 'getsingle', array("label" => "Postvak", "option_group_id" => 1)); } catch (Exception $e) { die("CF Postvak niet gevonden"); }
		try { $this->catAanBestandToegevoegd 	= civicrm_api3('OptionValue', 'getsingle', array("label" => "Aan bestand toegevoegd", "option_group_id" => 2)); } catch (Exception $e) { die("CF Aanbestandtoegevoegd niet gevonden"); }
		try { $cgMigratieContacten 				= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Migratie_Contacten")); } catch (Exception $e) { die("CG Migratie_Contacten niet gevonden"); }
		try { $this->cfBron 					= civicrm_api3('CustomField', 'getsingle', array("name" => "Bron", "custom_group_id" => $cgMigratieContacten['id'])); } catch (Exception $e) { die("CF Bron niet gevonden"); }
		try { $this->cfOptions 					= civicrm_api3('OptionValue', 'get', array("option_group_id" => $this->cfBron['option_group_id'])); } catch (Exception $e) { die("CF option group values niet gevonden"); }
		foreach ($this->cfOptions['values'] as $value) {
			switch ($value['label']) {
				case "Website": $this->bronOpties['WebsiteValue'] = $value['value']; break;
				case "Verkiezingen": $this->bronOpties['VerkiezingenValue'] = $value['value']; break;
				case "Actie landelijk": $this->bronOpties['ActieLandelijkValue'] = $value['value']; break;
				case "Afdeling": $this->bronOpties['AfdelingValue'] = $value['value']; break;
				case "Telefonisch": $this->bronOpties['TelefonischValue'] = $value['value']; break;
				case "ZO-krant": $this->bronOpties['ZOkrantValue'] = $value['value']; break;
				case "Ouderenkrant": $this->bronOpties['OuderenkrantValue'] = $value['value']; break;
				case "Solidair-krant": $this->bronOpties['SolidairValue'] = $value['value']; break;
				case "ROOD": $this->bronOpties['RoodValue'] = $value['value']; break;
				case "Tribune-bon": $this->bronOpties['TribuneBonValue'] = $value['value']; break;
				case "Drukwerk overig": $this->bronOpties['DrukwerkOverigValue'] = $value['value']; break;
				case "Ledenwerfcampagne": $this->bronOpties['LedenwerfcampagneValue'] = $value['value']; break;
				case "Lid-werft-lid": $this->bronOpties['LidwerftlidValue'] = $value['value']; break;
				case "Terugwerving": $this->bronOpties['TerugwervingValue'] = $value['value']; break;
				case "Infoaanvraag": $this->bronOpties['InfoaanvraagValue'] = $value['value']; break;
				case "Evenement": $this->bronOpties['EvenementValue'] = $value['value']; break;
				case "Overig": $this->bronOpties['OverigValue'] = $value['value']; break;
			}
		}
	}

	public function fetchTags() {
		$this->tags = new stdClass;
		try { $this->tags->geroyeerd 		= civicrm_api3('tag','getsingle',array("name" => "Geroyeerd")); } catch (Exception $e) { die("Tag Geroyeerd ontbreekt"); }
		try { $this->tags->geweigerd	 	= civicrm_api3('tag','getsingle',array("name" => "Geweigerd")); } catch (Exception $e) { die("Tag Geweigerd ontbreekt"); }
	}

	public function migreer() {

		while ($this->contactRow = $this->resultSet->fetch_assoc()) {

			// Create contact entry in CIVI database
			$this->dbcAdapter->query("INSERT INTO `civicrm_contact` SET `id` = " . $this->contactRow['regnumber']);

			// Determine contact is individual or organization
			$contact_type 		= ($this->contactRow['contact_type'] == "P") ? "individual" : "organization";
			// Determine contact gender - 2 is male, 1 is female
			$gender 			= ($this->contactRow['gender'] == "M") ? 2 : 1;
			// Determine if contact has a known birthdate, if so, register it
			$birthdate 			= ($this->contactRow['dateofbirth'] != "-  -") ? date("Y-m-d", strtotime(substr($this->contactRow['dateofbirth'], 0, 10))) : NULL;
			// Determine if contact has a known join date, if so, register it
			$joindate 			= ($this->contactRow['join_date'] != "-  -") ? date("Y-m-d", strtotime(substr($this->contactRow['join_date'], 0, 10))) : NULL;
			// Determine if contact has multiple last names (marriage), if they have concate this with their maiden name
			$lastname 			= (!empty($this->contactRow['second_last_name']) AND strlen($this->contactRow['second_last_name']) > 1) ? $this->contactRow['lastname'] . " - " . $this->contactRow['second_last_name'] : $this->contactRow['lastname'];
			// Determine if the initials for a contact are known, if so, register
			$initials 			= (!empty($this->contactRow['nameletters'])) ? $this->contactRow['nameletters'] : NULL;
			// Determine firstname
			$firstname			= (!empty($this->contactRow['firstname'])) ? $this->contactRow['firstname'] : $initials.".";
			// Determine full name for display
			$display_name		= (!empty($this->contactRow['middlename'])) ? $firstname." ".$this->contactRow['middlename']." ".$lastname : $firstname." ".$lastname;

			// Default contact parameters
			$this->contactParameters 	= array(
				'id' => $this->contactRow['regnumber'],
				'contact_type' => $contact_type,
				'first_name' => $firstname,
				'middle_name' => $this->contactRow['middlename'],
				'last_name' => $lastname,
				'gender_id' => $gender,
				'birth_date' => $birthdate,
				'display_name' => $display_name,
				'custom_'.$this->cfVoorletters['id'] => $initials
			);

			// Extra greeting parameters for contacts
			if($this->contactRow['contact_type'] == "P"){
				$this->contactParameters['email_greeting_id'] = 1;
				$this->contactParameters['postal_greeting_id'] = 1;
				$this->contactParameters['addressee_id'] = 1;
			}

			// If a contact is an organization then, replace name with organization name
			if ($this->contactRow['contact_type'] == "O") {
				$this->contactParameters['organization_name'] = $this->contactParameters['last_name'];
				unset($this->contactParameters['first_name']);
				unset($this->contactParameters['middle_name']);
				unset($this->contactParameters['last_name']);
			}

			// Check for communication preferences
			$this->communicationPreferences();

			// Check if contact is deceased
			$this->deceasedCheck();

			// Bronveld
			$this->bronVeld();

			// Create contact
			if($this->CIVIAPI->Contact->Create($this->contactParameters)) {
				// Creating contact succeeded, let's continue
				$this->registerJoinActivity();
				$this->registerAddress();
				$this->registerCommunication();
				if($this->contactRow['status'] == 8) $this->registerTags();
			} else {
				// Creating contact failed, log
				echo "Contact ".$this->contactRow['regnumber']." opslaan mislukt\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}

		}

	}

	public function communicationPreferences() {
		// Determine if contact has a communication prefrence of the type "postvak"
		$this->contactParameters['custom_'.$this->cfComVoorkeurPV['id']] = ($this->contactRow['comVoorkeur'] == "PV") ? 1 : NULL;
		// Do not call option
		$this->contactParameters['do_not_phone'] = ($this->contactRow['nietbellen'] == "T") ? 1 : NULL;
		// Do not mail option
		$this->contactParameters['do_not_mail'] = ($this->contactRow['status'] == 9) ? 1 : NULL;
		// Value for CF "Retourpost"
		switch($this->contactRow['status']) {
			case 1: $this->contactParameters["custom_".$this->cfRetourpost['id']] = "1"; break;
			case 6: $this->contactParameters["custom_".$this->cfRetourpost['id']] = "1"; break;
		}
	}

	public function deceasedCheck() {
		/* Check if a contact is deceased */
		$deceasedQuery = $this->dbAdapter->query("SELECT * FROM `lidmaatschappen` WHERE `COL 2` = ".$this->contactRow['regnumber']." AND `COL 17` = 'PR_88'");
		if((is_object($deceasedQuery) && $deceasedQuery->num_rows) && $this->contactRow['status'] == 8) {
			$this->contactParameters['is_deceased'] = 1;
		}
	}

	public function registerJoinActivity() {
		if ($this->contactRow['join_date'] != "-  -") {
			$params = array('activity_status_id' => 2, 'activity_subject' => '', 'activity_date_time' => date("Y-m-d", strtotime(substr($this->contactRow['join_date'], 0, 10))), 'activity_type_id' => (int)$this->catAanBestandToegevoegd['value'], 'source_contact_id' => $this->contactRow['regnumber']);
			if (!$this->CIVIAPI->Activity->Create($params)) {
				echo "Activity add to file for contact ".$this->contactRow['regnumber']." failed to save\r\n";
				echo $this->CIVIAPI->errorMsg()."\r\n";
			}
		}
	}

	public function registerAddress() {

		/* Register main address for the contact */
		$this->addressParameters = array(
			'contact_id' => $this->contactRow['regnumber'],
			'location_type_id' => 1,
			'street_address' => trim(ucfirst($this->contactRow['street']))." ".$this->contactRow['housenumber'].$this->contactRow['housenumber_extra'],
			'street_name' => trim(ucfirst($this->contactRow['street'])),
			'street_number' => $this->contactRow['housenumber'],
			'street_unit' => substr($this->contactRow['housenumber_extra'], 0, 8),
			'city' => strtoupper(strtolower($this->contactRow['city'])),
			'postal_code' => $this->contactRow['postalcode'],
			'country_id' => $this->determineCountry()
		);

		// Determine if "ter attentie van" is present
		if(!empty($this->contactRow['tavNaam']) OR !empty($this->contactRow['tavAchternaam']) AND $this->contactRow['tavRelatie'] == "TA") {
			if(!empty($this->contactRow['tavNaam']) AND empty($this->contactRow['tavAchternaam'])) {
				$tavNaam = $this->contactRow['tavNaam'];
			} else if(empty($this->contactRow['tavNaam']) AND !empty($this->contactRow['tavAchternaam'])) {
				$tavNaam = $this->contactRow['tavAchternaam'];
			} else if(!empty($this->contactRow['tavNaam']) AND !empty($this->contactRow['tavAchternaam'])) {
				$tavNaam = $this->contactRow['tavNaam']." ".$this->contactRow['tavAchternaam'];
			}
			$this->addressParameters['supplemental_address_1'] = $tavNaam;
		}

		// "Ter attentie van" was present, register it as supplemental address
		if(!empty($this->contactRow['tav'])) { $this->addressParameters['supplemental_address_1'] = $this->contactRow['tav']; }

		// Attempt to save address for contact
		if (!$this->CIVIAPI->Address->Create($this->addressParameters)) {
			echo "Opslaan van adres voor contact " . $this->contactRow['regnumber'] . " is gefaald\r\n";
			echo $this->CIVIAPI -> errorMsg() . "\r\n";
		}

		// If there's a second address known in the database, register it
		if(!empty($this->contactRow['bStraat']) && !empty($this->contactRow['bNummer']) && !empty($this->contactRow['bPostcode']) && !empty($this->contactRow['bWoonplaats'])){
			if(!$this->CIVIAPI->Address->Create(array(
				'contact_id' => $this->contactRow['regnumber'],
				'location_type_id' => $this->ltBezoekAdres['id'],
				'street_address' => trim(ucfirst($this->contactRow['bStraat']))." ".$this->contactRow['bNummer'],
				'street_name' => trim(ucfirst($this->contactRow['bStraat'])),
				'street_number' => $this->contactRow['bNummer'],
				'city' => ucfirst(strtolower($this->contactRow['bWoonplaats'])),
				'postal_code' => $this->contactRow['bPostcode'],
				'country_id' => $this->determineCountry()
			))) {
				echo "Opslaan van tweede adres voor contact " . $this->contactRow['regnumber'] . " is gefaald\r\n";
				echo $this->CIVIAPI -> errorMsg() . "\r\n";
			}
		}

	}

	public function determineCountry() {

		/* Determine country identifier */
		switch($this->contactRow['country']) {
			case "CS":
			case "CZ": 	$returnValue = 1058; break;
			case "AUS": $returnValue = 1013; break;
			case "B": 	$returnValue = 1020; break;
			case "D": 	$returnValue = 1082; break;
			case "E": 	$returnValue = 1198; break;
			case "EAU": $returnValue = 1223; break;
			case "F": 	$returnValue = 1076; break;
			case "P": 	$returnValue = 1173; break;
			case "RI": 	$returnValue = 1102; break;
			case "CH": 	$returnValue = 1205; break;
			case "GB": 	$returnValue = 1226; break;
			case "GN": 	$returnValue = 1091; break;
			case "IRL": $returnValue = 1105; break;
			case "L": 	$returnValue = 1126; break;
			case "USA": $returnValue = 1228; break;
			case "A": 	$returnValue = 1014; break;
			case "CU": 	$returnValue = 1248; break;
			case "CY": 	$returnValue = 1057; break;
			case "DK": 	$returnValue = 1059; break;
			case "GR": 	$returnValue = 1085; break;
			case "H": 	$returnValue = 1099; break;
			case "I": 	$returnValue = 1107; break;
			case "N": 	$returnValue = 1161; break;
			case "PL": 	$returnValue = 1172; break;
			case "S": 	$returnValue = 1204; break;
			case "DOM": $returnValue = 1062; break;
			case "IND": $returnValue = 1102; break;
			case "NZ":  $returnValue = 1154; break;
			case "SF":  $returnValue = 1075; break;
			case "T":   $returnValue = 1211; break;
			case "EAK": $returnValue = 1112; break;
			case "IL":  $returnValue = 1106; break;
			case "MAL": $returnValue = 1131; break;
			case "NIC": $returnValue = 1155; break;
			case "BH": 	$returnValue = 1021; break;
			case "CL": 	$returnValue = 1199; break;
			case "IS": 	$returnValue = 1100; break;
			case "J": 	$returnValue = 1109; break;
			case "NA": 	$returnValue = 1250; break;
			case "TR": 	$returnValue = 1219; break;
			case "CDN": $returnValue = 1039; break;
			case "GH": 	$returnValue = 1083; break;
			case "MA": 	$returnValue = 1146; break;
			case "WAL": $returnValue = 1190; break;
			case "ZA": 	$returnValue = 1196; break;
			case "GUY": $returnValue = 1093; break;
			case "RO": 	$returnValue = 1176; break;
			case "SME": $returnValue = 1201; break;
			case "TN": 	$returnValue = 1218; break;
			case "UAE": $returnValue = 1225; break;
			case "C": 	$returnValue = 1037; break;
			case "EST": $returnValue = 1069; break;
			case "M": 	$returnValue = 1134; break;
			case "RCH": $returnValue = 1044; break;
			case "ZW": 	$returnValue = 1240; break;
			default: 	$returnValue = 1152; break;
		}
		return $returnValue;
	}

	public function registerCommunication() {

		/* Register all communication methods for contact */
		$contactCommunications = $this->dbAdapter->query("SELECT * FROM `communication` WHERE `COL 1` = " . $this->contactRow['regnumber']);

		// Check if we have any records
		if(is_object($contactCommunications)) {
			// Loop trough all the contact communication methods
			while ($cr = $contactCommunications->fetch_assoc()) {
				switch ($cr["COL 2"]) {
					case "E" :
						// E-mail - check if length is longer then 6 characters
						if(strlen($cr["COL 3"]) > 6) {
							// Length is valid, set-up parameters for e-mail
							$emailParameters = array('contact_id' => $this->contactRow['regnumber'], 'email' => $cr["COL 6"]);
							// Check if we need to add work or home location type
							if($cr["COL 5"] == "T") {
								// Home
								$emailParameters["location_type_id"] = 1;
							} else if($cr["COL 5"] == "W") {
								// Work
								$emailParameters["location_type_id"] = 2;
							} else {
								// Other
								$phoneParameters["location_type_id"] = 4;
							}

							// Create e-mail identity
							if(!$this->CIVIAPI->Email->Create($emailParameters)) {
								echo "          Email address for contact " . $this->contactRow['regnumber'] . " failed to save\r\n";
								echo "          " . $this->CIVIAPI->errorMsg() . "\r\n";
							}
						}
					break;
					case "W" :
						// Website - check if link contains www.
						if(stristr($cr["COL 3"], 'www')) {

							// Length is valid, set-up parameters for website
							$websiteParameters = array('contact_id' => $this->contactRow['regnumber'], 'url' => $cr["COL 3"]);

							// Create website identity
							if(!$this->CIVIAPI->Website->Create($websiteParameters)) {
								echo "          Website address for contact " . $this->contactRow['regnumber'] . " failed to save\r\n";
								echo "          " . $this->CIVIAPI->errorMsg() . "\r\n";
							}
						}
					break;
					case "T" :
						// Telephone - check if length is longer then 7 characters
						if(strlen($cr["COL 3"]) > 7) {

							// Length is valid, set-up parameters for e-mail
							$phoneParameters = array('contact_id' => $this->contactRow['regnumber'], 'phone' => $cr["COL 3"], 'phone_type_id' => 1);

							// Check if we need to add work or home location type
							if($cr["COL 5"] == "T") {
								// Home
								$phoneParameters["location_type_id"] = 1;
							} else if($cr["COL 5"] == "W") {
								// Work
								$phoneParameters["location_type_id"] = 2;
							} else {
								// Other
								$phoneParameters["location_type_id"] = 4;
							}

							// Create e-mail identity
							if(!$this->CIVIAPI->Phone->Create($phoneParameters)) {
								echo "Regular phone number for contact " . $this->contactRow['regnumber'] . " failed to save\r\n";
								echo $this->CIVIAPI->errorMsg() . "\r\n";
							}
						}
					break;
					case "F" :
						// Telephone - check if length is longer then 6 characters
						if(strlen($cr["COL 3"]) > 6) {

							// Length is valid, set-up parameters for e-mail
							$phoneParameters = array('contact_id' => $this->contactRow['regnumber'], 'phone' => $cr["COL 3"], 'phone_type_id' => 3);

							// Check if we need to add work or home location type
							if($cr["COL 5"] == "T") {
								// Home
								$phoneParameters["location_type_id"] = 1;
							} else if($cr["COL 5"] == "W") {
								// Work
								$phoneParameters["location_type_id"] = 2;
							} else {
								// Other
								$phoneParameters["location_type_id"] = 4;
							}

							// Create e-mail identity
							if(!$this->CIVIAPI->Phone->Create($phoneParameters)) {
								echo "Fax phone number for contact " . $this->contactRow['regnumber'] . " failed to save\r\n";
								echo $this->CIVIAPI->errorMsg() . "\r\n";
							}
						}
					break;
					case "M" :
						// Telephone - check if length is longer then 7 characters
						if(strlen($cr["COL 3"]) > 7) {

							// Length is valid, set-up parameters for e-mail
							$phoneParameters = array('contact_id' => $this->contactRow['regnumber'], 'phone' => $cr["COL 3"], 'phone_type_id' => 2);

							// Check if we need to add work or home location type
							if($cr["COL 5"] == "T") {
								// Home
								$phoneParameters["location_type_id"] = 1;
							} else if($cr["COL 5"] == "W") {
								// Work
								$phoneParameters["location_type_id"] = 2;
							} else {
								// Other
								$phoneParameters["location_type_id"] = 4;
							}

							// Create e-mail identity
							if(!$this->CIVIAPI->Phone->Create($phoneParameters)) {
								echo "Mobile phone number for contact ".$this->contactRow['regnumber']." failed to save\r\n";
								echo $this->CIVIAPI->errorMsg()."\r\n";
							}
						}
					break;
				}
			}
		}
	}

	public function bronVeld() {
        if (in_array($this->contactRow['bronVeldValue'], $this->bronWebsite)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['WebsiteValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronVerkiezingen)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['VerkiezingenValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronActieLandelijk)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['ActieLandelijkValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronAfdeling)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['AfdelingValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronTelefonisch)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['TelefonischValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronZOKrant)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['ZOkrantValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronOuderenkrant)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['OuderenkrantValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronSolidair)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['SolidairValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronROOD)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['RoodValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronTribuneBon)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['TribuneBonValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronDrukwerkOverig)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['DrukwerkOverigValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronLedenwerfcapagne)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['LedenwerfcampagneValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronLidWerftLid)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['LidwerftlidValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronTerugWerving)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['TerugwervingValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->Infoaanvraag)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['InfoaanvraagValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronEvenement)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['EvenementValue'];
        } elseif (in_array($this->contactRow['bronVeldValue'], $this->bronOverig)) {
            $this->contactParameters['custom_' . $this->cfBron['id']] = $this->bronOpties['OverigValue'];
        }
    }

	public function registerTags() {

		// Geroyeerd
		$geroyeerdQuery = $this->dbAdapter->query("SELECT * FROM `lidmaatschappen` WHERE `COL 2` = ".$this->contactRow['regnumber']." AND `COL 17` = 'AD_98'");
		if((is_object($geroyeerdQuery) && $geroyeerdQuery->num_rows) && $this->contactRow['status'] == 8) {
			try { civicrm_api3('EntityTag','create',array("entity_id" => $this->contactRow['regnumber'], "tag_id" => $this->tags->geroyeerd['id'])); } Catch (Exception $e) { echo "Tag Geroyeerd koppeling mislukt \r\n"; }
		}
		// Geweigerd
		$geweigerdQuery = $this->dbAdapter->query("SELECT * FROM `lidmaatschappen` WHERE `COL 2` = ".$this->contactRow['regnumber']." AND `COL 17` IN ('SP_50','SP_51','SP_52')");
		if((is_object($geweigerdQuery) && $geweigerdQuery->num_rows) && $this->contactRow['status'] == 8) {
			try { civicrm_api3('EntityTag','create',array("entity_id" => $this->contactRow['regnumber'], "tag_id" => $this->tags->geweigerd['id'])); } Catch (Exception $e) { echo "Tag Geweigerd koppeling mislukt \r\n"; }
		}

	}

}
new contacten(false, $argv);
?>
