<?

makefilterfunc();
require_once("CACHE/filterfunc.php");

function makefilterfunc()

{
  //
  // cherche les champs a filtrer
  //
  include ($home."connect.php");
  $result=mysql_query("SELECT classe,$GLOBALS[tp]champs.nom,filtrage FROM $GLOBALS[champsgroupesjoin] WHERE $GLOBALS[tp]groupesdechamps.statut>0 AND $GLOBALS[tp]champs.statut>0 AND filtrage!=''") or die (mysql_error());
  while (list($classe,$nom,$filter)=mysql_fetch_row($result)) {
#echo $classe," ",$nom," ",$filter,"<br>\n";
    // converti filtrage en fonction.
    $filters=preg_split("/\|/",$filter);
    $filterfunc='$x';
    foreach ($filters as $filter) {
      if (preg_match("/^([A-Za-z][A-Za-z_0-9]*)(?:\((.*?)\))?$/",$filter,$result2)) { 
	$funcname=$result2[1]; // name of the pipe function
	$arg=$result2[2]; // argument if any

	if ($arg) $arg.=",";
	$filterfunc=$funcname.'('.$arg.$filterfunc.')';
      } else {
	die("invalid filter function: $filter");
      }
    }
    $filterfunc="return ".$filterfunc.";";
    $filterstr="'$classe.$nom'=>'".addslashes($filterfunc)."',";
  }
  //if (!$filterstr) die("erreur interne dans filterfunc");
  // pas tres optimal. Il faudrait plutot que la boucle appel mysql_fetch_assoc dans ce cas... mais bon.


  //
  // cree la fonction avec filtrage
  //

  $fp=fopen("CACHE/filterfunc.php","w");      
  fputs($fp,'<? function filtered_mysql_fetch_assoc ($result) {
  $filters=array('.$filterstr.');
  $count=mysql_num_fields($result);
  $row=mysql_fetch_row($result);
  if (!$row) return array();
  for($i=0; $i<$count; $i++) {
     $fieldname=mysql_field_name($result,$i);
     $fullfieldname=mysql_field_table($result,$i).".".$fieldname;
     if ($filters[$fullfieldname]) {
        $filter=create_function(\'$x\',$filters[$fullfieldname]);
        $ret[$fieldname]=$filter($row[$i]);
# echo $filters[$fullfieldname]," ",$fieldname," ",$ret[$fieldname]," ",$filter,"<br>";

     } else {
        $ret[$fieldname]=$row[$i];
     }
  }
  return $ret;
}?>');
  fclose($fp);
}
?>
