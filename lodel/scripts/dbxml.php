<?php
// fonctions pour enregistrer un document dans la base de donnée

require_once ($home."func.php");
include_once ($home."xmlparser.php");
include_once ($home."xmlfunc.php");


function enregistre ($context,&$text)

{
  #  die(htmlentities($text));
  global $home,$iduser,$superadmin;
  if ($superadmin) $iduser=0; // n'enregistre pas l'id des superutilisateur... sinon, on risque de les confondre avec les utilisateurs du site.

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

  lock_write("entites","documents","personnes","entrees","entites_personnes","entites_entrees","typeentrees","typepersonnes","relations","types");

  // recherche l'ordre
  if ($context[ordre]) {
    $ordre=$context[ordre];
  } else { // ajoute apres l'ordre MAX
    $ordre=get_ordre_max("entites","idparent='$context[idparent]'");
  }

  // recherche l'id du type
  $result=mysql_query("SELECT id FROM $GLOBALS[tp]types WHERE type='$lcontext[typedoc]'") or die (mysql_error());
  if (!mysql_num_rows($result)) die("le type de document n'existe pas... probleme dans l'interface");
  list($lcontext[idtype])=mysql_fetch_row($result);


####  pour modifier un document, il faut le supprimer proprement puis l'inserer. Le systeme doit produire une erreur si le document existe

// valeur par defaut
  $id=$context[iddocument] ? $context[iddocument] : 0;
  $statusdocument=$context[statusdocument] ? $context[statusdocument] : -1;

  // reverifie ici que le document n'a pas un titre vide... pour pister le bug des documents vide.
  if (!$lcontext[titre]) die ("Probleme dans dbxml.php. Document avec titre vide. Envoyer un mail sur lodel-devel: $lcontext[titre]<br><br>".htmlentities($text));

  // ecrit dans la base de donnee le document
  mysql_query ("INSERT INTO $GLOBALS[tp]entites (id,idparent,idtype,nom,ordre,user,status) VALUES ('$id','$context[idparent]','$lcontext[idtype]','$lcontext[titre]','$ordre','$iduser','$statusdocument')") or die (mysql_error());

  if (!$id) $id=mysql_insert_id();

  mysql_query ("INSERT INTO $GLOBALS[tp]documents (identite,titre,soustitre,surtitre,intro,langresume,lang,datepubli) VALUES ('$id','$lcontext[titre]','$lcontext[soustitre]','$lcontext[surtitre]','','$lang[resume]','$lang[texte]','$context[datepubli]')") or die (mysql_error());
  require_once($home."managedb.php");
  creeparente($id,$context[idparent],FALSE);

  // ajoutes les personnes, et index

  $status=$statusdocument>0 ? 1 : -1;
  enregistre_personnes($id,$vals,$index,$status);  // ajoute les personnes
  enregistre_entrees($id,$vals,$index,$status); // indexs, etc...

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


function enregistre_personnes ($identite,&$vals,&$index,$status)

{
  // detruit les liens dans la table entites_personnes
 mysql_query("DELETE FROM $GLOBALS[tp]entites_personnes WHERE identite='$identite'") or die (mysql_error());

  if (!$index[personne]) return;

  // cree un hash avec les informations pour les index
  $result=mysql_query("SELECT id,type FROM $GLOBALS[tp]typepersonnes WHERE status>0") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result)) $typepersonnes[$row[type]]=$row;


  foreach($index[personne] as $ind) {
    $tag=$vals[$ind];
    if ($tag[type]!="open") { continue; }
    // recherche le type de la personne
    if (!$tag[attributes] || !$tag[attributes][type]) die("erreur interne 1 dans enregistre_personnes");
    $nomtype=$tag[attributes][type];
    $typepersonne=$typepersonnes[$nomtype];
    if (!$typepersonne) die ("probleme dans enregistre_personnes... nomtype=$nomtype");

    $tag=$vals[++$ind]; // on rentre dans le tag personne
    $context=array();
    while ($tag[tag]!="personne") {
      $b=strtolower($tag[tag]);
      $context[$b]=trim(addslashes(stripslashes(strip_tags($tag[value]))));
      $tag=$vals[++$ind]; // on rentre dans le tag personne
    }

    // cherche si l'personne existe deja
    $result=mysql_query("SELECT id,status FROM $GLOBALS[tp]personnes WHERE nomfamille='".$context[nomfamille]."' AND prenom='".$context[prenom]."'") or die (mysql_error());
    if (mysql_num_rows($result)>0) { // ok, l'personne existe deja
      list($id,$oldstatus)=mysql_fetch_array($result); // on recupere sont id et sont status
      if ($status>0 && $oldstatus<0) { // Faut-il publier l'personne ?
	mysql_query("UPDATE $GLOBALS[tp]personnes SET status=1 WHERE id='$id'") or die (mysql_error());
      }
    } else {
      mysql_query ("INSERT INTO $GLOBALS[tp]personnes (status,nomfamille,prenom) VALUES ('$status','$context[nomfamille]','$context[prenom]')") or die (mysql_error());
      $id=mysql_insert_id();
    }
  
    // cherche l'ordre de l'personne
    if ($tag[attributes] &&
	$tag[attributes][ORDRE]) { // l'ordre est specifiee
      $ordre=$tag[attributes][ORDRE];
    } else {
      $ordre=get_ordre_max("entites_personnes","identite='$identite'");
    }

    // ajoute l'personne dans la table entites_personnes
    // ainsi que la description
    mysql_query("INSERT INTO $GLOBALS[tp]entites_personnes (idpersonne,identite,idtype,ordre,description,prefix,affiliation,fonction,courriel) VALUES ('$id','$identite','$typepersonne[id]','$ordre','$context[description]','$context[prefix]','$context[affiliation]','$context[fonction]','$context[courriel]')") or die (mysql_error());
  }
}


function enregistre_entrees ($identite,&$vals,&$index,$status)

{
  // detruit les liens dans la table entites_indexhs
  mysql_query("DELETE FROM $GLOBALS[tp]entites_entrees WHERE identite='$identite'") or die (mysql_error());

  if (!$index[entree]) continue; // s'il n'y a pas d'entrees, on reboucle

  // cree un hash avec les informations pour les index
  $result=mysql_query("SELECT id,type,newimportable,useabrev FROM $GLOBALS[tp]typeentrees WHERE status>0") or die (mysql_error());
  while ($row=mysql_fetch_assoc($result)) $typeentrees[$row[type]]=$row;

#  print_r($index);
#  print_r($vals);
#  exit();

  // boucle sur les differents entrees
  foreach ($index[entree] as $itag) {
    $tag=$vals[$itag];
#    print_r($tag);
    // recherche le type de l'entree
    if (!$tag[attributes] || !$tag[attributes][type]) die("erreur interne 1 dans enregistre_entrees");
    $nomtype=$vals[$itag][attributes][type];
    $typeentree=$typeentrees[$nomtype];
    if (!$typeentree) die ("probleme dans enregistre_entrees... nomtype=$nomtype");
    // ok, on connait le type de l'entree.

    // on recupere et on nettoie le contenu de l'entree
    $entree=trim(addslashes(strip_tags($tag[value]))); 
    if (!$entree) continue; // etrange elle est vide... tant pis, XML pas propre
    // cherche la langue dans l'attribut sinon valeur par defaut.
    $lang=($tag[attributes] && $tag[attributes][lang]) ? strtolower($tag[attributes][lang]) : "fr";
    // cherche l'id de la entree si elle existe
    $result2=mysql_query("SELECT id,status FROM $GLOBALS[tp]entrees WHERE (abrev='$entree' OR nom='$entree') AND lang='$lang' AND status>0 AND idtype='$typeentree[id]'") or die(mysql_error());

    if (mysql_num_rows($result2)) { // l'entree exists
      list($id,$oldstatus)=mysql_fetch_array($result2);
      if ($status>0 && $oldstatus<0) { // faut-il publier ?
	mysql_query("UPDATE $GLOBALS[tp]entrees SET status=1 WHERE id='$id'") or die (mysql_error());	
      }
    } elseif ($typeentree[newimportable]) { // l'entree n'existe pas. est-ce qu'on a le droit de l'ajouter ?
      // oui,il faut ajouter le mot cle
      $abrev=$typeentree[useabrev] ? strtoupper($entree) : "";
      mysql_query ("INSERT INTO $GLOBALS[tp]entrees (status,nom,abrev,idtype,lang) VALUES ('$status','$entree','$abrev','$typeentree[id]','$lang')") or die (mysql_error());
      $id=mysql_insert_id();
    } else {
      // non, on ne l'ajoute pas... mais il y a une erreur quelque part...
      $id=0;
      die ("erreur interne 2 dans enregistre_entrees: type: $balise entree: $entree");
    }
    // ajoute l'entree dans la table entites_entrees
    // on pourrait optimiser un peu ca... en mettant plusieurs values dans 
    // une chaine et en faisant la requette a la fin !
    if ($id)
      mysql_query("INSERT INTO $GLOBALS[tp]entites_entrees (identree,identite) VALUES ('$id','$identite')") or die (mysql_error());
    
  } // tags
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
