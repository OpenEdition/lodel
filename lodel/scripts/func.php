<?php

function writefile ($filename,&$text)
{
 //echo "nom de fichier : $filename";
   if (file_exists($filename)) 
   { 
     if (! (unlink($filename)) ) die ("Ne peut pas supprimer $filename. probleme de droit contacter Luc ou Ghislain");
   }
   return ($f=fopen($filename,"w")) && fputs($f,$text) && fclose($f) && chmod ($filename,0644);
}


function get_tache (&$id)

{
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tp]taches WHERE id='$id' AND statut>0") or die (mysql_error());
  if (!($row=mysql_fetch_assoc($result))) { back(); return; }
  $row=array_merge($row,unserialize($row[context]));
  return $row;
}

function posttraitement(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	posttraitement($context[$key]);
      } else {
	if ($key!="meta") $context[$key]=str_replace("\n"," ",htmlspecialchars(stripslashes($val)));
      }
    }
  }
}

//
// $context est soit un tableau qui sera serialise soit une chaine deja serialise
//

function make_tache($nom,$etape,$context,$id=0)

{
  global $iduser;
  if (is_array($context)) $context=serialize($context);
  mysql_query("REPLACE INTO $GLOBALS[tp]taches (id,nom,etape,user,context) VALUES ('$id','$nom','$etape','$iduser','$context')") or die (mysql_error());
  return mysql_insert_id();
}

function update_tache_etape($id,$etape)

{
  mysql_query("UPDATE $GLOBALS[tp]taches SET etape='$etape' WHERE id='$id'") or die (mysql_error());
 # ne pas faire ca, car si la tache n'est pas modifiee, il renvoie 0
# if (mysql_affected_rows()!=1) die ("Erreur d'update de id=$id");
}

//
// previouscontext est la chaine serialisee
// newcontext est un array

function update_tache_context($id,$newcontext,$previouscontext="")

{
  if ($previouscontext) { // on merge les deux contextes
    $contextstr=serialize(array_merge(unserialize($previouscontext),$newcontext));
  } else {
    $contextstr=serialize($newcontext);
  }

  mysql_query("UPDATE $GLOBALS[tp]taches SET context='$contextstr' WHERE id='$id'") or die (mysql_error());

}

function rmscript($source) {
	// Remplace toutes les balises ouvrantes susceptibles de lancer un script
	return eregi_replace("<(\%|\?|( *)script)", "&lt;\\1", $source);
}


function extract_post() {
	// Extrait toutes les variables passées par la méthode post puis les stocke dans 
	// le tableau $context
	global $home;
	
	foreach ($GLOBALS[HTTP_POST_VARS] as $key=>$val) {
		#if ($key!="adminlodel" && $key!="admin" && $key!="editeur" && $key!="redacteur" && $key!="visiteur") { // protege
	  $GLOBALS[context][$key]=$val;
		#}
	}
	function clean_for_extract_post(&$var) {
	  if (is_array($var)) {
	    array_walk($var,"clean_for_extract_post");
	  } else return rmscript(trim($val));
	}
#	print_r($GLOBALS[context]);
	array_walk($GLOBALS[context],"clean_for_extract_post");
#	echo "--------------------------------";
#	print_r($GLOBALS[context]);flush();
#	die("fini $context");
}

function get_ordre_max ($table,$where="") 

{
  global $home;
  if ($where) $where="WHERE ".$where;

  include_once ($home."connect.php");
  $result=mysql_query ("SELECT MAX(ordre) FROM $GLOBALS[tp]$table $where") or die (mysql_error());
  if (mysql_num_rows($result)) list($ordre)=mysql_fetch_row($result);
  if (!$ordre) $ordre=0;

  return $ordre+1;
}

function chordre($table,$id,$critere,$dir,$inverse="")

{
  $table=$GLOBALS[tp].$table;
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  $result=mysql_query("SELECT id,ordre FROM $table WHERE $critere ORDER BY ordre $desc") or die (mysql_error());

  $ordre=$dir>0 ? 1 : mysql_num_rows($result);
  while ($row=mysql_fetch_assoc($result)) {
    if ($row[id]==$id) {
      # intervertit avec le suivant s'il existe
      if (!($row2=mysql_fetch_assoc($result))) break;
      mysql_query("UPDATE $table SET ordre='$ordre' WHERE id='$row2[id]'") or die (mysql_error());
      $ordre+=$dir;
    }
    if ($row[ordre]!=$ordre) {
      mysql_query("UPDATE $table SET ordre='$ordre' WHERE id='$row[id]'") or die (mysql_error());
    }
    $ordre+=$dir;
  }
} 

function myquote (&$var)

{
  if (is_array($var)) {
    array_walk($var,"myquote");
    return $var;
  } else {
    return $var=addslashes(stripslashes($var));
  }
}


function mystripslashes (&$var)

{
  if (is_array($var)) {
    array_walk($var,"mystripslashes");
    return $var;
  } else {
    return $var=stripslashes($var);
  }
}

function myfilemtime($filename)

{
  return file_exists($filename) ? filemtime($filename) : 0;
}


function copy_images (&$text,$callback,$argument="")

{
    // copy les images en lieu sur et change l'acces
    preg_match_all("/<img\s+src=\"([^\"]+\.([^\"\.]+))\"/i",$text,$results,PREG_SET_ORDER);
    $count=1;
    $imglist=array();
    foreach ($results as $result) {
      $imgfile=$result[1];
      if ($imglist[$imgfile]) {
	$text=str_replace($result[0],"<img src=\"$imglist[$imgfile]\"",$text);
      } else {
	$ext=$result[2];
	$imglist[$imgfile]=$newimgfile=$callback($imgfile,$ext,$count,$argument);
#	echo "images: $imgfile $newimgfile <br>";
      	$text=str_replace($result[0],"<img src=\"$newimgfile\"",$text);
      	$count++;
	}
    }
}


//function addmeta(&$meta) # liste variable de valeurs
//
//{
//  $arg=array_shift(func_get_args());
//  $meta=serialize(array_merge($arg,unserialize($meta)));
//}
//

function addmeta(&$arr,$meta="")

{
  foreach ($arr as $k=>$v) {
    if (strpos($k,"meta_")===0) {
      if (!isset($metaarr)) { // cree le hash des meta
	  $metaarr=$meta ? unserialize($meta) : array();
      }
      if ($v) {
	$metaarr[$k]=$v;
      } else {
	unset($metaarr[$k]);
      }
    }
  }
  return $metaarr ? serialize($metaarr) : $meta;
}


function back()

{
  global $database,$idsession;
  //echo "idsession = $idsession<br>\n";
  $result=mysql_db_query($database,"SELECT id,currenturl FROM $GLOBALS[tp]session WHERE id='$idsession'") or die (mysql_error());
  list ($id,$currenturl)=mysql_fetch_row($result);

  mysql_db_query($database,"UPDATE $GLOBALS[tp]session SET currenturl='' WHERE id='$idsession'") or die (mysql_error());

  //echo "retourne: id=$id url=$currenturl";
  header("Location: http://$GLOBALS[SERVER_NAME]$currenturl");exit;
}




function export_prevnextpublication (&$context)

{
//
// cherche le numero precedent et le suivant
//

// suivant

  $querybase="SELECT id FROM $GLOBALS[tp]entites WHERE idparent='$context[idparent]' AND";
  $result=mysql_query ("$querybase ordre>$context[ordre] ORDER BY ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    $context[nextpublication]=makeurlwithid("sommaire",$nextid);
  }
  // precedent:
  $result=mysql_query ("$querybase ordre<$context[ordre] ORDER BY ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    $context[prevpublication]=makeurlwithid("sommaire",$previd);
  }
}

function translate_xmldata($data) 

{
	return strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
}


function unlock()
{
	// Dévérouille toutes les tables vérouillées précédemment par la 
	// fonction lock_write()
	mysql_query("UNLOCK TABLES") or die (mysql_error());
}


function lock_write()
{
	// Vérouille toutes les tables en écriture
	$list=func_get_args();
	mysql_query("LOCK TABLES $GLOBALS[tp]".join (" WRITE ,".$GLOBALS[tp],$list)." WRITE") or die (mysql_error());
}

#function prefix_keys($prefix,$arr)
#
#{
#  if (!$arr) return $arr;
#  foreach ($arr as $k=>$v) $outarr[$prefix.$k]=$v;
#  return $outarr;
#}

function array_merge_withprefix($arr1,$prefix,$arr2)

{
  if (!$arr2) return $arr1;
  foreach ($arr2 as $k=>$v) $arr1[$prefix.$k]=$v;
  return $arr1;
}

#function extract_options($context,$listoptions)
#
#{
#  $newoptions=array();
#  foreach ($listoptions as $opt) { if ($context["option_$opt"]) $newoptions["option_$opt"]=1; }
#  return serialize($newoptions);
#}



function makeurlwithid ($base,$id)

{
  if ($GLOBALS[idagauche]) {
    return $base.$id.".".$GLOBALS[extensionscripts];
  } else {
    return $base.".".$GLOBALS[extensionscripts]."?id=".$id;
  }
}

if (!function_exists("file_get_contents")) {
  function file_get_contents($file) 
  {
    $fp=fopen($file,"r") or die("Impossible to read the file $file");
    while(!feof($fp)) $res.=fread($fp,2048);
    fclose($fp);
    return $res;
  }
}



// valeur de retour, identifiant ce script
return 568;

?>
