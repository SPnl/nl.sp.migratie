<?php
$contacten 			= FALSE;
$documenthistorie	= FALSE;
$lidmaatschappen	= FALSE;
$geostelsel			= FALSE;
$spanning 			= FALSE;
$toezeggingen 		= FALSE;
$tribunes			= FALSE;
$cursussen			= FALSE;
$kaderfuncties		= TRUE;
$speciaal			= FALSE;
$tomaatnet			= FALSE;
$ledendag			= FALSE;
$regioconferenties	= FALSE;
$bezorggebieden		= FALSE;

/* Contacten */
if($contacten) {
	echo "Start Contact - ".date("d-m-Y H:i")."\r\n";
	$pointer = 0;
	for($i = 0; $i < 295; $i++) {
		$output = shell_exec("php ./contacten.php pointer=".$pointer." >> ./logs/contacten.txt");
		echo $output."\r\n";
		$pointer += 500;
	}
	echo "Eind Contact - ".date("d-m-Y H:i")."\r\n";
}

/* Documenthistorie */
if($documenthistorie) {
	echo "Start Documenthistorie - ".date("d-m-Y H:i")."\r\n";
	$pointer = 0;
	for($i = 0; $i < 71; $i++) {
		$output = shell_exec("php ./documenthistorie.php pointer=".$pointer." >> ./logs/documenthistorie.txt");
		echo $output."\r\n";
		$pointer += 2500;
	}
	echo "Eind Documenthistorie - ".date("d-m-Y H:i")."\r\n";
}

/* Lidmaatschappen */
if($lidmaatschappen) {
	echo "Start Lidmaatschappen - ".date("d-m-Y H:i")."\r\n";
	$pointer = 500;
	for($i = 1; $i < 250; $i++) {
		echo "Start Lidmaatschappen Batch ".$i." - ".date("d-m-Y H:i")."\r\n";
		$output = shell_exec("php ./lidmaatschappen.php pointer=".$pointer." >> ./logs/lidmaatschappen.txt");
		echo $output."\r\n";
		$pointer += 500;
		echo "Eind Lidmaatschappen Batch ".$i." - ".date("d-m-Y H:i")."\r\n";
	}
	echo "Eind Lidmaatschappen - ".date("d-m-Y H:i")."\r\n";
}

/* Geostelsel */
if($geostelsel) {
	$output = shell_exec("php ./geostelsel.php > ./logs/geostelsel.txt");
	echo $output."\r\n";
}

/* SPanning */
if($spanning) {
	$pointer = 0;
	for($i = 0; $i < 11; $i++) {
		if($i < 3) {
			$output = shell_exec("php ./spanning.php pointer=".$pointer." mode=betaald > ./logs/betaald-".$i.".txt");
		} else {
			if($i == 3) $pointer = 0;
			$output = shell_exec("php ./spanning.php pointer=".$pointer." mode=gratis > ./logs/gratis-".$i.".txt");
		}
		echo $output."\r\n";
		$pointer += 500;
	}
}

/* Toezeggingen */
if($toezeggingen) {
	$pointer = 0;
	for($i = 0; $i < 5; $i++) {
		$output = shell_exec("php ./toezeggingen.php pointer=".$pointer." > ./logs/toezeggingen.txt");
		echo $output."\r\n";
		$pointer += 500;
	}
}

/* Tribunes */
if($tribunes) {
	$pointer = 0;
	for($i = 0; $i < 6; $i++) {
		$output = shell_exec("php ./tribunes.php pointer=".$pointer." >> ./logs/tribunes.txt");
		echo $output."\r\n";
		$pointer += 500;
	}
}

/* Cursussen */
if($cursussen) {
	$output = shell_exec("php ./cursussen.php > ./logs/cursussen.txt");
	echo $output."\r\n";
}

/* Regioconferenties */
if($regioconferenties) {
	$output = shell_exec("php ./regioconferenties.php > ./logs/regioconferenties.txt");
	echo $output."\r\n";
}

/* Kaderfuncties */
if($kaderfuncties) {
	$pointer = 0;
	for($i = 0; $i < 55; $i++) {
		$output = shell_exec("php ./kaderfuncties.php pointer=".$pointer." >> ./logs/kaderfuncties.txt");
		echo $output."\r\n";
		$pointer += 500;
	}
}

/* SPeciaal */
if($speciaal) {
	$output = shell_exec("php ./speciaal.php > ./logs/speciaal.txt");
	echo $output."\r\n";
}

/* Tomaatnet */
if($tomaatnet) {
	$output = shell_exec("php ./tomaatnet.php > ./logs/tomaatnet.txt");
	echo $output."\r\n";
}

/* Ledendag */
if($ledendag) {
	$output = shell_exec("php ./ledendag.php > ./logs/ledendag.txt");
	echo $output."\r\n";
}

/* Bezorggebieden */
if($bezorggebieden) {
	$output = shell_exec("php ./bezorggebieden.php > ./logs/bezorggebieden.txt");
	echo $output."\r\n";
}


?>
