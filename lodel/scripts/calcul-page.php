<?

include_once("$home/func.php");
# fonction d'entree pour le calcul d'une page

function calcul_page(&$context,$lbase="",$cache_rep="",$base_rep="tpl/") {

  global $base,$home;

  if (!$lbase) $lbase=$base;

  $template_cache = $cache_rep."CACHE/tpl_$lbase.php";
  $lbase=$base_rep.$lbase.".html";
  $macro=$base_rep."macros.html";
  $template_time=myfilemtime($template_cache);

#  echo $template_time-time()," ",lyfilemtime($lbase)-time(),"<br>";

  if (($template_time <= myfilemtime($lbase)) ||
      ($template_time <= myfilemtime($macro)) ||
      $GLOBALS[recalcul_templates]) {
	if ($GLOBALS[admin]) echo "<H3>Le template $lbase vient d'être mis à jour </H3><BR>";
    include_once ("$home/parser.php");
    parse($lbase, $template_cache);
  }
  // execute le template php
		
  include_once("$home/textfunc.php");
  if ($GLOBALS[showhtml] && $GLOBALS[visiteur]) {
    ob_start();
    include($template_cache);
    $content=ob_get_contents();
    ob_end_clean();
    include ("$home/showhtml.php");
    echo show_html($content);
    return;
  }
  include($template_cache);
  return;
}

function insert_template($filename)

{
	# ce n'est pas tres propre parce qu'on depend d'une global
  calcul_page($GLOBALS[context],$filename,"");
}

?>
