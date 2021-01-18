<?php
echo "<pre>";

$isocode  = "pt";
$template = "Predefinição:Dados da pandemia de COVID-19";
$sumario  = "bot: Atualizando estatísticas";
$toadd    = "Territórios a adicionar";
$toremove = "Territórios a remover";
$log      = "log";

function refparser($ref) {
	$de = array(
		"|url-status=live",
		" January 2020",
		" March 2020",
		" April 2020",
		" May 2020",
		" June 2020",
		" July 2020",
		" August 2020",
		" September 2020",
		" October 2020",
		" November 2020",
		" December 2020"
	);
	$para = array(
		"",
		"-01-2020",
		"-03-2020",
		"-04-2020",
		"-05-2020",
		"-06-2020",
		"-07-2020",
		"-08-2020",
		"-09-2020",
		"-10-2020",
		"-11-2020",
		"-12-2020"
	); 
	$refparsed = str_replace($de, $para, preg_replace('/date=([0-9]{4})-([0-9]{2})-([0-9]{2})/', 'date=$3-$2-$1', trim($ref)));
	return $refparsed;
}

include './bin/globals.php';
include './bin/covid-19-mundo.php';