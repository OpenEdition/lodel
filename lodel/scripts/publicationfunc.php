<?

//
// gere la creation et la modification d'une publication
//

function pub_edition (&$context,$critere) 

{
  global $home,$admin;

  if (!$context[nom]) { $context[erreur_nom]=$err=1; }
  include_once("$home/date.php");
  if ($context[date]) {
    $date=mysqldate($context[date]);
    if (!$date) { $context[erreur_date]=$err=1; }
  } else { $date=""; }

  if ($err) return FALSE;
  include_once ("$home/connect.php");
  
  $id=intval($context[id]);
  $parent=intval($context[parent]);

  if ($id && $context[grouperec] && $admin) {
    lock_write("publications","documents");
  } else {
    lock_write("publications");
  }
  if ($id>0) { // il faut rechercher le status et l'ordre
    if (!$critere) die ("erreur interne");
    $result=mysql_query("SELECT ordre,meta,groupe,status FROM $GLOBALS[tableprefix]publications WHERE $critere") or die (mysql_error());
    if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits"); }
    list($ordre,$meta,$groupe,$status)=mysql_fetch_array($result);
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
  if (!$id) $id=mysql_insert_id();
  unlock();

  return $id;
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
