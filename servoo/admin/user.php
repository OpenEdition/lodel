<?


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

