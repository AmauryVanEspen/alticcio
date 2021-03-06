<?php
/*
 * Configuration
 */
$config->core_include("outils/mysql", "outils/langue", "outils/phrase");
$config->core_include("produit/sku", "produit/produit", "produit/attribut");

$sql = new Mysql($config->db());
$langue = new Langue($sql);
$id_langue = $langue->id($config->get("langue"));

$dirname = dirname(__FILE__).'/../traductions/';
$main_lg = 'fr_FR';

$menu->current('main/params/clean');

$titre_page = $dico->t('NettoyerBase');
$checked1 = "";
$checked2 = "";
$checked3 = "";
$checked4 = "";
$checked5 = "";
$checked6 = "";
$checked7 = "";
$checked8 = "";
$checked9 = "";
$checked10 = "";
$checked11 = "";
$checked12 = "";
if (isset($_POST['ctrl'])) {
	switch($_POST['ctrl']) {
		case "doublons":
			$checked1 = ' checked="checked" ';
			break;
		case "vide":
			$checked2 = ' checked="checked" ';
			break;
		case "orphelinsku":
			$checked3 = ' checked="checked" ';
			break;
		case "orphelinprod":
			$checked4 = ' checked="checked" ';
			break;
		case "skusansfamille":
			$checked5 = ' checked="checked" ';
			break;
		case "exportseloncouleurs":
			$checked6 = ' checked="checked" ';
			break;
		case "exportselonral":
			$checked7 = ' checked="checked" ';
			break;
		case "produitsperso":
			$checked8 = ' checked="checked" ';
			break;
		case "produitsstandard":
			$checked9 = ' checked="checked" ';
			break;
		case "produitssurmesure":
			$checked10 = ' checked="checked" ';
			break;
		case "produitsconfig":
			$checked11 = ' checked="checked" ';
			break;
		case "produitseul":
			$checked12 = ' checked="checked" ';
			break;
	}
}
$main = <<<HTML
<form name="checkdb" action="" method="post" >
	<fieldset>
	<legend>{$dico->t('ControlerElementsSuivants')}</legend> 
	<p><label for="ctrl"><input type="radio" name="ctrl" value="doublons" $checked1 /> {$dico->t('DoublonsSKU')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="vide" $checked2 /> {$dico->t('SkuSansNom')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="orphelinsku" $checked3 /> {$dico->t('SkuOrphelins')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="orphelinprod" $checked4 /> {$dico->t('ProduitsOrphelins')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="skusansfamille" $checked5 /> {$dico->t('SkuSansFamille')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="exportseloncouleurs" $checked6 /> {$dico->t('ExportSelonCouleurs')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="exportselonral" $checked7 /> {$dico->t('ExportSelonRal')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="produitsperso" $checked8 /> {$dico->t('ListeProduitsPerso')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="produitsstandard" $checked9 /> {$dico->t('ListeProduitsStandard')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="produitssurmesure" $checked10 /> {$dico->t('ListeProduitsSurMesure')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="produitsconfig" $checked11 /> {$dico->t('ListeProduitsConfigurables')}</label></p>
	<p><label for="ctrl"><input type="radio" name="ctrl" value="produitseul" $checked12 /> {$dico->t('ListeProduitsSeuls')}</label></p>
	<p><input type="submit" name="envoyer" value="{$dico->t('Envoyer')}" /></p>
	</fieldset>
</form>
HTML;


/*
 * **************  FONCTIONS
 */
function create_table_admin($tab_lines, $ctrl) {
	$html = <<<HTML
<form name="upd_datas" action="" method="post">
	<fieldset>
	<legend></legend>
	<select name="operation" class="">
		<option value="">...</option>
		<option value="delete">Supprimer</option>
		<option value="disable">Désactiver</option>
		<option value="change1">Modifier Famille de vente</option>
	</select>
	<input type="submit" class="" name="valider" value="valider" />
	<input type="hidden" name="ctrl" value="$ctrl" />
	<table name="" class="" >
	<caption>test</caption>
	<thead>
		<tr>
			<th>id</th>
			<th>designation</th>
			<th>statut</th>
			<th>voir</th>
		</tr>
	</thead>
	<tbody>
HTML;

	foreach($tab_lines as $line) {
		$html .= <<<HTML
<tr>
	<td><input type="checkbox" name="ref[]" value="{$line['id']}" /></td>
	<td>{$line['designation']}</td>
	<td>{$line['statut']}</td>
	<td>voir</td>
</tr>
HTML;
	}
	
	$html .= <<<HTML
	</tbody>
	</table>
</form>
HTML;
	return $html;
}









/*
 * **************  DOUBLONS DES SKU
 * On contrôle s'il y a des doublons sku/ref_ultralog
 * Si oui, on propose un lien vers page 601 pour nettoyer la base
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "doublons") {
	$main .= '<h3>'.$dico->t('DoublonsSKU').'</h3>';
	$q = "SELECT id, ref_ultralog FROM dt_sku ORDER BY ref_ultralog";
	$rs = $sql->query($q);
	$doublons = array();
	$prev = 0;
	while($row = $sql->fetch($rs)) {
		if ($prev == $row['ref_ultralog'] AND $prev > 0) {
			$doublons[] = $row['ref_ultralog'];
		}
		$prev = $row['ref_ultralog'];
	}
	$doublons_bis = array_unique($doublons);
	if (count($doublons_bis) > 0) {
		$main .= '<p>'.str_replace('%nbre%', count($doublons_bis), $dico->t('DoublonsSkuUltralog')).'</p>';
		$main .= '<p>'.$page->l($dico->t('NettoyerLaBase'), $url->make('CleanSku')).'</p>';
	}
}

/*
 * **************  SKU VIDES
 * On recherche les SKU qui n'ont pas de noms (phrase vide)
 * Si oui, on les liste avec un lien vers leur fiche
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "vide") {
	$main .= '<h3>'.$dico->t('SkuSansNom').'</h3>';
	$q = "SELECT id, phrase_ultralog FROM dt_sku";
	$rs = $sql->query($q);
	$sku_vides = array();
	while($row = $sql->fetch($rs)) {
		if ($row['phrase_ultralog'] == 0) {
			$sku_vides[] = array( "sku"=>$row['id'], "lg"=>"");
		}
		else {
			$q1 = "SELECT phrase, id_langues FROM dt_phrases WHERE id=".$row['phrase_ultralog'];
			$rs1 = $sql->query($q1);
			while($row1 = $sql->fetch($rs1)) {
				$row1['phrase'] = trim($row1['phrase']);
				$row1['phrase'] = str_replace(' ', '', $row1['phrase']);
				if (empty($row1['phrase'])) {
					$sku_vides[] = array( "sku"=>$row['id'], "lg"=>$row1['id_langues']);
				}
			}
		}
	}
	if (count($sku_vides)>0) {
		$main .= '<ul>';
		foreach($sku_vides as $key => $values) {
			$main .= '<li><a href="'.$url2->make("Produits", array("type" => "sku", "action" => "edit", "id" => $values["sku"])).'">'.$values['sku'].' /// '.$values['lg'].'</li>';
		}
		$main .= '</ul>';
	}
}


/*
 * **************  SKU ORPHELINS
 * On recherche les SKU orphelins (liés à aucun produit)
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "orphelinsku") {
	
	if (isset($_POST['ref'])) {
		foreach($_POST['ref'] as $ref) {
			switch($_POST['operation']) {
				case "delete":
					$langue = new Langue($sql);
					$id_langues = $langue->id($config->get("langue"));
					$phrase = new Phrase($sql);
					$sku = new Sku($sql, $phrase, $id_langues);
					$sku->load($ref);
					$sku->id = ref;
					$sku->delete();
					break;
			}
		}
		
	}
	echo 'bbb';
	
	$main .= '<h3>'.$dico->t('SkuOrphelins').'</h3>';
	//$main .= '<ul>';
	$q = "SELECT s.id, s.ref_ultralog, s.actif, ph.phrase, v.id AS variantes, a.id AS accessoires 
			FROM dt_sku AS s
			INNER JOIN dt_phrases AS ph
			ON ph.id = s.phrase_ultralog AND ph.id_langues = 1 
			LEFT JOIN dt_sku_variantes AS v 
			ON v.id_sku = s.id 
			LEFT JOIN dt_sku_accessoires AS a 
			ON a.id_sku = s.id ";
	$rs = $sql->query($q);
	$tab_lines = array();
	while($row = $sql->fetch($rs)) {
		if ($row['variantes'] == NULL AND $row['accessoires'] == NULL) {
			$tab_lines[] = array('id'=> $row["id"], "designation" => $row['phrase'].' (ref UL : '.$row['ref_ultralog'].')', "statut" => $row['actif']);
			//$main .= '<li><a href="'.$url2->make("Produits", array("type" => "sku", "action" => "edit", "id" => $row["id"])).'">'.$row['id'].' '.$row['phrase'].' (ref UL : '.$row['ref_ultralog'].')</li>';
		}
	}
	//$main .= '</ul>';
	$main .= create_table_admin($tab_lines, "orphelinsku");
}


/*
 * **************  PRODUITS ORPHELINS
 * On recherche les produits orphelins (liés à aucun sku)
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "orphelinprod") {
	$main .= '<h3>'.$dico->t('ProduitsOrphelins').'</h3>';
	$main .= '<ul>';
	$q = "SELECT p.id, v.id AS variantes, c.id AS composants, a.id AS accessoires 
			FROM dt_produits AS p
			LEFT JOIN dt_sku_variantes AS v 
			ON v.id_produits = p.id 
			LEFT JOIN dt_sku_accessoires AS a 
			ON a.id_produits = p.id
			LEFT JOIN dt_sku_composants AS c 
			ON c.id_produits = p.id ";
	$rs = $sql->query($q);
	while($row = $sql->fetch($rs)) {
		if ($row['variantes'] == NULL AND $row['composants'] == NULL AND $row['composants'] == NULL) {
			$main .= '<li><a href="'.$url2->make("Produits", array("type" => "produits", "action" => "edit", "id" => $row["id"])).'">'.$row['id'].'</li>';
		}
	}
	$main .= '</ul>';
}



/*
 * **************  SKU SANS FAMILLE
 * On recherche les sku avec une famille de vente à 0
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "skusansfamille") {
	$main .= '<h3>'.$dico->t('SkuSansFamille').'</h3>';
	$main .= '<ul>';
	$q = "SELECT s.id, s.ref_ultralog, ph.phrase 
			FROM dt_sku AS s
			INNER JOIN dt_phrases AS ph
			ON ph.id = s.phrase_ultralog 
			AND ph.id_langues = ".$id_langue."
			AND s.id_familles_vente = 0 ";
	$rs = $sql->query($q);
	while($row = $sql->fetch($rs)) {
		$main .= '<li><a href="'.$url2->make("Produits", array("type" => "sku", "action" => "edit", "id" => $row["id"])).'">'.$row['id'].' '.$row['phrase'].' ('.$row['ref_ultralog'].')</li>';
	}
	$main .= '</ul>';
}



/*
 * **************  SKU AVEC COULEUR DANS LE TEXTE
 * On recherche les sku avec une couleur dans leur designation
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "exportseloncouleurs") {
	$liste_couleurs = array();
	$q_couleurs = "SELECT c.id, ph.phrase FROM dt_couleurs AS c INNER JOIN dt_phrases AS ph ON c.phrase_couleurs = ph.id";
	$rs_couleurs = $sql->query($q_couleurs);
	while($row_couleurs = $sql->fetch($rs_couleurs)) {
		$liste_couleurs[$row_couleurs['id']] = $row_couleurs['phrase'];
	}
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$main .= '<h3>'.$dico->t('ExportSelonCouleurs').'</h3>';
	foreach($liste_couleurs as $key => $couleur) {
		$q = "SELECT s.id, s.ref_ultralog, ph.phrase
			FROM dt_sku AS s
			INNER JOIN dt_phrases AS ph ON ph.id = s.phrase_ultralog
			AND s.id_couleurs = 0
			AND ph.id_langues = ".$id_langue." 
			AND ph.phrase LIKE '% ".$couleur." %'";
		$rs = $sql->query($q);
		if (mysql_num_rows($rs) > 0) {
			$filename = "export_sku_couleurs_".$couleur."_".time().".csv";
			$fp = fopen($dir.$filename,"a+");
			fputs($fp, "id;ref;designation;couleur;validation\r\n");
			while($row = $sql->fetch($rs)) {
				fputs($fp, $row['id'].";".$row['ref_ultralog'].";".$row['phrase'].";".$couleur.";0\r\n");
			}
			fclose($fp);
			$main .= '<p class="message">'.$page->l($dico->t('ExporterFichierCouleurs').$couleur, $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
		}
	}
}



/*
 * **************  SKU AVEC RAL DANS LE TEXTE
 * On recherche les sku avec un RAL dans leur designation
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "exportselonral") {
	$liste_ral = array();
	$q_ral = "SELECT id FROM dt_ral";
	$rs_ral = $sql->query($q_ral);
	while($row_ral = $sql->fetch($rs_ral)) {
		$liste_ral[] = $row_ral['id'];
	}
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$main .= '<h3>'.$dico->t('ExportSelonRal').'</h3>';
	foreach($liste_ral as $key => $ral) {
		$q = "SELECT s.id, s.ref_ultralog, ph.phrase
			FROM dt_sku AS s
			INNER JOIN dt_phrases AS ph ON ph.id = s.phrase_ultralog
			AND s.id_ral = 0
			AND ph.id_langues = ".$id_langue." 
			AND (ph.phrase LIKE '% RAL".$ral." %' OR ph.phrase LIKE '% RAL ".$ral." %' )";
		$rs = $sql->query($q);
		if (mysql_num_rows($rs) > 0) {
			$filename = "export_sku_couleurs_".$ral."_".time().".csv";
			$fp = fopen($dir.$filename,"a+");
			fputs($fp, "id;ref;designation;ral;validation\r\n");
			while($row = $sql->fetch($rs)) {
				fputs($fp, $row['id'].";".$row['ref_ultralog'].";".$row['phrase'].";".$ral.";0\r\n");
			}
			fclose($fp);
			$main .= '<p class="message">'.$page->l($dico->t('ExporterFichierCouleurs').$ral, $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
		}
	}
}



/*
 * **************  LISTE DES PRODUITS PERSONNALISES
 * On liste tous les produits dont le type est personnalisable
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "produitsperso") {
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$filename = "export_produits_perso_".time().".csv";
	$fp = fopen($dir.$filename,"a+");
	fputs($fp, "id;phrase\r\n");
	$main = '<h3>'.$dico->t('ListeProduitsPerso').'</h3>';
	$main .= '<ul>';
	$liste_prod = array();
	$q_prod = "SELECT p.id, ph.phrase 
					FROM dt_phrases AS ph
					INNER JOIN dt_produits AS p 
					ON p.phrase_nom = ph.id AND ph.id_langues = 1 
					AND p.id_types_produits = 2";
	$rs_prod = $sql->query($q_prod);
	while($row_prod = $sql->fetch($rs_prod)) {
		$main .= '<li>'.$row_prod['id'].' '.$row_prod['phrase'].'</li>';
		fputs($fp, $row_prod['id'].";".$row_prod['phrase']."\r\n");
	}
	fclose($fp);
	$main .= '</ul>';
	$main .= '<p class="message">'.$page->l($dico->t('ExportCSV'), $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
}

/*
 * **************  LISTE DES PRODUITS STANDARDS
 * On liste tous les produits dont le type est standard
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "produitsstandard") {
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$filename = "export_produits_standard_".time().".csv";
	$fp = fopen($dir.$filename,"a+");
	fputs($fp, "id;phrase\r\n");
	$main = '<h3>'.$dico->t('ListeProduitsStandard').'</h3>';
	$main .= '<ul>';
	$liste_prod = array();
	$q_prod = "SELECT p.id, ph.phrase 
					FROM dt_phrases AS ph
					INNER JOIN dt_produits AS p 
					ON p.phrase_nom = ph.id AND ph.id_langues = 1 
					AND p.id_types_produits = 1";
	$rs_prod = $sql->query($q_prod);
	while($row_prod = $sql->fetch($rs_prod)) {
		$main .= '<li>'.$row_prod['id'].' '.$row_prod['phrase'].'</li>';
		fputs($fp, $row_prod['id'].";".$row_prod['phrase']."\r\n");
	}
	fclose($fp);
	$main .= '</ul>';
	$main .= '<p class="message">'.$page->l($dico->t('ExportCSV'), $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
}

/*
 * **************  LISTE DES PRODUITS SUR MESURE
 * On liste tous les produits dont le type est sur mesure
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "produitssurmesure") {
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$filename = "export_produits_surmesure_".time().".csv";
	$fp = fopen($dir.$filename,"a+");
	fputs($fp, "id;phrase\r\n");
	$main = '<h3>'.$dico->t('ListeProduitsSurMesure').'</h3>';
	$main .= '<ul>';
	$liste_prod = array();
	$q_prod = "SELECT p.id, ph.phrase 
					FROM dt_phrases AS ph
					INNER JOIN dt_produits AS p 
					ON p.phrase_nom = ph.id AND ph.id_langues = 1 
					AND p.id_types_produits = 4";
	$rs_prod = $sql->query($q_prod);
	while($row_prod = $sql->fetch($rs_prod)) {
		$main .= '<li>'.$row_prod['id'].' '.$row_prod['phrase'].'</li>';
		fputs($fp, $row_prod['id'].";".$row_prod['phrase']."\r\n");
	}
	fclose($fp);
	$main .= '</ul>';
	$main .= '<p class="message">'.$page->l($dico->t('ExportCSV'), $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
}

/*
 * **************  LISTE DES PRODUITS CONFIGURABLES
 * On liste tous les produits dont le type est configurable
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "produitsconfig") {
	$dir = $config->get("medias_path")."www/medias/docs/csv/";
	$filename = "export_produits_config_".time().".csv";
	$fp = fopen($dir.$filename,"a+");
	fputs($fp, "id;phrase\r\n");
	$main = '<h3>'.$dico->t('ListeProduitsConfigurables').'</h3>';
	$main .= '<ul>';
	$liste_prod = array();
	$q_prod = "SELECT p.id, ph.phrase 
					FROM dt_phrases AS ph
					INNER JOIN dt_produits AS p 
					ON p.phrase_nom = ph.id AND ph.id_langues = 1 
					AND p.id_types_produits = 3";
	$rs_prod = $sql->query($q_prod);
	while($row_prod = $sql->fetch($rs_prod)) {
		$main .= '<li>'.$row_prod['id'].' '.$row_prod['phrase'].'</li>';
		fputs($fp, $row_prod['id'].";".$row_prod['phrase']."\r\n");
	}
	fclose($fp);
	$main .= '</ul>';
	$main .= '<p class="message">'.$page->l($dico->t('ExportCSV'), $config->get('medias_url').'medias/docs/csv/'.$filename).'</p>';
}



/*
 * **************  SKU SANS FAMILLE
 * On recherche les sku avec une famille de vente à 0
 */
if (isset($_POST['ctrl']) AND $_POST['ctrl'] == "produitseul") {
	$main .= '<h3>'.$dico->t('ListeProduitsSeuls').'</h3>';
	$main .= '<ul>';
	$q = "SELECT p.id, ph.phrase
        FROM dt_produits AS p
        INNER JOIN dt_phrases AS ph
        ON ph.id = p.phrase_nom AND ph.id_langues = ".$id_langue." 
        INNER JOIN dt_catalogues_categories_produits AS ccp
        ON ccp.id_produits = p.id 
        INNER JOIN dt_catalogues_categories AS cc
        ON cc.id = ccp.id_catalogues_categories AND cc.id_catalogues = 34";
	$rs = $sql->query($q);
	while($row = $sql->fetch($rs)) {
		$q2 = "SELECT id FROM dt_produits_complementaires WHERE id_produits = ".$row['id'];
		$rs2 = $sql->query($q2);
		$q3 = "SELECT id FROM dt_produits_similaires WHERE id_produits = ".$row['id'];
		$rs3 = $sql->query($q3);
		if (mysql_num_rows($rs2) == 0 AND mysql_num_rows($rs3) == 0) {
			$main .= '<li><a href="'.$url2->make("Produits", array("type" => "produits", "action" => "edit", "id" => $row["id"])).'">'.$row['id'].' '.$row['phrase'].'</li>';
		}
	}
	$main .= '</ul>';
}

/*
 * *******************  LISTE DES PRIX EN DOUBLE POUR UN MEME SKU
 * *******************
 */
/*
 * SELECT *
FROM `dt_prix` AS p1
INNER JOIN `dt_prix` AS p2 ON p1.id_sku = p2.id_sku
AND p1.id_catalogues = p2.id_catalogues
AND p1.id <> p2.id
 */		
?>