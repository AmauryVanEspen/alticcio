<?php

require_once "abstract_object.php";

class Asset extends AbstractObject {

	public $type = "asset";
	public $table = "dt_assets";
	public $id_field = "id_assets";
	public $phrase_fields = array(
		'phrase_nom',
		'phrase_description',
	);

	public function liste($link_types = array(), $attributs_refs = array(), $id_langues = 1, &$filter = null) {
		$select = "";
		$join = "";
		foreach ($link_types as $link_type) {
			switch ($link_type) {
				case 'catalogue_categorie':
					$table = "dt_catalogues_categories";
					$field = "nom";
					break;
				case 'gamme':
					$table = "dt_gammes";
					$field = "ref";
					break;
				case 'produit':
					$table = "dt_produits";
					$field = "ref";
					break;
				case 'sku':
					$table = "dt_sku";
					$field = "ref_ultralog";
					break;
			}
			$select .= <<<SQL
, GROUP_CONCAT(DISTINCT t_{$link_type}.{$field} ORDER BY t_{$link_type}.{$field} ASC SEPARATOR ', ') AS links_{$link_type}
SQL;
			$join .= <<<SQL
LEFT OUTER JOIN dt_assets_links AS al_{$link_type} ON al_{$link_type}.id_assets = a.id AND al_{$link_type}.link_type = '$link_type'
LEFT OUTER JOIN {$table} AS t_{$link_type} ON t_{$link_type}.id = al_{$link_type}.link_id

SQL;
		}
		if (count($attributs_refs)) {
			$attributs = $this->all_links_attributs($attributs_refs);
			foreach ($attributs as $id_attribut => $attribut) {
				$link_type = "attribut-{$id_attribut}";
				$link_name = "attribut_{$id_attribut}";
				$select .= <<<SQL
, GROUP_CONCAT(DISTINCT p_{$link_name}.phrase ORDER BY p_{$link_name}.phrase ASC SEPARATOR ', ') AS links_{$link_name}
SQL;
				$join .= <<<SQL
LEFT OUTER JOIN dt_assets_links AS al_{$link_name} ON al_{$link_name}.id_assets = a.id AND al_{$link_name}.link_type = '$link_type'
LEFT OUTER JOIN dt_options_attributs AS t_{$link_name} ON t_{$link_name}.id = al_{$link_name}.link_id
LEFT OUTER JOIN dt_phrases AS p_{$link_name} ON p_{$link_name}.id = t_{$link_name}.phrase_option AND  p_{$link_name}.id_langues = $id_langues

SQL;
			}
		}
		$q = <<<SQL
SELECT a.id, a.titre, a.fichier, a.actif, a.public,
GROUP_CONCAT(DISTINCT at.code ORDER BY at.code ASC SEPARATOR ', ') AS tags,
GROUP_CONCAT(DISTINCT atg.code ORDER BY atg.code ASC SEPARATOR ', ') AS targets
{$select}
FROM dt_assets AS a
LEFT OUTER JOIN dt_assets_tags_assets AS ata ON ata.id_assets = a.id
LEFT OUTER JOIN dt_assets_tags AS at ON at.id = ata.id_assets_tags
LEFT OUTER JOIN dt_assets_targets_assets AS atga ON atga.id_assets = a.id
LEFT OUTER JOIN dt_assets_targets AS atg ON atg.id = atga.id_assets_targets
{$join}
WHERE 1
GROUP BY a.id
SQL;
		if ($filter === null) {
			$filter = $this->sql;
		}
		$res = $filter->query($q);

		$liste = array();
		while ($row = $filter->fetch($res)) {
			$liste[$row['id']] = $row;
		}
		
		return $liste;
	}

	public function save($data) {
		$time = time();
		if (isset($data['asset']['id'])) {
			$q = <<<SQL
DELETE FROM dt_assets_links WHERE id_assets = {$data['asset']['id']}
SQL;
			$this->sql->query($q);

			$values = array();
			if (isset($data['asset_links'])) {
				foreach ($data['asset_links'] as $link_type => $link) {
					foreach ($link as $key => $value) {
						if (isset($value['classement'])) {
							$classement = (int)$value['classement'];
							$link_id = $key;
						}
						else {
							$classement = $key;
							$link_id = $value;
						}
						$values[] = "({$data['asset']['id']}, '{$link_type}', $link_id, {$classement})";

					}
				}
			}
			$values = implode(",", $values);
			if ($values) {
				$q = <<<SQL
INSERT INTO dt_assets_links (id_assets, link_type, link_id, classement) VALUES $values 
SQL;
				$this->sql->query($q);
			}
		}
		else {
			$data['asset']['date_creation'] = $time;
		}
		$data['asset']['date_modification'] = $time;

		if (!isset($data['asset']['infos'])) {
			$data['asset']['infos'] = "";
		}

		if (isset($data['file']) and isset($data['path'])) {
			$file = $data['file'];
			$path = $data['path'];
			if (is_array($file)) {
				preg_match("/(\.[^\.]*)$/", $file['name'], $matches);
				$ext = $matches[1];
				$file_name = md5_file($file['tmp_name']).$ext;
				move_uploaded_file($file['tmp_name'], $path.$file_name);
				$data['asset']['fichier'] = $file['name'];
				$data['asset']['fichier_md5'] = $file_name;
			}
			else if (file_exists($file)) {
				preg_match("/(\.[^\.]*)$/", $file, $matches);
				$ext = $matches[1];
				$file_name = md5_file($file).$ext;
				copy($file, $path.$file_name);
				$data['asset']['fichier'] = basename($file);
				$data['asset']['fichier_md5'] = $file_name;
			}
		}

		$id_assets = parent::save($data);

		if (isset($data['tags'])) {
			if (isset($data['asset']['id'])) {
				$q = <<<SQL
DELETE FROM dt_assets_tags_assets WHERE id_assets = {$data['asset']['id']} 
SQL;
				$this->sql->query($q);
			}

			$values = array();
			foreach ($data['tags'] as $id_assets_tags) {
				if ($id_assets_tags) {
					$values[] = "($id_assets, $id_assets_tags)";
				}
			}
			if ($list_values = implode(",", $values)) {
				$q = <<<SQL
INSERT INTO dt_assets_tags_assets (id_assets, id_assets_tags) VALUES $list_values
SQL;
				$this->sql->query($q);
			}
		}

		if (isset($data['targets'])) {
			if (isset($data['asset']['id'])) {
				$q = <<<SQL
DELETE FROM dt_assets_targets_assets WHERE id_assets = {$data['asset']['id']} 
SQL;
				$this->sql->query($q);
			}

			$values = array();
			foreach ($data['targets'] as $id_assets_targets) {
				if ($id_assets_targets) {
					$values[] = "($id_assets, $id_assets_targets)";
				}
			}
			if ($list_values = implode(",", $values)) {
				$q = <<<SQL
INSERT INTO dt_assets_targets_assets (id_assets, id_assets_targets) VALUES $list_values
SQL;
				$this->sql->query($q);
			}
		}

		if (isset($data['langues'])) {
			if (isset($data['asset']['id'])) {
				$q = <<<SQL
DELETE FROM dt_assets_langues WHERE id_assets = {$data['asset']['id']} 
SQL;
				$this->sql->query($q);
			}

			$values = array();
			foreach ($data['langues'] as $id_langues) {
				$values[] = "($id_assets, $id_langues)";
			}
			if ($list_values = implode(",", $values)) { 
				$q = <<<SQL
INSERT INTO dt_assets_langues (id_assets, id_langues) VALUES $list_values
SQL;
				$this->sql->query($q);
			}
		}

		return $id_assets;
	}

	public function delete($data) {
		$q = <<<SQL
DELETE FROM dt_assets_langues WHERE id_assets = {$this->id}
SQL;
		$this->sql->query($q);

		$q = <<<SQL
DELETE FROM dt_assets_tags_assets WHERE id_assets = {$this->id}
SQL;
		$this->sql->query($q);

		$q = <<<SQL
DELETE FROM dt_assets_targets_assets WHERE id_assets = {$this->id}
SQL;
		$this->sql->query($q);

		$q = <<<SQL
DELETE FROM dt_assets_links WHERE id_assets = {$this->id}
SQL;
		$this->sql->query($q);

		$return = parent::delete($data);
		
		if (isset($data['delete_path']) and isset($data['delete_file'])) {
#TODO vérifier que le fichier n'est plus utilisé par un autre asset 
			$q = <<<SQL
SQL;
			$file = $data['delete_path']."/".$data['delete_file'];
			if (file_exists($file)) {
				unlink($file);
			}
		}

		return $return;
	}

	public function all_links_catalogue_categorie($filter = null) {
		$liste = array();

		if ($filter === null) {
			$filter = $this->sql;
		}

		$asset_id = isset($this->id) ? $this->id : 0;
		$q = <<<SQL
SELECT cc.id_parent, cc.id, cc.nom as ref, p.phrase as nom, al.link_id, al.classement,
c.nom AS ref_catalogue, p2.phrase as nom_catalogue
FROM dt_catalogues_categories AS cc
INNER JOIN dt_catalogues AS c ON cc.id_catalogues = c.id
LEFT OUTER JOIN dt_assets_links AS al ON cc.id = al.link_id AND id_assets = $asset_id AND link_type = 'catalogue_categorie'
LEFT OUTER JOIN dt_phrases AS p ON p.id = cc.phrase_nom AND p.id_langues = {$this->langue}
LEFT OUTER JOIN dt_phrases AS p2 ON p2.id = c.phrase_nom AND p2.id_langues = {$this->langue}
SQL;
		$res = $filter->query($q);

		while ($row = $filter->fetch($res)) {
			if (!$row['nom']) {
				$row['nom'] = $row['ref'];
			}
			if (!$row['nom_catalogue']) {
				$row['nom_catalogue'] = $row['ref_catalogue'];
			}
			$liste[$row['id']] = $row;
		}
		
		$paths = array();
		$try_again = true;
		while ($try_again) {
			$try_again = false;
			foreach ($liste as $id => $row) {
				if (!isset($paths[$id])) {
					$try_again = true;
					if ($row['id_parent']) {
						if (!isset($liste[$row['id_parent']])) {
							$try_again = false; // sécurité boucle infinie
						}
						else if (isset($paths[$row['id_parent']])) {
							$paths[$id] = $paths[$row['id_parent']]." > ".$row['nom'];
						}
					}
					else {
						$paths[$id] = $row['nom_catalogue']." > ".$row['nom'];
					}
				}
			}
		}
		asort($paths);
		$sorted_liste = array();
		foreach ($paths as $id => $path) {
			$sorted_liste[$id] = $liste[$id];
			$sorted_liste[$id]['path'] = $path;
		}
		
		return $sorted_liste;
	}

	public function all_links_gamme($filter = null) {
		$liste = array();

		if ($filter === null) {
			$filter = $this->sql;
		}

		$asset_id = isset($this->id) ? $this->id : 0;
		$q = <<<SQL
SELECT g.id, g.ref as ref, p.phrase as nom, al.link_id, al.classement
FROM dt_gammes AS g
LEFT OUTER JOIN dt_assets_links AS al ON g.id = al.link_id AND id_assets = $asset_id AND link_type = 'gamme'
LEFT OUTER JOIN dt_phrases AS p ON p.id = g.phrase_nom AND p.id_langues = {$this->langue}
SQL;
		$res = $filter->query($q);

		while ($row = $filter->fetch($res)) {
			$liste[$row['id']] = $row;
		}
		
		return $liste;
	}
	
	public function all_links_produit($filter = null) {
		$liste = array();

		if ($filter === null) {
			$filter = $this->sql;
		}

		$asset_id = isset($this->id) ? $this->id : 0;
		$q = <<<SQL
SELECT p.id, p.ref as ref, p1.phrase as nom, p2.phrase as nom_gamme, al.link_id, al.classement
FROM dt_produits AS p
INNER JOIN dt_gammes AS g ON g.id = p.id_gammes
LEFT OUTER JOIN dt_assets_links AS al ON p.id = al.link_id AND id_assets = $asset_id AND link_type = 'produit'
LEFT OUTER JOIN dt_phrases AS p1 ON p1.id = p.phrase_nom AND p1.id_langues = {$this->langue}
LEFT OUTER JOIN dt_phrases AS p2 ON p2.id = g.phrase_nom AND p2.id_langues = {$this->langue}
SQL;
		$res = $filter->query($q);

		while ($row = $filter->fetch($res)) {
			$liste[$row['id']] = $row;
		}
		
		return $liste;
	}
	
	public function all_links_sku($filter = null) {
		$liste = array();

		if ($filter === null) {
			$filter = $this->sql;
		}

		$asset_id = isset($this->id) ? $this->id : 0;
		$q = <<<SQL
SELECT s.id, s.ref_ultralog as ref, p.phrase as nom, al.link_id, al.classement
FROM dt_sku AS s
LEFT OUTER JOIN dt_assets_links AS al ON s.id = al.link_id AND id_assets = $asset_id AND link_type = 'sku'
LEFT OUTER JOIN dt_phrases AS p ON p.id = s.phrase_ultralog AND p.id_langues = {$this->langue}
SQL;
		$res = $filter->query($q);

		while ($row = $filter->fetch($res)) {
			$liste[$row['id']] = $row;
		}
		
		return $liste;
	}
	
	public function all_links_attributs($limited_refs = null) {
		$q = <<<SQL
SELECT a.ref, oa.id_attributs, oa.id, p1.phrase AS phrase_option, p2.phrase AS phrase_nom FROM dt_options_attributs AS oa
INNER JOIN dt_attributs AS a ON a.id = oa.id_attributs
LEFT OUTER JOIN dt_phrases AS p1 ON p1.id = oa.phrase_option AND p1.id_langues = {$this->langue}
LEFT OUTER JOIN dt_phrases AS p2 ON p2.id = a.phrase_nom AND p2.id_langues = {$this->langue}
SQL;
		if ($limited_refs) {
			$limited_refs_liste = implode("','", $limited_refs);
			$q .= " WHERE a.ref IN ('$limited_refs_liste')";
		}
		$res = $this->sql->query($q);
		$attributs = array();
		while ($row = $this->sql->fetch($res)) {
			if (!isset($attributs[$row['id_attributs']])) {
				$attributs[$row['id_attributs']] = array('ref' => $row['ref'], 'nom' => $row['phrase_nom']);
			}
			$attributs[$row['id_attributs']]['options'][$row['id']] = $row['phrase_option'];
		}
		return $attributs;
	}

	public function links() {
		$links = array();

		$q = <<<SQL
SELECT * FROM dt_assets_links
WHERE id_assets = {$this->id}
SQL;
		$res = $this->sql->query($q);

		while ($row = $this->sql->fetch($res)) {
			$links[$row['link_type']][$row['link_id']] = $row;
		}
		
		return $links;
	}


	public function all_tags() {
		$q = <<<SQL
SELECT id, code FROM dt_assets_tags
SQL;
		$res = $this->sql->query($q);

		$tags = array();
		while ($row = $this->sql->fetch($res)) {
			$tags[$row['id']] = $row['code'];
		}

		return $tags;
	}

	public function tags() {
		$q = <<<SQL
SELECT id_assets_tags FROM dt_assets_tags_assets WHERE id_assets = {$this->id}
SQL;
		$res = $this->sql->query($q);

		$tags = array();
		while ($row = $this->sql->fetch($res)) {
			$tags[] = $row['id_assets_tags'];
		}

		return $tags;
	}

	public function all_targets() {
		$q = <<<SQL
SELECT id, code FROM dt_assets_targets
SQL;
		$res = $this->sql->query($q);

		$targets = array();
		while ($row = $this->sql->fetch($res)) {
			$targets[$row['id']] = $row['code'];
		}

		return $targets;
	}

	public function selected_targets() {
		$q = <<<SQL
SELECT at.id, ata.id_assets FROM dt_assets_targets AS at
LEFT OUTER JOIN dt_assets_targets_assets AS ata ON ata.id_assets_targets = at.id AND ata.id_assets = {$this->id}
SQL;
		$res = $this->sql->query($q);

		$targets = array();
		while ($row = $this->sql->fetch($res)) {
			$targets[$row['id']] = $row['id_assets'] ? true : false;
		}

		return $targets;
	}

	public function targets() {
		$q = <<<SQL
SELECT id_assets_targets FROM dt_assets_targets_assets WHERE id_assets = {$this->id}
SQL;
		$res = $this->sql->query($q);

		$targets = array();
		while ($row = $this->sql->fetch($res)) {
			$targets[] = $row['id_assets_targets'];
		}

		return $targets;
	}
	
	public function all_langues() {
		$q = <<<SQL
SELECT id, code_langue FROM dt_langues
SQL;
		$res = $this->sql->query($q);

		$langues = array();
		while ($row = $this->sql->fetch($res)) {
			$langues[$row['id']] = $row['code_langue'];
		}

		return $langues;
	}

	public function langues() {
		$q = <<<SQL
SELECT id_langues FROM dt_assets_langues WHERE id_assets = {$this->id}
SQL;
		$res = $this->sql->query($q);

		$langues = array();
		while ($row = $this->sql->fetch($res)) {
			$langues[] = $row['id_langues'];
		}

		return $langues;
	}

	public function is_image() {
		if (isset($this->values['fichier'])) {
			$ext = pathinfo($this->values['fichier'], PATHINFO_EXTENSION);
			switch (strtolower($ext)) {
				case "jpg" :
				case "jpeg" :
				case "gif" :
				case "png" :
					return true;
			}
		}

		return false;
	}
}
