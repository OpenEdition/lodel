<?php
//	script-nexen.php,v 1.5 2001/05/30 13:02:01 pierre Exp
/**
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 *  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 *  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *  Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *  Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

<!--    $Id$    -->
<!--	db.inc,v 1.238 2001/05/30 15:43:51 pierre Exp	-->
*/


/*
 *
 *
 * Avertissement : Cette librairie de fonctions PHP est distribuee avec l'espoir 
 * qu'elle sera utile, mais elle l'est SANS AUCUNE GARANTIE; sans meme la garantie de 
 * COMMERCIALISATION ou d'UTILITE POUR UN BUT QUELCONQUE.
 * Elle est librement redistribuable tant que la presente licence, ainsi que les credits des 
 * auteurs respectifs de chaque fonctions sont laisses ensembles. 
 * En aucun cas, Nexen.net ne pourra etre tenu responsable de quelques consequences que ce soit
 * de l'utilisation ou la mesutilisation de ces fonctions PHP.

*/

/****
 * Titre : Dsembrouille HTML 
 * Auteur : Damien seguy 
 * Email : damidamien.seguy@nexen.net
 * Url : www.nexen.net/
 * Description : Quand on programme un script en PHP, on se soucie peu de la lisibilit du code HTML.
Or, c'est bien pratique de pouvoir le parcourir rapidement.
Ce script affiche le code HTML indent, et liste les balises qui n'ont pas t fermes.


Modifie le 9/11/02 par Ghislain PICARD:
* changement des couleurs
* numerotation des tr

****/
function show_html($chaine)
{
	// le deuxieme argument sert a indiquer l'indentation
	// la valeur par defaut est recommandee
	if (func_num_args() == 2)
	{
		$ident = func_get_arg(1);
	}
	else
	{
		$indent = "  ";
	}

	// ce tableau sert au comptage des balises ouvrante/fermante
	$suivi = array ();
	// ce tableau contient les balises ouvrante/fermante
	$tags = array ('html', 'head', 'script', 'noscript', 'div', 'center', 'table', 'td', 'tr', 'select', 'map', 'iframe', 'body', 'title', 'font', 'form', 'left', 'abbrev', 'acronym', 'textarea', 'author', 'blockquote', 'code', 'dl', 'dd', 'dt', 'option', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'li', 'noframes', 'note', 'ul', 'ol', 'pre', 'tt', 'layer', 'span', 'strong');
	// ce tableau contient les balises qui seront laisses dans le corps du texte
	$tagsi = array ('a', 'b', 'address', 'i', 'u', 'blink', 'applet', 'embed', 'sub', 'sup');
	// toutes les autres balises sont ramenees en debut de ligne.

	// traitement des javascript.
	// on les ignore, pour ne pas les chambouler
	preg_match_all("/(<script.*?>.*?<\/script>)/is", $chaine, $js);
	$chaine = preg_replace("/(<script.*?>.*?<\/script>)/is", "<ici_un_script js>", $chaine, $js);
	preg_match_all("/(<noscript.*?>.*?<\/noscript>)/is", $chaine, $njs);
	$chaine = preg_replace("/(<noscript.*?>.*?<\/noscript>)/is", "<ici_un_script njs>", $chaine, $njs);
	$njs = $njs[0];
	$js = $js[0];

	// preparation des lignes
	$chaine = preg_replace("/\n/", "", $chaine);
	$chaine = preg_replace("/\n\s*/", "\n", $chaine);
	$chaine = preg_replace("/(<.*?>)/", "\n\\1\n", $chaine);
	$chaine = preg_replace("/\n\n/", "\n", $chaine);
	$chaine = preg_replace("/\n\s*/", "\n", $chaine);

	$lignes = explode("\n", $chaine);
	$retour = "";
	$i = 0;
	$trarr = array ();
	$trclosearr = array ();
	foreach ($lignes as $l)
	{
		$r = "";
		// si c'est une balise
		if (ereg("^<.*>$", $l))
		{
			// obtention du tag
			if (ereg(' ', $l))
			{
				$tag = substr($l, 1, strpos($l, ' ') - 1);
				$reste = htmlspecialchars(strstr(substr($l, 0, -1), ' '));
			}
			else
			{
				$tag = substr($l, 1, -1);
				$reste = "";
			}
			$tag = strtolower($tag);

			// etude des ouvrant/fermants
			if (ereg('^/', $tag))
			{
				// cas d'une balise fermante
				if (in_array(substr($tag, 1), $tagsi))
				{
					// cas d'une balise fermante a ignorer
					if ((substr($retour, -1) == "\n") && ($i > 0))
					{
						$r .= str_repeat("$indent", $i);
					}
					$r .= "&lt;<b><font color=green>$tag</font></b>$reste&gt;";
				}
				else
					if (in_array(substr($tag, 1), $tags))
					{
						// cas d'une balise fermante reconnue
						$i --;
						@ $suivi[substr($tag, 1)]--;
						$r .= "\n";
						if ($i > 0)
						{
							$r .= str_repeat("$indent", $i);
						}
						if ($tag == "/table")
						{
							$color = "violet";
							if (array_shift($trarr) != array_shift($trclosearr))
								$r .= "<font color=gray>(problme de tr)</font>";
						}
						else
						{
							$color = "red";
						}
						if ($tag == "/tr")
						{
							$trclosearr[0]++;
							$r .= "<font color=\"gray\">($trclosearr[0])</font>";
						}
						$r .= "&lt;<b><font color=\"$color\">$tag</font></b>$reste&gt;\n";
					}
					else
					{
						// une balise inconnue
						if ((substr($retour, -1) == "\n") && ($i > 0))
						{
							$r .= str_repeat("$indent", $i);
						}
						$r .= "&lt;<b><font color=red><blink>$tag</blink></font></b>$reste&gt;";
					}
			}
			else
			{
				// cas des balises ouvrantes
				if (in_array($tag, $tags))
				{
					// cas d'une balise ouvrante reconnue
					$r .= "\n";
					if ($i > 0)
					{
						$r .= str_repeat("$indent", $i);
					}
					if ($tag == "table")
					{
						$color = "violet";
						array_unshift($trarr, 0);
						array_unshift($trclosearr, 0);
					}
					else
					{
						$color = "red";
					}
					if ($tag == "tr")
					{
						$trarr[0]++;
						$r .= "<font color=\"gray\">($trarr[0])</font>";
					}
					$r .= "&lt;<b><font color=\"$color\">$tag</font></b>$reste&gt;\n";
					$i ++;
					@ $suivi[$tag]++;
				}
				else
					if (in_array($tag, $tagsi))
					{
						if ((substr($retour, -1) == "\n") && ($i > 0))
						{
							$r .= str_repeat("$indent", $i);
						}
						$r .= "&lt;<b><font color=green>$tag</font></b>$reste&gt;";
					}
					else
						if ($tag == "ici_un_script")
						{
							// cas d'une balise ouvrante a ignorer
							$reste = substr($reste, 1);
							$script = htmlspecialchars(array_shift($$reste));
							$r .= str_repeat("$indent", $i).preg_replace("/\n\s*/", "\n".str_repeat("$indent", $i +1), $script)."\n";
							$r = preg_replace("/\n$indent(.*?)\n$/", "\n\\1\n", $r);
						}
						else
						{
							// cas d'une balise inconnue
							$r .= "\n";
							if ($i > 0)
							{
								$r .= str_repeat("$indent", $i);
							}
							$r .= "&lt;<b><font color=red><blink>$tag</blink></font></b>$reste&gt;\n";
						}
			}
		}
		else
		{
			// si c'est du texte brut
			if ((substr($retour, -1) == "\n") && ($i > 0))
			{
				$r .= str_repeat("$indent", $i);
			}
			$r .= htmlspecialchars($l);
		}
		$retour .= $r;
	}

	// toilettage final
	$retour = preg_replace("/\n(&nbsp;)+\n/", "\n", $retour);
	$retour = preg_replace("/\n+/", "\n", $retour);
	$retour = preg_replace("/\n+/", "\n", $retour);
	$retour = preg_replace("/&lt;<b><font color=blue>!--<\/font><\/b>(.*?)--&gt;/i", "<font color=\"green\"><b>&lt;--\\1--&gt;</b></font>", $retour);
	$retour = preg_replace("/&gt;(&nbsp;)+/", "&gt;", $retour);
	// cas des commentaires
	$retour = preg_replace("/&quot;(.*?)&quot;/is", "&quot;<font color=blue>\\1</font>&quot;", $retour);

	// la page elle meme
	$out = "<html><body><pre>";
	$out .= $retour;
	$out .= "</pre><hr>";
	// bilan des balises qui ne sont pas suffisamment utilisees
	while (list ($cle, $val) = each($suivi))
	{
		if ($val > 0)
		{
			$out .= "&lt;<b>/$cle</b>&gt; manque $val fois<br>\n";
		}
		else
			if ($val < 0)
			{
				$out .= "&lt;<b>/$cle</b>&gt; est  ".abs($val)." fois en trop<br>\n";
			}
	}
	// on retourne le tout pour affichage
	return $out."</body></html>\n";
}
?>