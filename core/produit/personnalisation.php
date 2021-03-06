<?php

require_once "abstract_object.php";

class Personnalisation {

	const UPLOAD_ERROR = 1;
	const INVALID_FORMAT = 2;
	const TOO_SMALL_FILE = 3;
	const TOO_LARGE_FILE = 4;
	const TOO_SMALL_WIDTH = 5;
	const TOO_LARGE_WIDTH = 6;
	const TOO_SMALL_HEIGHT = 7;
	const TOO_LARGE_HEIGHT = 8;

	function __construct($sql, $url, $path_files, $path_www) {
		$this->sql = $sql;
		$this->url = $url;
		$this->path_files = $path_files;
		$this->path_www = $path_www;
	}

	function get_default($id_gabarit, $min_statut = 0) {
		$personnalisations = array(
			'textes' => array(),
			'images' => array(),
		);
		$q = <<<SQL
SELECT * FROM dt_produits_perso_textes WHERE id_produits_perso_gabarits = {$id_gabarit}
SQL;
		$res = $this->sql->query($q);
		while ($row = $this->sql->fetch($res)) {
			if ($row['statut'] >= $min_statut) {
				$personnalisations['textes'][$row['id']] = $row;
			}
		}

		$q = <<<SQL
SELECT * FROM dt_produits_perso_images WHERE id_produits_perso_gabarits = {$id_gabarit}
SQL;
		$res = $this->sql->query($q);
		while ($row = $this->sql->fetch($res)) {
			if ($row['statut'] >= $min_statut) {
				$personnalisations['images'][$row['id']] = $row;
			}
		}

		return $personnalisations;
	}

	function split_css($css) {
		$css_position = array();
		$css_render = array();
		foreach (explode(";", $css) as $propertie) {
			switch (trim(strstr($propertie, ":", true))) {
				case "top":
				case "bottom":
				case "left":
				case "right":
				case "position":
				case "z-index":
					$css_position[] = $propertie;
					break;
				case "width":
				case "height":
					$css_position[] = $propertie;
					$css_render[] = $propertie;
				default:
					$css_render[] = $propertie;
			}
		}

		return array(implode(";", $css_position), implode(";", $css_render));
	}

	function edit_default($id_gabarit, $perso, $nl_tag = false, $replace = array()) {
		$html = <<<HTML
<div class="personnalisation-produit" id="personnalisation-produit-{$id_gabarit}" style="text-align: center;">
<div class="personnalisation-produit-element" style="display: inline-block; position: relative;">
HTML;
		$personnalisations = $this->get_default($id_gabarit);
		$nb_texte = 0;
		foreach($personnalisations['textes'] as $id_texte => $texte) {
			$nb_texte++;
			$css = "";
			$css .= <<<CSS
position: absolute;
z-index: 1;
resize: none;
overflow: hidden;
color: black;
border: none;
box-sizing: border-box;
CSS;
			$css .= str_replace("edit:", "", $texte['css']);
			$css = preg_replace("/\s+/", " ", $css);
			
			list($css_span, $css_field) = $this->split_css($css);

			$contenu = $texte['contenu'];
			if (isset($perso['textes'][$id_texte]) and $perso['textes'][$id_texte]) {
				$contenu = $perso['textes'][$id_texte];
			}
			if ($nl_tag) {
				$contenu = str_replace("\n", $nl_tag, $contenu);
			}
			$readonly = "";
			$editable = "";
			switch ($texte['statut']) {
				case 0 :
					$readonly = 'readonly disabled="disabled"';
					break;
				case 1 :
					$editable = "editable";
					break;
				case 2 :
					$editable = "editable required";
					break;
			}
			$maxlength = $texte['max_caracteres'] ? 'maxlength="'.$texte['max_caracteres'].'"' : "";
			$name = "personnalisation[textes][$id_texte]";
			$html .= <<<HTML
<span class="zone-editable" style="{$css_span}">
<textarea autocomplete="off" {$readonly} {$maxlength} class="personnalisation-produit-texte {$editable}" style="{$css_field}" name="{$name}" id_texte="{$id_texte}">{$contenu}</textarea>
<label class="zone-editable-label" for="personnalisation[textes][$id_texte]">TextZone $nb_texte</label>
<span class="icon-zone-editable texte"></span>
</span>
HTML;
		}
		$nb_image = 0;
		foreach($personnalisations['images'] as $id_image => $image) {
			$apercu = $image['fichier'];
			$bg_size = $image['contain'] ? "contain" : "cover";
			if (isset($perso['images'][$id_image]['apercu']) and $perso['images'][$id_image]['apercu']) {
				$apercu = $perso['images'][$id_image]['apercu'];
			}
			$css = "";
			if ($image['background']) {
				$css .= "position: relative; z-index: 0;";
			}
			else {
				$css .= "position: absolute; z-index: 1;";
			}
			$css .= <<<CSS
background-image: url({$this->url}{$apercu});
background-size: {$bg_size};
background-position: center;
background-repeat: no-repeat;
box-sizing: border-box;
CSS;
			$css .= str_replace("edit:", "", $image['css']);
			$css = preg_replace("/\s+/", " ", $css);

			$input = "";
			$editable = "";
			if ($image['statut']) {
				$nb_image++;
				$input = <<<HTML
<span class="zone-editable">
	<span class="custom-file">
			<input type="file">
		<span class="file-label">ChoseFile</span>
	</span>
	<label class="zone-editable-label" for="">Image {$nb_image}</label>
	<span class="icon-zone-editable image"></span>
</span>
HTML;
				$editable = "editable";
				if ($image['statut'] == 2) {
					$editable .= " required";
				}
			}
			$html .= <<<HTML
<div class="personnalisation-produit-image {$editable}" style="{$css}" id_image="{$id_image}">{$input}</div>
HTML;
		}
		$html .= <<<HTML
</div>
</div>
HTML;
		foreach ($replace as $before => $after) {
			$html = str_replace($before, $after, $html);
		}

		return $html;
	}

	function add_image($id_image, $fichier) {
		$return = array(
			'id_image' => $id_image,
			'url' => "",
			'error' => 0,
			'image' => null,
			'fichier' => "",
			'apercu' => "",
		);
		if ($fichier['error'] == 0) {
			$name = $fichier['name'];
			$tmp_name = $fichier['tmp_name'];
			preg_match("/(\.[^\.]*)$/", $name, $matches);
			$ext = $matches[1];
			$md5 = md5_file($tmp_name);
			$file_name = $md5.$ext;
			$web_name = $md5.".png";
			move_uploaded_file($tmp_name, $this->path_files.$file_name);
			try {
				$im = new Imagick($this->path_files.$file_name);
				$data_image = $this->data_image($id_image);
				if ($error = $this->check_image($im, $data_image)) {
					$return['error'] = $error;
					$return['image'] = $data_image;

					return $return;
				}
				else {
					if (!file_exists($this->path_www.$web_name)) {
						$im->setImageFormat('png');

						$im->resizeImage(500, 500, Imagick::FILTER_LANCZOS, 1, true);

						$im->writeImage( $this->path_www.$web_name);
					}
				}
				$im->clear();
				$im->destroy(); 
			}
			catch (ImagickException $e) {
				$return['error'] = self::INVALID_FORMAT;

				return $return;
			}

			$return['url'] = $this->url.$web_name;
			$return['apercu'] = $web_name;
			$return['fichier'] = $file_name;

			return $return;
		}

		$return['error'] = self::UPLOAD_ERROR;
		
		return $return;
	}

	function data_image($id_image) {
		$q = <<<SQL
SELECT * FROM dt_produits_perso_images WHERE id = $id_image
SQL;
		$res = $this->sql->query($q);
		$row = $this->sql->fetch($res);

		return $row;
	}

	function check_image($im, $data_image) {
		$format = $im->getImageFormat();
		if ($this->is_vector($format) or $format == "TIFF") {
			return 0;
		}
		else if ($format == "JPEG") {
			$length = $im->getImageLength();
			if ($data_image['min_poids'] and ($length < (1000.0 * $data_image['min_poids']))) {
				return self::TOO_SMALL_FILE;
			}
			if ($data_image['max_poids'] and ($length > (1000.0 * $data_image['max_poids']))) {
				return self::TOO_LARGE_FILE;
			}
			
			$width = $im->getImageWidth();
			if ($data_image['min_largeur'] and ($width < $data_image['min_largeur'])) {
				return self::TOO_SMALL_WIDTH;
			}
			if ($data_image['max_largeur'] and ($width > $data_image['max_largeur'])) {
				return self::TOO_LARGE_WIDTH;
			}

			$width = $im->getImageHeight();
			if ($data_image['min_hauteur'] and ($width < $data_image['min_hauteur'])) {
				return self::TOO_SMALL_HEIGHT;
			}
			if ($data_image['max_hauteur'] and ($width > $data_image['max_hauteur'])) {
				return self::TOO_LARGE_HEIGHT;
			}
		}
		else {
			return self::INVALID_FORMAT;
		}
	}

	function is_vector($format) {
		switch ($format) {
			case "PDF" :
			case "PS" :
			case "SVG" :
				return true;
		}
		return false;
	}
}
