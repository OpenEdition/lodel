<?

require_once ($home."balises.php");

// fonction pour parser du xml

function myxmlparse(&$text,$startHandler,$endHandler="defaultEndHandler",$charHandler="") {

  $parser = xml_parser_create();
  xml_parser_set_option($parser,XML_OPTION_CASE_FOLDING,0);
  xml_set_element_handler($parser, $startHandler,$endHandler);
  if ($charHandler) xml_set_character_data_handler($parser, $charHandler);
#  xml_set_default_handler($parser, "xml_parse_into_struct_ns_defaultHandler");

  if (!xml_parse($parser, $text)) {
    echo sprintf("<br>XML error: %s at line %d <br><br>",
		 xml_error_string(xml_get_error_code($parser)),
		 xml_get_current_line_number($parser));
	global $home;
	include_once ($home."checkxml.php");
	checkstring($text);
 }
}

function defaultEndHandler($parser,$name) {}


function extract_xml ($balises,&$text)

{
  $ret=array();
  if (!is_array($balises)) $balises=array($balises);
  foreach ($balises as $b) {
    if ($b!=strtolower($b)) die("Informez Ghislain de ce \"bug\"<BR>balise $b");

    if (preg_match_all ("/<r2r:$b\b([^>]*)>(.*?)<\/r2r:$b>/s",$text,$results,PREG_SET_ORDER)) {
	foreach ($results as $result) {
	  /////temporaire... doit devenir du XSL
	  // avril 03: ca ne deviendra pas du XSL.
      $result[2]=preg_replace(array(
				    "/<r2r:(\w+)(?:\b[^>]+)?>/i", // replace les autres balises r2r par des DIV
				    "/<\/r2r:[^>]+>/i"				    				    ),
			      array(
				    "<div classe=\"\\1\">",
				    "</div>"
				    ),traite_separateur($result[2]));
      ///// fin temporaire
      // cherche la langue
	$lang="";
      if ($b=="resume" && $result[1]) {
	// cherche la langue
	if (preg_match("/lang\s*=\s*\"([^\"]+)\"/i",$result[1],$result2)) {
	  $lang="_lang".$result2[1];
	}
      }
      $ret[strtolower($b.$lang)]=$result[2];
    }
  }
 }
  return $ret;
}


/*
// la balise peut etre une liste de balise separé par des |
function extract_attr(&$texte,$balise) {

  preg_match_all("/<r2r:($balise)(?:\s+(\w+)\s*=\s$\"([^\"]+)\")+>/i",$texte,$results,PREG_SET_ORDER);

  $arrall=array();

  foreach ($results as $result) {
    $arr[0]=array_shift($result); // tout le match
    $arr[1]=array_shift($result); // nom de la balise
    while ($result) {
      $attrname=array_shift($result);
      $arr[$attrname]=array_shift($result);
    }
    array_push($arrall,$arr);
  }
  return $arrall;
}

// ecrit un tag avec en argument un tableau fournit par extract_attr
function write_tag($arr) {
  
  $str="<".$arr[1];

  foreach ($arr as $k=>$v) {
    if ($k==0 || $k==1) continue;
    if ($v) $str.=" $k=\"$v\"";
  }

  return $str.">";
}
*/
?>
