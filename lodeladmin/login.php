<?

die("lodeldevel est trop instable en ce moment car je fais des changements importants sur le code. Les rapports de bug seraient donc inutiles.<br>Merci de votre comprehension et bonnes vacances merci.<br> Ghislain le 04/08/03");

require("lodelconfig.php");
include ($home."auth.php");

// timeout pour les cookies
$cookietimeout=4*3600;


if ($login) {
  include_once($home."func.php");
  extract_post();
  do {
    include_once ($home."connect.php");
    if (!check_auth(&$revue)) {
      $context[erreur_login]=1; break; 
    }
    // ouvre une session

    // context
    $contextstr=serialize(array("userpriv"=>intval($userpriv),"usergroupes"=>$usergroupes,"username"=>$context[login]));
    $expire=time()+$timeout;
    $expire2=time()+$cookietimeout;

    mysql_select_db($database);
    if ($userpriv<LEVEL_SUPERADMIN) {
      lock_write("revues","session"); // seulement session devrait etre locke en write... mais c'est pas hyper grave vu le peu d'acces sur revue.
      // verifie que c'est ok
      $result=mysql_query("SELECT 1 FROM $GLOBALS[tableprefix]revues WHERE rep='$revue' AND status>=32") or die(mysql_error());
      if (mysql_num_rows($result)) { $context[erreur_revuebloquee]=1; unlock(); break; }
    }

    for ($i=0; $i<5; $i++) { // essaie cinq fois, au cas ou on ait le meme nom de session
      // nom de la session
      $name=md5($context[login].microtime());
      // enregistre la session, si ca marche sort de la boucle
      if (mysql_query("INSERT INTO $GLOBALS[tableprefix]session (name,iduser,revue,context,expire,expire2) VALUES ('$name','$iduser','$revue','$contextstr','$expire','$expire2')")) break;
    }
    unlock();
    if ($i==5) { $context[erreur_opensession]=1; break; }

    if (!setcookie($sessionname,$name,time()+$cookietimeout,$urlroot)) { $context[erreur_setcookie]=1; break;}

    header ("Location: http://$SERVER_NAME$url_retour");
    die ("$url_retour");
  } while (0);
}

$context[passwd]=$passwd=0;


// variable: revuebloquee
if ($context[erreur_revue_bloquee]) { // on a deja verifie que la revue est bloquee.
  $context[revuebloquee]=1;
} else { // test si la revue est bloquee dans la DB.
  include_once ($home."connect.php");
  mysql_select_db($database);
  $result=mysql_query("SELECT 1 FROM revues WHERE rep='$revue' AND status>=32") or die(mysql_error());
  $context[revuebloquee]=mysql_num_rows($result);
}


$context[url_retour]=$url_retour;
$context[erreur_timeout]=$erreur_timeout;
$context[erreur_privilege]=$erreur_privilege;



include ($home."calcul-page.php");
calcul_page($context,"login");



function check_auth (&$revue)

{
  global $context,$iduser,$userpriv,$usergroupes;

  do { // block de control
    if (!$context[login] || !$context[passwd]) break;

    $user=addslashes($context[login]);
    $pass=md5($context[passwd].$context[login]);

    // cherche d'abord dans la base generale.
#ifndef LODELLIGHT
    mysql_select_db($GLOBALS[database]);
    $result=mysql_query ("SELECT id,status,privilege FROM users WHERE username='$user' AND passwd='$pass' AND status>0")  or die(mysal_error());
    if ($row=mysql_fetch_assoc($result)) {
      // le user est dans la base generale
      $revue="toutes les revues";
     } else { // le user n'est pas dans la base generale
      if (!$revue) break; // si $revue n'est pas definie on s'ejecte

      // cherche ensuite dans la base de la revue
      mysql_select_db($GLOBALS[currentdb]);
      $result=mysql_query ("SELECT id,status,privilege FROM users WHERE username='$user' AND passwd='$pass' AND status>0")  or die(mysql_error());
      if (!($row=mysql_fetch_assoc($result))) break;
    }
#else
#      if (!($result=mysql_query ("SELECT id,status,privilege FROM $GLOBALS[tableprefix]users WHERE username='$user' AND passwd='$pass' AND status>0")))  break;
#      if (!($row=mysql_fetch_assoc($result))) break;
#endif
    // pass les variables en global
    $userpriv=$row[privilege];
    $context[iduser]=$iduser=$row[id];

    // cherche les groupes pour les non administrateurs
    if ($userpriv<LEVEL_ADMIN) {
      $result=mysql_query("SELECT idgroupe FROM $GLOBALS[tableprefix]users_groupes WHERE iduser='$iduser'") or die(mysql_error());
      $usergroupes="1"; // sont tous dans le groupe "tous"
      while ($row=mysql_fetch_row($result)) $usergroupes.=",".$row[0];
    } else {
      $usergroupes="";
    }
    $context[usergroupes]=$usergroupes;

    // efface les donnees de la memoire et protege pour la suite
    $context[passwd]=0;

    return true;
  } while (0);

  return false;
}
?>
