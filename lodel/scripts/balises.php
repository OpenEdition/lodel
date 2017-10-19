<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des balises XHTML
 */

// $balises contient toutes les balises reconnues.
// la valeur de la balise definie l'affichage dans chkbalisage.php (et n'a aucune incidence ailleurs).
// les balises principales doivent etre associe a leur name litteral
// les ss balises doivent etre associees a une/ou des balises html ou etre vide.

/*$GLOBALS['balises'] = array (
		"-" => "-",
		"citation" => "<blockquote>",
		"epigraphe" => "<div class=\"balisesinternes\">",
		"typedoc" => "Type de document",
		"finbalise" => "fin",
		"section1" => "<h1>",
		"section2" => "<h2>",
		"section3" => "<h3>",
		"section4" => "<h4>",
		"section5" => "<h5>",
		"section6" => "<h6>",
		"titredoc" => "<i>",
		"legendedoc" => "<i>",
		"titreillustration" => "<i>",
		"legendeillustration" => "<i>",
		"langues" => "Langues",
		# champs auteurs
		"description" => "Description de l'auteur prcdent",
		#
		# balises pour l'import de sommaire
		"regroupement" => "Regroupement",
		"titrenumero" => "Titre de la publication",
		"nomnumero" => "Nom de la publication", 
		"typenumero" => "Type de la publication");*/

// transparent style . Useful for the PDF export
$GLOBALS['stylestransparents'] = "paragraphetransparent|caracteretransparent";

$GLOBALS['balisesdocumentassocie'] = array ("objetdelarecension" => "Objet de la recension", "traduction" => "de la traduction");


// Groups of xhtml tags
// temporaire en attendant la 0.8

$GLOBALS['xhtmlgroups']['xhtml:fontstyle'] = array ("tt", "i", "b", "big", "small");

$GLOBALS['xhtmlgroups']['xhtml:phrase'] = array ("em", "strong", "dfn", "code", "q", "samp", "kbd", "var", "cite", "abbr", "acronym", "sub", "sup", "del");

$GLOBALS['xhtmlgroups']['xhtml:special'] = array ("span", "img", "object", "br", "bdo", "map", "embed", "param", "iframe", "audio", "video", "source", "track");

	$GLOBALS['xhtmlgroups']['xhtml:block'] = array (
		"p", "h1", "h2", "h3", "h4", "h5", "h6", # heading
		"div", "ul", "ol", "dl", "li", "dt", "dd", # list
		"pre", "hr", "blockquote", "address", # blocktext
		"fieldset", "table", "tr", "td", "th", "thead", "tfoot", "tbody", 
		"col", "colgroup", "caption");

$GLOBALS['xhtmlgroups']['Lien'] = array ("a");

$GLOBALS['xhtmlgroups']['Appel de Note'] = array ("a" => "class=\"(foot|end)notecall\"");
$GLOBALS['xhtmlgroups']['mathml'] = array ('abs','and','annotation','annotation-xml','apply','approx','arccos','arccosh','arccot','arccoth','arccsc','arccsch','arcsec','arcsech','arcsin','arcsinh','arctan','arctanh','arg','bind','bvar','card','cartesianproduct','cbytes','ceiling','cerror','ci','cn','codomain','complexes','compose','condition','conjugate','cos','cosh','cot','coth','cs','csc','csch','csymbol','curl','declare','degree','determinant','diff','divergence','divide','domain','domainofapplication','emptyset','eq','equivalent','eulergamma','exists','exp','exponentiale','factorial','factorof','false','floor','fn','forall','gcd','geq','grad','gt','ident','image','imaginary','imaginaryi','implies','in','infinity','int','integers','intersect','interval','inverse','lambda','laplacian','lcm','leq','limit','list','ln','log','logbase','lowlimit','lt','maction','maligngroup','malignmark','math','matrix','matrixrow','max','mean','median','menclose','merror','mfenced','mfrac','mglyph','mi','mi"','min','minus','mlabeledtr','mlongdiv','mmultiscripts','mn','mo','mode','moment','momentabout','mover','mpadded','mphantom','mprescripts','mroot','mrow','ms','mscarries','mscarry','msgroup','msline','mspace','msqrt','msrow','mstack','mstyle','msub','msubsup','msup','mtable','mtd','mtext','mtr','munder','munderover','naturalnumbers','neq','none','not','notanumber','notin','notprsubset','notsubset','or','otherwise','outerproduct','partialdiff','pi','piece','piecewise','plus','power','primes','product','prsubset','quotient','rationals','real','reals','reln','rem','root','scalarproduct','sdev','sec','sech','selector','semantics','sep','set','setdiff','share','sin','sinh','span','subset','sum','tan','tanh','tendsto','times','transpose','true','union','uplimit','variance','vector','vectorproduct','xor');

$GLOBALS['xhtmlgroups']['style:strict'] = array ();
$GLOBALS['xhtmlgroups']['style:none'] = array ();
