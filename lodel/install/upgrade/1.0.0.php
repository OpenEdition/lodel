<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

require 'siteconfig.php';
require 'connect.php';
require 'auth.php';

authenticate(LEVEL_ADMINLODEL);

$sql = array();

foreach( array('entrytypes', 'persontypes', 'internalstyles', 'characterstyles', 'tablefields') as $table ) {
        $sql[] = lq("ALTER TABLE #_TP_{$table} ADD COLUMN `otx` tinytext NOT NULL AFTER `rank`");
}

$sql[] = lq("ALTER TABLE #_TP_tablefields ADD COLUMN `mask` text NOT NULL AFTER `comment`");

$correspondances = array(
	"motsclesfr" 	=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword']",
	"motsclesen" 	=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword']",
	"motscleses" 	=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword']",
	"motsclesde" 	=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='keyword']",
	"chrono"		=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='chronological']",
	"theme"			=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='subject']",
	"licence"		=> "/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:availability",
	"geographie"	=> "/tei:TEI/tei:teiHeader/tei:profileDesc/tei:textClass/tei:keywords[@scheme='geographical']"
);

$sql[] = lq("update #_TP_entrytypes set otx=''");

foreach($correspondances as $k => $v)
{
	$sql[] = lq("UPDATE #_TP_entrytypes SET otx=".$db->quote($v)." WHERE type=".$db->quote($k));
}

$correspondances = array(
	"auteur"				=> "/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:author",
	"traducteur" 			=> "/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[@role='translator']",
	"auteuroeuvre" 			=> "/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-author']",
	"editeurscientifique"	=> "/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:editor[not(@role)]"
);

$sql[] = lq("update #_TP_persontypes set otx=''");

foreach($correspondances as $k => $v)
{
	$sql[] = lq("UPDATE #_TP_persontypes SET otx=".$db->quote($v)." WHERE type=".$db->quote($k));
}

/* Style internes */

$sql[] = lq("update #_TP_internalstyles set otx=''");

$correspondances = array(
	"citation" 					=>	"//*[@rend='quotation']",
	"quotations"				=>	"//*[@rend='reference']",
	"citationbis"				=>	"//*[@rend='quotation2']",
	"citationter"				=>	"//*[@rend='quotation3']",
	"titreillustration"			=>	"//*[@rend='figure-title']",
	"legendeillustration"		=>	"//*[@rend='figure-legend']",
	"code"						=>	"//*[@rend='code']",
	"question"                  =>	"//*[@rend='question']",
	"reponse"					=>	"//*[@rend='answer']",
	"separateur"				=>	"//*[@rend='break']",
	"section1"					=>	"//tei:head[@subtype='level1']",
	"section3"					=>	"//tei:head[@subtype='level3']",
	"section4"					=>	"//tei:head[@subtype='level4']",
	"section5"					=>	"//tei:head[@subtype='level5']",
	"section6"					=>	"//tei:head[@subtype='level6']",
	"paragraphesansretrait"		=>	"//*[@rend='noindent']",
	"epigraphe"					=>	"//*[@rend='epigraph']",
	"section2"					=>	"//tei:head[@subtype='level2']",
	"quotation"					=>	"//*[@rend='quotation']",
	"bibliographiereference"	=>	"//*[@rend='bibliographicreference']",
	"creditillustration,crditillustration,creditsillustration,crditsillustration"
								=>	"//*[@rend='figure-license']",
	"remerciements,acknowledgment"
								=>	"/tei:TEI/tei:text/tei:front/tei:div[@type='ack']",
    "remerciements"             =>  "/tei:TEI/tei:text/tei:front/tei:div[@type='ack']",
	"encadre"					=>  "//*[@rend='box']"
// 	"pigraphe"					=>	"body:epigraph",
// 	"sparateur"					=>	"text:break",
);

foreach($correspondances as $k => $v)
{
	$sql[] = lq("UPDATE #_TP_internalstyles SET otx=".$db->quote($v)." WHERE style=".$db->quote($k));
}

/* Suppression de styles internes */

$suppression = array("puces");
foreach($suppression as $k)
{
    $sql[] = lq("DELETE FROM #_TP_internalstyles WHERE style=".$db->quote($k));
}

/* Modification de style interne */
$modifications = array(
    'separateur'            => '',
    'separateur,sparateur'  => ',',
);

foreach($modifications as $k => $v)
{
    $sql[] = lq("UPDATE #_TP_internalstyles SET conversion=".$db->quote($v)." WHERE style=".$db->quote($k));
}

/* Styles de documents */

$sql[] = lq("update #_TP_tablefields set otx=''");

$correspondances = array(
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='main']"	=> array('titre', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sup']"	=> array('surtitre', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='sub']"	=> array('soustitre', 'textes'),
	"/tei:TEI/tei:text/tei:body/child::*"										=> array('texte', 'textes'),
	"/tei:TEI/tei:text/tei:back/tei:div[@type='appendix']"						=> array('annexe', 'textes'),
	"/tei:TEI/tei:text/tei:back/tei:div[@type='bibliography']"					=> array('bibliographie', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:date"			=> array('datepubli', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:date" 
																				=> array('datepublipapier', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:notesStmt/tei:note[@type='bibl']" 
																				=> array('noticebiblio', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:sourceDesc/tei:biblFull/tei:publicationStmt/tei:idno[@type='pp']"
																				=> array('pagination', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:profileDesc/tei:langUsage/tei:language" 		=> array('langue', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='correction']" 					=> array('addendum', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:note[@resp='editor']/tei:p" 				=> array('ndlr', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='dedication']" 					=> array('dedicace', 'textes'),
	"//tei:roleName[@type='honorific']" 										=> array('prefix', 'entities_auteurs'),
	"//tei:orgName"																=> array('affiliation', 'entities_auteurs'),
 	"//tei:roleName[@type='function']" 											=> array('fonction', 'entities_auteurs'),
	"//tei:affiliation" 														=> array('description', 'entities_auteurs'),
	"//tei:email" 																=> array('courriel', 'entities_auteurs'),
	"//tei:ref[@type='website']" 												=> array('site', 'entities_auteurs'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:titleStmt/tei:title[@type='alt']"	=> array('altertitre', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='abstract']" 					=> array('resume', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-title']" 			=> array('titreoeuvre', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-bibliography']" 	=> array('noticebibliooeuvre', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:div[@type='review']/tei:p[@rend='review-date']" 			=> array('datepublicationoeuvre', 'textes'),
	"/tei:TEI/tei:text/tei:front/tei:note[@resp='author']/tei:p" 								=> array('ndla', 'textes'),
	"/tei:TEI/tei:teiHeader/tei:fileDesc/tei:publicationStmt/tei:idno[@type='documentnumber']" 	=> array('numerodocument', 'textes'),
//	"/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='end']" 									=> array('notefin', 'textes'),
//	"/tei:TEI/tei:text/tei:body/tei:*/tei:note[@place='foot']"					=> array('notesbaspage', 'textes'),
//	'//tei:roleName' => array('role', 'entities_auteurs'),
// 	'header:keywords-fr' => array('motsclesfr', 'textes'),
// 	'lodel:author-lastname' => array('nomfamille', 'auteurs'),
// 	'lodel:author-firstname' => array('prenom', 'auteurs'),
// 	'' => array('editeurscientifique', 'textes'),
);

foreach($correspondances as $k => $v)
{
	$sql[] = lq("UPDATE #_TP_tablefields SET otx=".$db->quote($k)." WHERE name=".$db->quote(current($v))." AND class=".$db->quote(end($v)));
}

echo '<pre>';

print_r($sql);

echo '</pre>';

if(!isset($_GET['valid']))
	echo '<p>Si les requêtes vous semblent bonnes, et que vous avez fait un backup/dump de la base de données, vous pouvez les exécuter en cliquant <a href="?valid=1">ici</a>.</p>';
else
{
    foreach($sql as $s)
    {
            $db->execute($s);
            if($db->errorno() !== 0 && $db->errorno() !== 1060)
                die($s . $db->errormsg());
            
    }
    echo "Migration terminée";
}
