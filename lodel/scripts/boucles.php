<?
/*********************************************************************/
/*  Boucle permettant de trouver depuis une publication toutes les   */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une série.                     */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentpubli">[#ID]</BOUCLE>                     */
/*********************************************************************/
function boucle_topparentpubli(&$context)
{
        // $context est un tableau qui contient une pile. Si on fait $context[toto] 
        // alors [#TOTO] sera accessible dans lodelscript !!!
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
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
                        code_boucle_topparentpubli($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli1(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli1($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli2(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli2($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli3(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli3($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli4(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli4($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli5(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli5($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli6(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli6($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli7(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli7($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentpubli8(&$context)
{
        $id=$context[id];       // On récupère le paramètre id
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentpubli8($localcontext);
                        return;
                }
        }
        while(1);
}

/*********************************************************************/
/*  Boucle permettant de trouver depuis un document toutes les       */
/*  infos concernant la publication parente la plus haute dans       */
/*  l'arborescence et qui ne soit pas une série.                     */
/*                                                                   */
/*  Appeller cette boucle dans le code lodelscript par :             */
/*  <BOUCLE NAME="topparentdoc">[#ID]</BOUCLE>                       */
/*********************************************************************/
function boucle_topparentdoc(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc1(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc1($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc2(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc2($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc3(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc3($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc4(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc4($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc5(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc5($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc6(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc6($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc7(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc7($localcontext);
                        return;
                }
        }
        while(1);
}

function boucle_topparentdoc8(&$context)
{
        $id=$context[id];
        $result=mysql_query("SELECT publication FROM documents WHERE id='$id'") or die (mysql_error());
        $row=mysql_fetch_array($result);
        $id = $row[publication];
        do
        {
                $result=mysql_query("SELECT * FROM publications WHERE id='$id' AND type NOT LIKE 'serie_%'") or die (mysql_error());
                if(mysql_num_rows($result))
                {
                        $row=mysql_fetch_array($result);
                        $id=$row[parent];
                }
                else
                {
                        $localcontext=array_merge($context,$row);
                        code_boucle_topparentdoc8($localcontext);
                        return;
                }
        }
        while(1);
}
?>