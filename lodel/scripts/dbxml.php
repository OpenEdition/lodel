<?
// fonctions pour enregistrer un document dans la base de donnée

require_once ($home."func.php");
include_once ($home."xmlparser.php");
include_once ($home."xmlfunc.php");


function enregistre ($context,&$text)

{
  //  die(htmlentities($text));
  global $home,$iduser,$revue,$superadmin;
  if ($superadmin) $iduser=0; // n'enregistre pas l'id des superutilisateur... sinon, on risque de les confondre avec les utilisateurs de la revue.

  // enregistre le document
  include_once ($home."connect.php");

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

  lock_write("documents","auteurs","indexs","documents_auteurs","documents_indexs","typeindexs");

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
  enregistre_indexs($id,$vals,$index,$status); // indexs, etc...

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

/*
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
*/


function enregistre_indexs ($iddocument,&$vals,&$index,$status)

{
  // detruit les liens dans la table documents_indexhs
  mysql_query("DELETE FROM $GLOBALS[tableprefix]documents_indexs WHERE iddocument='$iddocument'") or die (mysql_error());

  // enregistre les indexs
  $result=mysql_query("SELECT id,balise,newimportable,useabrev FROM $GLOBALS[tableprefix]typeindexs WHERE status>0") or die (mysql_error());
  while ($typeindex=mysql_fetch_assoc($result)) {    
    $balise=$typeindex[balise];    $type=$typeindex[id];
    if (!$index[$balise]) continue; // s'il n'y a pas d'entrees, on reboucle

    // boucle sur les differents entrees
    foreach ($index[$balise] as $itag) {
      $tag=$vals[$itag];
      $entree=trim(addslashes(strip_tags($tag[value]))); // on recupere et on nettoie l'entree
      if (!$entree) continue; // etrange elle est vide... tant pis, XML pas propre
      // cherche la langue dans l'attribut sinon valeur par defaut.
      $lang=($tag[attributes] && $tag[attributes][LANG]) ? strtolower($tag[attributes][LANG]) : "fr";
      // cherche l'id de la entree si elle existe
      $result2=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]indexs WHERE (abrev='$entree' OR nom='$entree') AND lang='$lang' AND status>0");

      if (mysql_num_rows($result2)) { // l'entree exists
	list($id,$oldstatus)=mysql_fetch_array($result2);
	if ($status>0 && $oldstatus<0) { // faut-il publier ?
	  mysql_query("UPDATE $GLOBALS[tableprefix]indexs SET status=1 WHERE id='$id'") or die (mysql_error());	
	}
      } elseif ($typeindex[newimportable]) { // l'entree n'existe pas. est-ce qu'on a le droit de l'ajouter ?
	// oui,il faut ajouter le mot cle
	$abrev=$typeindex[useabrev] ? strtoupper($entree) : "";
	mysql_query ("INSERT INTO $GLOBALS[tableprefix]indexs (status,nom,abrev,type,lang) VALUES ('$status','$entree','$abrev','$type','$lang')") or die (mysql_error());
	$id=mysql_insert_id();
      } else {
	// non, on ne l'ajoute pas... mais il y a une erreur quelque part...
	$id=0;
	die ("erreur interne dans enregistre_indexs: type: $balise entree: $entree");
      }
      // ajoute l'index dans la table documents_indes
      // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
      // une chaine et en faisant la requette a la fin !
      if ($id)
	mysql_query("INSERT INTO $GLOBALS[tableprefix]documents_indexs (idindex,iddocument) VALUES ('$id','$iddocument')") or die (mysql_error());

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
