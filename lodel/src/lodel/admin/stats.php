<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/

// Affiche des statistiques sur le  site.

require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMIN,NORECORDURL);
# ghi ####### ne pas mettre de NORECORDURL ici

/*
include_once ($home."connect.php");

//////////////////////////////////  Documents
// Nombre total de documents
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]documents") or dberror();
list($context[nbdocs])=mysql_fetch_row($result);
$nbdocs=$context[nbdocs];
# la ligne suivante est toujours symptone d'un probleme de design de programmation. En fait, il faut que tu fasses un if qui empeche l'execution des requetes suivante (celle sur documents), et si necessaire initialiser les variables a 0. voir ci-dessous
if(!$nbdocs) $nbdocs=1;  // Evite les divisions par zéro

# ghi ##### if ($nbblocs) {
// Nombre de documents en brouillon
# ghi ##### certains de tes tests sur les status ne sont pas correctes. -32 est la limite entre brouillon et pas brouillon. Donc ici la condition est <=-32 je sais que ca marche... pour le moment. Si un jour on utilise les valeurs intermediaires tes stat ne marcheront plus... c'est pas tres grave, mais fait attention a ca parce que ce genre de chose laisse traine des incompatibilite tres difficile a debuguer.
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]documents WHERE status<-1") or dberror();
list($context[nbdocsbrouillons])=mysql_fetch_row($result);
# ghi ####### cette definition est inutile car tu ne te sers q'une fois de nbdocsbrouillons, utilise donc plutot $context[nbdocsbrouillons] directement. Ca permet d'eviter de charger l'espace global. Ca permet surtout de ne pas avoir des variables "inutiles" source de bugs et d'insecurite
$nbdocsbrouillons=$context[nbdocsbrouillons];
// pourcentage
$context[percdocsbrouillons]=round(($nbdocsbrouillons*100)/$nbdocs);
# ghi ##### } else { $context[nbdocsbrouillons]=$context[percdocsbrouillons]=0; }
# ghi #####  remarque que le else peut ne pas etre necessaire. ici il est obligatoire pour avoir des valeurs a 0 (numerique) et non vide ("").
# ghi ##### tu peux soit mettre autant de if que de requete sur documents, soit en mettre une unique et mettre toutes tes requetes a l'interieur.

// Nombre de documents prêt à être publiés
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]documents WHERE status>-32 AND status<0") or dberror();
list($context[nbdocsprets])=mysql_fetch_row($result);
$nbdocsprets=$context[nbdocsprets];
# ghi ##### meme remarque pour le nombre de variables
// pourcentage
$context[percdocsprets]=round(($nbdocsprets*100)/$nbdocs);

// Nombre de documents publiés
# ghi ##### requete inutile car tu connais le nombre total de doc, et tu as tester les doc negatif... un peu d'arithmetique suffit donc. Epargne les acces DB au maximum.
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]documents WHERE status>0") or dberror();
list($context[nbdocspublies])=mysql_fetch_row($result);
$nbdocspublies=$context[nbdocspublies];
# ghi ##### meme remarque pour le nombre de variables
// pourcentage
$context[percdocspublies]=round(($nbdocspublies*100)/$nbdocs);


# ghi #### tes trois dernieres boucles (certes la derniere est inutile donc ce que je dis ne se justifie pas a 100%, mais a partir de trois il faut le faire) pourrait etre faite via une fonciton a trois arguments: le premier serait le name de la variable ("nbdocspublies" par exemple), le deuxieme le critere et le troisieme $nbdocs. C'est important de faire ca, car premierement + de fonction = moins de bug, et deuxieme tu economises le parser php. N'oublie pas que php n'est pas pre-compiler, mais interpreter a chaque fois.
# ghi #### pour les publications ca se justifie pleinement vu que tu as encore plus de test.

//////////////////////////////////  Publications
// Nombre total de publications
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]publications") or dberror();
list($context[nbpublis])=mysql_fetch_row($result);
$nbpublis=$context[nbpublis];
# ghi #### meme remarque que pour les documents, il ne faut pas faire ce genre de chose... et surtout si tu le fais quand meme, ne l'appel pas $nbpublis parce qu'un jour quelqu'un pensera que c'est vraiment le nombre de publi... et la il pleurera parce qu'il n'arrivera pas a debuguer.
if(!$nbpublis) $nbpublis=1;  // Evite les divisions par zéro

// Nombre de publications en brouillon
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]publications WHERE status<-1") or dberror();
list($context[nbpublisbrouillons])=mysql_fetch_row($result);
$nbpublisbrouillons=$context[nbpublisbrouillons];
// pourcentage
$context[percpublisbrouillons]=round(($nbpublisbrouillons*100)/$nbpublis);

// Nombre de publications prêt à être publiées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]publications WHERE status>-32 AND status<0") or dberror();
list($context[nbpublispretes])=mysql_fetch_row($result);
$nbpublispretes=$context[nbpublispretes];
// pourcentage
$context[percpublispretes]=round(($nbpublispretes*100)/$nbpublis);

// Nombre de publications publiées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]publications WHERE status>0 AND status<32") or dberror();
list($context[nbpublispubliees])=mysql_fetch_row($result);
$nbpublispubliees=$context[nbpublispubliees];
// pourcentage
$context[percpublispubliees]=round(($nbpublispubliees*100)/$nbpublis);

// Nombre de publications publiées protégées
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]publications WHERE status>1") or dberror();
list($context[nbpublisprotegees])=mysql_fetch_row($result);
$nbpublisprotegees=$context[nbpublisprotegees];
// pourcentage
$context[percpublisprotegees]=round(($nbpublisprotegees*100)/$nbpublis);

//////////////////////////////////  Types documents
// Nombre de type de documents différents
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]typedocs") or dberror();
list($context[nbtypedocs])=mysql_fetch_row($result);


# ghi ######### ATTENTION,  tu ecris dans le context a l'interieur de la fonction... c'est pas bon du tout. Voir ci-dessous
function loop_nom_occ_type_doc(&$context,$funcname)
{
	$result=mysql_query("SELECT type AS nomtypedoc, COUNT(type) AS nbtypedoc FROM $GLOBALS[tp]documents GROUP BY type") or dberror();
	while($row=mysql_fetch_array($result,MYSQL_ASSOC))
	{
# ghi #### tu es en train d'ecrire dans le $context, alors que tu l'as passe en reference en argument, tu es donc en train de l'ecraser !!!!!!!!! c'est pas bon du tout. Il faut donc:
# ghi #### $lcontext=array_merge($context,$row);   (lcontext ou localcontext)
# ghi #### ce que tu as fait pose aussi un autre probleme (qui te posera pb dans d'autre cas si tu fais pas gaffe). Si row contient une entree ("toto" par exemple) au premier passage de la boucle, mais pas au second... ben dans context tu auras quand meme toto au deuxieme passage, c'est evidement pas ce que tu veux.
		$context=array_merge($context,$row);
		call_user_func("code_do_$funcname",$context); # ici il faut lcontext
	}
}

//////////////////////////////////  Types publications
// Nombre de type de publications différentes
$result=mysql_query("SELECT COUNT(*) FROM $GLOBALS[tp]typepublis") or dberror();
list($context[nbtypepublis])=mysql_fetch_row($result);

function loop_nom_occ_type_publi(&$context,$funcname)
{
# ghi #### a quoi sert le type AS nomtypepubli, type suffit pas ?
# ghi #### je presume que tu as fait ca pour avoir un name plus explicite que type.
# ghi #### si c'est bien ca, je comprends ton souhait d'un cote. D'un autre cote, il pose un probleme assez important, c'est le manque de standardisation. Ton template ne sera surement pas modifie par quelqu'un d'autre d'ici un bon bout de temps, mais si c'est le cas, il va devoir aller dans ton code pour savoir le name de la variable. Le plus simple est evident de rester standart lodel, c'est a dire de garder type qui certe est peu signifiant, mais est general et marche partout dans lodel. Cette volonte de standardiser, et non de specialiser a permis a lodelscript d'etre assez facilement utilisable par les gens. Ils savent quelle variable ils peuvent utiliser parce que ce sont toujours les memes. Donc garde type, meme si ca te dit rien.
# ghi #### derniere remarque, nom_occ_type_publi ... nombre_occurence_type_publi est pas bcp plus long, et bcp plus claire.
	$result=mysql_query("SELECT type AS nomtypepubli, COUNT(type) AS nbtypepubli FROM $GLOBALS[tp]publications GROUP BY type") or dberror();
	while($row=mysql_fetch_array($result,MYSQL_ASSOC))
	{
# ghi ##### meme remarque ici.
		$context=array_merge($context,$row);
    	call_user_func("code_do_$funcname",$context);
	}
}

include ($home."calcul-page.php");
calcul_page($context,"stats");
*/

?>
