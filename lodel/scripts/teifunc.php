<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Parse une DTD afin d'extraire les éléments de définition
 */

function parseDTD($file)
{
	class_exists('XML_DTD_Parser', false) || include 'PEAR/XML/XML_DTD/DTD.php';
	$err = error_reporting(E_ALL ^ E_NOTICE);
	$p = new XML_DTD_Parser;
	$dtd_tree = $p->parse($file);
	error_reporting($err);
	return $dtd_tree;
}

/**
 * Prépare un tableau de variables associatif contenant les éléments d'une DTD
 *
 * @param array &$context le context passé par référence
 * @param array $elements les éléments de la DTD
 */
function mkArrayFromDTD(&$context, array $elements)
{
	$nodes = array(
		'*',
		'node()',
		'text()',
		'.',
		'..'
	);

	$axis = array(
		'ancestor::',
		'ancestor-or-self::',
		'attributes::',
		'child::',
		'descendant::',
		'descendant-or-self::',
		'following::',
		'following-sibling::',
		'parent::',
		'preceding::',
		'preceding-sibling::',
		'self::'
	);

	$children = array('--', '*');
	foreach($axis as $ax)
	{
		foreach($nodes as $n)
		{
			$children[] = $ax.$n;
		}
	}

	$context['elements'] = array('*' => array('attributes' => array('rend' => array(), 'xml:lang' => array())));

	foreach($elements as $k => $v)
	{
		sort($v['children']);
		$v['children'] = array_merge($v['children'], $children);
		ksort($v['attributes']);
		$context['elements'][$k] = array('children' => array_unique($v['children']), 'attributes' => $v['attributes']);
	}
}

/**
 * Ajoute dans le contexte des xpath prédéfinies calquées sur le ME Revues.org
 *
 * @param array &$context le contexte passé par référence
 */
function mkPredefiniteXpath(&$context)
{
	$context['xpaths'] = array(
		'-- Champs --' => '--',
		'Addendum' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'correction\']',
		'Affiliation' => '//tei:affiliation/tei:orgName',
		'Annexes du document' => '/tei:TEI/tei:text/tei:back/tei:div[@type=\'appendix\']',
		'Bibliographie du document' => '/tei:TEI/tei:text/tei:back/tei:div[@type=\'bibliogr\']',
		'Courriel' => '//tei:affiliation/tei:email',
		'Date de la publication électronique' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date',
		'Date de la publication sur papier' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date',
		'Date de publication de l\'oeuvre commentée' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'review\']/tei:p[@rend=\'review-date\']',
		'Dédicace' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'dedication\']',
		'Description de l\'auteur' => '//tei:affiliation',
		'Langue du document' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language',
		'Note de l\'auteur' => '/tei:TEI/tei:text/tei:front/tei:note[@resp=\'author\']/tei:p',
		'Note de la rédaction' => '/tei:TEI/tei:text/tei:front/tei:note[@resp=\'editor\']/tei:p',
		'Notes de bas de page' => '/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place=\'foot\']',
		'Notes de fin de document' => '/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place=\'end\']',
		'Notice bibliographique de l\'oeuvre commentée' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'review\']/tei:p[@rend=\'review-bibliography\']',
		'Notice bibliographique du document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt/tei:note[@type=\'bibl\']',
		'Numéro du document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno[@type=\'documentnumber\']',
		'Pagination du document sur le papier' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:idno[@type=\'pp\']',
		'Préfixe' => '//tei:roleName[@type=\'honorific\']',
		'Résumé' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'abstract\']',
		'Role dans l\'élaboration du document' => '//tei:roleName',
		'Sous-titre du document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type=\'sub\']',
		'Surtitre du document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type=\'sup\']',
		'Texte du document' => '/tei:TEI/tei:text/tei:body/descendant::*[@xml:id]',
		'Titre alternatif du document (dans une autre langue)' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type=\'alt\']',
		'Titre de l\'oeuvre commentée' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'review\']/tei:p[@rend=\'review-title\']',
		'Titre du document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type=\'main\']',
		'-- Personnes --' => '--',
		'Auteur' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author',
		'Auteur d\'une oeuvre commentée' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'review\']/tei:p[@rend=\'review-author\']',
		'Éditeur scientifique' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[not(@role)]',
		'Traducteur' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[@role=\'translator\']',
		'-- Entrées --' => '--',
		'Index by keyword' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'keyword\']',
		'Index chronologique' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'chronological\']',
		'Index de mots-clés' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'keyword\']',
		'Index géographique' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'geographical\']',
		'Index thématique' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'subject\']',
		'Indice de palabras clave' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'keyword\']',
		'Licence portant sur le document' => '/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability',
		'Schlagwortindex' => '/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme=\'keyword\']',
		'-- Styles internes --' => '--',
		'bibliographiereference' => '//*[@rend=\'bibliographicreference\']',
		'citation' => '//*[@rend=\'quotation\']',
		'citationbis' => '//*[@rend=\'quotation2\']',
		'citationter' => '//*[@rend=\'quotation3\']',
		'code' => '//*[@rend=\'code\']',
		'creditillustration,crditillustration,creditsillustration,crditsillustration' => '//*[@rend=\'figure-license\']',
		'epigraphe' => '//*[@rend=\'epigraph\']',
		'legendeillustration' => '//*[@rend=\'figure-legend\']',
		'paragraphesansretrait' => '//*[@rend=\'noindent\']',
		'question' => '//*[@rend=\'question\']',
		'quotation' => '//*[@rend=\'quotation\']',
		'quotations' => '//*[@rend=\'reference\']',
		'remerciements,acknowledgment' => '/tei:TEI/tei:text/tei:front/tei:div[@type=\'ack\']',
		'reponse' => '//*[@rend=\'answer\']',
		'section1' => '//tei:head[@subtype=\'level1\']',
		'section2' => '//tei:head[@subtype=\'level2\']',
		'section3' => '//tei:head[@subtype=\'level3\']',
		'section4' => '//tei:head[@subtype=\'level4\']',
		'section5' => '//tei:head[@subtype=\'level5\']',
		'section6' => '//tei:head[@subtype=\'level6\']',
		'separateur' => '//*[@rend=\'break\']',
		'titreillustration' => '//*[@rend=\'figure-title\']',
	);
}