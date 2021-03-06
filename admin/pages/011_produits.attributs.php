<?php

$menu->current('main/products/attributs');

$config->core_include("produit/attribut", "outils/form", "outils/mysql");
$config->core_include("outils/phrase", "outils/langue");
$config->core_include("outils/filter", "outils/pager");

$page->javascript[] = $config->core_media("jquery.min.js");
$page->javascript[] = $config->core_media("jquery.tablednd.js");
$page->javascript[] = $config->media("produit.js");

$page->jsvars[] = array(
	"edit_url" => $url2->make("current", array('action' => 'edit', 'id' => "")),	
);

$page->css[] = $config->media("produit.css");

$sql = new Mysql($config->db());

$langue = new Langue($sql);
$id_langues = $langue->id($config->get("langue"));

$phrase = new Phrase($sql);

$attribut = new Attribut($sql, $phrase, $id_langues);

$action = $url2->get('action');
if ($id = $url2->get('id')) {
	$attribut->load($id);
}

$pager = new Pager($sql, array(20, 30, 50, 100, 200), "pager_attributs");
$filter = new Filter($pager, array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 'a.id',
	),
	'phrase' => array(
		'title' => $dico->t('Nom'),
		'type' => 'contain',
		'field' => 'p.phrase',
	),
	'code' => array(
		'title' => $dico->t('Type'),
		'type' => 'select',
		'field' => 't.code',
		'options' => $attribut->types(true),
	),
), array(), "filter_attributs");

$pager_gammes = new Pager($sql, array(20, 30, 50, 100, 200), "pager_attributs_gammes");
$filter_gammes = new Filter($pager_gammes, array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 'g.id',
	),
	'phrase' => array(
		'title' => $dico->t('Nom'),
		'type' => 'contain',
		'field' => 'p.phrase',
	),
	'ref' => array(
		'title' => $dico->t('Reference'),
		'type' => 'contain',
		'field' => 'g.ref',
	),
	'classement' => array(
		'title' => $dico->t('Classement'),
		'type' => 'between',
		'field' => 'gam.classement',
		'form' => array(
			'name' => "gammes[%id%][classement]",
			'method' => 'input',
			'type' => 'text',
			'template' => '#{field}',
			'class' => "input-text-numeric",
		),
	),
), array_keys($attribut->gammes()), "filter_attributs_gammes", true);

$pager_skus = new Pager($sql, array(20, 30, 50, 100, 200), "pager_attributs_skus");
$filter_skus = new Filter($pager_skus, array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 's.id',
	),
	'phrase' => array(
		'title' => $dico->t('Nom'),
		'type' => 'contain',
		'field' => 'p.phrase',
	),
	'ref_ultralog' => array(
		'title' => $dico->t('Reference'),
		'type' => 'contain',
		'field' => 's.ref_ultralog',
	),
	'classement' => array(
		'title' => $dico->t('Classement'),
		'type' => 'between',
		'field' => 'sam.classement',
		'form' => array(
			'name' => "sku[%id%][classement]",
			'method' => 'input',
			'type' => 'text',
			'template' => '#{field}',
			'class' => "input-text-numeric",
		),
	),
), array_keys($attribut->skus()), "filter_attributs_skus", true);

$form = new Form(array(
	'id' => "form-edit-attribut-$id",
	'class' => "form-edit",
	'actions' => array("save", "delete", "cancel"),
));

$section = "presentation";
if ($form->value('section')) {
	$section = $form->value('section');
}
$traduction = $form->value("lang");

$messages = array();

if ($form->is_submitted()) {
	$data = $form->escape_values();
	switch ($form->action()) {
		case "translate":
		case "filter":
		case "pager":
		case "reload":
			break;
		case "reset":
			$form->reset();
			$traduction = null;
			break;
		case "delete":
			$attribut->delete($form->values());
			$form->reset();
			$url2->redirect("current", array('action' => "", 'id' => ""));
			break;
		case "add-option":
			$attribut->add_option($data);
			$form->forget_value("new_option");
			break;
		case "delete-option":
			$attribut->delete_option($data, $form->action_arg());
			break;
		case 'addtogammes' :
			$attribut->add_to_gammes();
			$attribut_gammes = $attribut->gammes();
			$filter_gammes->select(array_keys($attribut_gammes));
			break;
		case 'addtoskus' :
			$attribut->add_to_skus();
			$attribut_skus = $attribut->skus();
			$filter_skus->select(array_keys($attribut_skus));
			break;
		default:
			if ($action == "edit" or $action == "create") {
				$filter_gammes->clean_data($data, "gammes");
				$id = $attribut->save($data);
				$form->reset();
				if ($action != "edit") {
					$url2->redirect("current", array('action' => "edit", 'id' => $id));
				}
				$attribut->load($id);
			}
			break;
	}
}

$attribut_gammes = $attribut->gammes();
$attribut_skus = $attribut->skus();
if ($form->changed()) {
	$messages[] = '<p class="message">'.$dico->t('AttentionNonSauvergarde').'</p>';
}
else {
	$filter_gammes->select(array_keys($attribut_gammes));
	$filter_skus->select(array_keys($attribut_skus));
}

if ($action == 'edit') {
	$form->default_values['attribut'] = $attribut->values;
	$form->default_values['phrases'] = $phrase->get($attribut->phrases());
	$form->default_values['options'] = $attribut->options();
	$form->default_values['reference'] = $attribut->reference();
	$form->default_values['valeurs'] = $attribut->valeurs();
	$form->default_values['gammes'] = $attribut_gammes;
	$form->default_values['sku'] = $attribut_skus;
}

$form_start = $form->form_start();

$form->template = $page->inc("snippets/produits-form-template");

$template_inline = <<<HTML
#{label} : #{field} #{description}
HTML;

// variable $displayed_lang définie dans ce snippet
$main = $page->inc("snippets/translate");

$main .= $page->inc("snippets/messages");

$hidden = array('presentation' => "");

if ($action == "edit") {
	$main .= <<<HTML
{$form->input(array('type' => "hidden", 'name' => "attribut[id]"))}
{$form->input(array('type' => "hidden", 'name' => "section", 'value' => $section))}
HTML;
	$buttons['delete'] = $form->input(array('type' => "submit", 'name' => "delete", 'class' => "delete", 'value' => $dico->t('Supprimer') ));
}

if ($action == "edit") {
	$sections = array(
		'presentation' => $dico->t('Presentation'),
	);

	$types_attributs = $attribut->types();
	$id_types_attributs = $attribut->attr('id_types_attributs');
	$form_changed = $form->changed(); 
	if ($form_changed) {
		$id_types_attributs = $form->value('attribut[id_types_attributs]');
	}
	$type_attribut = $types_attributs[$id_types_attributs];
	
	if (strpos($type_attribut, "select") !== false or strpos($type_attribut, "multi") !== false) {
		$sections['options'] = $dico->t('Options');
		// variable $hidden mise à jour dans ce snippet
		$left = $page->inc("snippets/produits-sections");
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('AjouterUneOption'), 'class' => "produit-section produit-section-options".$hidden['options'], 'id' => "produit-section-options-new"))}
{$form->input(array('name' => "new_option[phrase_option]", 'label' => $dico->t('Libelle'), 'items' => $displayed_lang))}
{$form->input(array('name' => "new_option[classement]", 'label' => $dico->t('Classement') ))}
{$form->input(array('type' => "submit", 'name' => "add-option", 'value' => $dico->t('Ajouter') ))}
{$form->fieldset_end()}
HTML;
		$options = $attribut->options();
		if (count($options)) {
			$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Options'), 'class' => "produit-section produit-section-options".$hidden['options'], 'id' => "produit-section-options"))}
<table id="options">
<thead>
<tr>
	<th>{$dico->t('Option')}</th>
	<th>{$dico->t('Classement')}</th>
	<td></td>
</tr>
</thead>
<tbody>
HTML;
			$form_template = $form->template;
			$form->template = "#{field}";
			foreach ($options as $option) {
				$main .= <<<HTML
	<tr>
		<td>
			{$form->input(array('name' => "options[".$option['id']."][phrase_option]", 'type' => "hidden"))}
			{$form->input(array('name' => "phrases[options][".$option['id']."][phrase_option]", 'items' => $displayed_lang))}
		</td>
		<td>
			{$form->input(array('name' => "options[".$option['id']."][classement]"))}
		</td>
		<td>
			{$form->input(array('type' => "submit", 'name' => "delete-option[".$option['id']."]", 'class' => "delete", 'value' => $dico->t('Supprimer')) )}
		</td>
	</tr>
HTML;
			}
			$form->template = $form_template;
			$main .= <<<HTML
	</tbody>
	</table>
	{$form->fieldset_end()}
HTML;
		}
	}
	else if (strpos($type_attribut, "reference") !== false) {
		$sections['reference'] = $dico->t('Reference');
		// variable $hidden mise à jour dans ce snippet
		$left = $page->inc("snippets/produits-sections");
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Reference'), 'class' => "produit-section produit-section-reference".$hidden['reference'], 'id' => "produit-section-reference"))}
{$form->input(array('name' => "reference[table_name]", 'label' => $dico->t('Table')))}
{$form->input(array('name' => "reference[field_label]", 'label' => $dico->t('ChampIntitule')))}
{$form->input(array('name' => "reference[field_value]", 'label' => $dico->t('ChampValeur')))}
{$form->fieldset_end()}
HTML;
	}
	else if (strpos($type_attribut, "readonly") !== false) {
		$sections['valeur'] = $dico->t('Valeur');
		// variable $hidden mise à jour dans ce snippet
		$left = $page->inc("snippets/produits-sections");
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Valeur'), 'class' =>	"produit-section produit-section-valeur".$hidden['valeur'], 'id' => "produit-section-valeur"))}
{$form->input(array('type' => "radio", 'name' => "valeurs[type_valeur]", 'id' => "attribut-type_valeur-numerique", 'class' => "switch", 'switch' => "fieldset.attribut-valeur-numerique", 'value' => 'valeur_numerique', 'label' => $dico->t('ValeurNumerique')))}
{$form->input(array('type' => "radio", 'name' => "valeurs[type_valeur]", 'id' => "attribut-type_valeur-phrase", 'class' => "switch", 'switch' => "fieldset.attribut-phrase-valeur", 'value' => 'phrase_valeur', 'label' => $dico->t('ValeurTextuelle')))}
{$form->fieldset_start(array('legend' => $dico->t('ValeurNumerique'), 'class' => "attribut-valeur-numerique"))}
{$form->input(array('name' => "valeurs[valeur_numerique]", 'label' => $dico->t('ValeurNumerique')))}
{$form->input(array('type' => "hidden", 'name' => "valeurs[phrase_valeur]"))}
{$form->fieldset_end()}
{$form->fieldset_start(array('legend' => $dico->t('ValeurTextuelle'), 'class' => "attribut-phrase-valeur"))}
{$form->input(array('name' => "phrases[valeurs][phrase_valeur]", 'label' => $dico->t('ValeurTextuelle'), 'items' => $displayed_lang))}
{$form->fieldset_end()}
{$form->fieldset_end()}
HTML;
	}
	$sections['gammes'] = $dico->t('Gammes');
	$sections['sku'] = $dico->t('SKU');
	// variable $hidden mise à jour dans ce snippet
	$left = $page->inc("snippets/produits-sections");

	$pager = $pager_gammes;
	$filter = $filter_gammes;
	$attribut->liste_gammes($filter);
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Gammes'), 'class' =>	"produit-section produit-section-gammes".$hidden['gammes'], 'id' => "produit-section-gammes"))}
{$page->inc("snippets/filter-form")}
{$form->input(array('name' => "all_gammes[classement]", 'label' => "Changer le classement pour toutes les gammes sélectionnées"))}
{$form->fieldset_end()}
HTML;
	foreach ($filter->selected() as $selected_gamme) {
		$main .= $form->hidden(array('name' => "gammes[$selected_gamme][classement]"));
	}
	$pager = $pager_skus;
	$filter = $filter_skus;
	$attribut->liste_skus($filter);
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('SKU'), 'class' => "produit-section produit-section-sku".$hidden['sku'], 'id' => "produit-section-sku"))}
{$page->inc("snippets/filter-form")}
{$form->input(array('name' => "all_sku[classement]", 'label' => "Changer le classement pour tous les SKU sélectionnés"))}
{$form->fieldset_end()}
HTML;
	foreach ($filter->selected() as $selected_sku) {
		$main .= $form->hidden(array('name' => "sku[$selected_sku][classement]"));
	}
}

if ($action == "create" or $action == "edit") {
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Presentation'), 'class' => "produit-section produit-section-presentation".$hidden['presentation'], 'id' => "produit-section-presentation"))}
{$form->input(array('name' => "attribut[phrase_nom]", 'type' => "hidden"))}
{$form->input(array('name' => "phrases[phrase_nom]", 'label' => $dico->t('Nom'), 'items' => $displayed_lang))}
{$form->input(array('name' => "attribut[ref]", 'label' => $dico->t('Référence') ))}
{$form->select(array('name' => "attribut[id_groupes_attributs]", 'label' => $dico->t('Groupe'), 'options' => $attribut->groupes($id_langues)))}
{$form->select(array('name' => "attribut[id_types_attributs]", 'label' => $dico->t('TypeValeurs'), 'options' => $attribut->types()))}
{$form->select(array('name' => "attribut[id_unites_mesure]", 'label' => $dico->t('UniteMesure'), 'options' => $attribut->unites(), 'nothing' => "..."))}
{$form->input(array('name' => "attribut[norme]", 'label' => $dico->t('Norme') ))}
{$form->input(array('name' => "attribut[actif]", 'type' => "checkbox", 'label' => $dico->t('Active') ))}
{$form->input(array('name' => "attribut[matiere]", 'type' => "checkbox", 'label' => $dico->t('Matiere')))}
{$form->fieldset_end()}
HTML;
	$buttons['save'] = $form->input(array('type' => "submit", 'name' => "save", 'value' => $dico->t('Enregistrer')));
	$buttons['reset'] = $form->input(array('type' => "submit", 'name' => "reset", 'value' => $dico->t('Reinitialiser')));
}

switch($action) {
	case "create" :
		$titre_page = $dico->t('CreerNouvelAttribut');
		break;
	case "edit" :
		$titre_page = $dico->t('EditerAttribut')." # ID : ".$id;
		break;
	default :
		$page->javascript[] = $config->core_media("filter-edit.js");
		$titre_page = $dico->t('ListeOfAttributs');
		$attribut->liste($filter);
		$main = $page->inc("snippets/filter");
		break;
}

$form_end = $form->form_end();

$buttons['new'] = $page->l($dico->t('NouvelAttribut'), $url2->make("current", array('action' => "create", 'id' => "")));
