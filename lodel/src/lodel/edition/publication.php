<?

// gere les publications. L'acces est reserve aux administrateurs de la revue.
// assure l'edition, la supression, la restauration des publications.

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include("$home/func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...

$context[id]=$id=intval($id);
$context[parent]=$parent=intval($parent);
$context[importsommaire]=$importsommaire;

if ($id>0) {
  $critere="id='$id'";
  if (!$admin) $critere.=" AND groupe IN ($usergroupes)";
} else $critere="";

if ($id>0 && $dir) {
  lock_write("publications");
  # cherche le parent
  $result=mysql_query ("SELECT parent FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
  list($parent)=mysql_fetch_row($result);
  chordre("publications",$id,"parent='$parent'",$dir);
  unlock("publications");
  back();

//
// supression et restauration
//
} elseif ($id>0 && ($delete || $restore)) { 
  include ("$home/trash.php");
  die ("il faut utiliser supprime.php a la place");
  treattrash("publications",$critere);
  return;
//
// ajoute ou edit
//
} elseif ($edit) { // modifie ou ajoute
  extract_post();

  // validation
  do {
    if (!$context[nom]) { $context[erreur_nom]=$err=1; }
    include("$home/date.php");
    if ($context[date]) {
      $date=mysqldate($context[date]);
      if (!$date) { $context[erreur_date]=$err=1; }
    } else { $date=""; }

    if ($err) break;
    include_once ("$home/connect.php");

    if ($id && $grouperec && $admin) {
      lock_write("publications","documents");
    } else {
      lock_write("publications");
    }
    if ($id>0) { // il faut rechercher le status et l'ordre
      $result=mysql_query("SELECT ordre,meta,groupe FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
      if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
      list($ordre,$meta,$groupe)=mysql_fetch_array($result);
      if ($admin && $context[groupe]) $groupe=$context[groupe];
    } else { 
      // cherche le groupe et les droits
      if ($admin) { // on prend celui qu'on nous donne
	$groupe=$context[groupe]; if (!$groupe) $groupe=1;
      } elseif ($parent) { // on prend celui du parent
	$result=mysql_query("SELECT groupe FROM $GLOBALS[tableprefix]publications WHERE id='$parent' AND groupe IN ($usergroupes)") or die (mysql_error());
	if (!mysql_num_rows($result)) 	die("vous n'avez pas les droits");
	list($groupe)=mysql_fetch_row($result);
      } else {
	die("vous n'avez pas les droits");
      }
      // cherche l'ordre
      $ordre=get_ordre_max("publications");
      $status=-1; // non publie par defaut
      $meta="";
    }
    $meta=addmeta($context,$meta);

    mysql_query ("REPLACE INTO $GLOBALS[tableprefix]publications (id,parent,nom,titre,soustitre,directeur,texte,meta,ordre,type,date,status,groupe) VALUES ('$id','$parent','$context[nom]','$context[titre]','$context[soustitre]','$context[directeur]','$context[texte]','$meta','$ordre','$context[type]','$date','$status','$groupe')") or die (mysql_error());

    if ($id && $grouperec && $admin) change_groupe_rec($id,$groupe);
    unlock();
    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  include_once ("$home/connect.php");
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
} else {
  include_once ("$home/textfunc.php");
  $context[type]=rmscript(strip_tags($type));
}

// post-traitement
posttraitement($context);

include ("$home/calcul-page.php");
calcul_page($context,"publication");


function makeselecttype() 

{
  global $context;

  $result=mysql_query("SELECT nom FROM $GLOBALS[tableprefix]typepublis WHERE status>0") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[type]==$row[nom] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[nom]\"$selected>$row[nom]</OPTION>\n";
  }
}

function makeselectgroupes() 

{
  global $context;
      
  $result=mysql_query("SELECT id,nom FROM $GLOBALS[tableprefix]groupes") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {
    $selected=$context[groupe]==$row[id] ? " SELECTED" : "";
    echo "<OPTION VALUE=\"$row[id]\"$selected>$row[nom]</OPTION>\n";
  }
}


function change_groupe_rec($id,$groupe)

{
  // cherche les publis a changer
  $ids=array($id);
  $idparents=array($id);

  do {
    $idlist=join(",",$idparents);
    // cherche les fils de idparents
    $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]publications WHERE parent IN ($idlist)") or die(mysql_error());

    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  mysql_query("UPDATE $GLOBALS[tableprefix]publications SET groupe='$groupe' WHERE id IN ($idlist)") or die(mysql_error());
  # cherche les ids

  mysql_query("UPDATE $GLOBALS[tableprefix]documents SET groupe='$groupe' WHERE publication IN ($idlist)") or die(mysql_error());
}

?>
