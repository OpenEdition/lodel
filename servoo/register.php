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



require("config.php");
require(TOINCLUDE."auth.php");
authenticate();
require(TOINCLUDE."func.php");

mysql_connect($dbhost,$dbusername,$dbpasswd) or die (mysql_error());
mysql_select_db($database)  or die (mysql_error());

//
// Make a new password
//

if ($lostpasswdkey) {
  //
  // get a lostpasswdkey, check we can change the passwd and show the new one
  $username=preg_replace("/\W/","",$username); // clean the username
  $result=mysql_query("SELECT id,username,realname,passwd FROM $GLOBALS[tp]users WHERE username='$username'") or die(mysql_error());
  if (!mysql_num_rows($result)) { header("location:index.php"); return; }
  list ($id,$username,$realname,$passwd)=mysql_fetch_row($result);
  if ($lostpasswdkey!=md5($id.":".$username.":".$realname.":".$passwd)) { header("location:index.php"); return; }

  // generate the passwd.
  list($passwd,$encodedpasswd)=generate_passwd($username);

  // ok, change the passwd.
  mysql_query("UPDATE $GLOBALS[tp]users SET passwd='$encodedpasswd' WHERE id='$id'") or die(mysql_error());

  $context['username']=$username;
  $context['passwd']=$passwd;

  posttraitement($context);
  require_once(TOINCLUDE."calcul-page.php");
  calcul_page($context,"register-foundpasswd");
  return;


} elseif (isset($lostpasswd)) {

  if ($_POST) {
    //
    // send the email with the lostpasswdkey
    extract_post();
    $context['webmaster']=WEBMASTER;

  do {
    if (!$context['email'] || !validemail($context['email'])) { $context['error_email']=$err=1; break; }
    $result=mysql_query("SELECT id,username,realname,passwd FROM $GLOBALS[tp]users WHERE email='$context[email]'") or die(mysql_error());
    if (!mysql_num_rows($result)) { $context['error_email_doesnot_exist']=$err=1; }
    list ($id,$username,$realname,$passwd)=mysql_fetch_row($result);

    $lostpasswdkey=md5($id.":".$username.":".$realname.":".$passwd);

    $context['lostpasswdurl']="http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."?lostpasswdkey=".$lostpasswdkey."&username=".$username." \n";

    // send an email with the changing passwd code url
    // make the registration email
    if (!makeandsendmail($context,"register-lostpasswdmail")) {
      $context['error_sending_email']=$err=1;
      break;
    }
    $context['email_send']=1;
  } while (0);
  } else {
    if ($_GET['email']) $context['email']=$_GET['email'];
  }

  posttraitement($context);
  require_once(TOINCLUDE."calcul-page.php");
  calcul_page($context,"register-lostpasswd");
  return;

} elseif ( ($edit && !$reject)) {
  extract_post();
  $context['webmaster']=WEBMASTER;

  // "ms" stands for Manager Safe. Avoid auto-completion.
  $context['username']=$context['usernamems'];

  do { // exception
    //    $len=strlen($context[username]);
    //    if (!preg_match("/^[0-9A-Za-z@\.]+$/",$context[username])) { $err=$context[erreur_username]=1; }

    $context[realname]=strip_tags($context[realname]);
    if (!$context[realname]) { $context[error_realname]=$err=1; }

    // check the email
    if (!$context[email] || !validemail($context[email])) { $context[error_email]=$err=1; break; }
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]users WHERE email='$context[email]'") or die(mysql_error());
    if (mysql_num_rows($result)) { $context[error_email_exists]=$err=1; }

    $context[url]=strip_tags($context[url]);
    $context[commentaire]=strip_tags($context[commentaire]);
      
    if ($err) break;

    srand((double)microtime()*1000000);

    // generate the login
    $username=generate_login($context[realname]);
    // check it does not exists
    do {
      $result=mysql_query("SELECT id FROM $GLOBALS[tp]users WHERE username='$username'") or die(mysql_error());
      if (mysql_num_rows($result)==0) break;
      $i++;
      $username.=rand() % 10;
    } while ($i<10);
    if ($i==10) { $context[error_realname]=$err=1; break; } //!!!!
    //

    // generate the passwd.
    list($passwd,$encodedpasswd)=generate_passwd($username);

    // ok, add the user.
    mysql_query("INSERT INTO $GLOBALS[tp]users (id,username,passwd,url,realname,email,priority,commentaire) VALUES ('$id','$username','$encodedpasswd','$context[url]','$context[realname]','$context[email]','5','$context[commentaire]')") or die(mysql_error());
    $id=mysql_insert_id();

    // make the registration email
    $context[passwd]=$passwd;
    $context[username]=$username;

    // send the registration mail
    if (!makeandsendmail ($context,"register-mail")) {
      $context[error_sending_email]=$err=1;
      mysql_query("DELETE FROM $GLOBALS[tp]users WHERE id='$id'") or die(mysql_error());
      break; 
    }

    require_once(TOINCLUDE."calcul-page.php");
    calcul_page($context,"register-final");

    return;
  } while (0);
}




// post-traitement
posttraitement($context);

$context[passwd]="";

require_once(TOINCLUDE."calcul-page.php");

if (!$reject && ($accept || $err)) {
  calcul_page($context,"register");
  return;
}

if ($reject) { $context[error_reject]=1; }

calcul_page($context,"register-conditions");
return;


function validemail($email)

{
  return ereg(".*\@[^\.]*\..*",$email);
}



function generate_login($string){
  return preg_replace("/\W/","",strtr(
	       strtr(utf8_decode($string),
		     '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
		     'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
	       array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
		     '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u')));
}


function generate_passwd($username) {

    $passwd="";
    $chars="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789$&*";
    for($i=0; $i<8;$i++){ 
      $passwd.= $chars[rand()%strlen($chars)];
    }
    return array($passwd,md5($passwd.".".$username));
}


function makeandsendmail($context,$tpl)

{
  require_once (TOINCLUDE."calcul-page.php");
  
  $context['subject']="";
  $context['from']="";

  ob_start();
  calcul_page($context,$tpl);
  $content=str_replace("\n","\r\n",ob_get_contents());
  ob_end_clean();

  // send the registration mail
  $ret=mail ($context['email'],$context['subject'],$content,"From: ".$context['from']);
  if ($ret) mail(WEBMASTER,$context['subject'],$content,"From: ".$context['from']);
  return $ret;
}


?>
