<?php

if (isset($_SERVER['HTTP_ORIGIN'])) {
	header("Access-Control-Allow-Origin: ".$_SERVER['HTTP_ORIGIN']);
	header("Access-Control-Allow-Credentials: true");
}
if (isset($_GET['session_id']) and $_GET['session_id']) {
	session_id($_GET['session_id']);
}
session_start();

include dirname(__FILE__)."/../includes/config.php";

$config->core_include("api/api", "outils/mysql", "outils/phrase", "outils/langue", "outils/dico");
$config->core_include("produit/produit");

$sql = new Mysql($config->db());

$api = new API("api_", $sql);
$api->config['perso_fichier_path'] = $config->get("medias_path")."files/personnalisations/";
$api->prepare($config->get("base_url"));

$langue = new Langue($sql);
$code_langue = $api->info('language');
$id_langues = $langue->id($code_langue);
$dico = new Dico($code_langue);
$dico->add($config->get("base_path")."/core/traductions");
$dico->add($config->get("base_path")."/traductions");

$api->errors(array(
	101 => "Clé incorrecte",
	102 => "Cette clé est désactivée",
	103 => "Cette fonctionnalité n'est pas disponible",
	104 => "Cette fonctionnalité n'est pas autorisée",
	105 => "Paramètre(s) manquant(s) pour cette fonctionnalité",
	106 => "IP non autorisée",
	107 => "Domaine non autorisé",

	201 => "Référence Ultralog invalide",
	202 => "Aucun produit n'a cette référence pour variante",

	301 => "Référence catalogue invalide",
	302 => "Référence catégorie invalide",
	303 => "Référence produit invalide",
	304 => "Aucun catalogue accessible",
));

$data = $api->execute();

$include_path = dirname(__FILE__)."/../";

include include_path("pages/page.php");

if (isset($_GET['format']) and !isset($data['error'])) {
	$format = "formats/{$_GET['format']}";
	if (file_exists(include_path($format))) {
		$theme = isset($_GET['theme']) ? $_GET['theme'] : "default";
		$file = "$format/$theme/{$api->func()}.php";
		if (!file_exists(include_path($file))) {
			$file = "$format/default/{$api->func()}.php";
		}
		if (file_exists(include_path($file))) {
			include include_path($file);
		}
	}
}

if (!isset($output)) {
	$output = json_encode($data);
}

if (isset($_GET['callback'])) {
	echo "{$_GET['callback']}({$output});";
}
else {
	echo $output;
}

function include_path($path) {
	global $include_path;
	if (file_exists($include_path."../../api/".$path)) {
		return $include_path."../../api/".$path;
	}
	else {
		return $include_path.$path;
	}
}


// Gestion des widgets

function widget($w) {
	global $widget, $config, $dico;

	$widget = $w;

	$dico->add($config->get("base_path")."/www/widgets/$widget/traductions");
}

function widget_html($html, $vars = array()) {
	global $widget, $config;

	if (!$html) {
		return "";
	}

	$file = $config->get("base_path")."/www/widgets/$widget/html/$html.html";
	if (file_exists($file)) {
		$html = file_get_contents($file);
	}
	else {
		return "no file $file";
	}

	$html = preg_replace_callback("/\{html:([^\}]+)\}/", "html_preg_replace_callback", $html);
	$html = preg_replace_callback("/\{dico:([^\}]+)\}/", "dico_preg_replace_callback", $html);
	$html = preg_replace("/\s+/", " ", $html);
	$html = str_replace("> <", "><", $html);

	foreach ($vars as $key => $value) {
		$html = str_replace("{".$key."}", $value, $html);
	}

	return $html;
}

function widget_image($image) {
	global $widget, $config;

	return "http://".$_SERVER['HTTP_HOST'].$config->get("base_url")."widgets/$widget/images/$image";
}

function dico_preg_replace_callback($matches) {
	global $dico;

	return $dico->t($matches[1]);
}

function html_preg_replace_callback($matches) {
	global $dico;

	return widget_html($matches[1]);
}
