<?
// fonctions pour enregistrer un document dans la base de donnée

include_once ("$home/func.php");
include ("$home/xmlparser.php");
include ("$home/xmlfunc.php");


function enregistre ($context,&$text)

{
  global $home,$iduser,$revue,$superadmin;
  if ($superadmin) $iduser=0; // n'enregistre pas l'id des superutilisateur... sinon, on risque de les confondre avec les utilisateurs de la revue.

  // enregistre le document
  include_once ("$home/connect.php");

  xml_parse_into_struct_ns($text,&$vals,&$index);

#  // cherche la langue du document
#  $lang=extract_langue("DOCUMENT",$vals,$index);
#  if ($lang[DOCUMENT]) {
#    $langue=$lang[DOCUMENT];
#  } else {
#    // cherche la langue par defaut de la revue
#    $result=mysql_db_query($GLOBALS[database],"SELECT lang FROM revues WHERE rep='$revue'") or die (mysql_error());
#    mysql_select_db($GLOBALS[currentdb]);
#    list($langue)=mysql_fetch_row($result);
#  }
#  if (!$langue) $langue="FR";


//  echo "Index array\n";
//  print_r($index);
//  echo "\nVals array\n";
//  print_r($vals);
//  echo "\ntext";
//  die (htmlentities($text));	    
//  
  // recherche les langues des resumes et des textes
  $lang=extract_langue (array("resume","texte"),$vals,$index,$langue);

  // recupere les informations dans le fichier et nettoie
  $lcontext=extract_xml(array("titre","soustitre","typedoc"),$text);
  $lcontext[titre]=strip_tags($lcontext[titre],"<I><U>");
  $lcontext[soustitre]=strip_tags($lcontext[soustitre],"<I><U>");
  $lcontext[typedoc]=strip_tags($lcontext[typedoc]);

  // enleve les <P> s'ils sont aux extremites, et qu'il n'y en a pas dedans
  /*  $lcontext=preg_replace("/^<P[^>]*>(((?>[^<]+)(?!<P[^>]*>)<?)*)<\/P>$/i",
  			"\\1",$lcontext); */
  $lcontext=preg_replace(array("/<\/?(P|BR)>/i","/^\s+/","/\s+$/"),
			 array("","",""),$lcontext);

  myquote($context);  myquote($lcontext);  myquote($lang);

  // recherche l'ordre
  if ($context[ordre]) {
    $ordre=$context[ordre];
  } else { // ajoute apres l'ordre MAX
    $ordre=get_ordre_max("documents","publication='$context[publication]'");
  }

####  pour modifier un document, il faut le supprimer proprement puis l'inserer. Le syste;e doit produire une erreur si le document existe

// valeur par defaut
  $id=$context[iddocument] ? $context[iddocument] : 0;
  $status=$context[status] ? $context[status] : -1;

  // ecrit dans la base de donnee le document
  mysql_query ("INSERT INTO documents (id,status,titre,soustitre,intro,langresume,lang,meta,publication,type,ordre,user,datepubli) VALUES ('$id','$status','$lcontext[titre]','$lcontext[soustitre]','','$lang[resume]','$lang[texte]','$context[meta]','$context[publication]','$lcontext[typedoc]','$ordre','$iduser','$context[datepubli]')") or die (mysql_error());

  $id=mysql_insert_id();

  // ajoute les auteurs

  enregistre_auteurs($id,$vals,$index);
  enregistre_indexls($id,$vals,$index); // mots cles, etc...
  enregistre_indexhs($id,$vals,$index); // indexhs, etc...

  return $id;
}



function extract_langue ($balises,&$vals,&$index,$defaut="")

{
  $lang=array();
  if (!is_array($balises)) $balises=array($balises);

  foreach ($balises as $bal) {
    // balise avec l'attribut
    $langs=array();
#    echo "$bal $defaut<br>";
#    print_r($index);
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


function enregistre_auteurs ($iddocument,&$vals,&$index)

{
  // detruit les liens dans la table documents_auteurs
 mysql_query("DELETE FROM documents_auteurs WHERE iddocument='$iddocument'") or die (mysql_error());

  if (!$index[auteur]) return;

  foreach($index[auteur] as $ind) {
    $tag=$vals[$ind];
    if ($tag[type]!="open") { continue; }
    $tag=$vals[++$ind]; // on rentre dans le tag auteur
    while ($tag[tag]!="auteur") {
      $lcontext[strtolower($tag[tag])]=trim(addslashes(stripslashes(strip_tags($tag[value]))));
      $tag=$vals[++$ind]; // on rentre dans le tag auteur
    }

    // extrait les balises
    foreach (array("prefix","nomfamille","prenom","affiliation","courriel") as $b) {
      $$b=$lcontext[$b];
    }

    // cherche si l'auteur existe deja
    $result=mysql_query("SELECT id FROM auteurs WHERE nomfamille='".$nomfamille."' AND prenom='".$prenom."'");
    if (mysql_num_rows($result)>0) {
      list($id)=mysql_fetch_array($result);
    } else { // il faut ajouter l'auteur
      mysql_query ("INSERT INTO auteurs (status,prefix,nomfamille,prenom,affiliation,courriel) VALUES ('-1','$prefix','$nomfamille','$prenom','$affiliation','$courriel')") or die (mysql_error());
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

    mysql_query("INSERT INTO documents_auteurs (idauteur,iddocument,ordre) VALUES ('$id','$iddocument','$ordre')") or die (mysql_error());
  }
}


function enregistre_indexls ($iddocument,&$vals,&$index)

{
  if (!$GLOBALS[context][option_pasdemotcle]) $balises[TYPE_MOTCLE]="motcle";
#  print_r($index); echo "<br><br>";
#  print_r($vals); echo "<br><br>";

  if (!$balises) return;

  // detruit les liens dans la table documents_indexls
   mysql_query("DELETE FROM documents_indexls WHERE iddocument='$iddocument'") or die (mysql_error());

  foreach ($balises as $type => $balise) {
#    echo "<br>$balise...";
    if (!$index[$balise]) continue;
#    echo "yes";
    foreach ($index[$balise] as $itag) {
      $tag=$vals[$itag];
#      print_r($tag);
      $indexl=trim(addslashes(strip_tags(strtr($tag[value],"\n"," "))));
      if (!$indexl) continue;
#      echo "<br>mot:$indexl";
      if ($tag[attributes] && $tag[attributes][lang]) $lang=strtolower($tag[attributes][lang]);
      // cherche si le mot cle existe deja existe deja
      $result=mysql_query("SELECT id FROM indexls WHERE mot='$indexl' AND lang='$lang'");
      if (mysql_num_rows($result)) {
	list($id)=mysql_fetch_array($result);
      } else { // il faut ajouter le mot cle
#	if ($type==TYPE_PERIODE) { // type que l'on ajoute pas
#	  $id=0;
#	} else {
	  //	  echo "--$type";
#	  echo "INSERT INTO indexls (mot,type,lang) VALUES ('$indexl','$type','$lang')<BR>";
	  mysql_query ("INSERT INTO indexls (status,mot,type,lang) VALUES ('-1','$indexl','$type','$lang')") or die (mysql_error());
	  $id=mysql_insert_id();
#	}
      }

      // ajoute le mot cle dans la table documents_auteurs
      if ($id)
	mysql_query("INSERT INTO documents_indexls (idindexl,iddocument) VALUES ('$id','$iddocument')") or die (mysql_error());

    } // tags
  } // balises
}


function enregistre_indexhs ($iddocument,&$vals,&$index)

{
  if (!$GLOBALS[context][option_pasdeperiode]) $balises[TYPE_PERIODE]="periode";
  if (!$GLOBALS[context][option_pasdegeographie]) $balises[TYPE_GEOGRAPHIE]="geographie";

  if (!$balises) return;
#  print_r($index); echo "<br><br>";
#  print_r($vals); echo "<br><br>";

  // detruit les liens dans la table documents_indexhs
  mysql_query("DELETE FROM documents_indexhs WHERE iddocument='$iddocument'") or die (mysql_error());

  foreach ($balises as $type => $balise) {
    if (!$index[$balise]) continue;
    foreach ($index[$balise] as $itag) {
      $tag=$vals[$itag];
      $indexh=trim(addslashes(strip_tags($tag[value])));
      if (!$indexh) continue;
      if ($tag[attributes] && $tag[attributes][LANG]) $lang=strtolower($tag[attributes][LANG]);
      if (!$lang) $lang="fr";
      // cherche l'id de la indexh
      $result=mysql_query("SELECT id FROM indexhs WHERE abrev='$indexh' AND lang='$lang'");
      if (mysql_num_rows($result)) {
	list($id)=mysql_fetch_array($result);
      } else { // il faut ajouter le mot cle
	# on ne l'ajoute pas... mais il y a une erreur quelque part...
	$id=0;
      }
#      echo "indexh: $indexh $id<br>";

      // ajoute l'indexh dans la table documents_indexhs
      if ($id)
	mysql_query("INSERT INTO documents_indexhs (idindexh,iddocument) VALUES ('$id','$iddocument')") or die (mysql_error());

    } // tags
  } // balises
}


?>
