<?
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


// gere les utilisateurs. L'acces est reserve au adminlodelistrateur.
// assure l'edition, la supression, la restauration des utilisateurs.

require("lodelconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);
include_once ($home."func.php");

// calcul le critere pour determiner le user a editer, restorer, detruire...
$id=intval($id);
$critere=" id='$id' AND statut>0";

mysql_select_db($database) or die (mysql_error());
if ($lock) { // lock
  // lock la revue en ecriture la revue.
  lock_write ("session","revues");
  // cherche le nom de la revue
  $result=mysql_query ("SELECT rep FROM  $GLOBALS[tp]revues WHERE $critere") or die (mysql_error());
  list($revue)=mysql_fetch_row($result);
  if (!$revue) die ("erreur lors de l'appel de la lockrevue. La revue est inconnue ou supprimee");
  // delogue tout le monde sauf moi.
  mysql_query ("DELETE FROM $GLOBALS[tp]session WHERE revue='$revue' AND iduser!='$iduser'") or die (mysql_error());
  // change le statut de la revue
  mysql_query ("UPDATE $GLOBALS[tp]revues SET statut=32 WHERE $critere") or die (mysql_error());
  unlock();

} elseif ($unlock) { // unlock
  mysql_query ("UPDATE $GLOBALS[tp]revues SET statut=1 WHERE $critere") or die (mysql_error());
} else { die ("lock ou unlock"); }

mysql_select_db($currentdb) or die (mysql_error());

back();
return;
?>
