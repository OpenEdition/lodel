<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
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


require_once($GLOBALS[home]."func.php");

# le $ret ne sert a rien, mais s'il n'est pas la, ma version de php n'aime pas: bug eratique.

# fonction d entree pour le calcul d'une page

# on sort du UTF-8 par defaut
# sinon, on applique une regle un peu dictatorialle, c'est l'iso-latin1

function calcul_page(&$context,$base,$cache_rep="",$base_rep="tpl/") {

  global $home,$format;

  if ($_REQUEST['clearcache']) {
    require_once($home."cachefunc.php");   
    if (defined("SITEROOT")) {
      removefilesincache( SITEROOT,SITEROOT."lodel/edition",SITEROOT."lodel/admin");
    } else {
      removefilesincache( "." );
    }
    $_REQUEST['clearcache']=false; // to avoid to erase the CACHE again
  }

  if ($format && !preg_match("/\W/",$format)) $base.="_".$format;
  $format=""; // en cas de nouvel appel a calcul_page

  $template_cache = $cache_rep."CACHE/tpl_$base.php";
  $base=$base_rep.$base.".html";
  if (!file_exists($base)) die("le template $base n'existe pas.");
  $template_time=myfilemtime($template_cache);


  if (($template_time <= myfilemtime($base))) {
    if ($GLOBALS['lodeluser']['admin']) $context['templatesrecompiles'].="$base | ";
    if (!defined("TOINCLUDE")) define("TOINCLUDE",$home);

    require_once ($home."lodelparser.php");
    $parser=new LodelParser;
    $parser->parse($base, $template_cache);
  }

  require_once ($GLOBALS['home']."connect.php");

  // execute le template php
  require_once($home."textfunc.php");		
  if ($GLOBALS['showhtml'] && $GLOBALS['lodeluser']['visitor']) {
    ob_start();
    require($template_cache);
    $content=ob_get_contents();
    ob_end_clean();
    require_once ($home."showhtml.php");
    echo show_html($content);
    return;
  }
  require_once($home."loops.php");

  if ($context['charset']=="utf-8") { // utf-8 c'est le charset natif, donc on sort directement la chaine.
    require($template_cache);
  } else {
    // isolatin est l'autre charset par defaut
    ob_start();
    require($template_cache);
    $contents=ob_get_contents();
    ob_end_clean();
    echo utf8_decode($contents);
  }
}

function insert_template($context,$filename)

{
  if (file_exists("tpl/".$filename.".html")) {
    calcul_page($context,$filename);
  } elseif (file_exists($GLOBALS['home']."../tpl/".$filename.".html")) {
    calcul_page($context,$filename,"",$GLOBALS['home']."../tpl/");
  } else {
    die("ERROR: unable to find the file $filename.html");
  }
}


function mymysql_error($query,$tablename="")

{
  if ($GLOBALS['lodeluser']['editor']) {
    if ($tablename) $tablename="LOOP: ".$tablename." ";
    die("</body>".$tablename."QUERY: ".htmlentities($query)."<br><br>".mysql_error());
  } else {
    if ($GLOBALS['contactbug']) @mail($contactbug,"[BUG] LODEL - $GLOBALS[version] - $GLOBALS[database]","Erreur de requete sur la page ".$_SERVER['REQUEST_URI']."<br>".htmlentities($query)."<br><br>".mysql_error());
    die("Une error est survenue lors de la g&eacute;n&eacute;ration de cette page. Veuillez nous excuser, nous traitons le probl&egrave;me le plus rapidement possible");
  }
}


?>
