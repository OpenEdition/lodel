<?

include_once($home."func.php");
# fonction d'entree pour le calcul d'une page

function calcul_page(&$context,$base,$cache_rep="",$base_rep="tpl/") {

  global $home,$format;

  if ($format && !preg_match("/\W/",$format)) $base.="_".$format;
  $format=""; // en cas de nouvel appel a calcul_page

  $template_cache = $cache_rep."CACHE/tpl_$base.php";
  $base=$base_rep.$base.".html";
  $template_time=myfilemtime($template_cache);

  if (($template_time <= myfilemtime($base)) ||
      //      ($template_time <= myfilemtime($macro)) ||
      $GLOBALS[recalcul_templates]) {
	if ($GLOBALS[admin]) {
	  $context[templatesrecompiles].="$base | ";

#	  $script='<SCRIPT LANGUAGE="JavaScript">if(window.defaultStatus==""){window.defaultStatus="ATTENTION LES TEMPLATES SUIVANTS ONT ETE MODIFIES : ";}
#window.defaultStatus+=\'$base | \';</SCRIPT>';
        }

    include_once ($home."parser.php");
    parse($base, $template_cache);
  }
  // execute le template php
		
  include_once($home."textfunc.php");
  if ($GLOBALS[showhtml] && $GLOBALS[visiteur]) {
    ob_start();
    include($template_cache);
    $content=ob_get_contents();
    ob_end_clean();
    include_once ($home."showhtml.php");
    echo show_html($content);
    return;
  }
  include_once($home."boucles.php");
  include($template_cache);
  return;
}

function insert_template($filename)

{
	# ce n'est pas tres propre parce qu'on depend d'une global
  calcul_page($GLOBALS[context],$filename,"");
}

?>
