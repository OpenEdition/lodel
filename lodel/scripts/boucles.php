<?php
/*********************************************************************/
/*  Boucle permettant de trouver depuis une publication toutes les   */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une au sommet.                 */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentpubli">[#ID]</BOUCLE>                     */
/*********************************************************************/
function loop_topparentpubli(&$context,$funcname)
{
  // $context est un tableau qui contient une pile. Si on fait $context[toto] 
  // alors [#TOTO] sera accessible dans lodelscript !!!
  $id=$context[id];       // On récupère le paramètre id

  $result=mysql_query("SELECT * FROM $GLOBALS[publicationstypesjoin],$GLOBALS[tp]relations WHERE $GLOBALS[tp]entites.id=id1 AND id2='$id' AND $GLOBALS[tp]entites.statut>".($GLOBALS[visiteur] ? -64 : 0)." ORDER BY degres DESC LIMIT 1,1") or die (mysql_error());

  while ($row=mysql_fetch_assoc($result)) {       
    // On fait un array_merge pour récupérer toutes les infos contenues
    // dans le tableau $row et les mettre dans le tableau $context.
    $localcontext=array_merge($context,$row);
    // Puis on fait appel à la fonction en concaténant avant "code_" 
    // et en lui passant en paramètre la dernière valeur.
    // C'est équivalent à un return et ça permet d'avoir les
    // valeurs accessibles en lodelscript. 
    call_user_func("code_do_$funcname",$localcontext);
    return;
  }
}

/*********************************************************************/
/*  Boucle permettant de trouver depuis un document toutes les       */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une série.                     */
/*  La condition d'arrêt de la boucle est la chaine de caractères :  */
/*  "serie_"                                                         */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentdoc">[#ID]</BOUCLE>                       */
/*********************************************************************/
function loop_topparentdoc(&$context,$funcname)
{
  topparentpubli($context,funcname);
}

function loop_rubriquesparentes (&$context,$funcname) {
	 $id=intval($context[id]);
	 die ("a reecrire. Ghislain le 01/08/03");
#ifndef LODELLIGHT
	 $type="AND type='rubrique'";
#else
	 $type="";
#endif
	 if (!$id) return;

	 $contexts=array(); $i=0;

	$result=mysql_query("SELECT * FROM $GLOBALS[tp]publications WHERE id='$id' $type AND statut>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
	  while (mysql_num_rows($result)>0) {
		$contexts[$i]=mysql_fetch_array($result);
		$parent=$contexts[$i][parent];
		$result=mysql_query("SELECT * FROM $GLOBALS[tp]publications WHERE id='$parent' $type AND statut>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
		$i++;
	 }

	$i--;
	while ($i>=0) {
		 $localcontext=array_merge($context,$contexts[$i]);
		 call_user_func("code_do_$funcname",$localcontext);
		 $i--;
	 }
}

function loop_publisparentes(&$context,$funcname,$critere="")
{
  $id=intval($context[id]);
  if (!$id) return;
  
  $result=mysql_query("SELECT *, type  FROM $GLOBALS[publicationstypesjoin],$GLOBALS[tp]relations WHERE $GLOBALS[tp]entites.id=id1 AND id2='$id' AND $GLOBALS[tp]entites.statut>".($GLOBALS[visiteur] ? -64 : 0)." ORDER BY degres DESC") or die (mysql_error());
    
  while ($row=mysql_fetch_assoc($result)) {
    $localcontext=array_merge($context,$row);
    call_user_func("code_do_$funcname",$localcontext);
  }
}


function loop_toc($context,$funcname,$arguments)

{
  if (!isset($arguments[text])) {
    if ($GLOBALS[visiteur]) die("ERROR: the loop \"toc\" requires a TEXT attribut");
    return;
  }

  if (!preg_match_all("/<(r2r:section(\d+))>(.*?)<\/\\1>/is",$arguments[text],$results,PREG_SET_ORDER)) {
    if (!preg_match_all("/<(div)\s+class=\"section(\d+)\">(.*?)<\/\\1>/is",$context[texte],$results,PREG_SET_ORDER)) return;
  }
  foreach($results as $result) {
    $localcontext=$context;
    $localcontext[tocid]=(++$tocid);
    $localcontext[titre]=$result[3];
    $localcontext[niveau]=intval($result[2]);
    call_user_func("code_do_$funcname",$localcontext);
  }
}

?>
