<?

//
// gere la creation et la modification d'une publication
//

function pub_edition (&$context,$critere) 

{
  global $home,$admin,$usergroupes;

  if (!$context[nom]) $context[erreur_nom]=$err=1;
  if (!$context[idtype]) die ("pub_edition: manque l'idtype"); 

  include_once($home."date.php");
  if ($context[date]) {
    $date=mysqldate($context[date]);
    if (!$date) { $context[erreur_date]=$err=1; }
  } else { $date=""; }

  if ($err) return FALSE;
  include_once ($home."connect.php");
  
  $id=intval($context[id]);
  $idparent=intval($context[idparent]);

  if ($id && $context[grouperec] && $admin) {
    lock_write("publications","documents","entites","relations");
  } else {
    lock_write("publications","entites","relations");
  }
  require_once($home."entitefunc.php");
  if ($id>0) { // il faut rechercher le status, l'ordre, le groupe
    if (!$critere) die ("erreur interne");
    list($ordre,$groupe,$status)=get_variables_perennes($context,$critere);
  } else { 
    // cherche le groupe et les droits
    $groupe=get_groupe($critere,$idparent);
    // cherche l'ordre
    $ordre=get_ordre_max("entites");
    $status=-1; // non publie par defaut
    $meta="";
  }
#  $meta=addmeta($context,$meta);


  mysql_query ("REPLACE INTO $GLOBALS[tp]entites (id,idparent,idtype,nom,ordre,status,groupe) VALUES ('$id','$idparent','$context[idtype]','$context[nom]','$ordre','$status','$groupe')") or die (mysql_error());

  if (!$id) $id=mysql_insert_id();
  mysql_query("REPLACE INTO $GLOBALS[tp]publications (identite,titre,soustitre,directeur,texte,date) VALUES ('$id','$context[titre]','$context[soustitre]','$context[directeur]','$context[texte]','$date')") or die (mysql_error());
  require_once($home."managedb.php");
  creeparente($id,$context[idparent],FALSE);
  
  if ($id && $grouperec && $admin) change_groupe_rec($id,$groupe);
  if (!$id) $id=mysql_insert_id();
  unlock();

  return $id;
}



function change_groupe_rec($id,$groupe)

{

##### a reecrire avec une table temporaire... plus de SQL moins de PHP

  // cherche les publis a changer
  $ids=array($id);
  $idparents=array($id);

  do {
    $idlist=join(",",$idparents);
    // cherche les fils de idparents
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]entites WHERE parent IN ($idlist)") or die(mysql_error());

    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  mysql_query("UPDATE $GLOBALS[tp]entites SET groupe='$groupe' WHERE id IN ($idlist)") or die(mysql_error());
  # cherche les ids
}


?>
