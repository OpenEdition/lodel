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


// fonction pour metre et enlever de la poubelle;
// si $critere est numerique, il est considerer comme etant l'id

function deletetotrash($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ($home."connect.php");
  mysql_query("UPDATE $GLOBALS[tp]$table SET status=-abs(status) WHERE $critere") or die(mysql_error());
  return mysql_affected_rows()>0;
}

function restorefromtrash($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ($home."connect.php");
  mysql_query("UPDATE $GLOBALS[tp]$table SET status=abs(status) WHERE $critere") or die(mysql_error());
  return mysql_affected_rows()>0;
}

function delete($table,$critere)

{
  global $home;
  if (is_int($critere)) $critere="id='$critere'";
  include_once ($home."connect.php");
  mysql_query("DELETE FROM $GLOBALS[tp]$table WHERE $critere") or die(mysql_error());
  return mysql_affected_rows()>0;
}

function treattrash ($table,$critere="",$lock=FALSE)

{
  global $home,$delete,$restore,$id,$url_retour;

  if (!$critere) $critere="id='$id'";

  if ($delete) {
    if ($delete<2) { 
      if (!deletetotrash($table,$critere)) { die ("entite introuvable"); @Header("Location: not-found.html"); exit(); }
      include_once($home."func.php");
      if ($lock) unlock();
      back();
    }
    //
    // destruction complete
    //
    if ($delete>=2) { 
      if (!delete($table,$critere)) { die ("entite introuvable");@Header("Location: not-found.html"); exit(); }
      include_once($home."func.php");
      if ($lock) unlock();
      back();
    }
  }
//
// restauration
//
  if ($restore) { 
      if (!restorefromtrash($table,$critere)) { die ("entite introuvable");@Header("Location: not-found.html"); exit(); }
      include_once($home."func.php"); 
      if ($lock) unlock();
      back();
  }

 return 0; 
}
?>
