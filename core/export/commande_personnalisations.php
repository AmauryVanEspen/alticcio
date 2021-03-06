<?php

require_once "abstract_export.php";

class ExportCommandePersonnalisations extends AbstractExport {
	
	public $export_table = "commandes_produits_personnalisations";

	public $nb_textes = 30;
	public $nb_images = 15;

	public function export() {
		$date_export = time();
		$fields = $this->fields();
		$this->prepare($fields);

		$values = array();
		$i = 1;
		foreach ($this->cmds_perso_a_exporter($date_export) as $cmd) {
			$values[] = $cmd;
			if ($i % 500 == 0) {
				$this->insert_values($fields, $values);
				$values = array();
			}
			$i++;
		}
		$this->insert_values($fields, $values);
	}

	public function fields() {
		$fields = array(
			'id',
			'id_commande',
			'id_produit',
			'id_sku',
			'code_famille_vente',
			'ref',
			'nom',
			'quantite',
			'time_commande',
			'date_commande',
			'time_export',
			'date_export',
			'bat',
		);

		for ($i = 1; $i <= $this->nb_textes; $i++) {
			$fields[] = "texte_$i";
		}

		for ($i = 1; $i <= $this->nb_images; $i++) {
			$fields[] = "image_$i";
		}

		return $fields;
	}

	public function time_last_commande() {
		$q = <<<SQL
SELECT MAX(time_commande) AS time_last_commande FROM {$this->export_table}
SQL;
		$res = $this->sql_export->query($q);
		$row = $this->sql_export->fetch($res);

		return $row['time_last_commande'];
	}

	public function last_id() {
		$q = <<<SQL
SELECT MAX(id) AS last_id FROM {$this->export_table}
SQL;
		$res = $this->sql_export->query($q);
		$row = $this->sql_export->fetch($res);

		return (int)$row['last_id'];
	}

# TODO Gérer les révisions
	public function cmds_perso_a_exporter($date_export) {
		$time_last_commande = $this->time_last_commande();
		$id = $this->last_id();
		$cmds = array();
		$ids_cmds = array();
		$textes = array();
		$images = array();

		// Les textes
		$q = <<<SQL
SELECT cp.id, cpt.texte
FROM dt_commandes_produits AS cp
INNER JOIN dt_commandes AS c ON c.id = cp.id_commandes
INNER JOIN dt_commandes_perso_textes AS cpt ON cpt.id_commandes_produits = cp.id
INNER JOIN dt_produits_perso_textes AS ppt ON ppt.id = cpt.id_produits_perso_textes
WHERE 1
SQL;
		if ($time_last_commande) {
			$q .= " AND c.date_commande > $time_last_commande";
		}
		$q .= " ORDER BY ppt.id";
		$res = $this->sql->query($q);
		while ($row = $this->sql->fetch($res)) {
			$ids_cmds[$row['id']] = $row['id'];
			$textes[$row['id']][] = addslashes($row['texte']);
		}

		// Les images
		$q = <<<SQL
SELECT cp.id, cpi.fichier
FROM dt_commandes_produits AS cp
INNER JOIN dt_commandes AS c ON c.id = cp.id_commandes
INNER JOIN dt_commandes_perso_images AS cpi ON cpi.id_commandes_produits = cp.id
INNER JOIN dt_produits_perso_images AS ppi ON ppi.id = cpi.id_produits_perso_images
WHERE 1
SQL;
		if ($time_last_commande) {
			$q .= " AND c.date_commande > $time_last_commande";
		}
		$q .= " ORDER BY ppi.id";
		$res = $this->sql->query($q);
		while ($row = $this->sql->fetch($res)) {
			$ids_cmds[$row['id']] = $row['id'];
			$images[$row['id']][] = addslashes($row['fichier']);
		}

		if (count($ids_cmds)) {
			$id_commandes_liste = implode(",", $ids_cmds);

			// Les autres infos des commandes
			$q = <<<SQL
SELECT cp.id, cp.id_commandes, cp.id_produits, cp.id_sku, fv.code, cp.ref, cp.nom, cp.quantite, c.date_commande
FROM dt_commandes_produits AS cp
INNER JOIN dt_commandes AS c ON c.id = cp.id_commandes
INNER JOIN dt_sku AS s ON s.id = cp.id_sku
INNER JOIN dt_familles_ventes AS fv ON fv.id = s.id_familles_vente
WHERE cp.id IN ({$id_commandes_liste})
SQL;
			$res = $this->sql->query($q);
			while ($row = $this->sql->fetch($res)) {
				$cmd = array(
					'id' => $row['id'],
					'id_commande' => $row['id_commandes'],
					'id_produit' => $row['id_produits'],
					'id_sku' => $row['id_sku'],
					'code_famille_vente' => $row['code'],
					'ref' => $row['ref'],
					'nom' => $row['nom'],
					'quantite' => $row['quantite'],
					'time_commande' => $row['date_commande'],
					'date_commande' => date("Y-m-d", $row['date_commande']),
					'time_export' => $date_export,
					'date_export' => date("Y-m-d", $date_export),
					'bat' => "",
				);

				for ($i = 1; $i <= $this->nb_textes; $i++) {
					$cmd["texte_$i"] = isset($textes[$row['id']][$i - 1]) ? $textes[$row['id']][$i - 1] : "";
				}

				for ($i = 1; $i <= $this->nb_images; $i++) {
					$cmd["image_$i"] = isset($images[$row['id']][$i - 1]) ? $images[$row['id']][$i - 1] : "";
				}

				$cmds[$row['id']] = $cmd;
			}
		}

		return $cmds;
	}

	public function prepare($fields) {
		$field_list = "";
		foreach ($fields as $field) {
			if (in_array($field, array("id", "id_commande", "id_produit", "id_sku", "code_famille_vente", "quantite", "id_texte", "id_fichier", "time_commande", "time_export"))) {
				$field_list .= "`$field` int(11) NOT NULL,";
			}
			else if (in_array($field, array("date_commande", "date_export"))) {
				$field_list .= "`$field` date NOT NULL,";
			}
			else {
				$field_list .= "`$field` mediumtext NOT NULL,";
			}
		}
		$q = <<<SQL
CREATE TABLE IF NOT EXISTS `{$this->export_table}` (
  $field_list
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
SQL;
		$this->sql_export->query($q);
	}
}

