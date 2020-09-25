<pre><?php

/*
	MediaWiki API Demos
	MIT license
*/

$endPoint = "https://pt.wikipedia.org/w/api.php";
require __DIR__.'/../../credenciais.php';
loginRequest(getLoginToken(), $usernameBQ, $passwordBQ);

////////////////////////////////////////////////////////////////////////////////////////
//
//	Abuselog
//
////////////////////////////////////////////////////////////////////////////////////////

//Define parâmetros para consulta do log do filtro
$params_abuselog = [
	"action" => "query",
	"format" => "json",
	"list" => "abuselog",
	"aflfilter" => "180",
	"afllimit" => "20",
	"aflend" => "2020-10-04T12:00:00.000Z",
	"aflprop" => "user"
];

//Executa cURL e retorna array
$ch_abuselog = curl_init($endPoint."?".http_build_query($params_abuselog));
curl_setopt($ch_abuselog, CURLOPT_RETURNTRANSFER, true);
$output_abuselog = curl_exec($ch_abuselog);
curl_close($ch_abuselog);
$result_abuselog = json_decode( $output_abuselog, true );

//Loop para gerar faixas de IP
$lista = array();
foreach ($result_abuselog["query"]["abuselog"] as $k => $v) {

	//IPv4
	if (preg_match("/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/", $v["user"])) {
		$ipexp = explode('.', $v["user"]);
		$user = $ipexp['0'].".".$ipexp['1'].".0.0/16";

	//IPv6
	} elseif (preg_match("/[0-9A-F]{1,4}(?:\:[0-9A-F]{1,4}){7}/", $v["user"])) {
		$ipexp = explode(':', $v["user"]);
		if (count($ipexp['1'] < 4)) {
			$x = 0;
		} else {
			$x = substr($ipexp['1'], 0, 1);
			if ($x = "1") $x = "0";
			if ($x = "3") $x = "2";
			if ($x = "5") $x = "4";
			if ($x = "7") $x = "6";
			if ($x = "9") $x = "8";
			if ($x = "B") $x = "A";
			if ($x = "D") $x = "C";
			if ($x = "F") $x = "E";
		}
		$user = $ipexp['0'].":".$x."000::/19";

	//Não é IP
	} else {
		continue;
	}

	//Insere faixa de IP na array $lista
	array_push($lista, $user);
}

//Remove duplicatas
array_unique($lista);

////////////////////////////////////////////////////////////////////////////////////////
//
//	Blocks
//
////////////////////////////////////////////////////////////////////////////////////////

//Define parâmetros para consulta de bloqueios
foreach ($lista as $user_block) {
	$params_blocks = [
		"action" => "query",
		"format" => "json",
		"list" => "blocks",
		"bkip" => $user_block,
		"bkprop" => "id"
	];

	//Executa cURL e retorna array
	$ch_blocks = curl_init($endPoint."?".http_build_query($params_blocks));
	curl_setopt($ch_blocks, CURLOPT_RETURNTRANSFER, true);
	$output_blocks = curl_exec($ch_blocks);
	curl_close($ch_blocks);
	$result_blocks = json_decode($output_blocks, true);

	//Verifica se faixa de IP está bloqueada. Caso não esteja, realiza o bloqueio.
	if (!isset($result_blocks["query"]["blocks"]['0']['id'])) {
		block(getCSRFToken(), $user_block);
	};
}

//Apaga cookie
unlink("./cookie.txt");

////////////////////////////////////////////////////////////////////////////////////////
//
//	Funções
//
////////////////////////////////////////////////////////////////////////////////////////

function getLoginToken() {
	global $endPoint;

	$params1 = [
		"action" => "query",
		"meta" => "tokens",
		"type" => "login",
		"format" => "json"
	];

	$ch = curl_init($endPoint."?".http_build_query($params1));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");
	$output = curl_exec($ch);
	curl_close($ch);

	return json_decode($output, true)["query"]["tokens"]["logintoken"];
}

function loginRequest($logintoken, $bot_user, $bot_pass) {
	global $endPoint;

	$params2 = [
		"action" => "login",
		"lgname" => $bot_user,
		"lgpassword" => $bot_pass,
		"lgtoken" => $logintoken,
		"format" => "json"
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $endPoint);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params2));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");
	$output = curl_exec($ch);
	curl_close($ch);
}

function getCSRFToken() {
	global $endPoint;

	$params3 = [
		"action" => "query",
		"meta" => "tokens",
		"format" => "json"
	];

	$ch = curl_init($endPoint."?".http_build_query($params3));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");
	$output = curl_exec($ch);
	curl_close($ch);

	return json_decode($output, true)["query"]["tokens"]["csrftoken"];
}

function block($csrftoken, $user_block) {
	global $endPoint;

	$params4 = [
		"action" => "block",
		"user" => $user_block,
		"expiry" => "infinite",
		"reason" => "{{deslogado}}: Para contribuir, faça login ou crie uma conta.",
		"anononly" => true,
		"nocreate" => false,
		"partial" => true,
		"namespacerestrictions" => "0|2|4|6|10|14|100|104|710|828",
		"token" => $csrftoken,
		"format" => "json"
	];

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $endPoint);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params4));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "./cookie.txt");
	curl_setopt($ch, CURLOPT_COOKIEFILE, "./cookie.txt");
	$output = curl_exec($ch);
	curl_close($ch);
	echo ($output);
}