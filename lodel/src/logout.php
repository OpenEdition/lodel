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

touch("CACHE/maj");

if ($PHP_AUTH_PW) {
  Header( "WWW-authenticate:  basic  realm=\"Revues.org\"");
  Header( "HTTP/1.0  401  Unauthorized");

#  Header("Location: logout.php");
  echo "<HTML><HEAD><TITLE>Logout</TITLE></HEAD>
<BODY><H3>Pour se deloguer appuyez deux fois sur Ok et n'entrez aucun mot de passe</H3><P></BODY></HTML>\n";
  exit;
} else {
  Header("Location: index.html");
}

?>
