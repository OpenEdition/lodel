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

require_once($home."auth.php");


class Controler {


  function Controler($authlevel,$tables,$table="") 

  {
    global $home,$context;


    if ($_POST) {
      $therequest=&$_POST;
    } else {
      $therequest=&$_GET;
    }
    $do=$therequest['do'];

    authenticate($authlevel, $do=="view" || $do=="edit" || $do=="copy" || $do=="delete"  || $do=="changerank");
    #require($home."langues.php");
    require_once($home."func.php");

    extract_post($therequest);


    if ($do) {
      if (!$table) $table=$therequest['table'];
      if (!in_array($table,$tables)) die("ERROR: unknown table");
      $context['table']=$table;

      // get the various common parameters
      foreach(array("class","classtype","type","textgroups") as $var) {
	if ($therequest[$var]) {
	  require_once($home."validfunc.php");
	  if (!validfield($therequest[$var],$var)) die("ERROR: a valid $var name is required");
	  $context[$var]=$therequest[$var];
	}
      }
      // ids. Warning: don't remove this, the security in the following rely on these ids are real int.
      foreach(array("id","idgroup","idclass","idparent") as $var) {
	$context[$var]=intval($therequest[$var]);
      }
      // dir
      if ($therequest['dir'] && ($therequest['dir']=="up" || 
				 $therequest['dir']=="down" || 
				 is_numeric($therequest['dir']))) $context['dir']=$therequest['dir'];


      // valid the request
      if (!preg_match("/^[a-zA-Z]+$/",$do)) die("ERROR: invalid action");
      $do=$do."Action";

      require_once($home."logic.php");
      $logic=getLogic($table);

      switch($do) {
      case 'listAction' :
	$ret='ok';
	break;
      default:
	// create the logic for the table
	if (!method_exists($logic,$do)) die("ERROR: invalid action");
	// call the logic action
	$ret=$logic->$do($context,$error);
      }


      // create the view
      require_once($home."view.php");
      $view=new View;

      switch($ret) {
      case 'back' :
	$view->back();
	break;
      case 'error' :
	$context['error']=$error;
	print_r($error);
      case 'ok' :
	if ($do=="listAction") {
	  $view->render($context,$table);
	} else {
	  $view->render($context,"edit_".$table);
	}
	break;
      default:
	die("ERROR: invalid viewAction: $ret");
      }

    } else {
      require($home."calcul-page.php");
      calcul_page($context,"index");
    }
  } // constructor

} // class Controler

function loop_fielderror(&$context,$funcname,$arguments)

{
  if (!$arguments['field']) die("ERROR: loop fielderror require a field attribute");
  $localcontext=$context;
  $localcontext['error']=$context['error'][$arguments['field']];

  if ($localcontext['error']) {
    call_user_func("code_do_$funcname",$localcontext);
  }
}

?>
