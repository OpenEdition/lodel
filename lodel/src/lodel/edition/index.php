<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_VISITEUR);

if ($id) { // c'est l'id et non le parent qu'on veut... il faut chercher le parent
  $id=intval($id);
  $result=mysql_query ("SELECT parent FROM $GLOBALS[tableprefix]publications WHERE id='$id'") or die (mysql_error());
  if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
  list($parent)=mysql_fetch_row($result);
}


$context[parent]=$parent=intval($parent);

if ($parent) {
  $result=mysql_query ("SELECT tpledit FROM $GLOBALS[tableprefix]typepublis,$GLOBALS[tableprefix]publications WHERE $GLOBALS[tableprefix]publications.id='$parent' AND type=$GLOBALS[tableprefix]typepublis.nom") or die (mysql_error());
  if (mysql_num_rows($result)<1) { header ("Location: not-found.html"); return; }
  list($base)=mysql_fetch_row($result);
} else {
  $base="edition";
}


include ("$home/calcul-page.php");
calcul_page($context,$base);


function boucle_themesparents (&$context,$funcname) {
	 $parent=intval($context[parent]);
#ifndef LODELLIGHT
	 $type="AND type='theme'";
#else
	 $type="";
#endif
	 if (!$parent) return;

	 $contexts=array(); $i=0;

	$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$parent' $type") or die (mysql_error());	 
	  while (mysql_num_rows($result)>0) {
		$contexts[$i]=mysql_fetch_array($result);
		$parent=$contexts[$i][parent];
		$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$parent' $type") or die (mysql_error());	 
		$i++;
	 }

	$i--;
	while ($i>=0) {
		 $localcontext=array_merge($context,$contexts[$i]);
		 call_user_func("code_boucle_$funcname",$localcontext);
		 $i--;
	 }
}
?>












