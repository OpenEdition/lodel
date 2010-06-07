<?php
/**
 * Fichier de définition des types de champs de Lodel
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
 * Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @copyright 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 * @since Fichier ajouté depuis la version 0.8
 */

//Gestion des champs génériques

// Champs génériques pour les types d'index
$GLOBALS['g_entrytypes_fields'] = array ('DC.Subject', 'DC.Coverage', 'DC.Rights', 'oai.set');
// Champs génériques pour les types d'index de personnes
$GLOBALS['g_persontypes_fields'] = array('DC.Creator', 'DC.Contributor');
//Champs génériques pour les entités
$GLOBALS['g_entities_fields'] = array(
					// Champs Dublin Core
					'DC.Title',
					'DC.Description',
					'DC.Publisher',
					'DC.Date',
					'DC.Format',
					'DC.Identifier',
					'DC.Source',
					'DC.Type',
					'DC.Language',
					'DC.Relation',
					'DC.Coverage',
					'DC.Rights',
					'DC.Creator',
					'DCTERMS.accessRights',
					'DCTERMS.issued',
					'DCTERMS.modified',
					'DCTERMS.available',
					'DCTERMS.bibliographicCitation',
					'DCTERMS.bibliographicCitation.volume',
					'DCTERMS.bibliographicCitation.issue',
					'DCTERMS.extent',
					'DCTERMS.isPartOf',
					'DCTERMS.alternative',
					'DCTERMS.abstract',
					'DCTERMS.ISSN',
					'DCTERMS.EISSN',
					'generic_icon');
//Champs génériques pour les entrées d'index
$GLOBALS['g_entries_fields'] = array('Index key');
//Champs génériques pour les entrées d'index de personnes
$GLOBALS['g_persons_fields'] = array('Firstname', 'Familyname', 'Title');
//Champs génériques pour les champs liés aux personnes et aux entités
$GLOBALS['g_entities_persons_fields'] = array('Title');




// If you add a type in this list, please update the XML Schema template
	$GLOBALS['lodelfieldtypes'] = 
		array ('tinytext' => array ('sql' => 'tinytext'),
					'text' => array ('sql' => 'text'),
					'mltext' => array ('sql' => 'text'),
					'image' => array ('sql' => 'tinytext', 'autostriptags' => true),
					'file' => array ('sql' => 'tinytext', 'autostriptags' => true),
				 	'url' => array ('sql' => 'text', 'autostriptags' => true),
					'email' => array ('sql' => 'text', 'autostriptags' => true),
					'color' => array ('sql' => 'char(10)', 'autostriptags' => true),
					'date' => array ('sql' => 'date', 'autostriptags' => true),
					'datetime' => array ('sql' => 'datetime', 'autostriptags' => true),
					'time' => array ('sql' => 'time', 'autostriptags' => true),
					'int' => array ('sql' => 'int', 'autostriptags' => true),
					'boolean' => array ('sql' => 'tinyint', 'autostriptags' => true),
					'number' => array ('sql' => 'double precision', 'autostriptags' => true),
					'lang' => array ('sql' => 'char(5)', 'autostriptags' => true),
					'longtext' => array ('sql' => 'longtext'),
					'passwd' => array ('sql' => 'tinytext',),
					'entities' => array ('autostriptags' => true),
					'list' => array ('sql' => 'text', 'autostriptags' => true),
					'username' => array ('sql' => 'tinytext', 'autostriptags' => true),
					'type' => array (), 'class' => array (),
					'tablefield' => array (), 
					'style' => array (), 
					'mlstyle' => array (), 
					'tplfile' => array (), 
					'history' => array ('sql' => 'text') # history is a type of field which is automaticly set when used
	);

// le style doit etre parfaitement valide
/**
 * Décode le style multilingue - détection de la langue
 * @param string $style le ou les styles à décoder 
 * @return un tableau contenant la liste des styles décodés
 */
function decode_mlstyle($style)
{
	$styles = preg_split("/[\n,]/", $style);
	if (!$styles)	{
		return array ();
	}

	foreach ($styles as $style)	{
		$ind = strpos($style, ":");
		if ($ind === FALSE)	{
			$stylesassoc["--"] = trim($style);
		}	else {
			$lang = trim(substr($style, $ind +1));
			$stylesassoc[$lang] = trim(substr($style, 0, $ind));
		}
	}
	return $stylesassoc;
}

/**
 * Indique si un mot est un mot réservé par Lodel
 *
 * @param string $name le mot
 * @return boolean un booleen indiquant si le mot est reservé ou non
 */
function reservedword($name)
{
	static $reserved_words;
	if (!$reserved_words) {
		$reserved_words = array (
		'ADD', 'ALL', 'ALTER', 'ANALYZE', 'AND', 'AS', 'ASC', 'ASENSITIVE', 
		'AUTO_INCREMENT', 'BDB', 'BEFORE', 'BERKELEYDB', 'BETWEEN', 'BIGINT', 
		'BINARY', 'BLOB', 'BOTH', 'BTREE', 'BY', 'CALL', 'CASCADE', 'CASE', 
		'CHANGE', 'CHAR', 'CHARACTER', 'CHECK', 'CLASSE', 'COLLATE', 'COLUMN', 
		'COLUMNS', 'CONDITION', 'CONNECTION', 'CONSTRAINT', 'CREATE', 'CROSS', 'CURRENT_DATE', 
		'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURSOR', 'DATABASE', 'DATABASES', 
		'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND', 'DEC', 'DECIMAL', 
		'DECLARE', 'DEFAULT', 'DELAYED', 'DELETE', 'DESC', 'DESCRIBE', 'DISTINCT', 
		'DISTINCTROW', 'DIV', 'DOUBLE', 'DROP', 'ELSE', 'ELSEIF', 'ENCLOSED', 
		'ERRORS', 'ESCAPED', 'EXISTS', 'EXPLAIN', 'FALSE', 'FIELDS', 'FLOAT', 
		'FOR', 'FORCE', 'FOREIGN', 'FROM', 'FULLTEXT', 'GRANT', 'GROUP', 'GROUPE', 
		'HASH', 'HAVING', 'HIGH_PRIORITY', 'HOUR_MICROSECOND', 'HOUR_MINUTE', 
		'HOUR_SECOND', 'IF', 'IGNORE', 'IN', 'INDEX', 'INFILE', 'INNER', 'INNODB', 
		'INOUT', 'INSENSITIVE', 'INSERT', 'INT', 'INTEGER', 'INTERVAL', 'INTO', 
		'IO_THREAD', 'IS', 'ITERATE', 'JOIN', 'KEY', 'KEYS', 'KILL', 'LEADING', 
		'LEAVE', 'LEFT', 'LIKE', 'LIMIT', 'LINES', 'LOAD', 'LOCALTIME', 
		'LOCALTIMESTAMP', 'LOCK', 'LONG', 'LONGBLOB', 'LONGTEXT', 'LOOP', 
		'LOW_PRIORITY', 'MAJ', 'MASTER_SERVER_ID', 'MATCH', 'MEDIUMBLOB', 
		'MEDIUMINT', 'MEDIUMTEXT', 'MIDDLEINT', 'MINUTE_MICROSECOND', 
		'MINUTE_SECOND', 'MOD', 'MRG_MYISAM', 'NATURAL', 'NAME', 'NOT', 
		'NO_WRITE_TO_BINLOG', 'NULL', 'NUMERIC', 'ON', 'OPTIMIZE', 'OPTION', 
		'OPTIONALLY', 'OR', 'ORDER', 'OUT', 'OUTER', 'OUTFILE', 'PRECISION', 
		'PRIMARY', 'PRIVILEGES', 'PROCEDURE', 'PURGE', 'READ', 'REAL', 
		'REFERENCES', 'REGEXP', 'RENAME', 'REPEAT', 'REPLACE', 'REQUIRE', 
		'RESTRICT', 'RETURN', 'RETURNS', 'REVOKE', 'RIGHT', 'RLIKE', 
		'RTREE', 'SECOND_MICROSECOND', 'SELECT', 'SENSITIVE', 'SEPARATOR', 'SET', 
		'SHOW', 'SMALLINT', 'SOME', 'SONAME', 'SPATIAL', 'SPECIFIC', 
		'SQL_BIG_RESULT', 'SQL_CALC_FOUND_ROWS', 'SQL_SMALL_RESULT', 'SSL', 
		'STARTING', 'STRAIGHT_JOIN', 'STRIPED', 'TABLE', 'TABLES', 'TERMINATED', 
		'THEN', 'TINYBLOB', 'TINYINT', 'TINYTEXT', 'TO', 'TRAILING', 'TRUE', 
		'TYPE', 'TYPES', 'UNION', 'UNIQUE', 'UNLOCK', 'UNSIGNED', 'UNTIL', 
		'UPDATE', 'USAGE', 'USE', 'USER_RESOURCES', 'USING', 'UTC_DATE', 
		'UTC_TIME', 'UTC_TIMESTAMP', 'VALUES', 'VARBINARY', 'VARCHAR', 
		'VARCHARACTER', 'VARYING', 'WARNINGS', 'WHEN', 'WHERE', 'WHILE', 
		'WITH', 'WRITE', 'XOR', 'YEAR_MONTH', 'ZEROFILL',
		# reservés par LODEL
		'STATUS', 'RANK', 'CLASS', 'TYPE', 'ID', 'IDENTITY', 'IDRELATION', 'IDPARENT', 'IDTYPE', 'IDUSER', 'IDPERSON', 'IDENTRY', 'ERROR', 'TPL', 'TPLCREATION', 'TPLEDITION', 'EDIT', 'FORMAT', 'PAGE', 'DO', 'LO');
	}
	return (in_array(strtoupper($name), $reserved_words));
}
/**
 * check if a string contain a word reserved by Lodel
 *
 * @param string $name the string to check
 * @return boolean true if $name is contained in the reserved words array
 */
function reservedByLodel($name)
{
	static $reserved_lodel;
	if (!$reserved_lodel) {
		$reserved_lodel = array (
		'ENTITIES', 'ENTRIES', 'PERSONS', 'CHARACTERSTYLES', 'CLASSES', 
		'ENTITYTYPES_ENTITYTYPES', 'ENTRYTYPES', 'INTERNALSTYLES', 'OBJECTS', 
		'OPTIONGROUPS', 'OPTIONS', 'PERSONTYPES', 'RELATIONS', 'SEARCH_ENGINE', 
		'SESSION', 'SITES', 'TABLEFIELDGROUPS', 'TABLEFIELDS', 'TASKS', 
		'TRANSLATIONS', 'TYPES', 'TEXTS', 'URLSTACK', 'USERGROUPS', 'USERS', 
		'USERS_USERGROUPS', 'HISTORY');
	}
	return (in_array(strtoupper($name), $reserved_lodel));
}
?>