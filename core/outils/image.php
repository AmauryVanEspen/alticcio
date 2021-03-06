<?php

class Image {

	private $sql;
	
	public function __construct($sql) {
		$this->sql = $sql;
	}

	public function types($id_langues) {
		$q = <<<SQL
SELECT ti.largeur, ti.hauteur, ph.phrase AS description FROM dt_types_images AS ti
LEFT OUTER JOIN dt_phrases AS ph ON ph.id = ti.phrase_description AND ph.id_langues = {$id_langues}
ORDER BY ti.largeur, ti.hauteur
SQL;
		$result = $this->sql->query($q);
		$liste = array();
		while ($row = $this->sql->fetch($result)) {
			$liste[] = $row;
		}
		return $liste;
	}
}
