<?
// fonctions pour enregistrer un document dans la base de donnée

require_once ("$home/func.php");
include_once ("$home/xmlparser.php");
include_once ("$home/xmlfunc.php");


function enregistre ($context,&$text)

{
  global $home,$iduser,$revue,$superadmin;
  if ($superadmin) $iduser=0; // n'enregistre pas l'id des superutilisateur... sinon, on risque de les confondre avec les utilisateurs de la revue.

  // enregistre le document
  include_once ("$home/connect.php");

  xml_parse_into_struct_ns($text,&$vals,&$index);

  // recherche les langues des resumes et des textes
  $lang=extract_langue (array("resume","texte"),$vals,$index,$langue);

  // recupere les informations dans le fichier et nettoie
  $lcontext=extract_xml(array("titre","soustitre","surtitre","typedoc"),$text);
  $lcontext[titre]=strip_tags($lcontext[titre],"<I><U>");
  $lcontext[soustitre]=strip_tags($lcontext[soustitre],"<I><U>");
  $lcontext[surtitre]=strip_tags($lcontext[surtitre],"<I><U>");
  $lcontext[typedoc]=strip_tags($lcontext[typedoc]);

  // enleve les <P> s'ils sont aux extremites, et qu'il n'y en a pas dedans
  $lcontext=preg_replace(array("/<\/?(P|BR)>/i","/^\s+/","/\s+$/"),
			 array("","",""),$lcontext);

  myquote($context);  myquote($lcontext);  myquote($lang);

  lock_write("documents","indexls","auteurs","indexhs","documents_auteurs","documents_indexls","documents_indexhs");

  // recherche l'ordre
  if ($context[ordre]) {
    $ordre=$context[ordre];
  } else { // ajoute apres l'ordre MAX
    $ordre=get_ordre_max("documents","publication='$context[publication]'");
  }

####  pour modifier un document, il faut le supprimer proprement puis l'inserer. Le systeme doit produire une erreur si le document existe

// valeur par defaut
  $id=$context[iddocument] ? $context[iddocument] : 0;
  $statusdocument=$context[statusdocument] ? $context[statusdocument] : -1;

  // reverifie ici que le document n'a pas un titre vide... pour pister le bug des documents vide.
  if (!$lcontext[titre]) die ("Probleme dans dbxml.php. Document vide. Envoyer un mail sur lodel-devel.");

  // ecrit dans la base de donnee le document
  mysql_query ("INSERT INTO $GLOBALS[tableprefix]documents (id,status,titre,soustitre,surtitre,intro,langresume,lang,meta,publication,type,ordre,user,datepubli) VALUES ('$id','$statusdocument','$lcontext[titre]','$lcontext[soustitre]','$lcontext[surtitre]','','$lang[resume]','$lang[texte]','$context[meta]','$context[publication]','$lcontext[typedoc]','$ordre','$iduser','$context[datepubli]')") or die (mysql_error());

  $id=mysql_insert_id();

  // ajoutes les auteurs, et index

  $status=$statusdocument>0 ? 1 : -1;
  enregistre_auteurs($id,$vals,$index,$status);  // ajoute les auteurs
  enregistre_indexls($id,$vals,$index,$status); // mots cles, etc...
  enregistre_indexhs($id,$vals,$index,$status); // indexhs, etc...

  unlock();

  return $id;
}



function extract_langue ($balises,&$vals,&$index,$defaut="")

{
  $lang=array();
  if (!is_array($balises)) $balises=array($balises);

  foreach ($balises as $bal) {
    // balise avec l'attribut
    $langs=array();
    if (!$index[$bal]) continue;
    foreach($index[$bal] as $ind) {
      $tag=$vals[$ind];
      if ($tag[type]!="close") { 
	if ($tag[attributes] &&
	    $tag[attributes][lang]) { // la langue est specifiee
	  array_push ($langs,$vals[$ind][attributes][lang]);
	} else { // sinon on prend la langue par defaut
	  array_push ($langs,$defaut);
	}
      }
    }
    $lang[$bal].=join (" ",array_unique($langs));
  }

  return $lang;
}


function enregistre_auteurs ($iddocument,&$vals,&$index,$status)

{
  // detruit les liens dans la table documents_auteurs
 mysql_query("DELETE FROM $GLOBALS[tableprefix]documents_auteurs WHERE iddocument='$iddocument'") or die (mysql_error());

  if (!$index[auteur]) return;

  foreach($index[auteur] as $ind) {
    $tag=$vals[$ind];
    if ($tag[type]!="open") { continue; }
    $tag=$vals[++$ind]; // on rentre dans le tag auteur
    $context=array();
    while ($tag[tag]!="auteur") {
      $b=strtolower($tag[tag]);
#      echo $b,"<br>";
#      if ($b=="description") {
#	// il faut reconstruire le bloc description... c'est chiant ca !
#	//	$context[description]=rebuildxml($b,$vals,$ind);
#	// temporaire
#	$context[description]=trim(addslashes(stripslashes(strip_tags($tag[value]))));
#      } else {
	$context[$b]=trim(addslashes(stripslashes(strip_tags($tag[value]))));
#      }
      $tag=$vals[++$ind]; // on rentre dans le tag auteur
    }

    // cherche si l'auteur existe deja
    $result=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]auteurs WHERE nomfamille='".$context[nomfamille]."' AND prenom='".$context[prenom]."'") or die (mysql_error());
    if (mysql_num_rows($result)>0) { // ok, l'auteur existe deja
      list($id,$oldstatus)=mysql_fetch_array($result); // on recupere sont id et sont status
      if ($status>0 && $oldstatus<0) { // Faut-il publier l'auteur ?
	mysql_query("UPDATE $GLOBALS[tableprefix]auteurs SET status=1 WHERE id='$id'") or die (mysql_error());
      }
    } else {
      mysql_query ("INSERT INTO $GLOBALS[tableprefix]auteurs (status,prefix,nomfamille,prenom) VALUES ('$status','$context[prefix]','$context[nomfamille]','$context[prenom]')") or die (mysql_error());
      $id=mysql_insert_id();
    }
  
    // cherche l'ordre de l'auteur
    if ($tag[attributes] &&
	$tag[attributes][ORDRE]) { // l'ordre est specifiee
      $ordre=$tag[attributes][ORDRE];
    } else {
      $ordre=get_ordre_max("documents_auteurs","iddocument='$iddocument'");
    }

    // ajoute l'auteur dans la table documents_auteurs
    // ainsi que la description
    mysql_query("INSERT INTO $GLOBALS[tableprefix]documents_auteurs (idauteur,iddocument,ordre,description,prefix) VALUES ('$id','$iddocument','$ordre','$context[description]','$context[prefix]')") or die (mysql_error());
  }
}


function enregistre_indexls ($iddocument,&$vals,&$index,$status)

{
  if (!$GLOBALS[context][option_pasdemotcle]) $balises[TYPE_MOTCLE]="motcle";

  if (!$balises) return;

  // detruit les liens dans la table documents_indexls
   mysql_query("DELETE FROM $GLOBALS[tableprefix]documents_indexls WHERE iddocument='$iddocument'") or die (mysql_error());

  foreach ($balises as $type => $balise) {
    if (!$index[$balise]) continue;
    foreach ($index[$balise] as $itag) {
      $tag=$vals[$itag];
      $indexl=trim(addslashes(strip_tags(strtr($tag[value],"\n"," "))));
      if (!$indexl) continue;
      if ($tag[attributes] && $tag[attributes][lang]) $lang=strtolower($tag[attributes][lang]);
      // cherche si le mot cle existe deja existe deja
      $result=mysql_query("SELECT id,status FROM indexls WHERE mot='$indexl' AND lang='$lang'");
      if (mysql_num_rows($result)) { // existe-t-il ?
	list($id,$oldstatus)=mysql_fetch_array($result); // oui, on recupere sont id et sont status
	if ($status>0 && $oldstatus<0) { // faut-il publier ce mot cle ?
	  mysql_query("UPDATE $GLOBALS[tableprefix]indexls SET status=1 WHERE id='$id'") or die (mysql_error());	
	}
      } else { // il faut ajouter le mot cle
	  mysql_query ("INSERT INTO $GLOBALS[tableprefix]indexls (status,mot,type,lang) VALUES ('$status','$indexl','$type','$lang')") or die (mysql_error());
	  $id=mysql_insert_id();
      }

      // ajoute le mot cle dans la table documents_auteurs
      if ($id)
	mysql_query("INSERT INTO $GLOBALS[tableprefix]documents_indexls (idindexl,iddocument) VALUES ('$id','$iddocument')") or die (mysql_error());

    } // tags
  } // balises
}


function enregistre_indexhs ($iddocument,&$vals,&$index,$status)

{
  if (!$GLOBALS[context][option_pasdeperiode]) $balises[TYPE_PERIODE]="periode";
  if (!$GLOBALS[context][option_pasdegeographie]) $balises[TYPE_GEOGRAPHIE]="geographie";

  if (!$balises) return;
#  print_r($index); echo "<br><br>";
#  print_r($vals); echo "<br><br>";

  // detruit les liens dans la table documents_indexhs
  mysql_query("DELETE FROM $GLOBALS[tableprefix]documents_indexhs WHERE iddocument='$iddocument'") or die (mysql_error());

  foreach ($balises as $type => $balise) {
    if (!$index[$balise]) continue;
    foreach ($index[$balise] as $itag) {
      $tag=$vals[$itag];
      $indexh=trim(addslashes(strip_tags($tag[value])));
      if (!$indexh) continue;
      if ($tag[attributes] && $tag[attributes][LANG]) $lang=strtolower($tag[attributes][LANG]);
      if (!$lang) $lang="fr";
      // cherche l'id de la indexh
      $result=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]indexhs WHERE abrev='$indexh' AND lang='$lang'");
      if (mysql_num_rows($result)) {
	list($id,$oldstatus)=mysql_fetch_array($result);
	if ($status>0 && $oldstatus<0) { // faut-il publier ?
	  mysql_query("UPDATE $GLOBALS[tableprefix]indexhs SET status=1 WHERE id='$id'") or die (mysql_error());	
	}
      } else { // il faut ajouter le mot cle
	# on ne l'ajoute pas... mais il y a une erreur quelque part...
	$id=0;
      }

      // ajoute l'indexh dans la table documents_indexhs
      if ($id)
	mysql_query("INSERT INTO $GLOBALS[tableprefix]documents_indexhs (idindexh,iddocument) VALUES ('$id','$iddocument')") or die (mysql_error());

    } // tags
  } // balises
}


/*
function rebuildxml($name,$vals,$ind);

{
  $ret=$tag[value];
  while (($tag=$vals[++$ind]) && ($tag[tag]!="description")) {
    if ($tag[type]=="open") {
      $ret.=translate_xmldata($tag[value]).rebuild_opentag($tag[name],$tag[attr]);
    } elseif  ($tag[type]=="close") {
      $ret.=translate_xmldata($tag[value])."</$tag[name]>";
    } elseif  ($tag[type]=="complete") {
      $ret.=rebuild_opentag($tag[name],$tag[attr]).translate_xmldata($tag[value])."</$tag[name]>";
    }
=$tag[value];
  }
}
*/


?>
