<?php


//
// fonction qui renvoie les valeures perennes: status, groupe, ordre
//


function get_variables_perennes($context,$critere) 

{
    $groupe= ($admin && $context[groupe]) ? intval($context[groupe]) : "groupe";

    $result=mysql_query("SELECT ordre,$groupe,status FROM $GLOBALS[tp]entites WHERE $critere") or die (mysql_error());
    if (!mysql_num_rows($result)) { die ("vous n'avez pas les droits: get_variables_perennes"); }
    return mysql_fetch_array($result);
    // renvoie l'ordre, le groupe, le status
}


//
// fonction pour recuperer les groupes.
//

function get_groupe($context,$idparent)

{
  global $admin,$usergroupes;

  // cherche le groupe et les droits
  if ($admin) { // on prend celui qu'on nous donne
    $groupe=intval($context[groupe]); if (!$groupe) $groupe=1;

  } elseif ($idparent) { // on prend celui du idparent
    $result=mysql_query("SELECT groupe FROM $GLOBALS[tp]entites WHERE id='$idparent' AND groupe IN ($usergroupes)") or die (mysql_error());
    if (!mysql_num_rows($result)) 	die("vous n'avez pas les droits: sortie 2");
    list($groupe)=mysql_fetch_row($result);
  } else {
    die("vous n'avez pas les droits: sortie 3");
  }
  return $groupe;
}

?>
