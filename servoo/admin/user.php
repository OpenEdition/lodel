<?
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


require("config.php");
require(TOINCLUDE."auth.php");
authenticate(LEVEL_ADMIN);
require(TOINCLUDE."func.php");

mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
mysql_select_db($database)  or die (mysql_error());

$id=intval($id);

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include (TOINCLUDE."trash.php");
  treattrash("users");
  return;
}


$critere="id='$id' AND status>0";



if ($edit) {
  extract_post();
  // "ms" stands for Manager Safe. Avoid auto-completion.
  $context[username]=$context[usernamems];
  $context[passwd]=$context[passwdms];

  do { // exception
    $len=strlen($context[username]);
    if (!preg_match("/^[0-9A-Za-z@\.]+$/",$context[username])) { $err=$context[erreur_username]=1; }

    if (!$context[realname]) { $context[erreur_realname]=$err=1; }
    $passwd=$context[passwd];
    if ($passwd || !$id) { // si le pass a ete modifie
      $len=strlen($passwd);
      if ($len<3 || $len>10) { $err=$context[erreur_passwd]=1; }
    }

    // verifie le email
    if ($context[email] && !ereg(".*\@[^\.]*\..*",$context[email])) { $context[erreur_email]=$err=1; }// repris de SPIP
      
    if ($err) break;

    if ($passwd) {
    $passwd=md5($context[passwd].".".$context[username]);
  } elseif ($id) {
    $result=mysql_query("SELECT passwd FROM $GLOBALS[tp]users WHERE id='$id'");
    list($passwd)=mysql_fetch_row($result);
    if (!$passwd) die("probleme id inconnue");
  } else {
    echo ("<h1>preciez un password</h1>");
    break;
  }

  mysql_query("REPLACE INTO $GLOBALS[tp]users (id,username,passwd,url,realname,email,priority) VALUES ('$id','$context[username]','$passwd','$context[url]','$context[realname]','$context[email]','$context[priority]')") or die(mysql_error());

  header("location: users.php");
  return;
  } while (0);
}


if ($id) {
  $result=mysql_query ("SELECT * FROM $GLOBALS[tp]users WHERE id='$id' AND status>0")  or die(mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
  $context[passwd]="";
  $context[usernamems]=$context[username];
}


// post-traitement
posttraitement($context);


$context[passwd]="";


require(TOINCLUDE."calcul-page.php");
calcul_page($context,"user");





function makeselectpriority()

{
  global $context,$adminpriv;
  $arr=array(10=>"Basse",
	     100=>"Normal",
	     200=>"Elev&eacute;e",
	     );

  echo $context[priority];
  if (!$context[priority]) $context[priority]=100;

  foreach ($arr as $k=>$v) {
    $selected=$context[priority]==$k ? "selected" : "";
    echo "<option value=\"$k\" $selected>$v</option>\n";
  }
}
