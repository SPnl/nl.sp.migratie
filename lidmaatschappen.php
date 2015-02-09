<?php
include('./baseclass.php');
class lidmaatschappen extends baseclass
{
	public
	$pointer,
	$lmsObject,
	$customFields,
	$financialTypes,
	$optionValues,
	$piAcceptGiro,
	$pipiIncasso,
	$piPeriodiek,
	$cfSPGeschenkGehad,
	$cfSPGeschenkType,
	$cfROODGeschenkGehad,
	$cfROODGeschenkType,
	$cfOpzegReden,
	$cfBron,
	$cfGezinslid,
	$resultSet,
	$debugString,
	$mandaatNummer = array(),
	$doubleMembership = array(),
	$bronWebsite = array(8, 11, 12, 66, 104, 164, 204, 218, 220, 240, 353, 400, 666),
	$bronVerkiezingen = array(101, 132, 194, 196, 208, 229, 236, 294, 300, 301, 304, 350, 351),
	$bronActieLandelijk = array(105, 109, 110, 111, 112, 115, 133, 134, 135, 137, 138, 140, 141, 142, 167, 175, 176, 181, 183, 193, 243, 245, 246, 292, 302, 307, 343, 344),
	$bronAfdeling = array(108, 118, 216, 221, 228, 233, 234, 244, 308),
	$bronTelefonisch = array(100, 117, 160, 205, 217, 219, 232, 271),
	$bronZOKrant = array(297),
	$bronOuderenkrant = array(237, 241, 266, 268, 303, 306),
	$bronSolidair = array(128, 130, 144, 285),
	$bronROOD = array(58, 131, 139, 174, 177, 178, 179, 255, 256, 320, 321, 322, 323, 324, 325, 329, 330, 331, 332, 333, 334, 336, 337, 338, 339, 341, 342),
	$bronTribuneBon = array(114, 121, 198, 207, 239, 345),
	$bronDrukwerkOverig = array(107, 113, 116, 119, 120, 122, 127, 149, 154, 155, 156, 157, 158, 161, 162, 163, 165, 166, 168, 172, 173, 180, 184, 185, 186, 187, 188, 189, 190, 191, 192, 212, 227, 235, 247, 248, 260, 262, 273, 275, 277, 288, 298, 299, 335, 347, 352),
	$bronLedenwerfcapagne = array(106, 123, 136, 150, 151, 152, 153, 195, 197, 200, 201, 202, 203, 211, 213, 214, 230, 231, 249, 250, 265, 267, 276, 278, 291, 354, 355),
	$bronLidWerftLid = array(124, 126, 182, 242, 280, 346),
	$bronTerugWerving = array(251, 289, 444, 500),
	$Infoaanvraag = array(102, 103, 129, 146, 159, 170, 171, 199, 209, 210, 215, 223, 226, 305),
	$bronEvenement = array(77, 79, 80, 81, 82, 83, 169, 270, 310, 2000),
	$bronOverig = array(0, 50, 51, 52, 53, 54, 55, 56, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 98, 99, 125, 143, 145, 147, 148, 206, 222, 224, 225, 238, 261, 272, 274, 290, 309, 311, 313, 340, 377, 1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011, 1012, 1013, 1014, 1015, 1016, 1017, 1018, 1019, 1020, 1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030, 1031, 1032, 1034, 6001, 6002, 6003, 9000, 9001);

	public function __construct($testMode, $arguments) {
		parent::baseclass($testMode);
		$this->fetchDoubles();
		$this->fetchLidmaatschappen();
		$this->fetchCustom();
		$this->fetchFinancialTypes();
		$pointer = explode("=", $arguments[1]);
		echo "Query pointer ".$pointer[1]."\r\n";
    	$this->resultSet = $this->dbAdapter->query("
			SELECT `lidmaatschappen`.*, `c`.`COL_13` as 'gepersonaliseerde_brief'
			FROM `lidmaatschappen`
			LEFT JOIN `contacten` as `c` ON `lidmaatschappen`.`COL 2` = `c`.`COL_1`
			WHERE `COL 28` IN ('ROODAC','ROODIN','LIDGAC','LIDGIN','LIDNAC','LIDNIN','LIDNPO','LIDVIN','LIDVPO')
			ORDER BY `COL 12` ASC, `COL 13` DESC
			LIMIT ".$pointer[1].", 500
		");
		$this->migreer();
		$this->storeDoubles();
	}

	public function fetchDoubles() {
		$this->doubleMembership = json_decode(file_get_contents("./doubles.php"));
	}

	public function storeDoubles() {
		file_put_contents("./doubles.php", json_encode($this->doubleMembership));
	}

	public function fetchLidmaatschappen() {
		$objLMS              		= new stdClass();
		$objLMS->lidsp       		= civicrm_api3('MembershipType', 'getsingle', array("name" => "Lid SP"));
		$objLMS->lidrood     		= civicrm_api3('MembershipType', 'getsingle', array("name" => "Lid Rood"));
		$objLMS->lidroodsp   		= civicrm_api3('MembershipType', 'getsingle', array("name" => "Lid SP en Rood"));
		$objLMS->lidroodsp   		= civicrm_api3('MembershipType', 'getsingle', array("name" => "Lid SP en Rood"));
		$objLMS->tribBladGratis  	= civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Blad-Tribune Gratis"));
		$objLMS->tribAudioGratis  	= civicrm_api3('MembershipType', 'getsingle', array("name" => "Abonnee Audio-Tribune Gratis"));
		$this->lmsObject     		= $objLMS;
    }

	public function fetchCustom() {
		try{
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
			$customGroupMigratie									= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Migratie_Lidmaatschappen"));
			$customGroupGeschenkSP								= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Welkomstcadeau_SP_lid"));
			$customGroupGeschenkROOD							= civicrm_api3('CustomGroup', 'getsingle', array("name" => "Welkomstcadeau_ROOD_lid"));
			$this->cfSPGeschenkGehad							= civicrm_api3('CustomField', 'getsingle', array("name" => "Heeft_cadeau_gehad","custom_group_id" => $customGroupGeschenkSP['id']));
			$this->cfSPGeschenkType								= civicrm_api3('CustomField', 'getsingle', array("name" => "Cadeau","custom_group_id" => $customGroupGeschenkSP['id']));
			$this->cfROODGeschenkGehad							= civicrm_api3('CustomField', 'getsingle', array("name" => "Heeft_cadeau_gehad","custom_group_id" => $customGroupGeschenkROOD['id']));
			$this->cfROODGeschenkType							= civicrm_api3('CustomField', 'getsingle', array("name" => "Cadeau","custom_group_id" => $customGroupGeschenkROOD['id']));
			$this->cfOpzegReden 									= civicrm_api3('CustomField', 'getsingle', array("name" => "Reden","custom_group_id" => $customGroupMigratie['id']));
			$this->cfGepersonaliseerdeBrief					= civicrm_api3('CustomField', 'getsingle', array("name" => "gepersonaliseerde_brief","custom_group_id" => $customGroupMigratie['id']));
			$this->cfBron 											= civicrm_api3('CustomField', 'getsingle', array("name" => "Bron","custom_group_id" => $customGroupMigratie['id']));
			$this->cfGezinslid									= civicrm_api3('CustomField', 'getsingle', array("name" => "Gezinslid","custom_group_id" => $customGroupMigratie['id']));
			$cfBronOpties 											= civicrm_api3('OptionValue', 'get', array("option_group_id" => $this->cfBron['option_group_id']));
			foreach ($cfBronOpties['values'] as $value) {
				switch ($value['label']) {
					case "Website": $this->bronOpties['WebsiteValue'] 								= $value['value']; break;
					case "Verkiezingen": $this->bronOpties['VerkiezingenValue'] 				= $value['value']; break;
					case "Actie landelijk": $this->bronOpties['ActieLandelijkValue'] 			= $value['value']; break;
					case "Afdeling": $this->bronOpties['AfdelingValue'] 							= $value['value']; break;
					case "Telefonisch": $this->bronOpties['TelefonischValue'] 					= $value['value']; break;
					case "ZO-krant": $this->bronOpties['ZOkrantValue'] 							= $value['value']; break;
					case "Ouderenkrant": $this->bronOpties['OuderenkrantValue'] 				= $value['value']; break;
					case "Solidair-krant": $this->bronOpties['SolidairValue'] 					= $value['value']; break;
					case "ROOD": $this->bronOpties['RoodValue'] 										= $value['value']; break;
					case "Tribune-bon": $this->bronOpties['TribuneBonValue'] 					= $value['value']; break;
					case "Drukwerk overig": $this->bronOpties['DrukwerkOverigValue'] 			= $value['value']; break;
					case "Ledenwerfcampagne": $this->bronOpties['LedenwerfcampagneValue'] 	= $value['value']; break;
					case "Lid-werft-lid": $this->bronOpties['LidwerftlidValue'] 				= $value['value']; break;
					case "Terugwerving": $this->bronOpties['TerugwervingValue'] 				= $value['value']; break;
					case "Infoaanvraag": $this->bronOpties['InfoaanvraagValue'] 				= $value['value']; break;
					case "Evenement": $this->bronOpties['EvenementValue'] 						= $value['value']; break;
					case "Overig": $this->bronOpties['OverigValue'] 								= $value['value']; break;
				}
			}
		} catch (Exception $e) {
			die($e);
		}
    }

	public function fetchFinancialTypes() {
		$financialTypes 		= new stdClass();
		$financialTypes->sp 	= civicrm_api3('financialType', 'getsingle', array("name" => "Contributie SP"));
		$financialTypes->rood 	= civicrm_api3('financialType', 'getsingle', array("name" => "Contributie ROOD"));
		$financialTypes->sprood = civicrm_api3('financialType', 'getsingle', array("name" => "Contributie SP+ROOD"));
		$this->financialTypes 	= $financialTypes;
	}

	public function migreer() {
		while ($_lmsObject = $this->resultSet->fetch_assoc()) {

			// Reset mandaatNummer
			$this->mandaatNummer = array();

			// Controleren of sub al niet verwerkt is
			if (in_array($_lmsObject['COL 1'], $this->doubleMembership)) {
				echo "-> ".$_lmsObject['COL 1']." is al verwerkt, word overgeslagen. \r\n";
				echo $this->debugString."\r\n\r\n";
				continue;
			}

			// Tegenovergestelde lidmaatschap types bepalen
			$teMatchenMST = (in_array($_lmsObject['COL 28'], array('LIDGAC','LIDGIN','LIDNAC','LIDNIN','LIDNPO','LIDVIN','LIDVPO'))) ? "'ROODIN','ROODAC'" : "'LIDGAC','LIDGIN','LIDNAC','LIDNIN','LIDNPO','LIDVIN','LIDVPO'";

			// Query opbouwen
			$q = "
				SELECT * FROM lidmaatschappen
				WHERE `COL 2` = '".$_lmsObject['COL 2']."'
				AND `COL 12` = '".$_lmsObject['COL 12']."'
			";
			if($_lmsObject['COL 13'] == "") {
				$q .= "AND `COL 13` IS NULL";
			}else {
				$q .= "AND `COL 13` = '".$_lmsObject['COL 13'];
			}
			$q .= "
				AND `COL 28` IN (".$teMatchenMST.")
			";
					
			// Hybrid controle - Exacte Match
			$qExacteMatch = $this->dbAdapter->query($q);
			if(is_object($qExacteMatch) && $qExacteMatch->num_rows == 1) {
				echo "if 1\r\n";
				// Exacte match gevonden
				echo "-> Exacte query match (".$qExacteMatch->num_rows.")\r\n";

				// Parse result
				$rExacteMatch = $qExacteMatch->fetch_assoc();

				// Match record in verwerkte array zetten
				$this->doubleMembership[] = $rExacteMatch['COL 1'];

				// mandaat nummer ophalen
				if($_lmsObject['COL 39'] != 0 || $rExacteMatch['COL 39'] != 0) {
					if($_lmsObject['COL 39'] > $rExacteMatch['COL 39']) {
						$mandaatNummer = $_lmsObject['COL 39'];
					} else {
						$mandaatNummer = $rExacteMatch['COL 39'];
					}
				}

				// Bedragen bepalen
				if($_lmsObject['COL 21'] != 0 || $rExacteMatch['COL 21'] != 0) {
					if($rExacteMatch['COL 21'] != 0) {
						$bedrag = $rExacteMatch['COL 21'];
					} else {
						$bedrag = $_lmsObject['COL 21'];
					}
				} else {
					$mandaatNummer = 0;
				}

				// Methode bepalen
				$methode = substr($rExacteMatch['COL 28'], -2);

				// Welkomstgeschenken array maken
				$welkomstGeschenken = array('sp' => $_lmsObject['COL 9'], 'rood' => $rExacteMatch['COL 9']);

				// Registreren van SP+ROOD lidmaatschap
				$this->registreerLidmaatschap($_lmsObject, $this->lmsObject->lidroodsp['id'], $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

			} else {
				// Geen exacte match gevonden, controleren op enkel lidmaatschap
				$qEnkelLMS = $this->dbAdapter->query("SELECT COUNT(*) as `aantal` FROM `lidmaatschappen` WHERE `COL 2` = ".$_lmsObject['COL 2']." AND `COL 28` IN ('ROODAC','ROODIN','LIDGAC','LIDGIN','LIDNAC','LIDNIN','LIDNPO','LIDVIN','LIDVPO')");
				$rEnkelLMS = $qEnkelLMS->fetch_assoc();
				if(is_object($qEnkelLMS) && $rEnkelLMS['aantal'] == 1) {
					// Enkel LMS gevonden
					echo "-> Enkele query match (".$qEnkelLMS->num_rows.")\r\n";

					// Match record in verwerkte array zetten
					$this->doubleMembership[] = $_lmsObject['COL 1'];

					// Mandaatnummer bepalen
					$mandaatNummer = $_lmsObject['COL 39'];

					// Welkomstgeschenken array maken
					$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

					// Bedrag bepalen
					$bedrag = $_lmsObject['COL 21'];

					// Methode bepalen
					$methode = substr($_lmsObject['COL 28'], -2);

					// MST bepalen
					$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

					// Registreren van origineel lidmaatschap
					$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

				} else {
					// Geen enkel LMS gevonden, controleren op een start inside / end outside LMS
					$qStartInside = $this->dbAdapter->query("
						SELECT * FROM lidmaatschappen
						WHERE `COL 2` = '".$_lmsObject['COL 2']."'
						AND `COL 12` > '".$_lmsObject['COL 12']."'
						AND (`COL 13` > '".$_lmsObject['COL 13']."' OR `COL 13` IS NULL)
						AND '".$_lmsObject['COL 13']."' > `COL 12`
						AND `COL 28` IN (".$teMatchenMST.")
					");
					if(is_object($qStartInside) && $qStartInside->num_rows == 1) {
						// We hebben een start inside match
						echo "-> Start inside query match (".$qStartInside->num_rows.")\r\n";

						// Parse resultaat
						$rStartInside = $qStartInside->fetch_assoc();

						// Match record in verwerkte array zetten
						$this->doubleMembership[] = $rStartInside['COL 1'];

						// Veranderen van originele eind datum naar start datum van start inside
						$_lmsObject['COL 13'] = $rStartInside['COL 12'];

						// mandaatNummer string maken
						$mandaatNummer = $_lmsObject['COL 39'];

						// Bedrag bepalen maken
						$bedrag = $_lmsObject['COL 21'];

						// Methode bepalen
						$methode = substr($_lmsObject['COL 28'], -2);

						// Welkomstgeschenken array maken
						$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

						// MST bepalen
						$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

						// Registreren van origineel lidmaatschap
						$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

						/* Verwerking van start inside */

						// mandaat nummer ophalen
						if($_lmsObject['COL 39'] != 0 || $rStartInside['COL 39'] != 0) {
							if($_lmsObject['COL 39'] > $rStartInside['COL 39']) {
								$mandaatNummer = $_lmsObject['COL 39'];
							} else {
								$mandaatNummer = $rStartInside['COL 39'];
							}
						} else {
							$mandaatNummer = 0;
						}

						// Bedragen bepalen
						if($_lmsObject['COL 21'] != 0 || $rStartInside['COL 21'] != 0) {
							if($rStartInside['COL 21'] != 0) {
								$bedrag = $rStartInside['COL 21'];
							} else {
								$bedrag = $_lmsObject['COL 21'];
							}
						}

						// Methode bepalen
						$methode = substr($rStartInside['COL 28'], -2);

						// Welkomstgeschenken array maken
						$welkomstGeschenken = array('sp' => $_lmsObject['COL 9'], 'rood' => $rStartInside['COL 9']);

						// Registreren van SP+ROOD lidmaatschap
						$this->registreerLidmaatschap($rStartInside, $this->lmsObject->lidroodsp['id'], $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);
					} else {
						// Geen start inside gevonden, zoeken naar fit inside LMS
						$eindDatum = ($_lmsObject['COL 13'] == "") ? '2099-12-31' : $_lmsObject['COL 13'];
						$qFitInside = $this->dbAdapter->query("
							SELECT * FROM lidmaatschappen
							WHERE `COL 2` = '".$_lmsObject['COL 2']."'
							AND `COL 12` > '".$_lmsObject['COL 12']."'
							AND `COL 12` < '".$eindDatum."'
							AND `COL 13` <= '".$eindDatum."'
							AND '".$_lmsObject['COL 12']."' < `COL 13`
							AND `COL 28` IN (".$teMatchenMST.")
						");
						if(is_object($qFitInside) && $qFitInside->num_rows == 1) {
							// We hebben een fit inside match
							echo "-> Fit inside query match (".$qFitInside->num_rows.")\r\n";

							// Originele eind datum bewaren
							$orgineleEindDatum = $_lmsObject['COL 13'];

							// Parse resultaat
							$rFitInside = $qFitInside->fetch_assoc();

							// Match record in verwerkte array zetten
							$this->doubleMembership[] = $rFitInside['COL 1'];

							// Veranderen van originele eind datum naar start datum van start inside
							$_lmsObject['COL 13'] = $rFitInside['COL 12'];

							// Mandaat nummer bepalen
							$mandaatNummer = $_lmsObject['COL 39'];

							// Bedrag bepalen
							$bedrag = $_lmsObject['COL 21'];

							// Methode bepalen
							$methode = substr($_lmsObject['COL 28'], -2);

							// Welkomstgeschenken array maken
							$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

							// MST bepalen
							$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

							// Registreren van origineel lidmaatschap
							$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

							/* Verwerking van fit inside */

							// mandaat nummer ophalen
							if($_lmsObject['COL 39'] != 0 || $rFitInside['COL 39'] != 0) {
								if($_lmsObject['COL 39'] > $rFitInside['COL 39']) {
									$mandaatNummer = $_lmsObject['COL 39'];
								} else {
									$mandaatNummer = $rFitInside['COL 39'];
								}
							} else {
								$mandaatNummer = 0;
							}

							// Bedragen bepalen
							if($_lmsObject['COL 21'] != 0 || $rFitInside['COL 21'] != 0) {
								if($rFitInside['COL 21'] != 0) {
									$bedrag = $rFitInside['COL 21'];
								} else {
									$bedrag = $_lmsObject['COL 21'];
								}
							}

							// Methode bepalen
							$methode = substr($rFitInside['COL 28'], -2);

							// Welkomstgeschenken array maken
							$welkomstGeschenken = array('sp' => $_lmsObject['COL 9'], 'rood' => $rFitInside['COL 9']);

							// Registreren van SP+ROOD lidmaatschap
							$this->registreerLidmaatschap($rFitInside, $this->lmsObject->lidroodsp['id'], $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

							/* Origineel lidmaatschap afmaken */

							// Controleren of het originele lidmaatschap nog doorloopt, anders afbreken
							if($orgineleEindDatum != $rFitInside['COL 13']) {

								// Veranderen van originele start datum naar eind datum fit inside
								$_lmsObject['COL 12'] = $rFitInside['COL 13'];

								// Veranderen van originele eind datum naar start datum van fit inside
								$_lmsObject['COL 13'] = $orgineleEindDatum;

								// mandaatNummer string maken
								$mandaatNummer = $_lmsObject['COL 39'];

								// Welkomstgeschenken array maken
								$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

								// Bedragen array maken
								$bedrag = $_lmsObject['COL 21'];

								// Methode bepalen
								$methode = substr($_lmsObject['COL 28'], -2);

								// MST bepalen
								$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

								// Registreren van origineel lidmaatschap
								$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);
								
							}
							
						} else {
							// Geen fit inside record gevonden, zoeken naar start gelijk, einde eerder LMS
							$qStartGelijk = $this->dbAdapter->query("
								SELECT * FROM lidmaatschappen
								WHERE `COL 2` = '".$_lmsObject['COL 2']."'
								AND `COL 12` = '".$_lmsObject['COL 12']."'
								AND `COL 13` < '".$_lmsObject['COL 13']."'
								AND `COL 28` IN (".$teMatchenMST.")
							");
							if(is_object($qStartGelijk) && $qStartGelijk->num_rows == 1) {
								// We hebben een start gelijk match
								echo "-> Start gelijk query match (".$qStartGelijk->num_rows.")\r\n";

								// Originele eind datum opslaan
								$origineleEindDatum = $_lmsObject['COL 13'];

								// Parse result
								$rStartGelijk = $qStartGelijk->fetch_assoc();

								// Match record in verwerkte array zetten
								$this->doubleMembership[] = $rStartGelijk['COL 1'];

								// Eind datum aanpassen
								$_lmsObject['COL 13'] = $rStartGelijk['COL 13'];

								// mandaat nummer ophalen
								if($_lmsObject['COL 39'] != 0 || $rStartGelijk['COL 39'] != 0) {
									if($_lmsObject['COL 39'] > $rStartGelijk['COL 39']) {
										$mandaatNummer = $_lmsObject['COL 39'];
									} else {
										$mandaatNummer = $rStartGelijk['COL 39'];
									}
								} else {
									$mandaatNummer = 0;
								}

								// Bedrag bepalen
								if($_lmsObject['COL 21'] != 0 || $rStartGelijk['COL 21'] != 0) {
									if($rStartGelijk['COL 21'] != 0) {
										$bedrag = $rStartGelijk['COL 21'];
									} else {
										$bedrag = $_lmsObject['COL 21'];
									}
								}

								// Methode bepalen
								$methode = substr($rStartGelijk['COL 28'], -2);

								// Welkomstgeschenken array maken
								$welkomstGeschenken = array('sp' => $_lmsObject['COL 9'], 'rood' => $rStartGelijk['COL 9']);

								// Registreren van SP+ROOD lidmaatschap
								$this->registreerLidmaatschap($_lmsObject, $this->lmsObject->lidroodsp['id'], $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

								/* Verwerking originele LMS */

								// Veranderen van start datum origineel naar eind datum start gelijk
								$_lmsObject['COL 12'] = $rStartGelijk['COL 13'];

								// Veranderen van veranderde eind datum naar originele eind datum
								$_lmsObject['COL 13'] = $origineleEindDatum;

								// mandaatNummer string maken
								$mandaatNummer = $_lmsObject['COL 39'];

								// Bedragen bepalen
								$bedrag = $_lmsObject['COL 21'];

								// Methode bepalen
								$methode = substr($_lmsObject['COL 28'], -2);

								// Welkomstgeschenken array maken
								$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

								// MST bepalen
								$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

								// Registreren van origineel lidmaatschap
								$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);
							} else {
								// Geen start gelijk gevonden, zoeken naar start inside eind gelijk LMS
								$q = "
									SELECT * FROM lidmaatschappen
									WHERE `COL 2` = '".$_lmsObject['COL 2']."'
									AND `COL 12` > '".$_lmsObject['COL 12']."'
								";
								if($_lmsObject['COL 13'] == "") {
									$q .= "
										AND `COL 13` IS NULL
									";
								} else {
									$q .= "
									AND `COL 13` = '".$_lmsObject['COL 13']."'
									AND '".$_lmsObject['COL 13']."' > `COL 12`
									";
								}
								$q .= "
									AND `COL 28` IN (".$teMatchenMST.")
								";
								$qStartInsideEindGelijk = $this->dbAdapter->query($q);
								if(is_object($qStartInsideEindGelijk) && $qStartInsideEindGelijk->num_rows == 1) {
									// We hebben een start inside match
									echo "-> Start inside, eind gelijk query match (".$qStartInsideEindGelijk->num_rows.")\r\n";

									// Parse resultaat
									$rStartInsideEindGelijk = $qStartInsideEindGelijk->fetch_assoc();

									// Match record in verwerkte array zetten
									$this->doubleMembership[] = $rStartInsideEindGelijk['COL 1'];

									// Veranderen van originele eind datum naar start datum van start inside
									$_lmsObject['COL 13'] = $rStartInsideEindGelijk['COL 12'];

									// mandaatNummer string maken
									$mandaatNummer = $_lmsObject['COL 39'];

									// Welkomstgeschenken array maken
									$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

									// Bedragen bepalen
									$bedrag = $_lmsObject['COL 21'];

									// Methode bepalen
									$methode = substr($_lmsObject['COL 28'], -2);

									// MST bepalen
									$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

									// Aanmaken origineel LMS
									$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);

									/* Verwerking van SP+ROOD */

									// Welkomstgeschenken array maken
									$welkomstGeschenken = array('sp' => $_lmsObject['COL 9'], 'rood' => $rStartInsideEindGelijk['COL 9']);

									// mandaat nummer ophalen
									if($_lmsObject['COL 39'] != 0 || $rStartInsideEindGelijk['COL 39'] != 0) {
										if($_lmsObject['COL 39'] > $rStartInsideEindGelijk['COL 39']) {
											$mandaatNummer = $_lmsObject['COL 39'];
										} else {
											$mandaatNummer = $rStartInsideEindGelijk['COL 39'];
										}
									} else {
										$mandaatNummer = 0;
									}

									// Bedrag bepalen
									if($_lmsObject['COL 21'] != 0 || $rStartInsideEindGelijk['COL 21'] != 0) {
										if($rStartInsideEindGelijk['COL 21'] != 0) {
											$bedrag = $rStartInsideEindGelijk['COL 21'];
										} else {
											$bedrag = $_lmsObject['COL 21'];
										}
									}

									// Methode
									$methode = substr($rStartInsideEindGelijk['COL 28'], -2);

									// Registreren van SP+ROOD lidmaatschap
									$this->registreerLidmaatschap($rStartInsideEindGelijk, $this->lmsObject->lidroodsp['id'], $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);
								} else {
									// Alle andere opties hebben niks opgeleverd, normale afhandeling LMS gevonden
									echo "-> Normale verwerking (".$qEnkelLMS->num_rows.")\r\n";

									// Match record in verwerkte array zetten
									$this->doubleMembership[] = $_lmsObject['COL 1'];

									// Mandaatnummer bepalen
									$mandaatNummer = $_lmsObject['COL 39'];

									// Welkomstgeschenken array maken
									$welkomstGeschenken = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? array('rood' => $_lmsObject['COL 9']) : array('sp' => $_lmsObject['COL 9']);

									// Bedrag bepalen
									$bedrag = $_lmsObject['COL 21'];

									// Methode bepalen
									$methode = $_lmsObject['COL 14'];

									// MST bepalen
									$mst = (in_array($_lmsObject['COL 28'], array("ROODAC","ROODIN"))) ? $this->lmsObject->lidrood['id'] : $this->lmsObject->lidsp['id'];

									// Registreren van origineel lidmaatschap
									$this->registreerLidmaatschap($_lmsObject, $mst, $bedrag, $methode, $welkomstGeschenken, $mandaatNummer);
								}
							}
						}
					}
				}
			}
			echo $this->debugString."\r\n\r\n";
		}
	}

	private function registreerLidmaatschap($lmsObject, $mst, $bedrag, $methode = false, $welkomstGeschenken, $mandaatNummer) {

		// Start en eind datum bepalen
		$startDatum       = ($lmsObject['COL 12'] != '-  -') ? $lmsObject['COL 12'] : NULL;
		$eindDatum        = (!empty($lmsObject['COL 13'])) ? $lmsObject['COL 13'] : date("Y-m-d",strtotime("last day of march"));

		// Basis parameters opzetten voor lidmaatschap
		$_lmsParams  = array(
			'membership_contact_id' => $lmsObject['COL 2'],
			'join_date' => $startDatum,
			'membership_start_date' => $startDatum,
			'membership_end_date' => $eindDatum,
			'membership_type_id' => $mst,
			'custom_'.$this->cfBron['id'] => $this->fetchBronVeld($lmsObject['COL 20'])
		);

		// Eind datum controleren
		if(is_null($eindDatum)) {
			/* EIND DATUM VOOR DECEMBER Q4-2014 */
			$kwartaalDatum = new DateTime('last day of december');
			$_lmsParams['membership_end_date'] = $kwartaalDatum->format('Y-m-d');
		}

		// Gezinslid controle
		if(in_array($lmsObject['COL 28'], array("LIDGAC","LIDGIN"))) {
			$_lmsParams['custom_'.$this->cfGezinslid['id']] = 1;
		}

		// Mandaten aanmaken indien aanwezig
		if($mandaatNummer != "0") {
			$this->registeerMandaten($lmsObject['COL 2'], $mandaatNummer);
			$_lmsParams['custom_'.$this->customFields->member_mandaat_id['id']] = $this->mandaatNummer;
		}

		// Welkomstgeschenken registeren
		if($mst == $this->lmsObject->lidroodsp['id']) {
			if($welkomstGeschenken['sp'] > 0) {
				$_lmsParams['custom_'.$this->cfSPGeschenkGehad['id']] = 1;
				$_lmsParams['custom_'.$this->cfSPGeschenkType['id']] = $this->fetchWelkomstGeschenk($welkomstGeschenken['sp']);
			} else {
				$_lmsParams['custom_'.$this->cfSPGeschenkGehad['id']] = 0;
				$_lmsParams['custom_'.$this->cfSPGeschenkType['id']] = "Geen";
			}
			if($welkomstGeschenken['rood'] > 0) {
				$_lmsParams['custom_'.$this->cfROODGeschenkGehad['id']] = 1;
				$_lmsParams['custom_'.$this->cfROODGeschenkType['id']] = $this->fetchWelkomstGeschenk($welkomstGeschenken['rood']);
			} else {
				$_lmsParams['custom_'.$this->cfROODGeschenkGehad['id']] = 0;
				$_lmsParams['custom_'.$this->cfROODGeschenkType['id']] = "Geen";
			}
		} else if ($mst == $this->lmsObject->lidsp['id']) {
			if($welkomstGeschenken['sp'] > 0) {
				$_lmsParams['custom_'.$this->cfSPGeschenkGehad['id']] = 1;
				$_lmsParams['custom_'.$this->cfSPGeschenkType['id']] = $this->fetchWelkomstGeschenk($welkomstGeschenken['sp']);
			} else {
				$_lmsParams['custom_'.$this->cfSPGeschenkGehad['id']] = 0;
				$_lmsParams['custom_'.$this->cfSPGeschenkType['id']] = "Geen";
			}
		} else if ($mst == $this->lmsObject->lidrood['id']) {
			if($welkomstGeschenken['rood'] > 0) {
				$_lmsParams['custom_'.$this->cfROODGeschenkGehad['id']] = 1;
				$_lmsParams['custom_'.$this->cfROODGeschenkType['id']] = $this->fetchWelkomstGeschenk($welkomstGeschenken['rood']);
			} else {
				$_lmsParams['custom_'.$this->cfROODGeschenkGehad['id']] = 0;
				$_lmsParams['custom_'.$this->cfROODGeschenkType['id']] = "Geen";
			}
		}
		
		// Indien lidmaatschap verlopen, opzegreden registeren
		if(!is_null($eindDatum) && strtotime($eindDatum) < strtotime("today")) $_lmsParams['custom_' . $this->cfOpzegReden['id']] = $this->fetchOpzegReden($lmsObject['COL 17']);
		if(!is_null($eindDatum) && strtotime($eindDatum) < strtotime("today") && $lmsObject['gepersonaliseerde_brief'] == "2") $_lmsParams['custom_' . $this->cfGepersonaliseerdeBrief['id']] = 1;


		// Lidmaatschap registeren
		if (!$this->CIVIAPI->Membership->Create($_lmsParams)) {
			echo "-> Registratie mislukt bij record: " . $lmsObject['COL 1'] . "\r\n";
			echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
		} else {
			echo "-> Registratie gelukt voor record: " . $lmsObject['COL 1'] . "\r\n";
			$_lmsNummer = $this->CIVIAPI->lastResult->id;
			if($bedrag > 0) $this->registreerBetaling($_lmsNummer, $lmsObject['COL 2'], $bedrag, $methode, $mst);
			if(in_array($lmsObject['COL 11'], array(1,3,4))) $this->registreerTribune($lmsObject['COL 2'], $startDatum, $eindDatum, $lmsObject['COL 11']);
			$this->doubleMembership[] = $lmsObject['COL 1'];
		}

	}

	private function fetchBronVeld($bronCode) {
		if (in_array($bronCode, $this->bronWebsite)) {
            return $this->bronOpties['WebsiteValue'];
        } elseif (in_array($bronCode, $this->bronVerkiezingen)) {
            return $this->bronOpties['VerkiezingenValue'];
        } elseif (in_array($bronCode, $this->bronActieLandelijk)) {
            return $this->bronOpties['ActieLandelijkValue'];
        } elseif (in_array($bronCode, $this->bronAfdeling)) {
            return $this->bronOpties['AfdelingValue'];
        } elseif (in_array($bronCode, $this->bronTelefonisch)) {
            return $this->bronOpties['TelefonischValue'];
        } elseif (in_array($bronCode, $this->bronZOKrant)) {
            return $this->bronOpties['ZOkrantValue'];
        } elseif (in_array($bronCode, $this->bronOuderenkrant)) {
            return $this->bronOpties['OuderenkrantValue'];
        } elseif (in_array($bronCode, $this->bronSolidair)) {
            return $this->bronOpties['SolidairValue'];
        } elseif (in_array($bronCode, $this->bronROOD)) {
            return $this->bronOpties['RoodValue'];
        } elseif (in_array($bronCode, $this->bronTribuneBon)) {
            return $this->bronOpties['TribuneBonValue'];
        } elseif (in_array($bronCode, $this->bronDrukwerkOverig)) {
            return $this->bronOpties['DrukwerkOverigValue'];
        } elseif (in_array($bronCode, $this->bronLedenwerfcapagne)) {
            return $this->bronOpties['LedenwerfcampagneValue'];
        } elseif (in_array($bronCode, $this->bronLidWerftLid)) {
            return $this->bronOpties['LidwerftlidValue'];
        } elseif (in_array($bronCode, $this->bronTerugWerving)) {
            return $this->bronOpties['TerugwervingValue'];
        } elseif (in_array($bronCode, $this->Infoaanvraag)) {
            return $this->bronOpties['InfoaanvraagValue'];
        } elseif (in_array($bronCode, $this->bronEvenement)) {
            return $this->bronOpties['EvenementValue'];
        } elseif (in_array($bronCode, $this->bronOverig)) {
            return $this->bronOpties['OverigValue'];
        } else {
		   return $this->bronOpties['OverigValue'];
		}
	}

	private function fetchWelkomstGeschenk($geschenkID) {
		$qWelkomstGeschenk = $this->dbAdapter->query("SELECT `COL 2` FROM `welkomstgeschenk` WHERE `COL 1` = '".$geschenkID."'");
		if(is_object($qWelkomstGeschenk) && $qWelkomstGeschenk->num_rows == 1) {
			$rWelkomstGeschenk = $qWelkomstGeschenk->fetch_assoc();
			return ($rWelkomstGeschenk) ? $rWelkomstGeschenk['COL 2'] : "Onbekend";
		} else {
			return "Onbekend";
		}
	}

	private function fetchOpzegReden($opzegRedenCode) {
		switch($opzegRedenCode) {
			default:
			case "0": 		return "Onbekend"; break;
			case "AD_88": 	return "Geen SP-Rood koppeling"; break;
			case "AD_89": 	return "Rood uitschr 28 jr"; break;
			case "AD_94": 	return "Correctie"; break;
			case "AD_97": 	return "Contrib.achterstand"; break;
			case "AD_98": 	return "Royement"; break;
			case "PO_40": 	return "Politiek"; break;
			case "PR_80": 	return "Privé"; break;
			case "PR_82": 	return "Financieel"; break;
			case "PR_84": 	return "Verhuizing"; break;
			case "PR_88": 	return "Overleden"; break;
			case "SP_50": 	return "Lokaal"; break;
			case "SP_61": 	return "Landelijk"; break;
			case "AD_444": 	return "Contrib.achterstand"; break;
			case "AD_95": 	return "Correctie"; break;
			case "AD_96": 	return "Correctie"; break;
			case "AD_99": 	return "Correctie"; break;
			case "PO_30": 	return "Politiek"; break;
			case "PO_31": 	return "Politiek"; break;
			case "PO_32": 	return "Politiek"; break;
			case "PO_33": 	return "Politiek"; break;
			case "PO_34": 	return "Politiek"; break;
			case "PO_35": 	return "Politiek"; break;
			case "PO_41": 	return "Politiek"; break;
			case "PO_42": 	return "Politiek"; break;
			case "PO_43": 	return "Politiek"; break;
			case "PR_81": 	return "Privé"; break;
			case "PR_83": 	return "Privé"; break;
			case "PR_85": 	return "Privé"; break;
			case "SP_51": 	return "Lokaal"; break;
			case "SP_52": 	return "Lokaal"; break;
			case "SP_72": 	return "Lokaal"; break;
			case "SP_73": 	return "Lokaal"; break;
			case "SP_74": 	return "Lokaal"; break;
			case "SP_76": 	return "Lokaal"; break;
			case "SP_77": 	return "Lokaal"; break;
			case "SP_60": 	return "Landelijk"; break;
			case "SP_62": 	return "Landelijk"; break;
			case "SP_63": 	return "Landelijk"; break;
			case "SP_64": 	return "Landelijk"; break;
			case "SP_70": 	return "Landelijk"; break;
			case "SP_71": 	return "Landelijk"; break;
			case "SP_75": 	return "Landelijk"; break;
		}
	}

	private function registeerMandaten($contactID, $mandaatNummer) {
		echo $contactID;
		$qMandaten = $this->dbAdapter->query("SELECT * FROM `mandaten` WHERE `COL 1` = ".$mandaatNummer);
		if(!is_object($qMandaten)) return false;
		while($rMandaten = $qMandaten->fetch_assoc()) {
			try { $_mandaatGevonden = civicrm_api3('Contact','Get',array("id" => $contactID, "custom_".$this->customFields->mandaat_nr['id'] => $rMandaten['COL 15'])); } catch (Exception $e) { $_mandaatGevonden = NULL; }
			if(!isset($_mandaatGevonden['id'])) {
				$_mndDate = (!empty($rMandaten['COL 8']) && $rMandaten['COL 8'] != "-  -") ? $rMandaten['COL 8'] : NULL;
				$_expDate = (!empty($rMandaten['COL 10']) && $rMandaten['COL 10'] != "-  -") ? $rMandaten['COL 10'] : NULL;
				switch($rMandaten['COL 13']){
					case "NEW":  $_status = $this->optionValues->status_new['value']; break;
					case "FRST": $_status = $this->optionValues->status_frst['value']; break;
					case "RCUR": $_status = $this->optionValues->status_rcur['value']; break;
					case "OOFF": $_status = $this->optionValues->status_ooff['value']; break;
				}
				$_mandaatParams = array(
					'id' => $contactID,
					'custom_'.$this->customFields->status['id'] => $_status,
					'custom_'.$this->customFields->mandaat_nr['id'] => $rMandaten['COL 15'],
					'custom_'.$this->customFields->mandaat_datum['id'] => $_mndDate,
					'custom_'.$this->customFields->plaats['id'] => $rMandaten['COL 9'],
					'custom_'.$this->customFields->verval_datum['id'] => $_expDate,
					'custom_'.$this->customFields->IBAN['id'] => $rMandaten['COL 16'],
					'custom_'.$this->customFields->BIC['id'] => $rMandaten['COL 17']
				);
				if($this->CIVIAPI->Contact->Create($_mandaatParams)) {
					echo "-> Mandaat aangemaakt voor record: " . $rMandaten['COL 1'] . "\r\n";
					$this->mandaatNummer = $rMandaten['COL 15'];
				} else {
					echo "-> Aanmaken mandaat met mandaatnummer ".$rMandaten['COL 1']." is mislukt!\r\n";
					echo "-> ".$this->CIVIAPI->errorMsg()."\r\n";
				}
			} else {
				echo "-> Mandaat geskipt, bestaat al\r\n";
				$this->mandaatNummer = $rMandaten['COL 15'];
			}
		}
	}

	private function registreerBetaling($lmsNummer, $contactID, $bedrag, $methode, $mst) {
		try {
			if($mst == $this->lmsObject->lidsp['id']) {
				$financial_type_id = $this->financialTypes->sp['id'];
			} else if ($mst == $this->lmsObject->lidrood['id']) {
				$financial_type_id = $this->financialTypes->rood['id'];
			} else if ($mst == $this->lmsObject->lidroodsp['id']) {
				$financial_type_id = $this->financialTypes->sprood['id'];
			}
			switch($methode) {
				case "AC": $_pid = $this->optionValues->pid_ac['value']; break;
				case "PO": $_pid = $this->optionValues->pid_po['value']; break;
				case "IN": $_pid = $this->optionValues->pid_in['value']; break;
				default  : $_pid = NULL; break;
			}
			$_contribution = civicrm_api3('Contribution', 'create', array(
			  'financial_type_id' => $financial_type_id,
			  'total_amount' => round(($bedrag / 4),2),
			  'contact_id' => $contactID,
			  'source' => "Migratie Lidmaatschappen 2015",
			  'receive_date' => '2015-01-01 12:00:00',
			  'payment_instrument_id' => $_pid
			));
			civicrm_api3('MembershipPayment', 'create', array(
			  'membership_id' => $lmsNummer,
			  'contribution_id' => $_contribution['id']
			));
			echo "-> Contributie en Payment succesvol vastgelegd \r\n";
		} catch (Exception $e){
			echo "-> Contributie en Payment aanmaken mislukt! \r\n";
			echo "-> ".$e."\r\n";
		}
	}

	private function registreerTribune($contact_id, $startDatum, $eindDatum, $tribuneType) {

		// Tribune parameters
		$_lmsParams  = array(
			'membership_contact_id' => $contact_id,
			'join_date' => $startDatum,
			'membership_start_date' => $startDatum,
			'membership_end_date' => $eindDatum
		);

		// Tribune lidmaatschap bepalen
		if($tribuneType == "1") {
			$_lmsParams['membership_type_id'] = $this->lmsObject->tribBladGratis['id'];
		} else if ($tribuneType == "3") {
			$_lmsParams['membership_type_id'] = $this->lmsObject->tribAudioGratis['id'];
		} else if ($tribuneType == "4") {
			$_lmsParams['membership_type_id'] = $this->lmsObject->tribBladGratis['id'];
		}

		// Lidmaatschap registeren
		if (!$this->CIVIAPI->Membership->Create($_lmsParams)) {
			echo "-> Registratie mislukt bij record: " . $contact_id . "\r\n";
			echo $this->CIVIAPI->errorMsg() . "\r\n\r\n";
		} else {
			echo "-> Registratie gelukt voor record: " . $contact_id . "\r\n";
		}
	}
}
new lidmaatschappen(false, $argv);
?>
