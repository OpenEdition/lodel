<?

/*********************************************************************/
/*  Boucle permettant de trouver depuis une publication toutes les   */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une série.                     */
/*  La condition d'arrêt de la boucle est la chaine de caractères :  */
/*  "serie_"                                                         */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentpubli">[#ID]</BOUCLE>                     */
/*********************************************************************/
function boucle_topparentpubli(&$context,$funcname)
{
        // $context est un tableau qui contient une pile. Si on fait $context[toto] 
        // alors [#TOTO] sera accessible dans lodelscript !!!
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * $GLOBALS[tableprefix]FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%' AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());
                // On teste si on a un résultat dans la requête
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        // On fait un array_merge pour récupérer toutes les infos contenues
                        // dans le tableau $row et les mettre dans le tableau $context.
                        $localcontext=array_merge($context,$row);
						// Puis on fait appel à la fonction en concaténant avant "code_" 
                        // et en lui passant en paramètre la dernière valeur.
                        // C'est équivalent à un return et ça permet d'avoir les
                        // valeurs accessibles en lodelscript. 
 						call_user_func("code_boucle_$funcname",$localcontext);
                       return;
                }
        }
        while(1);
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
function boucle_topparentdoc(&$context,$funcname)
{
        $id=$context[id];
        $result=mysql_query("SELECT $GLOBALS[tableprefix]publication FROM documents WHERE id='$id' AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$id' AND type NOT LIKE 'serie_%' AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        //code_boucle_topparentdoc($localcontext);
						call_user_func("code_boucle_$funcname",$localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_themesparents (&$context,$funcname) {
	 $id=intval($context[id]);
#ifndef LODELLIGHT
	 $type="AND type='theme'";
#else
	 $type="";
#endif
	 if (!$id) return;

	 $contexts=array(); $i=0;

	$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$id' $type AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
	  while (mysql_num_rows($result)>0) {
		$contexts[$i]=mysql_fetch_array($result);
		$parent=$contexts[$i][parent];
		$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$parent' $type AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
		$i++;
	 }

	$i--;
	while ($i>=0) {
		 $localcontext=array_merge($context,$contexts[$i]);
		 call_user_func("code_boucle_$funcname",$localcontext);
		 $i--;
	 }
}

function boucle_publisparentes(&$context,$funcname,$critere="")
{
	 $id=intval($context[id]);
	 if (!$id) return;

	 $contexts=array(); $i=0;

	$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$id' $critere AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
	  while (mysql_num_rows($result)>0) {
		$contexts[$i]=mysql_fetch_array($result);
		$parent=$contexts[$i][parent];
		$result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id='$parent' $critere AND status>".($GLOBALS[visiteur] ? -64 : 0)) or die (mysql_error());	 
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
