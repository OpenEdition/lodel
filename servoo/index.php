<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

//
// authentification
//

$passwd=md5($_REQUEST[passwd].".".$_REQUEST[username]);
$username=addslashes($_REQUEST[username]);

if (!$passwd || !$username) die("ERROR: unknown user");


// connection a la database
require("config.php");
mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
mysql_select_db($database)  or die (mysql_error());

// cherche l'utilisateur et verifie son identification
$result=mysql_query ("SELECT * FROM $GLOBALS[tp]users WHERE username='$username' AND passwd='$passwd' AND status>0")  or die(mysql_error());
$user=mysql_fetch_assoc($result);

if (!$user)  die("ERROR: user not allowed");

// au cas ou
unset($passwd);
// ok l'utilisateur est authentifie maintenant
//-------------------------------------------------------------------------------//

//
// Enregistre les informations sur la requete
//

$commands=$_REQUEST[commands];

mysql_query("INSERT INTO $GLOBALS[tp]log (iduser,commands) VALUES ('$user[id]','".addslashes($commands)."')") or die(mysql_error());

//-------------------------------------------------------------------------------//

require ($home."serveurfunc.php");

define("VERSION","0.99");


//
// on traite les commandes maintenant
//

$cmdsarr=getcommands($commands); // recupere dans un tableau les differentes commandes
$sourcefile=""; // fichier source lu ou charge
$convertedfiles=array(); // fichier provenant de la conversion
$msg=0; // retourne ou pas des messages

foreach ($cmdsarr as $cmd) { // boucle sur les commandes
  // command VER ----------------
  if ($cmd[0]=="VER") {
    // send the version
    die("SAY: ServOO; v".VERSION."; OpenOffice.org v".OPENOFFICEVERSION."; ".MESSAGEVERSION);
    // command DWL ----------------
  } elseif ($cmd[0]=="DWL") {
    // download de fichier
    if (!is_uploaded_file($_FILES[$cmd[1]]['tmp_name'])) die("ERROR: file download");
    $sourcefile=$_FILES[$cmd[1]]['tmp_name'];
    // command UPL ----------------
  } elseif ($cmd[0]=="UPL") {
    // que faut-il uploader ?
    if ($cmd[1]=="all") {
      $auploader=$convertedfiles;
      array_push($auploaded,$sourcefile);
    } elseif ($cmd[1]=="source") {
      $auploader=array($sourcefile);
    } else { // valeur par defaut
      $auploader=$convertedfiles;
    }
    // on upload maintenant
    list($ret,$retvar)=upload($user[url],array("tache"=>$tache),$auploader,array($sessionname=>$session));
    echo $ret;

    // command CVT ---------------- conversion
  } elseif ($cmd[0]=="CVT") {
    $type=$cmd[1];
#    $t=time();
    #if ($type=="HTMLLodel-1.0") {      
    #  require_once($home."convert.php");
    #  $convertedfiles=HTMLLodel($sourcefile,$msg);
    #} else
    if ($type=="XHTMLLodel-1.0") {
      require_once($home."convert.php");
      $convertedfiles=XHTMLLodel($sourcefile,$msg);
    } else die("ERROR: unknow conversion type");
#    error_log("CVT ".(time()-$t)."\n",3,"/tmp/error_log");
    // command XVL ---------------- validation de XML
  } elseif ($cmd[0]=="XVL") {
    $type=$cmd[1];
#    $t=time();
    if ($type=="MSV") {
      require_once($home."xmlvalidator.php");
      $convertedfiles=xmlvalidwithMSV($sourcefile,$msg);
    } else die("ERROR: unknow XML Validation");
#    error_log("CVT ".(time()-$t)."\n",3,"/tmp/error_log");
    // command ZIP ----------------
  } elseif ($cmd[0]=="ZIP") {
    $ope=$cmd[1];
    // que faut-il zipper ?
    if ($ope=="all") {
      $azipper=$convertedfiles;
      array_push($azipper,$sourcefile);
    } elseif ($ope=="source") {
      $azipper=array($sourcefile);
    } else { // valeur par defaut
      $azipper=$convertedfiles;
    }
    if (!$azipper) continue;
    // on zip maintenant
    if ($zipcmd) {
      $archive=tempnam("","arch").".zip";
      system("$zipcmd -rq $archive ".join(" ",$azipper)." 2>$archive.err");
      @array_walk($azipper,"unlink");
      if (filesize($archive.".err")>0) die("ERROR: zip failed<br>".str_replace("\n","<br>",htmlentities(@join("",@file($archive.".err")))));
      if ($ope=="source") { // dans ce cas on met a jour la variable sourcefile
	$sourcefile=$archive;
      } else {
	$convertedfiles=array($archive);
      }
    } else die("ERROR: zip undefined");
    // command UNZIP ----------------
  } elseif ($cmd[0]=="UNZIP")
      // on dezip le premier fichier, on suppose que l'archive contient qu'un fichier
    if ($unzipcmd) {
      $dest=$sourcefile.".unzipped";
      system("$unzipcmd -p $sourcefile >$sourcefile.unzipped 2>$sourcefile.err");
     
      if (filesize($sourcefile.".err")>0) die("ERROR: unzip failed<br>".str_replace("\n","<br>",htmlentities(@join("",@file($sourcefile.".err")))));
      $sourcefile=$dest;     
    } else die("ERROR: unzip undefined");
    // command RTN ----------------
    elseif ($cmd[0]=="RTN") {
    $type=$cmd[1];
#    $t=time();
    if ($type=="convertedfile") {
#      error_log("fichier $convertedfiles[0]\n",3,"/tmp/log");
      echo "version: ServOO; v".VERSION."; OpenOffice.org v".OPENOFFICEVERSION.";\n";
      echo "content-length: ".filesize($convertedfiles[0])."\n"; # envoie la longueur
#      error_log("filesize".filesize($convertedfiles[0])."\n",3,"/tmp/log");
      readfile($convertedfiles[0]);
      
#      error_log("RTN ".(time()-$t)." ".date("l dS of F Y h:i:s A")."\n",3,"/tmp/log");
      return;
    } elseif ($type=="MSG") {
      $msg=1;
    } else die("ERROR: unknow argument for RTN command");
    // unknown command  ----------------
  } else die("ERROR: unknown command");
}




//-------------------------------------------------------------------------------//
// fonctions diverses



function getcommands($commands)

{
  $arr=preg_split("/(\s*;\s*| +|(?<!\\\)')/",$commands,-1,PREG_SPLIT_DELIM_CAPTURE);

#  print_r($arr);

  $icmd=0;  $i=0; $count=count($arr);
  while ($i<$count) {
    $inquote=0; $iarg=0;
    while ($i<$count && ($inquote || trim($arr[$i])!=";")) {
      if ($arr[$i]=="'") $inquote=!$inquote; // quote
      if (!$inquote && trim($arr[$i])=="") { $iarg++; $i++; continue; }// si on a des espaces alors on passe a l'argument suivant
      $cmds[$icmd][$iarg].=$arr[$i];
      $i++;
    }
    $icmd++;
    $i++;
  }
  if ($inquote) return FALSE;
  return $cmds;
}

?>

