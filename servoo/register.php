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

if ($edit && !$reject) {
  extract_post();
  // "ms" stands for Manager Safe. Avoid auto-completion.
  $context[username]=$context[usernamems];

  do { // exception
    //    $len=strlen($context[username]);
    //    if (!preg_match("/^[0-9A-Za-z@\.]+$/",$context[username])) { $err=$context[erreur_username]=1; }

    $context[realname]=strip_tags($context[realname]);
    if (!$context[realname]) { $context[error_realname]=$err=1; }

    // check the email
    if (!$context[email] || !ereg(".*\@[^\.]*\..*",$context[email])) { $context[error_email]=$err=1; break; }// from SPIP
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
    $passwd="";
    $chars="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789#$&*";

    for($i=0; $i<8;$i++){ 
      $passwd.= $chars[rand()%strlen($chars)];
    }

    $encodedpasswd=md5($passwd.".".$context[username]);

    // ok, add the user.
    mysql_query("INSERT INTO $GLOBALS[tp]users (id,username,passwd,url,realname,email,priority,commentaire) VALUES ('$id','$username','$encodedpasswd','$context[url]','$context[realname]','$context[email]','5','$context[commentaire]')") or die(mysql_error());
    $id=mysql_insert_id();

    // make the registration email
    $context[webmaster]=WEBMASTER;
    $context[passwd]=$passwd;
    $context[username]=$username;
    $context[subject]="";
    $context[from]="";
    require_once (TOINCLUDE."calcul-page.php");
    ob_start();
    calcul_page($context,"register-mail");
    $content=str_replace("\n","\r\n",ob_get_contents());
    ob_end_clean();

    // send the registration mail
    if (!mail ($context[email],$context[subject],$content,"From: $context[from]")) {
      $context[error_sending_email]=1;
      mysql_query("DELETE FROM $GLOBALS[tp]users WHERE id='$id'") or die(mysql_error());
      break; 
    }

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





function generate_login($string){
  return preg_replace("/\W/","",strtr(
	       strtr(utf8_decode($string),
		     '¦´¨¸¾ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÑÒÓÔÕÖØÙÚÛÜÝàáâãäåçèéêëìíîïñòóôõöøùúûüýÿ',
		     'SZszYAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy'),
	       array('Þ' => 'TH', 'þ' => 'th', 'Ð' => 'DH', 'ð' => 'dh', 'ß' => 'ss',
		     '¼' => 'OE', '½' => 'oe', 'Æ' => 'AE', 'æ' => 'ae', 'µ' => 'u')));
}