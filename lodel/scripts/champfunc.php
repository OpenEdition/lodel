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



// apres l'ajout d'un type, il faut mettre a jour le Schema XML

$GLOBALS[typechamps]=array("tinytext"=>"Texte court",
			   "text"=>"Texte",
			   "mltext"=>"Texte multilingue",
			   "image"=>"Image",
			   "fichier"=>"Fichier",
			   "url"=>"URL",
			   "date"=>"Date",
			   "datetime"=>"Date et Heure",
			   "time"=>"Heure",
			   "int"=>"Nombre entier",
			   "boolean"=>"Bool&eacute;en",
			   "number"=>"Nombre &agrave virgule",
			   "lang"=>"Langue",
			   "longtext"=>"Texte long",
			   );

$GLOBALS[sqltype]=array("tinytext"=>"tinytext",
			"text"=>"text",
			"mltext"=>"text",
			"image"=>"tinytext",
			"fichier"=>"tinytext",
			"url"=>"tinytext",
			"date"=>"date",
			"datetime"=>"datetime",
			"time"=>"time",
			"int"=>"int",
			"boolean"=>"tinyint",
			"number"=>"double precision",
			"lang"=>"char(2)",
			"longtext"=>"longtext",
			);

# fields for whom the strip_tags is applied automatically.

$GLOBALS[type_autostriptags]=array("image",
				   "fichier",
				   "url",
				   "date",
				   "datetime",
				   "time",
				   "int",
				   "boolean",
				   "number",
				   "lang",
				   );


// le style doit etre parfaitement valide
function decode_mlstyle($style)

{
  $styles=preg_split("/[\n,]/",$style);
  if (!$styles) return array();

  foreach ($styles as $style) {
    $ind=strpos($style,":");
    if ($ind===FALSE) {
      $stylesassoc["--"]=trim($style);
    } else {
      $lang=trim(substr($style,$ind+1));
      $stylesassoc[$lang]=trim(substr($style,0,$ind));
    }
  }
  return $stylesassoc;
}


function reservedword($nom) {

  $reserved_words = 
    array("ADD",
	  "ALL",
	  "ALTER",
	  "ANALYZE",
	  "AND",
	  "AS",
	  "ASC",
	  "ASENSITIVE",
	  "AUTO_INCREMENT",
	  "BDB",
	  "BEFORE",
	  "BERKELEYDB",
	  "BETWEEN",
	  "BIGINT",
	  "BINARY",
	  "BLOB",
	  "BOTH",
	  "BTREE",
	  "BY",
	  "CALL",
	  "CASCADE",
	  "CASE",
	  "CHANGE",
	  "CHAR",
	  "CHARACTER",
	  "CHECK",
	  "CLASSE",
	  "COLLATE",
	  "COLUMN",
	  "COLUMNS",
	  "CONNECTION",
	  "CONSTRAINT",
	  "CREATE",
	  "CROSS",
	  "CURRENT_DATE",
	  "CURRENT_TIME",
	  "CURRENT_TIMESTAMP",
	  "CURSOR",
	  "DATABASE",
	  "DATABASES",
	  "DAY_HOUR",
	  "DAY_MICROSECOND",
	  "DAY_MINUTE",
	  "DAY_SECOND",
	  "DEC",
	  "DECIMAL",
	  "DECLARE",
	  "DEFAULT",
	  "DELAYED",
	  "DELETE",
	  "DESC",
	  "DESCRIBE",
	  "DISTINCT",
	  "DISTINCTROW",
	  "DIV",
	  "DOUBLE",
	  "DROP",
	  "ELSE",
	  "ELSEIF",
	  "ENCLOSED",
	  "ERRORS",
	  "ESCAPED",
	  "EXISTS",
	  "EXPLAIN",
	  "FALSE",
	  "FIELDS",
	  "FLOAT",
	  "FOR",
	  "FORCE",
	  "FOREIGN",
	  "FROM",
	  "FULLTEXT",
	  "GRANT",
	  "GROUP",
	  "GROUPE",
	  "HASH",
	  "HAVING",
	  "HIGH_PRIORITY",
	  "HOUR_MICROSECOND",
	  "HOUR_MINUTE",
	  "HOUR_SECOND",
	  "ID",
	  "IDENTITE",
	  "IDPARENT",
	  "IDTYPE",
	  "IDUSER",
	  "IF",
	  "IGNORE",
	  "IN",
	  "INDEX",
	  "INFILE",
	  "INNER",
	  "INNODB",
	  "INOUT",
	  "INSENSITIVE",
	  "INSERT",
	  "INT",
	  "INTEGER",
	  "INTERVAL",
	  "INTO",
	  "IO_THREAD",
	  "IS",
	  "ITERATE",
	  "JOIN",
	  "KEY",
	  "KEYS",
	  "KILL",
	  "LEADING",
	  "LEAVE",
	  "LEFT",
	  "LIKE",
	  "LIMIT",
	  "LINES",
	  "LOAD",
	  "LOCALTIME",
	  "LOCALTIMESTAMP",
	  "LOCK",
	  "LONG",
	  "LONGBLOB",
	  "LONGTEXT",
	  "LOOP",
	  "LOW_PRIORITY",
	  "MAJ",
	  "MASTER_SERVER_ID",
	  "MATCH",
	  "MEDIUMBLOB",
	  "MEDIUMINT",
	  "MEDIUMTEXT",
	  "MIDDLEINT",
	  "MINUTE_MICROSECOND",
	  "MINUTE_SECOND",
	  "MOD",
	  "MRG_MYISAM",
	  "NATURAL",
	  "NOM",
	  "NOT",
	  "NO_WRITE_TO_BINLOG",
	  "NULL",
	  "NUMERIC",
	  "ON",
	  "OPTIMIZE",
	  "OPTION",
	  "OPTIONALLY",
	  "OR",
	  "ORDER",
	  "ORDRE",
	  "OUT",
	  "OUTER",
	  "OUTFILE",
	  "PRECISION",
	  "PRIMARY",
	  "PRIVILEGES",
	  "PROCEDURE",
	  "PURGE",
	  "READ",
	  "REAL",
	  "REFERENCES",
	  "REGEXP",
	  "RENAME",
	  "REPEAT",
	  "REPLACE",
	  "REQUIRE",
	  "RESTRICT",
	  "RETURN",
	  "RETURNS",
	  "REVOKE",
	  "RIGHT",
	  "RLIKE",
	  "RTREE",
	  "SECOND_MICROSECOND",
	  "SELECT",
	  "SENSITIVE",
	  "SEPARATOR",
	  "SET",
	  "SHOW",
	  "SMALLINT",
	  "SOME",
	  "SONAME",
	  "SPATIAL",
	  "SPECIFIC",
	  "SQL_BIG_RESULT",
	  "SQL_CALC_FOUND_ROWS",
	  "SQL_SMALL_RESULT",
	  "SSL",
	  "STARTING",
	  "STRAIGHT_JOIN",
	  "STRIPED",
	  "TABLE",
	  "TABLES",
	  "TERMINATED",
	  "THEN",
	  "TINYBLOB",
	  "TINYINT",
	  "TINYTEXT",
	  "TO",
	  "TPL",
	  "TPLCREATION",
	  "TPLEDITION",
	  "TRAILING",
	  "TRUE",
	  "TYPE",
	  "TYPES",
	  "UNION",
	  "UNIQUE",
	  "UNLOCK",
	  "UNSIGNED",
	  "UNTIL",
	  "UPDATE",
	  "USAGE",
	  "USE",
	  "USER_RESOURCES",
	  "USING",
	  "UTC_DATE",
	  "UTC_TIME",
	  "UTC_TIMESTAMP",
	  "VALUES",
	  "VARBINARY",
	  "VARCHAR",
	  "VARCHARACTER",
	  "VARYING",
	  "WARNINGS",
	  "WHEN",
	  "WHERE",
	  "WHILE",
	  "WITH",
	  "WRITE",
	  "XOR",
	  "YEAR_MONTH",
	  "ZEROFILL",

# reserver par LODEL

	  "STATUT",
	  "DROITVISITEUR",
	  "DROITREDACTEUR",
	  "DROITEDITEUR",
	  "DROITADMIN",
	  "DROITADMINLODEL"
	  );
  return (in_array (strtoupper($nom), $reserved_words));
}



?>
