<?php

// droit
define(LEVEL_VISITEUR,10);
define(LEVEL_REDACTEUR,20);
define(LEVEL_EDITEUR,30);
define(LEVEL_ADMIN,40);
define(LEVEL_ADMINLODEL,128);

function authenticate ($level=0,$norecordurl=FALSE)

{
  global $HTTP_COOKIE_VARS,$context,$iduser,$userpriv,$usergroupes;
  global $home,$urlroot,$timeout,$database,$sessionname,$site,$back;

  $retour="url_retour=".urlencode($GLOBALS[REQUEST_URI]);

  do { // block de control
    $name=addslashes($HTTP_COOKIE_VARS[$sessionname]);
    if (!$name) break;

    include_once($home."connect.php");
    mysql_select_db($database) or die(mysql_error());
    if (!($result=mysql_query ("SELECT id,iduser,site,context,expire,expire2,currenturl FROM $GLOBALS[tp]session WHERE name='$name'")))  break;
    if (!($row=mysql_fetch_assoc($result))) break;
    $GLOBALS[idsession]=$idsession=$row[id];
    $GLOBALS[session]=$name;

    // verifie qu'on est dans le bon site
    if ($row[site]!="tous les sites" && $row[site]!=$site) break;

    // verifie que la session n'est pas expiree
    $time=time();
    //        echo $name,"   ",$row[expire],"  ",$time,"<br>";
    if ($row[expire]<$time || $row[expire2]<$time) { 
      $login="";
      if (file_exists("login.php")) { 
	$login="login.php"; 
      } elseif (file_exists("lodel/edition/login.php")) {
	$login="lodel/edition/login.php"; 
      } else {
	break;
      }
      header("location: $login?erreur_timeout=1&".$retour); exit();
    }

    // pass les variables en global
   
    $context=array_merge($context,unserialize($row[context])); // recupere le contexte
    $userpriv=$context[userpriv];
    $usergroupes=$context[usergroupes];
    $context[iduser]=$iduser=$row[iduser];

    if ($userpriv<$level) { header("location: login.php?erreur_privilege=1&".$retour); exit; }

#ifndef LODELLIGHT
    // verifie encore une fois au cas ou...
    if ($userpriv<LEVEL_ADMINLODEL && !$site) break;
#endif

    if ($userpriv>=LEVEL_ADMINLODEL) $context[adminlodel]=$GLOBALS[adminlodel]=1;
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
    if (!$norecordurl) $update=", currenturl='$url'"; // si norecordurl ne change rien

    $expire=$timeout+$time;
    mysql_query("UPDATE $GLOBALS[tp]session SET expire='$expire'$update WHERE name='$name'") or die (mysql_error());

    //
    // gestion de l'url de retour
    //
    if ($back) {
      // on detruit l'entree dans la pile
      $back=intval($back);
      mysql_query ("DELETE FROM $GLOBALS[tp]pileurl WHERE id='$back' AND idsession='$idsession'") or die (mysql_error());
    }
    $urlmd5=md5($url);

    // enregistre l'url de retour à partir de l'info dans la session
    if ($row[currenturl] && $row[currenturl]!=$url && !$norecordurl && !$back) {
      mysql_query ("INSERT INTO $GLOBALS[tp]pileurl (idsession,url,urlretour) VALUES ('$idsession','$urlmd5','$row[currenturl]')") or die (mysql_error());
      $context[url_retour]=mkurl($row[currenturl],"back=".mysql_insert_id());
    } else {
      // cherche l'url de retour dans la base de donnee
      $result=mysql_query ("SELECT urlretour,id FROM $GLOBALS[tp]pileurl WHERE idsession='$idsession' AND url='$urlmd5' ORDER BY id DESC LIMIT 0,1") or die (mysql_error());
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
    // relselection la DB du site comme DB par defaut.
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
function getsiteoptions ()

{
  global $home,$context,$site;

  include_once ($home."connect.php");

  mysql_select_db($GLOBALS[database]);
  $result=mysql_query("SELECT options FROM $GLOBALS[tp]sites WHERE rep='$site'") or die (mysql_error());
  if (!mysql_num_rows($result)) { die ("erreur site"); }

  list($options)=mysql_fetch_array($result);
  if ($options) $context=array_merge($context,unserialize($options));

  mysql_select_db($GLOBALS[currentdb]);
}
#endif

function getacceptedcharset($charset) {
	// Détermine le charset a fournir au navigateur
	global $HTTP_SERVER_VARS;
	$browserversion = array (	
						"opera" => 6,
						"netscape" => 4,
						"msie" => 4,
						"ie" => 4,
						"mozilla" => 3
					);
	if (!$charset) {
		// Si ce n'est pas envoye par l'url ou par cookie, on recupere ce que demande le navigateur.
		if ($HTTP_SERVER_VARS["HTTP_ACCEPT_CHARSET"]) {
			// Si le navigateur retourne HTTP_ACCEPT_CHARSET on l'analyse et on en déduit le charset
			if (preg_match("/\butf-8\b/i", $HTTP_SERVER_VARS["HTTP_ACCEPT_CHARSET"])) return "utf-8";
			else return "iso-8859-1";
		// Sinon on analyse le HTTP_USER_AGENT retourné par le navigateur et si ca matche on vérifie 
		// que la version du navigateur est supérieure ou égale à la version déclarée unicode
		} elseif ((preg_match("/\b(\w+)\W(\d+)/i", $HTTP_SERVER_VARS["HTTP_USER_AGENT"], $matches)) && 
				($matches[2] >= $browserversion[strtolower($matches[1])])) {
			return "utf-8";
		} else return "iso-8859-1"; // Si on a rien trouvé on renvoie de l'iso
	}
	else return $charset;
}

// securite... initialisation
$userpriv=0;
$usergroupes="";
$iduser=0;
$idsession=0;
$session="";

$context=array(
	       "version" => doubleval($version)
	       ); // tres important d'initialiser le context.
$adminlodel=0;
$admin=0;
$user=0;
$context[shareurl]=$shareurl;

// cherche le nom du site

if ($site) {
     $context[site]=$site;
} else {
	$url=parse_url("http://".$SERVER_NAME.$REQUEST_URI);
	if ($siteagauche) {
		if (preg_match("/^(\w+)\./",$url[host],$result) && $result[1]!="lodel" && $result[1]!="www") {
		  $context[site]=$site=$result[1];
		} else {
		  $context[site]=$site="";
		}
	} else {
		if (preg_match("/^".preg_quote($urlroot,"/")."([^\/\.]*)(\/|$)/",$url[path],$result) && $result[1]!="lodeladmin") {
		  $context[site]=$site=$result[1];
		} else {
		  $context[site]=$site="";
		}
	}
}

$context[charset] = getacceptedcharset($charset);
header("Content-type: text/html; charset=$context[charset]");

?>
