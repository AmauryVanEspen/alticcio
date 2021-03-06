<?php
/*
 * Classe PHP qui génére des statistiques sur les contacts
 */


class StatsCommandes {
	
	public function __construct($sql = null, $dico = null, $shop = 3) {
		$this->sql = $sql;
		$this->dico = $dico;
		$this->shop = (int)$shop;
		die($this->shop);
	}
	
	public function nombre_commandes_par_mois() {
		$q = "SELECT DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%Y') AS annee, DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%m') AS mois, COUNT(*) AS total
				FROM dt_commandes as c
				WHERE (c.paiement_statut != 'refuse' AND c.paiement_statut != 'annule') AND shop = {$this->shop} AND id_api_keys = 0
				GROUP BY annee, mois ";
		$rs = $this->sql->query($q);
		$tab = array();
		while ($row = $this->sql->fetch($rs)) {
			$tab[] = $row;
		};
		return $tab;
	}
	
	public function nombre_lignes_commandes_par_mois() {
		$q = "SELECT DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%Y') AS annee, DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%m') AS mois, AVG(cp.id_commandes) AS total
				FROM dt_commandes_produits AS cp
				INNER JOIN dt_commandes AS c
				ON cp.id_commandes = c.id  
				AND (c.paiement != 'refuse' OR c.paiement != 'annule') AND c.shop = {$this->shop} AND c.id_api_keys = 0
				GROUP BY cp.id_commandes, c.annee, c.mois ";
		$rs = $this->sql->query($q);
		$tab = array();
		while ($row = $this->sql->fetch($rs)) {
			$tab[] = $row;
		};
		return $tab;
	}
	
	public function chiffre_affaires_par_annee_mois() {
		$q = "SELECT DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%Y') AS annee, DATE_FORMAT(FROM_UNIXTIME(c.date_commande), '%m') AS mois, SUM(c.montant) AS total
				FROM dt_commandes as c
				WHERE (c.paiement_statut != 'refuse' AND c.paiement_statut != 'annule') AND shop = {$this->shop} AND id_api_keys = 0
				GROUP BY annee, mois";
		$rs = $this->sql->query($q);
		$tab = array();
		while ($row = $this->sql->fetch($rs)) {
			$tab[] = $row;
		};
		return $tab;
	}
	
	public function panier_moyen_par_annee_mois() {
		$q = "SELECT DATE_FORMAT( FROM_UNIXTIME( c.date_commande ) , '%Y' ) AS annee, DATE_FORMAT( FROM_UNIXTIME( c.date_commande ) , '%m' ) AS mois, SUM( c.montant ) AS total, COUNT( * ) AS nbre
				FROM dt_commandes AS c
				WHERE ( c.paiement_statut != 'refuse' AND c.paiement_statut != 'annule' )
				AND shop = {$this->shop}
				AND id_api_keys =0
				GROUP BY annee, mois";
		$rs = $this->sql->query($q);
		$tab = array();
		while ($row = $this->sql->fetch($rs)) {
			$tab[] = array("annee"=>$row['annee'], "mois"=>$row['mois'], "total"=>$row['total']/$row['nbre']);
		};
		return $tab;
	}
	
	private function format_results($nombre, $format) {
		if ($format == "montant") {
			return number_format($nombre, 2, ',', '.');
		}
		else {
			return $nombre;
		}
	}
	
	public function afficher_tableau($valeurs, $format="") {
		$i = 0;
		$prev_annee = 0;
		$mois = array(	"01" => $this->dico->t('MoisJanvier'),
				    "02" => $this->dico->t('MoisFevrier'),
				    "03" => $this->dico->t('MoisMars'),
				    "04" => $this->dico->t('MoisAvril'),
				    "05" => $this->dico->t('MoisMai'),
				    "06" => $this->dico->t('MoisJuin'),
				    "07" => $this->dico->t('MoisJuillet'),
				    "08" => $this->dico->t('MoisAout'),
				    "09" => $this->dico->t('MoisSeptembre'),
				    "10" => $this->dico->t('MoisOctobre'),
				    "11" => $this->dico->t('MoisNovembre'),
				    "12" => $this->dico->t('MoisDecembre') );
		
		// on recense les années
		$annees = array();
		$annees[] = "";
		foreach($valeurs as $k => $v) {
			if ($prev_annee != $v['annee']) {
				$annees[] = $v['annee'];
			}
			$prev_annee = $v['annee'];
		}
		
		// On génére le tableau HTML
		$html = '<table>';
		foreach($annees as $a) {
			$html .= '<tr>';
			$html .= '<td>'.$a.'</td>';
			if (empty($a)) {
				foreach($mois as $m => $month) {
					$html .= '<td>'.$month.'</td>';
				}
				$html .= '<td><strong>TOTAL</strong></td>';
			}
			else {
				$total_annee = 0;
				foreach($mois as $m => $month) {
					$total = 0;
					foreach($valeurs as $k => $v) {
						if ($v['annee'] == $a AND $v['mois'] == $m) {
							$total = round($v['total'],2);
						}
					}
					$total_annee = $total_annee + $total;
					if ($total > 0) {
						$html .= '<td>'.$this->format_results($total, $format).'</td>';
						
					}
					else {
						$html .= '<td>0</td>';
					}
				}
				$html .= '<td><strong>'.$this->format_results($total_annee, $format).'</strong></td>';
			}
			$html .= '</tr>';
		}
		$html .= '</table>';
		return $html;
	}
}
?>
