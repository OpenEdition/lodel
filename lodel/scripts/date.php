<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
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

function mysqldate($s)

{ 
  // convertie une date humaine en time stamp

  //what is the delimiting character? (support space, slash, dash, point) 
  $s=trim($s);
    if (strpos($s,"/")>0) { $delimiter="\/"; }
  elseif (strpos($s," ")>0) { $delimiter=" "; }
  elseif (strpos($s,"-")>0)  { $delimiter="-"; }
  elseif (strpos($s,".")>0) { $delimiter="."; }

  if (!$delimiter) return ""; 

  list($d,$m,$y)=preg_split("/s*$delimiter+/",$s);

  $d=intval(trim($d));
  if ($d<1 || $d>31) return "";
  $m=trim($m);
  if (intval($m)==0) $m=mois($m);
  if ($m==0) return "";

  if (!isset($y)) { // la date n'a pas ete mise
    $today=getdate(time());
    $y=$today[year]; // cette annee
    if ($m<$today[mon]) $y++; // ou l'annee prochaine
  }
  $y=trim($y);

  //the last value is always the year, so check it for 2- to 4-digit convertion 
  if (intval($y)<100) { $y+=2000; }

  if (!checkdate($m,$d,$y)) return "";

  if ($d<10 && strlen($d)==1) { 
    $d="0$d";
  }
  if ($m<10 && strlen($m)==1) {
    $m="0$m";
  } 
  return "$y-$m-$d";
}


function mois($m) 
{ 
#  $m=strtolower($m); // cette fonction n'est pas multibyte, elle pose probleme

  $m=strtolower(utf8_decode($m));

  switch(substr($m,0,3)) { 
    case "jan": return 1;
    case "fev": return 2;
    case "fév": return 2;
#    case "fÃ©": return 2;
    case "mar": return 3;
    case "avr": return 4;
    case "mai": return 5;
    case "aou": return 8;
    case "aoû": return 8;
    case "sep": return 9;
    case "oct": return 10;
    case "nov": return 11;
    case "dec": return 12;
    case "déc": return 12;
#    case "dÃ©": return 12;
  }
  switch(substr($m,0,4)) { 
    case "juin": return 6;
    case "juil": return 7;
#    case "aoÃ»": return 8;
  }
  return 0;
} 




?>
