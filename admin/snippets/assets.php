<?php
global $sql, $page, $dico, $form, $config, $phrase, $id_langues, $pager, $filter,
	   $url, $pager_assets, $filter_assets, $object, $asset, $assets_selected;

$pager = $pager_assets = new Pager($sql, array(10, 30, 50, 100, 200), "pager_{$object->type}_assets");
$filter = $filter_assets = new Filter($pager, array(
	'id' => array(
		'title' => 'ID',
		'type' => 'between',
		'order' => 'DESC',
		'field' => 'a.id',
		'link' => array(
			'href' => $url->make("assets", array('action' => "edit", 'id' => "{value}")),
			'target' => "_blank",
		),
		'template' => "{id} <img alt=\"\" src=\"{$config->get("asset_url")}{id}?thumb=1\" />",
	),
	'titre' => array(
		'title' => $dico->t('Titre'),
		'type' => 'contain',
		'field' => "a.titre",
	),
	'tags' => array(
		'title' => $dico->t('Tags'),
		'type' => 'select',
		'field' => 'at.id',
		'options' => $object->all_assets_tags(),
	),
	'classement' => array(
		'title' => $dico->t('Classement'),
		'type' => 'between',
		'field' => 'al.classement',
		'form' => array(
			'name' => "assets[%id%][classement]",
			'method' => 'input',
			'type' => 'text',
			'template' => '#{field}',
			'class' => "input-text-numeric",
		),
	),
), array(), "filter_{$object->type}_assets", true);


if (isset($assets_selected)) {
	$filter->select($assets_selected);
}
$object->all_assets($filter);
echo $page->inc("snippets/filter-form");

