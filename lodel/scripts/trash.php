<?


// fonction pour metre et enlever de la poubelle;
// si $critere est numerique, il est considerer comme etant l'id

function deletetotrash($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ("$home/connect.php");
  mysql_query("UPDATE $GLOBALS[tableprefix]$table SET status=-abs(status) WHERE $critere") or die("erreur UPDATE");
  return mysql_affected_rows()>0;
}

function restorefromtrash($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ("$home/connect.php");
  mysql_query("UPDATE $GLOBALS[tableprefix]$table SET status=abs(status) WHERE $critere") or die("erreur UPDATE");
  return mysql_affected_rows()>0;
}

function delete($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ("$home/connect.php");
  mysql_query("DELETE FROM $GLOBALS[tableprefix]$table WHERE $critere") or die(mysql_error());
  return mysql_affected_rows()>0;
}

function treattrash ($table,$critere="")

{
  global $home,$delete,$restore,$id,$url_retour;

  if (!$critere) $critere="id='$id'";

  if ($delete) {
    if ($delete<2) { 
      if (!deletetotrash($table,$critere)) { die ("entite introuvable"); @Header("Location: not-found.html"); exit(); }
      include_once("$home/func.php"); back();
    }
    //
    // destruction complete
    //
    if ($delete>=2) { 
      if (!delete($table,$critere)) { die ("entite introuvable");@Header("Location: not-found.html"); exit(); }
      include_once("$home/func.php"); back();
    }
  }
//
// restauration
//
  if ($restore) { 
      if (!restorefromtrash($table,$critere)) { die ("entite introuvable");@Header("Location: not-found.html"); exit(); }
      include_once("$home/func.php"); back();
  }

 return 0; 
}
?>
