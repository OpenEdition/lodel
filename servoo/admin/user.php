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



// connection a la database
require("../config.php");
mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
mysql_select_db($database)  or die (mysql_error());


if ($edit) {
  do { // exception
    $id=intval($id);
    if ($passwd) {
    $passwd=md5($passwd.".".$username);
  } elseif ($id) {
    $result=mysql_query("SELECT passwd FROM $GLOBALS[tp]users WHERE id='$id'");
    list($passwd)=mysql_fetch_row($result);
    if (!$passwd) die("probleme id inconnue");
  } else {
    echo ("<h1>preciez un password</h1>");
    break;
  }

  myquote($username); myquote($url);


  mysql_query("REPLACE INTO $GLOBALS[tp]users (id,username,passwd,url) VALUES ('$id','$username','$passwd','$url')") or die(mysql_error());

  echo "ok";
  return;
  } while (0);
}


if ($id) {
  $result=mysql_query ("SELECT * FROM $GLOBALS[tp]users WHERE id='$id' AND statut>0")  or die(mysal_error());
  $user=mysql_fetch_assoc($result);
  $user[passwd]="";
}


function myquote (&$var)

{
  if (is_array($var)) {
    array_walk($var,"myquote");
    return $var;
  } else {
    return $var=addslashes(stripslashes($var));
  }
}


?>
<form method="POST" action="user.php">
	<input type="hidden" name="edit" value="1" />
	<input type="hidden" name="id" value="<?=$id ?>" />
	<table class="table_normale" style="margin-top:30px;" border="0" cellspacing="0" cellpadding="5">
		<tr>
			<td class="texte_intitule">Login :</td>
			<td><input size="30" type="text" name="username" value="<?=$user[username] ?>" /></td>
		</tr>
		<tr>
			<td class="texte_intitule">Mot de passe :</td>
			<td><input size="30" type="password" name="passwd" value="" /></td>
		</tr>
		<tr>
			<td class="texte_intitule">URL :</td>
			<td><input size="30" type="text" name="url" value="<?=$user[url] ?>" /></td>
		</tr>

	</table>

	<div class="pied_de_formulaire">
		<input class="form" type="submit" value=" <? if ($id) { ?>Modifier<? } else { ?>Ajouter<? } ?>    " />&nbsp;&nbsp;&nbsp;&nbsp;
	</div>
</form>

