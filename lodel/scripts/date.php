<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier utilitaire de gestion des dates
 */

/**
 * Converti une date humaine en date mysql
 *
 * Cette fonction accepte diverses formats de date :
 * - jj/mm/aaaa
 * - jj mm aaaa
 * - jj.mm.aaaa
 * - jj-mm-aaaa
 *
 * @param string $s la date 'humaine'
 * @return string la date transformée en format mySQL
 */
function mysqldate($s, $type)
{
	//what is the delimiting character? (support space, slash, dash, point) 
	$s = trim($s);
	if($type == 'time') {
		if (strpos($s, ':') > 0) {
			$delimiter = ':';
		}	elseif (strpos($s, 'h') > 0) {
			$delimiter = 'h';
		}	elseif (strpos($s, 'H') > 0) {
			$delimiter = 'H';
		}
	} else {
		if (strpos($s, '/') > 0) {
			$delimiter = "\/";
		}	elseif (strpos($s, '-') > 0) {
			$delimiter = '-';
		}	elseif (strpos($s, '.') > 0) {
			$delimiter = '.';
		}	elseif (strpos($s, ' ') > 0) {
			$delimiter = ' ';
		}
	}
	if (!isset($delimiter)) {
		if (strlen($s) == 4 && is_numeric($s)) { // une année seulement
			return $s . '-01-01';
		} elseif(strlen($s) > 0) {
			return "bad date";
		} else { 
			return ''; 
		}
	}
	if(preg_match("`^\d\d\d\d.\d\d.\d\d$`", $s)) 
		list ($y, $m, $d) = preg_split("/s*$delimiter+/", $s);
	else
		@list ($d, $m, $y) = preg_split("/s*$delimiter+/", $s);
	$d = (int)trim($d);

	if ((($d < 1 || $d > 31) && !preg_match("`[:hH-]`", $delimiter))) {
		return 'bad date';
	}
	$m = trim($m);

	if($type != 'time') {
		if ((int)$m == 0) {
			$m = mois($m);
		}
		if ($m == 0) {
			return 'bad date';
		}
	
		if (!isset ($y)) { // la date n'a pas ete mise
			$today = getdate(time());
			$y = $today['year']; // cette annee
			if ($m < $today['mon']) {
				$y ++; // ou l'annee prochaine
			}
		}
	
		$y = (int)trim($y);
	
		//the last value is always the year, so check it for 2- to 4-digit convertion 
		if ($y < 100)	{
			$y += 2000;
		}
	
		if (!checkdate($m, $d, $y)) {
			return 'bad date';
		}
	
		if ($d < 10 && strlen($d) == 1)	{
			$d = "0$d";
		}
		if ($m < 10 && strlen($m) == 1)	{
			$m = "0$m";
		}
		return "$y-$m-$d";
	}
	else {
		if(!isset($y))
			$y = '00';
		return $d.":".$m.":".$y;
	}
}


/**
 * Retourne le chiffre du mois par rapport à son nom
 *
 * @param string le nom du mois
 * @return integer le numéro du mois
 */
function mois($m)
{
	$m = strtolower($m);

	switch (mb_substr($m, 0, 3, 'UTF-8'))	{
		case "jan" : // janeiro (pt), januar (de), janvier (fr), january (en)
		case "ene" : // enero (es)
		case "gen" : // gennaio (it)
			return 1;
		case "fv" :
		case "fev" : // fevereiro (pt)
		case "fév" : // février (fr)
		case "feb" : // february (en), februar (de), febrero (es), febbraio (it)
			return 2;
		case "mar" : // março (pt), mars (fr), march (en), marzo (es), marzo (it)
		case "mär" : // märz (de)
			return 3;
		case 'apr' : // april (en), april (de), aprile (it)
		case "avr" : // avril (fr)
		case "abr" : // abril (pt), abril (es)
			return 4;
		case 'may' : // may (en), mayo (es)
		case "mai" : // maio (pt), mai (de), mai (fr)
		case "mag" : // maggio (it)
			return 5;
		case "jun" : // junho (pt), juni (de), june (en), junio (es)
		case "giu" : // giugno
			return 6;
		case "jul" : // julho (pt), juli (de), july (en), julio (es)
		case "lug" : // luglio (it)
			return 7;
		case "ao" :
		case "aug" : // august (en), august (de)
		case "aou" :
		case "aoû" : // août (fr)
		case "ago" : // agosto (pt), agosto (es), agosto (it)
			return 8;
		case "sep" : // september (de), septembre (fr), september (en), septiembre (es)
		case "set" : // setembro (pt), settembre (it)
			return 9;
		case "oct" : // octobre (fr), october (en), octubre (es)
		case "okt" : // oktober (de)
		case "out" : // outubro (pt)
		case "ott" : // ottobre (it)
			return 10;
		case "nov" : // novembro (pt), november (de), novembre (fr), november (en), noviembre (es), novembre (it)
			return 11;
		case "dc" :
		case "dez" : // dezembro (pt), dezember (de)
		case "dec" : // december (en)
		case "déc" : // décembre (fr)
		case "dic" : // diciembre (es), dicembre (it)
			return 12;
	}
	switch (substr($m, 0, 4)) {
		case "juin" : // juin (fr)
			return 6;
		case "juil" : // juillet (fr)
			return 7;
	}
	return 0;
}

/**
 * Transforme une date avec heure dans le format 'datetime' de MySQL
 *
 * @param string $s la date
 * @param string $type le type de format dans lequel transformer la date donnée. Par défaut
 * 'datetime'
 * @return string la date transformée
 */
function mysqldatetime($s, $type = 'datetime')
{
	$s = trim(stripslashes($s));
	if (!$s) {
		return '';
	}
	if(('date' == $type && '0000-00-00' == $s) || ('datetime' == $type && '0000-00-00 00:00:00' == $s)) return $s;

	if ($s == 'aujourd\'hui' || $s == 'today' || $s == 'maintenant' || $s == 'now') {
		$timestamp = time();
	}	elseif ($s == 'hier' || $s == 'yesterday') {
		$arr = localtime(time(), 1);
		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'] - 1, 1900 + $arr['tm_year']);
	} elseif ($s == 'demain' || $s == 'tomorrow')	{
		$arr = localtime(time(), 1);
		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'] + 1, 1900 + $arr['tm_year']);
	}	elseif (preg_match("/^\s*(dans|il y a)\s+(\d+)\s*(an|mois|jour|heure|minute)s?\s*$/i", $s, $result)) {
		$val = $result[1] == 'dans' ? $result[2] : - $result[2];
		$arr = localtime(time(), 1);
		switch ($result[3]) {
		case 'an' :
			$arr['tm_year'] += $val;
			break;
		case 'mois' :
			$arr['tm_mon'] += $val;
			break;
		case 'jour' :
			$arr['tm_mday'] += $val;
			break;
		case 'heure' :
			$arr['tm_hour'] += $val;
			break;
		case 'minute' :
			$arr['tm_min'] += $val;
			break;
		}

		$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $arr['tm_mon'] + 1, $arr['tm_mday'], 1900 + $arr['tm_year']);

	}	else {
		if($type == 'datetime') {
			$datetime = explode(' ', $s);
			if(count($datetime)>2)
			{
				$date = mysqldate($datetime[0].' '.$datetime[1].' '.$datetime[2], 'date');
				$time = isset($datetime[3]) ? mysqldate($datetime[3], 'time') : '';
			}
			else
			{
				$date = mysqldate($datetime[0], 'date');
				$time = mysqldate($datetime[1], 'time');
			}
			if($date == 'bad date' || $time == 'bad date')
				return $type;
			if(!$date) $date = date("Y-m-d");
			if(!$time) $time = date("H:i:s");
			return trim($date.' '.$time);
		} else {
			$date = mysqldate($s, $type);

			if($date == "bad date")
				return $type;
			elseif($type == 'time' && $date)
				return $date;
			elseif(!$date && $type != 'time')
				$date = date("Y-m-d");
			elseif(!$date)
				$date = date("H:i:s");
		}

		if ($type == "date") {
			return $date;
		}

		list ($y, $m, $d) = explode('-', $date);

		if (preg_match("/(\d+)[:hH](?:(\d+)(?:[:](\d+))?)?\s*$/", $s, $result)) { // time
			$timestamp = mktime($result[1], $result[2], $result[3], $m, $d, $y);
			if ($timestamp <= 0) { // no algebra	
				$time = sprintf("%02d:%02d:%02d", $result[1], $result[2], $result[3]);
			}
		}	else {
			$arr = localtime(time(), 1);
			$timestamp = mktime($arr['tm_hour'], $arr['tm_min'], $arr['tm_sec'], $m, $d, $y);
			if ($timestamp <= 0) { // no algebra
				$time = sprintf("%02d:%02d:%02d", $arr['tm_hour'], $arr['tm_min'], $arr['tm_sec']);
			}
		}
	}
	if ($timestamp <= 0 && $time) {
		if ($type == 'datetime' && $date) {
			return trim($date.' '.$time);
		}
		return '';
	}

	if ($type == 'date') {
		return date('Y-m-d', $timestamp);
	}	elseif ($type == 'datetime') {
		return date('Y-m-d H:i:s', $timestamp);
	}	elseif ($type == 'time') {
		return date('H:i:s', $timestamp);
	}	elseif ($type == 'timestamp') {
		return $timestamp;
	}	else {
		trigger_error('type inconnu dans mysqldatetime', E_USER_ERROR);
	}
}