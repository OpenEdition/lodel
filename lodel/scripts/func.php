<?



function writefile ($filename,&$text)

{
  return ($f=fopen($filename,"w")) && fputs($f,$text) && fclose($f);
}

function get_tache (&$id)

{
  $id=intval($id);
  $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]taches WHERE id='$id'") or die (mysql_error());
  if (!($row=mysql_fetch_assoc($result))) { header("Location: index.php"); return; }
  $row=array_merge($row,unserialize($row[context]));
  // verifie que le fichier existe encore
  if ($row[fichier] && !file_exists($row[fichier].".html")) {
    // detruit la tache
    header("location: abandon.php?id=$id");
  }

  return $row;
}

function posttraitement(&$context)

{
  if ($context) {
    foreach($context as $key=>$val) {
      if (is_array($val)) {
	posttraitement($context[$key]);
      } else {
	if ($key!="meta") $context[$key]=htmlspecialchars(stripslashes($val));
      }
    }
  }
}



function make_tache($nom,$etape,$context,$id=0)

{
  global $iduser;
  $contextstr=serialize($context);
  mysql_query("REPLACE INTO $GLOBALS[tableprefix]taches (id,nom,etape,user,context) VALUES ('$id','$nom','$etape','$iduser','$contextstr')") or die (mysql_error());
  return mysql_insert_id();
}

function update_taches($id,$etape)

{
  mysql_query("UPDATE $GLOBALS[tableprefix]taches SET etape='$etape' WHERE id='$id'") or die (mysql_error());
 # ne pas faire ca, car si la tache n'est pas modifiee, il renvoie 0
# if (mysql_affected_rows()!=1) die ("Erreur d'update de id=$id");
}

function rmscript($source) {
	return eregi_replace("<(\%|\?|( *)script)", "&lt;\\1", $source);
}


function extract_post() {
  global $home;

  foreach ($GLOBALS[HTTP_POST_VARS] as $key=>$val) {
#    if ($key!="superadmin" && $key!="admin" && $key!="editeur" && $key!="redacteur" && $key!="visiteur") { // protege
      $GLOBALS[context][$key]=rmscript(trim($val));
#    }
  }
}

function get_ordre_max ($table,$where="") 

{
  global $home;
  if ($where) $where="WHERE ".$where;

  include_once ("$home/connect.php");
  $result=mysql_query ("SELECT MAX(ordre) FROM $GLOBALS[tableprefix]$table $where") or die (mysql_error());
  if (mysql_num_rows($result)) list($ordre)=mysql_fetch_array($result);
  if (!$ordre) $ordre=0;

  return $ordre+1;
}

function chordre($table,$id,$critere,$dir,$inverse="")

{
  $table=$GLOBALS[tableprefix].$table;
  $dir=$dir=="up" ? -1 : 1;  if ($inverse) $dir=-$dir;
  $desc=$dir>0 ? "" : "DESC";
  $result=mysql_query("SELECT id,ordre FROM $table WHERE $critere ORDER BY ordre $desc") or die (mysql_error());

  $ordre=$dir>0 ? 1 : mysql_num_rows($result);
  while ($row=mysql_fetch_array($result,MYSQL_ASSOC)) {
    if ($row[id]==$id) {
      # intervertit avec le suivant s'il existe
      if (!($row2=mysql_fetch_array($result,MYSQL_ASSOC))) break;
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
    foreach ($var as $k => $v) { $var[$k]=addslashes(stripslashes($v)); }
  } else {
    $var=addslashes(stripslashes($var));
  }
}

function myfilemtime($filename)

{
  return file_exists($filename) ? filemtime($filename) : 0;
}


function copy_images (&$text,$callback)

{
    // copy les images en lieu sur et change l'acces
    preg_match_all("/<IMG\s+SRC=\"([^\"]+\.([^\"\.]+))\"/i",$text,$results,PREG_SET_ORDER);
    $count=1;
    $imglist=array();
    foreach ($results as $result) {
      $imgfile=$result[1];
      if ($imglist[$imgfile]) {
	$text=str_replace($result[0],"<IMG SRC=\"$imglist[$imgfile]\"",$text);
      } else {
	$ext=$result[2];
	$imglist[$imgfile]=$newimgfile=$callback($imgfile,$ext,$count);
#	echo "images: $imgfile $newimgfile <br>";
      	$text=str_replace($result[0],"<IMG SRC=\"$newimgfile\"",$text);
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

  $result=mysql_db_query($database,"SELECT id,currenturl FROM $GLOBALS[tableprefix]session WHERE id='$idsession'") or die (mysql_error());
  list ($id,$currenturl)=mysql_fetch_row($result);

  mysql_db_query($database,"UPDATE $GLOBALS[tableprefix]session SET currenturl='' WHERE id='$idsession'") or die (mysql_error());

#  echo "retourne: $currenturl";
  header("Location: http://$GLOBALS[SERVER_NAME]$currenturl");exit;
}




function export_prevnextpublication (&$context)

{
//
// cherche le numero precedent et le suivant
//

// suivant:
  $result=mysql_query ("SELECT id FROM $GLOBALS[tableprefix]publications WHERE parent='$context[parent]' AND ordre>$context[ordre] ORDER BY ordre LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($nextid)=mysql_fetch_row($result);
    $context[nextpublication]="sommaire.html?id=$nextid";
  }
  // precedent:
  $result=mysql_query ("SELECT id FROM $GLOBALS[tableprefix]publications WHERE parent='$context[parent]' AND ordre<$context[ordre] ORDER BY ordre DESC LIMIT 0,1") or die (mysql_error());
  if (mysql_num_rows($result)) {
    list($previd)=mysql_fetch_row($result);
    $context[prevpublication]="sommaire.html?id=$previd";
  }
}

function translate_xmldata($data) 

{
	return strtr($data,array("&"=>"&amp;","<" => "&lt;", ">" => "&gt;"));
}


function unlock()

{ mysql_query("UNLOCK TABLES") or die (mysql_error()); }


function lock_write()

{ 
  $list=func_get_args();
  mysql_query("LOCK TABLES $GLOBALS[tableprefix]".join (" WRITE ,".$GLOBALS[tableprefix],$list)." WRITE") or die (mysql_error());
}

?>
