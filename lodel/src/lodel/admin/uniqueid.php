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


require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_ADMINLODEL,NORECORDURL);

//
// this script is provided for conversion purpose only.
// it is not intented to become a feature of LODEL.
// it may be remove in the next version
//


do {

// cree la table si necessaire
  require_once($home."objetfunc.php");

  $err=mysql_query_cmds_forobjetfunc('
CREATE TABLE IF NOT EXISTS _PREFIXTABLE_objets (
	id		INT UNSIGNED DEFAULT 0 NOT NULL auto_increment,
	class		VARCHAR(255),

	PRIMARY KEY (id)
);
');
  if ($err) break;

  $err=makeobjetstable();

  if ($err) break;

  echo "ok";
}  while (0);

echo $err;




?>
