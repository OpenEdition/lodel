<?php

// droit
define(LEVEL_VISITEUR,1);
define(LEVEL_REDACTEUR,2);
define(LEVEL_EDITEUR,4);
define(LEVEL_ADMIN,32);
define(LEVEL_SUPERADMIN,128);

function authenticate ($level=0,$norecordurl=FALSE)

{
  global $HTTP_COOKIE_VARS,$context,$iduser,$userpriv,$usergroupes;
  global $home,$urlroot,$timeout,$database,$sessionname,$revue,$back;

  $retour="url_retour=".urlencode($GLOBALS[REQUEST_URI]);

  do { // block de control
    $name=$HTTP_COOKIE_VARS[$sessionname];

    if (!$name) break;

    include_once("$home/connect.php");
    mysql_select_db($database);
    if (!($result=mysql_query ("SELECT id,iduser,revue,context,expire,expire2,currenturl FROM $GLOBALS[tableprefix]session WHERE name='$name'")))  break;
    if (!($row=mysql_fetch_assoc($result))) break;
    $GLOBALS[idsession]=$idsession=$row[id];

#ifndef LODELLIGHT
    // verifie qu'on est dans la bonne revue
    if ($row[revue]!="toutes les revues" && $row[revue]!=$revue) break;
#endif

    // verifie que la session n'est pas expiree
    $time=time();
    //        echo $name,"   ",$row[expire],"  ",$time,"<br>";
    if ($row[expire]<$time || $row[expire2]<$time) { header("location: login.php?erreur_timeout=1&".$retour); exit; }

    // pass les variables en global
   
    $context=array_merge($context,unserialize($row[context])); // recupere le contexte
    $userpriv=$context[userpriv];
    $usergroupes=$context[usergroupes];
    $context[iduser]=$iduser=$row[iduser];

    if ($userpriv<$level) { header("location: login.php?erreur_privilege=1&".$retour); exit; }

#ifndef LODELLIGHT
    // verifie encore une fois au cas ou...
    if ($userpriv<LEVEL_SUPERADMIN && !$revue) break;
#endif

    if ($userpriv>=LEVEL_SUPERADMIN) $context[superadmin]=$GLOBALS[superadmin]=1;
    if ($userpriv>=LEVEL_ADMIN) $context[admin]=$GLOBALS[admin]=1;
    if ($userpriv>=LEVEL_EDITEUR) $context[editeur]=$GLOBALS[editeur]=1;
    if ($userpriv>=LEVEL_REDACTEUR) $context[redacteur]=$GLOBALS[redacteur]=1;
    if ($userpriv>=LEVEL_VISITEUR) $context[visiteur]=$GLOBALS[visiteur]=1;
    // efface les donnees de la memoire et protege pour la suite
    $HTTP_COOKIE_VARS[session]=0;

    //
    // change l'expiration de la session et l'url courrante
    //

    // nettoie l'url
    $url=preg_replace("/[\?&]recalcul\w+=oui/","",$GLOBALS[REQUEST_URI]);
    if ($back) $url=preg_replace("/[\?&]back=\d+/","",$url);
    if (!$norecordurl) $update=",currenturl='$url'"; // si norecordurl ne change rien

    $expire=$timeout+$time;
    mysql_query("UPDATE $GLOBALS[tableprefix]session SET expire='$expire'$update WHERE name='$name'") or die (mysql_error());

    //
    // gestion de l'url de retour
    //
    if ($back) {
      // on detruit l'entree dans la pile
      $back=intval($back);
      mysql_query ("DELETE FROM $GLOBALS[tableprefix]pileurl WHERE id='$back' AND idsession='$idsession'") or die (mysql_error());
    }
    $urlmd5=md5($url);

    // enregistre l'url de retour à partir de l'info dans la session
    if ($row[currenturl] && $row[currenturl]!=$url && !$norecordurl && !$back) {
      mysql_query ("INSERT INTO $GLOBALS[tableprefix]pileurl (idsession,url,urlretour) VALUES ('$idsession','$urlmd5','$row[currenturl]')") or die (mysql_error());
      $context[url_retour]=mkurl($row[currenturl],"back=".mysql_insert_id());
    } else {
      // cherche l'url de retour dans la base de donnee
      $result=mysql_query ("SELECT urlretour,id FROM $GLOBALS[tableprefix]pileurl WHERE idsession='$idsession' AND url='$urlmd5' ORDER BY id DESC LIMIT 0,1") or die (mysql_error());
      if (mysql_num_rows($result)) {
	list($urlretour,$id)=mysql_fetch_row($result);
	$context[url_retour]=mkurl($urlretour,"back=$id");
      } else {	
	$context[url_retour]="";
      }
    }
    #    echo "retour:$context[url_retour]";
    //
    // fin de gestion de l'url de retour
    //

    $context[url_recompile]=mkurl($url,"recalcul_templates=oui");

    //
    // relselection la DB de la revue comme DB par defaut.
    //
    mysql_select_db($GLOBALS[currentdb]) or die (mysql_error());
    return; // ok !!!
  } while (0);

  if ($GLOBALS[currentdb]) mysql_select_db($GLOBALS[currentdb]);

  // exception
  if ($level==0) {
    return; // les variables ne sont pas mises... on retourne
  } else {
    header("location: login.php?".$retour);
    exit;
  }
}


function mkurl ($url,$extraarg)

{
  if (strpos($url,"?")===FALSE) {
    return $url."?".$extraarg;
  } else {
    return $url."&".$extraarg;
  }
}

/* supprimer le 29/03/03 a enlever si ca fonctionne
function mkurlretour ($urlretour,$id)

{
  global $context;

  if (strpos($urlretour,"?")===FALSE) {
    $context[url_retour]=$urlretour."?back=$id";
  } else {
    $context[url_retour]=$urlretour."&back=$id";
  }
}
*/


#ifndef LODELLIGHT
function getrevueoptions ()

{
  global $home,$context,$revue;

  include_once ("$home/connect.php");

  mysql_select_db($GLOBALS[database]);
  $result=mysql_query("SELECT $GLOBALS[tableprefix]options FROM revues WHERE rep='$revue'") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("erreur revue"); }

  list($options)=mysql_fetch_array($result);
  if ($options) $context=array_merge($context,unserialize($options));

  mysql_select_db($GLOBALS[currentdb]);
}
#endif

// securite... initialisation
$userpriv=0;
$usergroupes="";
$iduser=0;
$revue="";
$idsession=0;

$context=array(
	       "version" => doubleval($version)
	       ); // tres important d'initialiser le context.
$superadmin=0;
$admin=0;
$user=0;
$context[shareurl]=$shareurl;

// cherche le nom de la revue


#ifndef LODELLIGHT
$url=parse_url("http://".$SERVER_NAME.$REQUEST_URI);
if ($revueagauche) {
	if (preg_match("/^(\w+)\./",$url[host],$result) && $result[1]!="lodel" && $result[1]!="www") {
	  $context[revue]=$revue=$result[1];
	} else {
	  $context[revue]=$revue="";
	}
} else {
	if (preg_match("/^".preg_quote($urlroot,"/")."([^\/\.]*)(\/|$)/",$url[path],$result) && $result[1]!="lodel") {
	  $context[revue]=$revue=$result[1];
	} else {
	  $context[revue]=$revue="";
	}
}
#else
#	  $context[revue]=$revue="";
#endif
?>
