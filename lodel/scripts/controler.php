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

require_once("auth.php");


class Controler {


  function Controler($logics,$lo="") 

  {
    global $home,$context;

    if ($_POST) {
      $therequest=&$_POST;
    } else {
      $therequest=&$_GET;
    }
    $do=$therequest['do'];

    if ($do=="back") {
      require_once("view.php");
      View::back(2);
      return;
    }

    require_once("func.php");

    extract_post($therequest);

    if ($do) {
      if ($therequest['lo']) $lo=$therequest['lo'];
      if (!in_array($lo,$logics)) die("ERROR: unknown logic");
      $context['lo']=$lo;

      // get the various common parameters
      foreach(array("class","classtype","type","textgroups") as $var) {
	if ($therequest[$var]) {
	  require_once("validfunc.php");
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

      require_once("logic.php");

      switch($do) {
      case 'listAction' :
	recordurl();
      default:
	$logic=&getLogic($lo);
	// create the logic for the table
	if (!method_exists($logic,$do)) {
	  if ($do=="listAction") {
	    $ret="_ok";
	  } else {
	    die("ERROR: invalid action");
	  }
	} else {
	  // call the logic action
	  $ret=$logic->$do($context,$error);
	}
      }
      if (!$ret) die("ERROR: invalid return from the logic.");

      // create the view
      require_once("view.php");
      $view=&getView();

      switch($ret) {
      case '_back' :
	$view->back();
	break;
      case '_error' :
	// hum... needs to remove the slashes... don't really like that, because some value may still 
	// come from  database or lodel. Doing this way is not a security issue but may forbide
	// user to use \' in there text
	mystripslashes($context);
	$logic->viewAction($context,$error); // in case anything is needed to be put in the context
	$context['error']=$error;
	print_r($error);
      case '_ok' :
	if ($do=="listAction") {
	  $view->renderCached($context,$lo);
	} else {
	  $view->render($context,"edit_".$lo);
	}
	break;
      default:
	if (strpos($ret,"_location:")===0) {
	  header(substr($ret,1));
	  exit();
	}

	$view->render($context,$ret);
      }
    } else {
      recordurl();
      require_once("view.php");
      $view=&getView();
      $view->renderCached($context,"index");
      //require("calcul-page.php");
      //calcul_page($context,"index");
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


function loop_field_selection_values(&$context,$funcname,$arguments)

{
  //Get values of the list in the editionparams field for the current field
  // and if no editionparams call alter
  if (!isset($context['editionparams'])) die("ERROR: internal error in loop_field_selection_values");

  $arr = explode(",",$context['editionparams']);
  $choosenvalues = explode(",",$context['value']); //if field contains more than one value (comma separated)
  foreach($arr as $value) {
    $value = trim($value);
    $localcontext=$context;
    $localcontext['value'] = $value;
    if(in_array($value,$choosenvalues)) {		
      $localcontext['checked'] = 'checked="checked"';
      $localcontext['selected'] = 'selected="selected"';
    }
    call_user_func("code_do_$funcname",$localcontext);
  }
}



?>
