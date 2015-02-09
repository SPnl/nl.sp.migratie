<?php
/* Script om eenmalig contributies nogmaals te importeren */

/*
  SET foreign_key_checks = 0;
  DELETE FROM civicrm_odoo_entity WHERE entity = 'civicrm_contribution';
  TRUNCATE TABLE civicrm_line_item;
  TRUNCATE TABLE civicrm_membership_payment;
  TRUNCATE TABLE civicrm_contribution;
  TRUNCATE TABLE civicrm_entity_financial_trxn;
  TRUNCATE TABLE civicrm_financial_item;
  TRUNCATE TABLE civicrm_financial_trxn;
  SET foreign_key_checks = 1;
 */

require('./baseclass.php');
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'on');

class Contributions extends baseclass {

	private $counter = 0;

	public function __construct($testMode, $arguments = '') {
		parent::baseclass($testMode);
	}

	public function import() {

		$limit = 1000;

		for ($i = 0; $i < 242000; $i += $limit) {

			echo date('d-m-Y H:i:s') . " - Getting memberships " . $i . "-" . ($i + $limit) . "...\n";

			/* Get memberships */
			if (!$this->CIVIAPI->Membership->get([
				'options' => ['limit' => $limit, 'offset' => $i],
			])
			) {
				echo "CiviCRM fetch API error: " . $this->CIVIAPI->errorMsg();
			}
			$records = $this->CIVIAPI->result();

			foreach ($records->values as $record) {

				$this->counter ++;
				$findStatuses  = '';
				$financialType = '';

				$chk = $this->CIVIAPI->MembershipPayment->get([
					'membership_id' => $record->id,
				]);
				$res = $this->CIVIAPI->result();
				if (is_object($res) && $res->count > 0) {
					echo "X Skipping membership {$record->id}, payment already exists. ({$this->counter})\n";
					continue;
				}

				switch ($record->membership_type_id) {
					case 1: // Lid SP
						$findStatuses  = '"LIDNIN", "LIDNPO", "LIDNAC", "LIDGIN", "LIDGPO", "LIDGAC", "LIDVIN", "LIDVPO"';
						$financialType = 6;
						$this->doLidmaatschap($record, $findStatuses, $financialType);
						break;
					case 2: // Lid SP en ROOD
						$findStatuses  = '"LIDNIN", "LIDNPO", "LIDNAC", "LIDGIN", "LIDGPO", "LIDGAC", "LIDVIN", "LIDVPO"';
						$financialType = 7;
						$this->doLidmaatschap($record, $findStatuses, $financialType);
						break;
					case 3: // Lid ROOD
						$findStatuses  = '"ROODIN", "ROODAC"';
						$financialType = 5;
						$this->doLidmaatschap($record, $findStatuses, $financialType);
						break;
					case 4: // Abonnee (audio-)Tribune betaald
					case 6:
						$findStatuses  = '"ABNAC", "ABNIN", "ABNPO"';
						$financialType = 10;
						$this->doLidmaatschap($record, $findStatuses, $financialType);
						break;
					case 5: // Abonnee Tribune proef
						$findStatuses  = '"ABOPR"';
						$financialType = 10;
						$this->doLidmaatschap($record, $findStatuses, $financialType);
						break;
					case 10: // Abonnee Spanning betaald
						$financialType = 8;
						$this->doSpanning($record, $financialType);
						break;
					case 12: // Donateurs
						$financialType = 1;
						$this->doDonateurs($record, $financialType);
						break;
					case 11: // Abonnee speciaal, doen we niks mee
					case 8: // Gratis e.d., doen we niks mee
					case 9:
					case 13:
						echo "X Skipping membership with type {$record->membership_type_id}. ({$this->counter})\n";
						break;
				}

			}

		}
	}

	private function doLidmaatschap($record, $findStatuses, $financialType) {

		$q = 'SELECT * FROM lidmaatschappen WHERE `COL 2` = "' . (int)$record->contact_id . '" AND `COL 12` = "' . $record->join_date . '" AND `COL 28` IN (' . $findStatuses . ')';

		$lidm = $this->dbAdapter->query($q);

		if (!is_object($lidm) || $lidm->num_rows == 0) {
			echo "X Zoeken naar soort-van-match voor " . $record->id . " / " . $record->contact_id . ".\n";

			$q    = 'SELECT * FROM lidmaatschappen WHERE `COL 2` = "' . (int)$record->contact_id . '" AND `COL 13` IS NULL AND `COL 28` IN (' . $findStatuses . ')';
			$lidm = $this->dbAdapter->query($q);
		}

		if (!is_object($lidm) || $lidm->num_rows == 0) {
			echo "X Lidmaatschap niet gevonden voor " . $record->id . " / " . $record->contact_id . ".\n";
			return false;
		}

		$rlidm = $lidm->fetch_assoc();

		$bedrag   = $rlidm['COL 21'];
		$betwijze = $rlidm['COL 14'];

		if (!$bedrag || (int)$bedrag == 0) {
			if ($rlidm['COL 28'] == 'LIDNAC') {
				$bedrag = 24;
			} else {
				echo "X Skipping record, geen bedrag ingevuld voor sp_data.lidmaatschappen {$rlidm['COL 1']}. ({$this->counter})\n";

				return false;
			}
		}

		switch ($betwijze) {
			case 'IN':
				$betwijze_code = 10;
				break;
			case 'PO';
				$betwijze_code = 8;
				break;
			case 'AC':
				$betwijze_code = 9;
				$bedrag        = $bedrag / 4;
				break;
			default:
				$betwijze_code = 10;
				echo "X Let op, geen geldige betaalwijze voor sp_data.lidmaatschappen {$rlidm['COL 1']}, ingesteld op incasso.\n";
				break;
		}

		$this->createContribution($record->id, $record->contact_id, $betwijze_code, $bedrag, $financialType);
	}

	private function doSpanning($record, $financialType) {

		$q = 'SELECT * FROM spanning WHERE `COL 2` = "' . (int)$record->contact_id . '" AND `COL 8` = "' . $record->join_date . '"';

		$lidm = $this->dbAdapter->query($q);

		if (is_object($lidm) && $lidm->num_rows == 1) {

			$rlidm         = $lidm->fetch_assoc();
			$betwijze_code = 9;
			$bedrag        = ceil($rlidm['COL 10'] / 4);

			$this->createContribution($record->id, $record->contact_id, $betwijze_code, $bedrag, $financialType, 'spanning');

		} else {
			echo "X Spanning-abonnement niet gevonden voor " . $record->id . " / " . $record->contact_id . ".\n";
		}
	}

	private function doDonateurs($record, $financialType) {
		$q = 'SELECT * FROM toezeggingen WHERE `COL 2` = "' . (int)$record->contact_id . '" AND `COL 4` = "' . $record->join_date . '"';

		$lidm = $this->dbAdapter->query($q);

		if (is_object($lidm) && $lidm->num_rows == 1) {

			$rlidm = $lidm->fetch_assoc();

			$bedrag = $rlidm['COL 3'];

			switch ($rlidm['COL 7']) {
				case 'IN':
					$betwijze_code = 10;
					break;
				case 'PO';
					$betwijze_code = 8;
					break;
				case 'AC':
					$betwijze_code = 9;
					$bedrag        = $bedrag / 4;
					break;
				default:
					$betwijze_code = 10;
					echo "X Let op, geen geldige betaalwijze voor sp_data.lidmaatschappen {$rlidm['COL 1']}, ingesteld op incasso.\n";
					break;
			}

			$this->createContribution($record->id, $record->contact_id, $betwijze_code, $bedrag, $financialType, 'toezegging');

		} else {
			echo "X Toezegging niet gevonden voor " . $record->id . " / " . $record->contact_id . ".\n";
		}
	}

	private function createContribution($recordId, $contactId, $paymentInstrument, $amount, $financialType, $name = 'lidmaatschappen') {

		echo "O Creating {$name} contribution for contact id {$contactId} with payment instrument {$paymentInstrument}, amount {$amount}, financial type {$financialType}. ({$this->counter})\n";

		$_contribution = civicrm_api3('Contribution', 'create', [
			'financial_type_id'     => $financialType,
			'total_amount'          => $amount,
			'contact_id'            => $contactId,
			'source'                => "Migratie {$name}",
			'receive_date'          => '2014-01-01 00:00:00',
			'payment_instrument_id' => $paymentInstrument,
		]);
		civicrm_api3('MembershipPayment', 'create', array(
			'membership_id'   => $recordId,
			'contribution_id' => $_contribution['id']
		));
	}
}


$c = new Contributions(false);
$c->import();