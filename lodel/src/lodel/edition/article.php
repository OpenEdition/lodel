<?

// specifique a LODELIGHT
#ifndef LODELLIGHT
die("erreur");
#endif

// gere les articles. L'acces est reserve aux administrateurs de la revue.
// assure l'edition, la supression, la restauration des articles.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include("$home/func.php");

$context[id]=$id=intval($id);
$context[publication]=$publication=intval($publication);

if ($id>0) {
  $critere="id='$id'";
  if (!$admin) {
    $critere2=" AND groupe IN ($usergroupes)";
    $critere.=$critere2;
  } else {
    $critere2="";
  }
} else $critere="";

if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT publication,type FROM $GLOBALS[tableprefix]documents WHERE id='$id'") or die (mysql_error());
  list($publication,$typedoc)=mysql_fetch_row($result);
  chordre("documents",$id,"publication='$publication' $critere2",$dir);
  back();
//
// supression et restauration
//
} elseif ($id>0 && $delete) {
  include ("$home/trash.php");
  $delete=2; // detruit vraiment... attention quand il y aura des motcles, il faut detruit les mots cles avec !!!!!!!!!!!
  treattrash("documents",$critere);
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  extract_post();

  // validation
  do {
    if (!$context[type]) die ("pas de type");

#    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
#    include("$home/date.php");
#    if ($context[date]) {
#      $date=mysqldate($context[date]);
#      if (!$date) { $context[erreur_date]=$err=1; }
#    } else { $date=""; }
#
    if ($imgfile && $imgfile!="none") {
      $result=getimagesize($imgfile);
      if ($result[2]==1) { $ext="gif"; }
      elseif ($result[2]==2) { $ext="jpg"; }
      elseif ($result[2]==3) { $ext="png"; }
      else { $context[erreur_image]=1; break; }
    } else {
      $imgfile=""; // pas d'image alors
    }

    if ($err) break;
    include_once ("$home/connect.php");

    $image="";
    if ($id>0) { // il faut rechercher le status et l'ordre
      lock_write("documents");
#      $result=mysql_query("SELECT ordre,status,meta,image,publication,date,user,groupe FROM $GLOBALS[tableprefix]documents WHERE id='$id'") or die (mysql_error());
#      list($ordre,$status,$meta,$image,$publication,$date,$user,$groupe)=mysql_fetch_array($result);
#      $date="'".$date."'";
# les metas sont desactives pour le moment#      $meta=addmeta($context,$meta);

      $update="titre='$context[titre]', soustitre='$context[soustitre]', texte='$context[texte]', type='$context[type]', textetype='$context[textetype]'";

      if ($imgfile) {
	$image="Photos/img-$id.$ext";
	$update.=", image='$image'";
      }
      mysql_query("UPDATE $GLOBALS[tableprefix]documents SET $update WHERE $critere") or die (mysql_error());
      // verifie que le groupe est ok au cas ou...
      if (mysql_affected_rows()==0 && $imgfile) { // dans ce cas c'est suspect, mais pas forcement grave.
	$result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE $critere") or die (mysql_error());
	if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
	// ca permet de prevenir une modification de l'image...
      }
    } else { 
      lock_write("documents","publications");
      // cherche le groupe et verifie les droits
      if (!$admin) $critere2="AND groupe IN ($usergroupes)";
      $result=mysql_query("SELECT groupe FROM $GLOBALS[tableprefix]publications WHERE id='$publication' $critere2") or die (mysql_error());
      if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
      list($groupe)=mysql_fetch_row($result);

      // cherche l'ordre
      $ordre=get_ordre_max("documents");

      mysql_query ("INSERT INTO $GLOBALS[tableprefix]documents (titre,soustitre,texte,textetype,image,meta,publication,datepubli,ordre,type,status,user,groupe) VALUES ('$context[titre]','$context[soustitre]','$context[texte]','$context[textetype]','','','$publication',NOW(),'$ordre','$context[type]','1','$iduser','$groupe')") or die (mysql_error());
      // attention, publier par defaut !

      $id=mysql_insert_id();
      if ($imgfile) {
	$image="Photos/img-$id.$ext";
	mysql_query("UPDATE $GLOBALS[tableprefix]documents SET image='$image' WHERE id='$id'") or die (mysql_error());
      }
    }
    // copie du fichier images
    if ($imgfile && $image) {
      if ($context[taille]) {
	include_once("$home/images.php");
	resize_image($context[taille],$imgfile,"../../".$image);
      } else {
	copy($imgfile,"../../".$image);
      }
    }
    unlock();
    back();
  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ("$home/connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]documents WHERE $critere") or die ("erreur SELECT");
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  include_once("$home/textfunc.php");
  $context[type]=rmscript(strip_tags($type));
}

$base=preg_replace("/[\W]/","_",$context[type]);
if (!file_exists("tpl/$base.html")) $base="article";

// post-traitement
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,$base);


#function makeselecttype() 
#
#{
#  global $context;
#
#  $result=mysql_query("SELECT nom FROM $GLOBALS[tableprefix]typedocs WHERE status>0") or die (mysql_error());
#
#  while ($row=mysql_fetch_assoc($result)) {
#    $selected=$context[type]==$row[nom] ? " SELECTED" : "";
#    echo "<OPTION VALUE=\"$row[nom]\"$selected>$row[nom]</OPTION>\n";
#  }
#}

function makeselecttextetype() 

{
  global $context;
  $textetypes=array("norm","html");

  foreach ($textetypes as $textetype) {
    $selected=$context[textetype]==$textetype ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$textetype\"$selected>$textetype</OPTION>\n";
  }
}

?>
