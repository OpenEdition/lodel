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

// la publication des publications et documents
// assure la coherence de la base de donnee


// -64  à la poubelle
// -32  brouillon non publiable
// -1   non publié
//  1   publié
// +32  publié protegé


// pour les publications dans l'url on peut recevoir
// online: si vrai met le status a 1 si faux met le status a 0
// confirmation: si vrai alors depublie meme si les publications sont protegees

// pour les documents dans l'url on peut recevoir
// online

die("desuet");
require("siteconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITOR,NORECORDURL);
include ($home."func.php");

include_once("connect.php");

if ($cancel) back();

$status=$online ? 1 : -1;

// l'utilisation dans ce script d'un status de +32 ou -32 n'est pas recommander parce qu'il opere de facon recurrente.
// utiliser plutot status.php pour ajuster le status.

if ($publication) {
  $id=intval($publication);
} else {
  $id=intval($id);
}

require("managedb.php");

if (!publi($id,$status,$confirmation)) { // publications protegees ?
  $context[id]=$id;
  // post-traitement
  postprocessing($context);

  include ($home."calcul-page.php");
  calcul_page($context,"publi_error");
  return;
}

touch(SITEROOT."CACHE/maj");
unlock();

back();
return;


?>
