<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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


// inclue les documents et les publi dans objets

function makeobjetstable()

{
  $err=mysql_query_cmds_forobjetfunc('
DELETE FROM _PREFIXTABLE_objets;
INSERT INTO _PREFIXTABLE_objets (id,classe) SELECT identite,"documents" FROM documents;
INSERT INTO _PREFIXTABLE_objets (id,classe) SELECT identite,"publications" FROM publications;
');
  if ($err) return $err;

  // ajoute un grand nombre a tous les id.

  $offset=2000000000;

  $tables=array(
		"entites"=>array("idtype"),
		"types"=>array("id"),
		"typepersonnes"=>array("id"),
		"typeentrees"=>array("id"),
		"entrees"=>array("id","idparent","idtype"),
		"personnes"=>array("id"),
		"entites_personnes"=>array("idpersonne","idtype"),
		"entites_entrees"=>array("identree"),
		"typeentites_typeentrees"=>array("idtypeentree","idtypeentite"),
		"typeentites_typepersonnes"=>array("idtypepersonne","idtypeentite"),
		"typeentites_typeentites"=>array("idtypeentite","idtypeentite2")
		);

  foreach ($tables as $table=>$idsname) {
    foreach ($idsname as $idname) {
      $err.=mysql_query_cmds_forobjetfunc('
 UPDATE _PREFIXTABLE_'.$table.' SET '.$idname.'='.$idname.'+'.$offset.' WHERE '.$idname.'>0;
');
      if ($err) return $err;
    }
  }

  $conv=array(
	      "personnes"=>array(
				 "entites_personnes"=>"idpersonne",
				 ),
	      "entrees"=>array(
			      "entites_entrees"=>"identree",
			      "entrees"=>"idparent",
			      ),
	      "types"=>array(
			     "entites"=>"idtype",
			     "typeentites_typeentites"=>array("idtypeentite","idtypeentite2"),
			     "typeentites_typeentrees"=>"idtypeentite",
			     "typeentites_typepersonnes"=>"idtypeentite",
			     ),
	      "typepersonnes"=>array(
				     "typeentites_typepersonnes"=>"idtypepersonne",
				     "entites_personnes"=>"idtype",
				     ),
	      "typeentrees"=>array(
				   "typeentites_typeentrees"=>"idtypeentree",
				   "entrees"=>"idtype",
				   )
	      );

  foreach ($conv as $maintable=>$changes) {
    $result=mysql_query("SELECT id FROM $GLOBALS[tp]$maintable") or die(mysql_error());
    #echo "$maintable...\n";
    while (list($id)=mysql_fetch_row($result)) {
      #echo "$maintable...$id<br />\n";
      $newid=uniqueid($maintable);
      $err.=mysql_query_cmds_forobjetfunc('
UPDATE _PREFIXTABLE_'.$maintable.' SET id='.$newid.' WHERE id='.$id.';
 ');
      if ($err) return $err;

      foreach ($changes as $table=>$idsname) {
	if (!is_array($idsname)) $idsname=array($idsname);
	foreach ($idsname as $idname) {
	  $err.=mysql_query_cmds_forobjetfunc('
UPDATE _PREFIXTABLE_'.$table.' SET '.$idname.'='.$newid.' WHERE '.$idname.'='.$id.';
');
	  if ($err) return $err;
	}
      }
    }
    #echo "ok<br />";
     
  }

  // check all the id have been converted

  $err="";
  foreach ($tables as $table=>$idsname) {
    foreach ($idsname as $idname) {
      $result=mysql_query("SELECT count(*) FROM $GLOBALS[tp]$table WHERE $idname>$offset") or die (mysql_error());
      list($count)=mysql_fetch_row($result);
      if ($count) $err.="<strong>warning</strong>: reste $count $idname non converti dans $table. si vous pensez que ce sont des restes de bug, vous pouvez les detruire avec la requete SQL suivante: DELETE FROM $GLOBALS[tp]$table WHERE $idname>$offset<br />\n";
    }
  }
  if ($err) return $err;

  return FALSE;
}




function mysql_query_cmds_forobjetfunc($cmds,$table="") 

{
  $sqlfile=str_replace("_PREFIXTABLE_",$GLOBALS[tp],$cmds);
  if (!$sqlfile) return;
  $sql=preg_split ("/;/",preg_replace("/#.*?$/m","",$sqlfile));
  if ($table) { // select the commands operating on the table  $table
    $sql=preg_grep("/(REPLACE|INSERT)\s+INTO\s+$GLOBALS[tp]$table\s/i",$sql);
  }
  if (!$sql) return;

  foreach ($sql as $cmd) {
    $cmd=trim(preg_replace ("/^#.*?$/m","",$cmd));
    if ($cmd) {
      if (!mysql_query($cmd)) { 
	$err.="$cmd <font COLOR=red>".mysql_error()."</font><br>";
	break; // sort, ca sert a rien de continuer
      }
    }
  }
  return $err;
}


?>
