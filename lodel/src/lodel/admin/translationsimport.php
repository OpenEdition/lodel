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

die("-- a finir --");

require("siteconfig.php");
require ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
require_once($home."func.php");
require_once($home."validfunc.php");

require_once($home."importfunc.php");

$file=extract_import("translation",$context);

require_once($home."translationfunc.php");
$xmldb=new XMLDB_Translations("interface");

$xmldb->readFromString('
<lodeltranslations>
<translations>
<row><lang>fr</lang>
<title>Francais</title>
<textgroups></textgroups>
<translators></translators>
<modificationdate>0000-00-00</modificationdate>
<creationdate>2004-10-23</creationdate>
<textes>
<texte nom="admin_user_and_rights" textgroup="admin" statut="1"></texte>
<texte nom="add" textgroup="edition" statut="2">Ajouter</texte>
<texte nom="visualize_entity" textgroup="edition" statut="2"></texte>
<texte nom="edit" textgroup="edition" statut="1">Edite</texte>
<texte nom="advanced_functions" textgroup="edition" statut="1">Fonctions avancees</texte>
<texte nom="delete" textgroup="edition" statut="-1">Supprime</texte>
<texte nom="base" textgroup="edition" statut="2">ddd4442d</texte>
<texte nom="" textgroup="" statut="1">Utilisateur et droits</texte>
</textes>
</row></translations>
</lodeltranslations>');

?>
