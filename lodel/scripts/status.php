<?

// ne pas changer les valeurs numeriques

$listestatus=array(
		   -32 => "Brouillon",
		   -1  => "Non publié",
		   1   => "Publié",
		   32  => "Publié (protégé)");


function makeselectstatus(&$context) {
  global $listestatus;
  foreach ($listestatus as $status =>$statusstr) {
    $selected=$status==$context[status] ? "SELECTED" : "";
    echo "<OPTION $selected VALUE=\"$status\">$statusstr</OPTION>";
  }
}



?>
