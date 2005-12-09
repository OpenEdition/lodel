<?php
/**
 * Fichier utilitaire pour la génération des formulaires
 *
 * Ce fichier permet de générer les formulaires d'édition de la partie administration de Lodel.
 * Il se base sur le fichier init-site.xml (qui contient la description des tables et des champs
 * et sur le fichier forms.xsl qui est une feuille de style XSLT.
 *
 * Ce script est à lancer en ligne de commande
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno Cénou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/install
 */


require_once 'generatefunc.php';

$xsltproc = xslt_create();
xslt_set_encoding($xsltproc, 'ISO-8859-1');

$xmlpath=".";
$tplpath="../src/lodel/admin/tpl";

$tables=array("persontypes",
	      "entrytypes",
	      "types","classes",
	      "tablefields",
	      "indextablefields", // vtable
	      "tablefieldgroups",
	      "users",
	      "usergroups",
	      "translations",
	      "options",
	      "optiongroups",
	      "characterstyles",
	      "internalstyles",
	      "translations","texts");

foreach ($tables as $table) {

  $parameters=array(
		    "table"=>$table
		    );

  $html =
    xslt_process($xsltproc, "$xmlpath/init-site.xml", "$xmlpath/forms.xsl", NULL, NULL,$parameters);


  $html=str_replace(array("<phptag>","</phptag>",
			  "[#POSTACTION]","<ELSE></ELSE>",
			  "<br>"),
		    array("<"."?php ","?".">",
			  '<'.'?php echo basename($_SERVER[\'PHP_SELF\']); ?'.'>',"<ELSE/>",
			  "<br />"
			  ),$html
		    );

  if (empty($html)) {
    die('XSLT processing error: '. xslt_error($xsltproc));
  }


  $beginre='<!--\[\s*begin\{form\}[^\n]+?\]-->';
  $endre='<!--\[\s*end\{form\}[^\n]+?\]-->';

  $filename=$tplpath."/edit_$table.html";
  replaceInFile($filename,$beginre,$endre,$html);
}

xslt_free($xsltproc);
?>