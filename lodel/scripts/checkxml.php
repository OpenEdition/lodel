<?

function checkfile ($filename,$error=0) {
  $text=file($filename);
  return checkstring($text);
}

function checkstring (&$text,$error=0) {

  $xml_parser = xml_parser_create();
  xml_parser_set_option($xml_parser,XML_OPTION_CASE_FOLDING,0) or die("Parser incorrect");
  if ($error) {
    xml_set_element_handler($xml_parser, "startElementCHECK", "endElementCHECK");
    xml_set_character_data_handler($xml_parser, "characterHandlerCHECK");
  }
  
  if (!xml_parse($xml_parser, $text)) {
    if (!$error) {
      checkstring ($text,1);
      return;
    } else {
      echo "<font color=red>";
      echo preg_replace("/\n/se","'<br><b>'.((\$GLOBALS[line]++)+2).'</b> '",htmlspecialchars(substr($text,xml_get_current_byte_index($xml_parser)-2)));
      echo "</font>\n";
      echo sprintf("<br><H2>XML erreur: %s ligne %d</H2>",
		   xml_error_string(xml_get_error_code($xml_parser)),
		   xml_get_current_line_number($xml_parser));
      echo "L'erreur se situe avant la zone rouge. Elle peut être due à une erreur bien au dessus la ligne donnée par le parser<br>";
      echo "<br>".htmlentities($text);

      xml_parser_free($xml_parser);
      return FALSE;
    }
  }
  xml_parser_free($xml_parser);
  return TRUE;
}


function characterHandlerCHECK ($parser,$data) {
  echo preg_replace("/\n/se","'<br><b>'.((\$GLOBALS[line]++)+2).'</b> '",$data);
}

function startElementCHECK($parser, $name, $attrs) {
  $balise="<$name";
  foreach ($attrs as $att => $val) {
    $balise.=" $att=\"$val\"";
  }
  $balise.=">";

  echo "<font color=blue>".htmlentities($balise)."</font>";
}

function endElementCHECK($parser, $name) {
  echo "<font color=blue>&lt;/$name&gt;</font>";
}

//function defaultHandler($parser,$data)
//
//{ if (substr($data,0,1)=="&") echo "#$data#"; }
//
?>
