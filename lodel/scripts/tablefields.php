<?php
/**
 * Fichier de gestion des tables et des champs
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
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
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

// build the arrays containing tables and fields
require_once 'connect.php';

// try first to get the cached array
if (!(@include (getCachePath("tablefields.php")))) {

	// no, we have to build the tablefields array
	if (!function_exists("var_export"))	{
		function var_export($arr, $t)
		{
			$ret = "array(";
			foreach ($arr as $k => $v) {
				$ret .= "'$k'=>";
				if (is_array($v)) {
					$ret .= var_export($v, TRUE).",\n";
				}	else {
					$ret .= "'$v',\n";
				}
			}
			return $ret.")";
		}
	}

	$tablefields = array ();

	////////////////////////
if (!function_exists("maketablefields"))	{
	function maketablefields(& $tablefields)
	{
		global $db;
		$start = DATABASE != $GLOBALS['currentdb'] ? 0 : 1;
		#      $dbs[$GLOBALS['currentdb']]="";
		#      ) $dbs[DATABASE]=DATABASE.".";

		for ($i = $start; $i <= 1; $i ++)	{
			// select the DB
			if ($i == 0)	{ // main database
				usemaindb();
				$prefix = DATABASE.".";
			}	else	{ // current database
				usecurrentdb();
				$prefix = "";
			}
			$result = $db->MetaTables();
			foreach ($result as $table)	{
				$fields = $db->MetaColumns($table) or dberror();
				$table = $prefix.$table;

				$tablefields[$table] = array ();
				foreach ($fields as $field)	{
					$tablefields[$table][] = $field->name;
				}
			}
		}

		$fp = fopen(getCachePath("tablefields.php"), "w");
		fputs($fp, '<?php  $tablefields='.var_export($tablefields, TRUE).' ; ?>');
		fclose($fp);
	}
}
	maketablefields($tablefields);

}
?>