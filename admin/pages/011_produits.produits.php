<?php

$menu->current('main/products/products');

$config->core_include("produit/produit", "produit/application", "produit/attribut", "produit/catalogue");
$config->core_include("outils/form", "outils/mysql", "outils/phrase", "outils/langue");
$config->core_include("outils/filter", "outils/pager", "outils/url_redirection", "database/tools");
$config->base_include("functions/tree");

$page->javascript[] = $config->core_media("jquery.min.js");
$page->javascript[] = $config->core_media("jquery.tablednd.js");
$page->javascript[] = $config->media("produit.js");
$page->javascript[] = $config->media("personnalisation.js");
$page->javascript[] = $config->core_media("jquery.form.js");
$page->javascript[] = $config->core_media("jquery.dteditor.js");
$page->css[] = $config->media("dteditor.css");
$page->jsvars[] = array(
	"edit_url" => $url2->make("current", array('action' => 'edit', 'id' => "")),	
);

$page->css[] = $config->media("produit.css");

$sql = new Mysql($config->db());

$langue = new Langue($sql);
$id_langues = $langue->id($config->get("langue"));

$phrase = new Phrase($sql);

$object = $produit = new Produit($sql, $phrase, $id_langues);

$url_redirection = new UrlRedirection($sql);
if ($config->get("no_automatic_url")) {
	$url_redirection->automatic = false;
}

$action = $url2->get('action');
$personnalisation_files = array();
if ($id = $url2->get('id')) {
	$produit->load($id);
	$application = new Application($sql, $phrase, $id_langues);
	$application->load($produit->values['id_applications']);
	$personnalisations = $produit->personnalisations();
	foreach ($personnalisations['images'] as $i => $rien) {
		$personnalisation_files[] = "personnalisation_image[$i][fichier]";
	}
}

$page->javascript[] = $url->make("DTEditorTokens")."?id_produits=$id";

$form = new Form(array(
	'id' => "form-edit-produit-$id",
	'class' => "form-edit",
	'actions' => array(
		"save",
		"delete",
		"cancel",
		"add-image",
		"delete-image",
		"add-document",
		"delete-document",
		"duplicate",
	),
	'permissions' => $user->perms(),
	'permissions_object' => "produit",
	'files' => array_merge(array(
		"new_image_file",
		"new_document_file",
		"new_document_vignette",
		"new_gabarit_file",
		"new_personnalisation_image[fichier]",
	), $personnalisation_files),
));

$filter_schema_sku = array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 's.id',
		'link' => array(
			'href' => $url2->make("Produits", array('type' => "sku", 'action' => "edit", 'id' => "{value}")),
			'target' => "_blank",
		),
	),
	'ref_ultralog' => array(
		'title' => $dico->t('Reference'),
	),
	'nom' => array(
		'title' => $dico->t('Nom'),
		'type' => 'contain',
		'field' => 'p.phrase',
	),
	'classement' => array(
		'title' => $dico->t('Classement'),
		'type' => 'between',
		'field' => 'link.classement',
		'form' => array(
			'method' => 'input',
			'type' => 'text',
			'template' => '#{field}',
		),
	),
);
$filter_schema_produits = array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 'pr.id',
		'link' => array(
			'href' => $url2->make("Produits", array('type' => "produits", 'action' => "edit", 'id' => "{value}")),
			'target' => "_blank",
		),
	),
	'ref' => array(
		'title' => $dico->t('Reference'),
		'type' => 'contain',
		'field' => 'pr.ref',
	),
	'nom' => array(
		'title' => $dico->t('Nom'),
		'type' => 'contain',
		'field' => 'ph.phrase',
	),
	'gamme' => array(
		'title' => $dico->t('Gamme'),
		'type' => 'contain',
		'field' => 'phg.phrase',
	),
	'classement' => array(
		'title' => $dico->t('Classement'),
		'type' => 'between',
		'field' => 'link.classement',
		'form' => array(
			'method' => 'input',
			'type' => 'text',
			'template' => '#{field}',
		),
	),
);
$filter_schema_composants = $filter_schema_sku;
$filter_schema_composants['classement']['form']['name'] = 'composants[%id%][classement]';

$filter_schema_variantes = $filter_schema_sku;
$filter_schema_variantes['classement']['form']['name'] = 'variantes[%id%][classement]';

$filter_schema_accessoires = $filter_schema_sku;
$filter_schema_accessoires['classement']['form']['name'] = 'accessoires[%id%][classement]';

$filter_schema_complementaires = $filter_schema_produits;
$filter_schema_complementaires['classement']['form']['name'] = 'complementaires[%id%][classement]';

$filter_schema_similaires = $filter_schema_produits;
$filter_schema_similaires['classement']['form']['name'] = 'similaires[%id%][classement]';

$pager_composants = new Pager($sql, array(10, 30, 50, 100, 200), "pager_composants");
$filter_composants = new Filter($pager_composants, $filter_schema_composants, array_keys($produit->composants()), "filter_composants", true);

$pager_variantes = new Pager($sql, array(10, 30, 50, 100, 200), "pager_variantes");
$filter_variantes = new Filter($pager_variantes, $filter_schema_variantes, array_keys($produit->variantes()), "filter_variantes", true);

$pager_accessoires = new Pager($sql, array(10, 30, 50, 100, 200), "pager_accessoires");
$filter_accessoires = new Filter($pager_accessoires, $filter_schema_accessoires, array_keys($produit->accessoires()), "filter_accessoires", true);

$pager_complementaires = new Pager($sql, array(10, 30, 50, 100, 200), "pager_complementaires");
$filter_complementaires = new Filter($pager_complementaires, $filter_schema_complementaires, array_keys($produit->complementaires()), "filter_complementaires", true);

$pager_similaires = new Pager($sql, array(10, 30, 50, 100, 200), "pager_similaires");
$filter_similaires = new Filter($pager_similaires, $filter_schema_similaires, array_keys($produit->similaires()), "filter_similaires", true);

$section = "presentation";
if ($form->value('section')) {
	$section = $form->value('section');
}
$traduction = $form->value("lang");

$messages = array();

$id_gabarit = null;
if ($form->is_submitted() and $form->validate()) {
	$data = $form->escaped_values();
	if (isset($data['personnalisation_gabarit'])) {
      $id_gabarit = $data['personnalisation_gabarit'];
    }
	$data['dir_personnalisations'] = $config->get("medias_path")."www/medias/images/personnalisations/";
	switch ($form->action()) {
		case "translate":
		case "filter":
		case "pager":
		case "reload":
			break;
		case "reset":
			$form->reset();
			$id_gabarit = null;
			$traduction = null;
			break;
		case "delete":
			$produit->delete($data);
			$form->reset();
			$url2->redirect("current", array('action' => "", 'id' => ""));
			break;
		case "add-image" :
			if ($file = $form->value('new_image_file')) {
				$dir = $config->get("medias_path")."www/medias/images/produits/";
				$produit->add_image($data, $file, $dir);
			}
			$form->forget_value("new_image");
			break;
		case "delete-image" :
			$produit->delete_image($data, $form->action_arg());
			break;
		case "add-document" :
			if ($file = $form->value('new_document_file')) {
				$dir = $config->get("medias_path")."www/medias/docs/";
				$files_dirs["fichier"] = array('file' => $file, 'dir' => $dir);
				if ($file = $form->value('new_document_vignette')) {
					$dir = $config->get("medias_path")."www/medias/images/documents/";
					$files_dirs["vignette"] = array('file' => $file, 'dir' => $dir);
				}
				$produit->add_document($data, $files_dirs);
			}
			$form->forget_value("new_document");
			break;
		case "delete-document" :
			$produit->delete_document($data, $form->action_arg());
			break;
		case "add-gabarit" :
			if ($file = $form->value('new_gabarit_file')) {
				$dir = $config->get("medias_path")."www/medias/gabarits/";
				$produit->add_gabarit($data, $file, $dir);
			}
			break;
		case "add-personnalisation-gabarit":
			if ($id_gabarit = $produit->add_personnalisation_gabarit($data)) {
				$form->forget_value("new_personnalisation_gabarit");
			}
			break;
		case "delete-personnalisation-gabarit":
			$produit->delete_personnalisation_gabarit($data, $form->action_arg());
			break;
		case "add-personnalisation-texte":
			$produit->add_personnalisation_texte($data);
			$form->forget_value("new_personnalisation_texte");
			break;
		case "delete-personnalisation-texte":
			$produit->delete_personnalisation_texte($data, $form->action_arg());
			break;
		case "add-personnalisation-image":
			$produit->add_personnalisation_image($data);
			$form->forget_value("new_personnalisation_image");
			break;
		case "delete-personnalisation-image":
			$produit->delete_personnalisation_image($data, $form->action_arg());
			break;
		case "delete-gabarit" :
			$produit->delete_gabarit();
			break;
		case "duplicate" :
			$id = $produit->duplicate($data);
			$url2->redirect("current", array('action' => "edit", 'id' => $id));
			break;
		default :
			if ($action == "edit" or $action == "create") {
				if ($action == 'edit') {
					$page->inc("snippets/assets");
					$filter_assets->clean_data($data, 'assets');
				}
				foreach (array('composants', 'accessoires', 'variantes', 'complementaires', 'similaires') as $key) {
					$filter_name = "filter_$key";
					$$filter_name->clean_data($data, $key);
				}
				$id = $url_redirection->save_object($produit, $data, array('phrase_url_key' => 'phrase_nom'));
				
				if ($id === false) {
					$messages[] = '<p class="message">'."Le code URL est déjà utilisé !".'</p>';
				}
				else if ($id > 0) {
					$form->reset();

					if ($action != "edit") {
						$url2->redirect("current", array('action' => "edit", 'id' => $id));
					}
				}
			}
			break;
	}
}

if ($form->changed()) {
	$messages[] = '<p class="message">'.$dico->t('AttentionNonSauvergarde').'</p>';
	if (isset($application)) {
		$application->load($form->value("produit[id_applications]") ? $form->value("produit[id_applications]") : $produit->values['id_applications']);
	}
}
else {
	$filter_composants->select(array_keys($produit->composants()));
	$filter_accessoires->select(array_keys($produit->accessoires()));
	$filter_variantes->select(array_keys($produit->variantes()));
	$filter_complementaires->select(array_keys($produit->complementaires()));
	$filter_similaires->select(array_keys($produit->similaires()));
	$assets_selected = array_keys($produit->assets());
}

if ($action == 'edit') {
	$form->default_values['produit'] = $produit->values;
	$images = $produit->images();
	$form->default_values['image'] = $images;
	$documents = $produit->documents();
	$form->default_values['document'] = $documents;
	$form->default_values['attributs'] = $produit->attributs();
	$form->default_values['phrases'] = $phrase->get($produit->phrases());
	$composants = $produit->composants();
	$form->default_values['composants'] = $composants;
	$accessoires = $produit->accessoires();
	$form->default_values['accessoires'] = $accessoires;
	$variantes = $produit->variantes();
	$form->default_values['variantes'] = $variantes;
	$complementaires = $produit->complementaires();
	$form->default_values['complementaires'] = $complementaires;
	$similaires = $produit->similaires();
	$form->default_values['similaires'] = $similaires;
# Ancienne personnalisation
	$personnalisation = $produit->personnalisation();
	$form->default_values['personnalisation'] = $personnalisation;	
# Nouvelle personnalisation
	$personnalisations = $produit->personnalisations();
	$form->default_values['personnalisations'] = $personnalisations;
	$form->default_values['assets'] = $produit->assets();
}
if (isset($application)) {
	$phrases_applications = $phrase->get($application->phrases());
	$phrases_applications = $produit->substitution_attributs($phrases_applications);
	$form->default_values['phrases_application'] = $phrases_applications;
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

if ($action == "create" or $action == "edit") {
	$buttons['back'] = $page->l($dico->t('Retour'), $url2->make("current", array('action' => "", 'id' => "")));
}

if ($user->has_perm("create produit")) {
	$buttons['new'] = $page->l($dico->t('NouveauProduit'), $url2->make("current", array('action' => "create", 'id' => "")));
}

if ($action == "create" or $action == "edit") {
	$buttons['save'] = $form->input(array('type' => "submit", 'name' => "save", 'value' => $dico->t('Enregistrer') ));
	$buttons['duplicate'] = $form->input(array('type' => "submit", 'name' => "duplicate", 'value' => $dico->t('Dupliquer') ));
	$buttons['reset'] = $form->input(array('type' => "submit", 'name' => "reset", 'value' => $dico->t('Reinitialiser'), 'permitted' => true ));
	$buttons['fichetechnique'] = $page->l($dico->t('FicheTechnique'), $url3->make("FicheTechnique", array('id' => $id)));
	//$buttons['ficheperso'] = $page->l($dico->t('FichePerso'), $url3->make("FichePerso", array('id' => $id)));
}

if ($action == "edit") {
	$sections = array(
		'presentation' => $dico->t('Presentation'),
		'images' => $dico->t('Images'),
		'documents' => $dico->t('Documents'),
		'attributs' => $dico->t('Attributs'),
		'old_personnalisation' => $dico->t('Personnalisation'),
		'personnalisation' => "Personnalisation avancée",
		'variantes' => $dico->t('Declinaisons'),
		'accessoires' => $dico->t('Accessoires'),
		//'composants' => $dico->t('Composants'),
		'complementaires' => $dico->t('ProduitsComplementaires'),
		'similaires' => $dico->t('ProduitsSimilaires'),
		'referencement' => $dico->t('Referencement'),
		'catalogues' => $dico->t('Catalogues'),
	);
	if ($config->param('assets')) {
		$sections['assets'] = $dico->t('Assets');
	}
	// variable $hidden mise à jour dans ce snippet
	$left = $page->inc("snippets/produits-sections");

	if ($config->param('assets')) {		
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Assets'), 'class' => "produit-section produit-section-assets".$hidden['assets'], 'id' => "produit-section-assets"))}
{$page->inc("snippets/assets")}
{$form->fieldset_end()}
HTML;
		foreach (array_intersect($filter_assets->selected(), array_keys($produit->all_assets())) as $selected_asset) {
			$main .= $form->hidden(array('name' => "assets[$selected_asset][classement]", 'if_not_yet_rendered' => true));
		}
	}

	$main .= <<<HTML
{$form->input(array('type' => "hidden", 'name' => "produit[id]"))}
{$form->input(array('type' => "hidden", 'name' => "section", 'value' => $section))}
{$form->fieldset_start(array('legend' => $dico->t('AjouterUneImage'), 'class' => "produit-section produit-section-images".$hidden['images'], 'id' => "produit-section-images-new"))}
{$form->input(array('type' => "file", 'name' => "new_image_file", 'label' => $dico->t('SelectFichier') ))}
{$form->input(array('name' => "new_image[phrase_legende]", 'label' => $dico->t('TexteAlternatif'), 'items' => $displayed_lang))}
{$form->input(array('name' => "new_image[classement]", 'type' => "hidden", 'forced_value' => $produit->new_classement()))}
{$form->input(array('type' => "submit", 'name' => "add-image", 'value' => $dico->t('Ajouter') ))}
<p class="message">{$dico->t('AttentionSuppressionImage')}</p>
<p class="lien_photomail"><a href="mailto:{$config->get("photomail_email")}?Subject=prod= {$id}">{$dico->t('AjouterImagePhotomail')}</a></p>
{$form->fieldset_end()}
HTML;

	if (count($images)) {
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('LesImages'), 'class' => "produit-section produit-section-images".$hidden['images'], 'id' => "produit-section-images-images"))}
<table class="sortable" id="images">
<thead>
<tr>
	<th>{$dico->t('Ordre')}</th>
	<th>{$dico->t('Apercu')}</th>
	<th>{$dico->t('TexteAlternatif')}</th>
	<th>{$dico->t('Visibilite')}</th>
	<th>{$dico->t('Diaporama')}</th>
	<th>{$dico->t('Vignette')}</th>
	<th>Image HD</th>
	<td></td>
</tr>
</thead>
<tbody>
HTML;
		$form_template = $form->template;
		$form->template = "#{field}";
		$images_rows = array();
		$hd_extensions = $dico->d('hd_extensions');
		foreach ($images as $image) {
			$order = $form->value("images[{$image['id']}]") !== null ? $form->value("images[{$image['id']}][classement]") : $image['classement'];
			$style_hd = $image['hd_extension'] ? '' : 'style="display:none;"';
			$images_rows[$order] = <<<HTML
<tr>
	<td class="drag-handle"></td>
	<td><img class="produit-image" src="{$config->core_media("produits/".$image['ref'])}" /></td>
	<td>
		{$form->input(array('name' => "image[".$image['id']."][phrase_legende]", 'type' => "hidden"))}
		{$form->input(array('name' => "phrases[image][".$image['id']."][phrase_legende]", 'items' => $displayed_lang))}
	</td>
	<td>{$form->input(array('name' => "image[".$image['id']."][affichage]", 'type' => "checkbox", 'checked' => $image['affichage']))}</td>
	<td>{$form->input(array('name' => "image[".$image['id']."][diaporama]", 'type' => "checkbox", 'checked' => $image['diaporama']))}</td>
	<td>{$form->input(array('name' => "image[".$image['id']."][vignette]", 'type' => "checkbox", 'checked' => $image['vignette']))}</td>
	<td>
		{$form->select(array('name' => "image[".$image['id']."][hd_extension]", 'options' => $hd_extensions))}
		<input class="nom_hd" name="{$produit->image_hd($image['id'])}" {$style_hd} value="{$produit->image_hd($image['id'])}.{$image['hd_extension']}" readonly="readonly" />
	</td>
	<td>
		{$form->input(array('name' => "image[".$image['id']."][classement]", 'type' => "hidden", 'forced_value' => $order))}
		{$form->input(array('type' => "submit", 'name' => "delete-image[".$image['id']."]", 'class' => "delete", 'value' => "Supprimer"))}
	</td>
</tr>
HTML;
		}
		ksort($images_rows);
		$main .= implode("\n", $images_rows);
		$form->template = $form_template;
		$main .= <<<HTML
</tbody>
</table>
{$form->fieldset_end()}
HTML;
	}

	// Documents
	$main .= $page->inc("snippets/documents");

	$checkbox_template = "#{field} #{label}";
	// Ancienne Personnalisation
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Personnalisation'), 'class' => "produit-section produit-section-old_personnalisation".$hidden['old_personnalisation'], 'id' => "produit-section-old_personnalisation"))}
{$form->input(array('type' => "checkbox", 'name' => "personnalisation[texte][has]", 'label' => "Texte à saisir", 'template' => $checkbox_template))}
{$form->input(array('name' => "personnalisation[texte][libelle]", 'label' => "Libellé du texte à saisir"))}
{$form->input(array('type' => "checkbox", 'name' => "personnalisation[fichier][has]", 'label' => "Fichier à télécharger", 'template' => $checkbox_template))}
{$form->input(array('name' => "personnalisation[fichier][libelle]", 'label' => "Libellé du fichier à télécharger"))}
{$form->fieldset_end()}
HTML;

	// Nouvelle personnalisation
	$personnalisation_url = $config->get("medias_url")."/medias/images/personnalisations/";
	$gabarits = $produit->personnalisations_gabarits();
	$statut_options = array(
		0 => "Bloqué (ne peut pas être remplacé par l'utilisateur)",
		1 => "Facultatif (peut être remplacé par l'utilisateur)",
		2 => "Obligatoire (doit être remplacé par l'utilisateur)",
	);
	$gabarit_fields = "";
	foreach ($gabarits as $id_produits_perso_gabarits => $gabarit) {
		$gabarit_options[$id_produits_perso_gabarits] = $gabarit['ref'];
		$gabarit_fields .= <<<HTML
<div class="personnalisation-gabarit personnalisation-gabarit-{$id_produits_perso_gabarits}">
<img src="{$personnalisation_url}{$gabarit['apercu']}" style="max-height: 100px; max-width: 150px;" alt="{$gabarit['ref']}" />
{$form->input(array('type' => "file", 'name' => "personnalisations[gabarits][$id_produits_perso_gabarits][apercu]", 'label' => "Aperçu"))}
{$form->input(array('name' => "personnalisations[gabarits][$id_produits_perso_gabarits][ref]", 'label' => "Référence"))}
{$form->input(array('name' => "personnalisations[gabarits][$id_produits_perso_gabarits][phrase_nom]", 'type' => "hidden"))}
{$form->input(array('name' => "phrases[personnalisations][gabarits][$id_produits_perso_gabarits][phrase_nom]", 'label' => $dico->t('Nom'), 'items' => $displayed_lang))}
{$form->input(array('type' => "submit", 'name' => "save", 'value' => $dico->t('Enregistrer') ))}
{$form->input(array('type' => "submit", 'name' => "delete-personnalisation-gabarit[$id_produits_perso_gabarits]", 'class' => "delete", 'value' => $dico->t('Supprimer') ))}
</div>
HTML;
	}
	$gabarit_options[0] = "- Nouveau gabarit -";
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => "Gabarit", 'class' => "produit-section produit-section-personnalisation".$hidden['personnalisation']))}
{$form->select(array('id' => "select-gabarit", 'name' => "personnalisation_gabarit", 'forced_value' => $id_gabarit,'label' => "Choix du gabarit", 'options' => $gabarit_options, 'template' => "#{label} : #{field}"))}
<div class="personnalisation-gabarit personnalisation-gabarit-0">
{$form->input(array('type' => "file", 'name' => "new_personnalisation_gabarit[apercu]", 'label' => "Aperçu"))}
{$form->input(array('name' => "new_personnalisation_gabarit[ref]", 'label' => "Référence"))}
{$form->input(array('name' => "new_personnalisation_gabarit[phrase_nom]", 'label' => $dico->t('Nom'), 'items' => $displayed_lang))}
{$form->input(array('type' => "submit", 'name' => "add-personnalisation-gabarit", 'value' => $dico->t('Ajouter') ))}
</div>
{$gabarit_fields}
{$form->fieldset_end()}
HTML;
	foreach ($gabarits as $id_gabarit => $gabarit) {
		$textes = isset($personnalisations['textes'][$id_gabarit]) ? $personnalisations['textes'][$id_gabarit] : array();
		$images = isset($personnalisations['images'][$id_gabarit]) ? $personnalisations['images'][$id_gabarit] : array();

		$main .= <<<HTML
<div class="personnalisation-gabarit personnalisation-gabarit-{$id_gabarit}">
{$form->fieldset_start(array('legend' => "{$dico->t('PersonnalisationApercu')} ($gabarit_options[$id_gabarit])", 'class' => "produit-section produit-section-personnalisation".$hidden['personnalisation'], 'id' => "produit-section-personnalisation-apercu"))}
{$produit->display_personnalisation($personnalisation_url, $id_gabarit)}
{$form->fieldset_end()}
{$form->fieldset_start(array('legend' => "{$dico->t('PersonnalisationTextes')} ($gabarit_options[$id_gabarit])", 'class' => "produit-section produit-section-personnalisation".$hidden['personnalisation'], 'id' => "produit-section-personnalisation-texte-{$id_gabarit}"))}
HTML;
		$number = 0;
		foreach ($textes as $i => $texte) {
			$number++;
			$main .= <<<HTML
{$form->fieldset_start(array('legend' => "Texte $number ($gabarit_options[$id_gabarit])"))}
{$form->textarea(array('name' => "personnalisations[textes][$id_gabarit][$i][contenu]", 'label' => "Texte par défaut"))}
{$form->input(array('name' => "personnalisations[textes][$id_gabarit][$i][min_caracteres]", 'label' => "Nombre minimum de caractères"))}
{$form->input(array('name' => "personnalisations[textes][$id_gabarit][$i][max_caracteres]", 'label' => "Nombre maximum de caractères"))}
{$form->input(array('name' => "personnalisations[textes][$id_gabarit][$i][min_lignes]", 'label' => "Nombre minimum de lignes"))}
{$form->input(array('name' => "personnalisations[textes][$id_gabarit][$i][max_lignes]", 'label' => "Nombre maximum de lignes"))}
{$form->textarea(array('name' => "personnalisations[textes][$id_gabarit][$i][css]", 'label' => "Style CSS"))}
{$form->select(array('name' => "personnalisations[textes][$id_gabarit][$i][statut]", 'label' => $dico->t("Statut"), 'options' => $statut_options))}
<br />
<br />
{$form->input(array('type' => "submit", 'name' => "save", 'value' => $dico->t('Enregistrer') ))}
{$form->input(array('type' => "submit", 'name' => "delete-personnalisation-texte[$i]", 'class' => "delete", 'value' => $dico->t('Supprimer') ))}
{$form->fieldset_end()}
HTML;
		}
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => "Nouveau texte ($gabarit_options[$id_gabarit])"))}
{$form->textarea(array('name' => "new_personnalisation_texte[$id_gabarit][contenu]", 'label' => "Texte par défaut"))}
{$form->input(array('name' => "new_personnalisation_texte[$id_gabarit][min_caracteres]", 'label' => "Nombre minimum de caractères"))}
{$form->input(array('name' => "new_personnalisation_texte[$id_gabarit][max_caracteres]", 'label' => "Nombre maximum de caractères"))}
{$form->input(array('name' => "new_personnalisation_texte[$id_gabarit][min_lignes]", 'label' => "Nombre minimum de lignes"))}
{$form->input(array('name' => "new_personnalisation_texte[$id_gabarit][max_lignes]", 'label' => "Nombre maximum de lignes"))}
{$form->textarea(array('name' => "new_personnalisation_texte[$id_gabarit][css]", 'label' => "Style CSS"))}
{$form->select(array('name' => "new_personnalisation_texte[$id_gabarit][statut]", 'label' => $dico->t("Statut"), 'options' => $statut_options, 'forced_value' => 1))}
<br />
<br />
{$form->input(array('type' => "submit", 'name' => "add-personnalisation-texte[$id_gabarit]", 'value' => $dico->t('Ajouter') ))}
{$form->fieldset_end()}
{$form->fieldset_end()}
{$form->fieldset_start(array('legend' => "{$dico->t('PersonnalisationImages')} ($gabarit_options[$id_gabarit])", 'class' => "produit-section produit-section-personnalisation".$hidden['personnalisation'], 'id' => "produit-section-personnalisation-image-{$id_gabarit}"))}
HTML;
		$number = 0;
		foreach ($images as $i => $image) {
			$number++;
			$main .= <<<HTML
{$form->fieldset_start(array('legend' => "Image $number ($gabarit_options[$id_gabarit])"))}
<img src="{$personnalisation_url}{$image['fichier']}" style="max-height: 200px; max-width: 300px;" alt="Image $i" />
{$form->input(array('type' => "file", 'name' => "personnalisations[images][$id_gabarit][$i][fichier]", 'label' => "Image par défaut"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][formats]", 'label' => "Formats autorisés (liste d'extensions séparées par des espaces)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][min_largeur]", 'label' => "Largeur minimale de l'image (en pixels)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][max_largeur]", 'label' => "Largeur maximale de l'image (en pixels)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][min_hauteur]", 'label' => "Hauteur minimale de l'image (en pixels)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][max_hauteur]", 'label' => "Hauteur maximale de l'image (en pixels)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][min_poids]", 'label' => "Poids minimal du fichier (en ko)"))}
{$form->input(array('name' => "personnalisations[images][$id_gabarit][$i][max_poids]", 'label' => "Poids maximal du fichier (en ko)"))}
{$form->textarea(array('name' => "personnalisations[images][$id_gabarit][$i][css]", 'label' => "Style CSS"))}
{$form->select(array('name' => "personnalisations[images][$id_gabarit][$i][statut]", 'label' => $dico->t("Statut"), 'options' => $statut_options))}
<br />
{$form->input(array('type' => "checkbox", 'name' => "personnalisations[images][$id_gabarit][$i][background]", 'label' => $dico->t("PersonnalisationBackground"), 'template' => $checkbox_template))}
<br />
{$form->input(array('type' => "checkbox", 'name' => "personnalisations[images][$id_gabarit][$i][contain]", 'label' => $dico->t("PersonnalisationContain"), 'template' => $checkbox_template))}
<br />
<br />
{$form->input(array('type' => "submit", 'name' => "save", 'value' => $dico->t('Enregistrer') ))}
{$form->input(array('type' => "submit", 'name' => "delete-personnalisation-image[$i]", 'class' => "delete", 'value' => $dico->t('Supprimer') ))}
{$form->fieldset_end()}
HTML;
		}
		$main .= <<<HTML
{$form->fieldset_start(array('legend' => "Nouvelle image ($gabarit_options[$id_gabarit])"))}
{$form->input(array('type' => "file", 'name' => "new_personnalisation_image[$id_gabarit][fichier]", 'label' => "Image par défaut"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][formats]", 'label' => "Formats autorisés (liste d'extensions séparées par des espaces)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][min_largeur]", 'label' => "Largeur minimale de l'image (en pixels)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][max_largeur]", 'label' => "Largeur maximale de l'image (en pixels)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][min_hauteur]", 'label' => "Hauteur minimale de l'image (en pixels)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][max_hauteur]", 'label' => "Hauteur maximale de l'image (en pixels)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][min_poids]", 'label' => "Poids minimal du fichier (en ko)"))}
{$form->input(array('name' => "new_personnalisation_image[$id_gabarit][max_poids]", 'label' => "Poids maximal du fichier (en ko)"))}
{$form->textarea(array('name' => "new_personnalisation_image[$id_gabarit][css]", 'label' => "Style CSS"))}
{$form->select(array('name' => "new_personnalisation_image[$id_gabarit][statut]", 'label' => $dico->t("Statut"), 'options' => $statut_options, 'forced_value' => 1))}
<br />
{$form->input(array('type' => "checkbox", 'name' => "new_personnalisation_image[$id_gabarit][background]", 'label' => $dico->t("PersonnalisationBackground"), 'template' => $checkbox_template))}
<br />
{$form->input(array('type' => "checkbox", 'name' => "new_personnalisation_image[$id_gabarit][contain]", 'label' => $dico->t("PersonnalisationContain"), 'template' => $checkbox_template))}
<br />
<br />
{$form->input(array('type' => "submit", 'name' => "add-personnalisation-image[$id_gabarit]", 'value' => $dico->t('Ajouter') ))}
{$form->fieldset_end()}
{$form->fieldset_end()}
</div>
HTML;
	}

	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Attributs'), 'class' => "produit-section produit-section-attributs".$hidden['attributs'], 'id' => "produit-section-attributs"))}
HTML;
	$attribut = new Attribut($sql, $phrase, $id_langues);
	$i = 0;
	foreach ($application->attributs() as $attribut_id) {
		$main .= <<<HTML
{$page->inc("snippets/attribut")}
{$form->input(array('type' => "hidden", 'name' => "attributs_management[$attribut_id][classement]", 'value' => $i))}
HTML;
		$i++;
	}
	$main .= <<<HTML
{$form->fieldset_end()}

{$form->fieldset_start(array('legend' => $dico->t('Referencement'), 'class' => "produit-section produit-section-referencement".$hidden['referencement'], 'id' => "produit-section-referencement"))}
{$form->input(array('name' => "produit[phrase_meta_title]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_meta_title]", 'label' => $dico->t('MetaTitle'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->input(array('name' => "produit[phrase_meta_keywords]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_meta_keywords]", 'label' => $dico->t('MetaKeywords'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->input(array('name' => "produit[phrase_meta_description]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_meta_description]", 'label' => $dico->t('MetaDescription'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->fieldset_end()}

{$form->fieldset_start(array('legend' => $dico->t('Declinaisons'), 'class' => "produit-section produit-section-variantes".$hidden['variantes'], 'id' => "produit-section-variantes"))}
<p>{$dico->t('ListeOfDeclinaisonsProduit')}</p>
HTML;
	$pager = $pager_variantes;
	$filter = $filter_variantes;
	$produit->all_variantes($filter);
	$main .= $page->inc("snippets/filter-form");
	foreach ($filter->selected() as $selected_variante) {
		$main .= $form->hidden(array('name' => "variantes[$selected_variante][classement]"));
	}
	$main .= <<<HTML
{$form->fieldset_end()}

{$form->fieldset_start(array('legend' => $dico->t('Accessoires'), 'class' => "produit-section produit-section-accessoires".$hidden['accessoires'], 'id' => "produit-section-accessoires"))}
<p>{$dico->t('ListeOfAccessoiresProduit')}</p>
HTML;
	$pager = $pager_accessoires;
	$filter = $filter_accessoires;
	$produit->all_accessoires($filter);
	$main .= $page->inc("snippets/filter-form");
	foreach ($filter->selected() as $selected_accessoire) {
		$main .= $form->hidden(array('name' => "accessoires[$selected_accessoire][classement]"));
	}
	$main .= <<<HTML
{$form->fieldset_end()}
HTML;

	$designation_auto_attributs = "%".implode(", %", $produit->attributs_ref());
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Désignation automatique'), 'class' => "produit-section produit-section-variantes".$hidden['variantes'], 'id' => "produit-section-variantes-designation-auto"))}
{$form->input(array('name' => "produit[phrase_designation_auto]", 'type' => "hidden"))}
{$form->input(array('name' => "phrases[phrase_designation_auto]", 'label' => $dico->t('Modèle'), 'items' => $displayed_lang, 'description' => $dico->t("Tokens disponibles : ".$designation_auto_attributs)))}
<table>
<tr>
	<th>Langue</th>
	<th>ID SKU</th>
	<th>Désignation actuelle</th>
	<th>Désignation automatique</th>
</tr>
HTML;
	foreach (array_keys($displayed_lang) as $code_langue) {
		foreach ($produit->designations_auto($langue->id($code_langue)) as $id_sku => $designations) {
			$main .= <<<HTML
<tr>
	<td>{$code_langue}</td>
	<td>{$id_sku}</td>
	<td>{$designations['actuelle']}</td>
	<td>{$designations['auto']}</td>
</tr>
HTML;
		}
	}
	$main .= <<<HTML
</table>
{$form->fieldset_end()}
HTML;

/** Composants **
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Composants'), 'class' => "produit-section produit-section-composants".$hidden['composants'], 'id' => "produit-section-composants"))}
<p>{$dico->t('ListeOfComposantsProduit')}</p>
HTML;
	$pager = $pager_composants;
	$filter = $filter_composants;
	$produit->all_composants($filter);
	$main .= $page->inc("snippets/filter-form");
	foreach ($filter->selected() as $selected_composant) {
		$main .= $form->hidden(array('name' => "composants[$selected_composant][classement]"));
	}
	$main .= <<<HTML
{$form->fieldset_end()}
*/
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('ProduitsComplémentaires'), 'class' => "produit-section produit-section-complementaires".$hidden['complementaires'], 'id' => "produit-section-complementaires"))}
<p>{$dico->t('ListeOfProduitsComplementaires')}</p>
HTML;
	$pager = $pager_complementaires;
	$filter = $filter_complementaires;
	$produit->all_complementaires($filter);
	$main .= $page->inc("snippets/filter-form");
	foreach ($filter->selected() as $selected_complementaire) {
		$main .= $form->hidden(array('name' => "complementaires[$selected_complementaire][classement]"));
	}
	$main .= <<<HTML
{$form->fieldset_end()}

{$form->fieldset_start(array('legend' => $dico->t('ProduitsSimilaires'), 'class' => "produit-section produit-section-similaires".$hidden['similaires'], 'id' => "produit-section-similaires"))}
<p>{$dico->t('ListeOfProduitsSimilaires')}</p>
HTML;
	$pager = $pager_similaires;
	$filter = $filter_similaires;
	$produit->all_similaires($filter);
	$main .= $page->inc("snippets/filter-form");
	foreach ($filter->selected() as $selected_similaire) {
		$main .= $form->hidden(array('name' => "similaires[$selected_similaire][classement]"));
	}
	$main .= <<<HTML
{$form->fieldset_end()}
HTML;

	function print_categorie($categorie) {
		global $page;
		global $url2;

		$nom_categorie = $categorie['nom'];
		if ($categorie['statut'] == 0) {
			$nom_categorie = "<strike>$nom_categorie</strike>";
		}

		return $page->l($nom_categorie, $url2->make("Produits", array('type' => "catalogues_categories", 'action' => "edit", 'id' => $categorie['id'])));
	}

	$catalogues_html = "";
	foreach ($produit->catalogues() as $id_catalogues) {
		$catalogue = new Catalogue($sql);
		$catalogue->load($id_catalogues);
		$nom_catalogue = $catalogue->values['nom'];
		if ($catalogue->values['statut'] == 0) {
			$nom_catalogue = "<strike>$nom_catalogue</strike>";
		}
		$catalogues_html .= <<<HTML
<h3>{$page->l($nom_catalogue, $url2->make("Produits", array('type' => "catalogues", 'action' => "edit", 'id' => $catalogue->values['id'])))}</h3>
HTML;
		$catalogues_html .= print_callback_tree(DBTools::tree($produit->categories($id_catalogues)), "print_categorie");
	}
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Catalogues'), 'class' => "produit-section produit-section-catalogues".$hidden['catalogues'], 'id' => "produit-section-catalogues"))}
{$catalogues_html}
{$form->fieldset_end()}
HTML;

	$buttons['delete'] = $form->input(array('type' => "submit", 'name' => "delete", 'class' => "delete", 'value' => $dico->t('Supprimer') ));
}

if ($action == "create" or $action == "edit") {
	
	$offres = array(
		0 => "...",
		1 => $dico->t('GammeEssentiel'),
		2 => $dico->t('GammePro'),
		3 => $dico->t('GammeExpert'),
	);
// A mettre ci-dessous après le nom
// {$form->input(array('name' => "produit[phrase_commercial]", 'type' => "hidden"))}
// {$form->input(array('name' => "phrases[phrase_commercial]", 'label' => "Designation commerciale", 'items' => $displayed_lang))}
	$main .= <<<HTML
{$form->fieldset_start(array('legend' => $dico->t('Presentation'), 'class' => "produit-section produit-section-presentation".$hidden['presentation'], 'id' => "produit-section-presentation"))}
{$form->input(array('name' => "produit[phrase_nom]", 'type' => "hidden"))}
{$form->input(array('name' => "phrases[phrase_nom]", 'label' => $dico->t('Nom'), 'items' => $displayed_lang))}
{$form->input(array('name' => "produit[ref]", 'label' => $dico->t('Reference') ))}
{$form->select(array('name' => "produit[id_applications]", 'label' => $dico->t('Application'), 'options' => $produit->applications()))}
{$form->select(array('name' => "produit[id_types_produits]", 'label' => $dico->t('Type'), 'options' => $produit->types()))}
{$form->select(array('name' => "produit[actif]", 'label' => $dico->t('Statut'), 'options' => array(1 => $dico->t('Active'), 0 => $dico->t('Desactive') )))}
{$form->select(array('name' => "produit[id_gammes]", 'label' => $dico->t('Gamme'), 'options' => $produit->gammes()))}
{$form->select(array('name' => "produit[offre]", 'label' => $dico->t('Offre'), 'options' => $offres))}
{$form->select(array('name' => "produit[id_recyclage]", 'label' => $dico->t('FiliereRecyclage'), 'options' => $produit->recyclage($id_langues) ))}
{$form->select(array('name' => "produit[echantillon]", 'label' => $dico->t('EchantillonDisponible'), 'options' => array(1 => $dico->t('Oui'), 0 => $dico->t('Non'))))}
{$form->select(array('name' => "produit[delai_livraison]", 'label' => $dico->t('DelaiLivraison'), 'options' => $dico->d('delais_livraison')))}
{$form->input(array('name' => "produit[phrase_url_key]", 'type' => "hidden"))}
{$form->input(array('name' => "phrases[phrase_url_key]", 'label' => $dico->t('UrlKey'), 'items' => $displayed_lang))}
{$form->input(array('name' => "produit[phrase_avantages_produit]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_avantages_produit]", 'label' => $dico->t('AvantagesProduit'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->input(array('name' => "produit[phrase_description_courte]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_description_courte]", 'label' => $dico->t('DescriptionCourte'), 'items' => $displayed_lang, 'class' => "dteditor dteditor-tokens-description_courte"))}
{$form->textarea(array('name' => "phrases_application[phrase_produit_description_courte]", 'label' => $dico->t("DescriptionAuto")." (".$dico->t('DescriptionCourte').")", 'items' => $displayed_lang, 'readonly' => true))}
{$form->input(array('name' => "produit[phrase_description]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_description]", 'label' => $dico->t('Description'), 'items' => $displayed_lang, 'class' => "dteditor dteditor-tokens-description"))}
{$form->textarea(array('name' => "phrases_application[phrase_produit_description]", 'label' => $dico->t("DescriptionAuto")." (".$dico->t('Description').")", 'items' => $displayed_lang, 'readonly' => true))}
{$form->input(array('name' => "produit[phrase_entretien]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_entretien]", 'label' => $dico->t('ConseilsEntretien'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->input(array('name' => "produit[phrase_mode_emploi]", 'type' => "hidden"))}
{$form->textarea(array('name' => "phrases[phrase_mode_emploi]", 'label' => $dico->t('ModeEmploi'), 'items' => $displayed_lang, 'class' => "dteditor"))}
{$form->fieldset_end()}
HTML;
}

switch($action) {
	case "create" :
		$titre_page = $dico->t('CreerNouveauProduit');
		break;
	case "edit" :
		$titre_page = $dico->t('EditerProduit')." # ID : ".$id;
		break;
	default :
		$page->javascript[] = $config->core_media("filter-edit.js");
		$titre_page = $dico->t('ListeOfProduits');
		$pager = new Pager($sql, array(20, 30, 50, 100, 200));
		$filter = new Filter($pager, array(
			'id' => array(
				'title' => 'ID',
				'type' => 'between',
				'order' => 'DESC',
				'field' => 'pr.id',
			),
			'ref' => array(
				'title' => $dico->t('Reference'),
			),
			'phrase' => array(
				'title' => $dico->t('Nom'),
				'type' => 'contain',
				'field' => 'ph.phrase',
			),
			'gamme' => array(
				'title' => $dico->t('Gamme'),
				'type' => 'contain',
				'field' => 'ph.phrase',
			),
			'gamme' => array(
				'title' => $dico->t('Gamme'),
				'type' => 'select',
				'field' => 'pr.id_gammes',
				'values' => array(0 => ""),
				'options' => $produit->gammes(),
			),
			'actif' => array(
				'title' => $dico->t('Active'),
				'type' => 'select',
				'field' => 'pr.actif',
				'options' => array(1 => $dico->t('Active'), 0 => $dico->t('Desactive')),
			),
		), array(), "filter_produits");
		$produit->liste($filter);
		$main = $page->inc("snippets/filter");
		break;
}

$form_end = $form->form_end();

