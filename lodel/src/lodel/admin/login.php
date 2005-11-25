<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
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

require("siteconfig.php");
require_once("auth.php");

if ($_POST['login']) {
  require_once("func.php");
  extract_post();
  do {
    require_once("connect.php");
    require_once("loginfunc.php");
    if (!check_auth($context['login'],$context['passwd'],$site)) {
      $context['error_login']=1; break; 
    }
    // ouvre une session
    $err=open_session($context['login']);
    if ($err) { $context[$err]=1; break; }
    header ("Location: http://".$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] ? ":".$_SERVER['SERVER_PORT'] : "").$url_retour);
    die ("url_retour: $url_retour");
  } while (0);
}

$context['passwd']=$passwd=0;


// variable: sitebloque
if ($context['error_sitebloque']) { // on a deja verifie que la site est bloque.
  $context['sitebloque']=1;
} else { // test si la site est bloque dans la DB.
  require_once("connect.php");
  usemaindb();
  $context['sitebloque']=$db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='$site' AND status>=32"));
  usecurrentdb();
  
}

$context['url_retour']=$url_retour;
$context['error_timeout']=$error_timeout;
$context['error_privilege']=$error_privilege;

require_once "view.php";
$view = &View::getView();
$view->render($context,"login");

?>
