<?

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR);
include ("$home/func.php");
include ("$home/langues.php");

if ($cancel) include ("abandon.php");

# recupere les infos dans le fichier xml
$row=get_tache($id);

if ($row[etape]==4) {  // on revient ici, donc il faut continuer a executer les sous-taches
  while ($taskid=array_shift($row[tasks])) { // recupere l'id de la tache a executer
    // enregistre le tableau des taches a executer apres le pop
    update_tache_context($id,array("tasks"=>$row[tasks]),$row[context]);

    $task=get_tache($taskid);     // recupere la tache a executer
    if ($task[nom]=="mkpublication") { // est-ce la creation d'une publication ?
      header("location: publication.php?id=$task[id]&importsommaire=oui");
      return;
    } elseif ($task[nom]=="mkdocument") { // est-ce la creation d'un document ?
      header("location: extrainfo.php?id=$taskid&importsommaire=oui");
      return;
    } else {
      die ("error in importsommaire.php");
    }
  }
  // il n'y a plus de sous tache a executer
    // on a fini alors
#    echo "on a fini";
    include ("abandon.php");
    return;
 
}


//////////// prepare la creation des publications et documents

// creer le repertoire pour y mettre les differents fichiers "xml".
$dir=$row[fichier].".dir";
if (!file_exists($dir)) {
  mkdir($dir,0700) or die ("impossible de creer le repertoire $row[fichier]");
}

// lit le fichier
$text=join("",file ($row[fichier].".html"));

// efface les tags articles
$text=preg_replace("/<\/?r2r:article\b[^>]*>/i","",$text);

//
// decoupe le document
//

$tasks=array();

//
// cherche le nom et le titre du sommaire
//

foreach(array("titrenumero","nomnumero") as $bal) {
  if (preg_match("/<r2r:$bal>(.*)<\/r2r:$bal>/is",$text,$result)) {
    $numero[$bal]=$result[1];
    $text=str_replace($result[0],"",$text);
  }
}

$parent=mkxmlpublication($numero[nomnumero],$numero[titrenumero],"numero",$row[publication]);

//
// on decoupe en fonction des regroupements
//
$regroupements=preg_split("/<r2r:regroupement>/i",$text);

#print_r($regroupements);
foreach ($regroupements as $regroupement) {
  // si c'est effectivement un regroupement, alors on cree le regroupement
  if (preg_match("/(.*)<\/r2r:regroupement>/i",$regroupement,$result)) {
    mkxmlpublication($result[1],$result[1],"regroupement",$parent);
    $numero[$bal]=$result[1];
    $regroupement=str_replace($result[0],"",$regroupement);
  }
  //
  // on decoupe selon les balises auteurs
  //
  $tagdelimit="<r2r:auteurs>";
  $documents=preg_split("/$tagdelimit/i",$regroupement);
  array_shift($documents); // enleve le debut qui ne contient rien
  foreach ($documents as $document) {
    mkxmldocument($tagdelimit.$document);
  }
}

// copie le rtf en lieu sur

$rtfname="$row[fichier].rtf";
if (file_exists($rtfname)) { 
  $dest="../rtf/r2r-pub-$parent.rtf";
  copy ($rtfname,$dest);
  chmod($dest,0644) or die ("impossible de chmod'er $dest");
}


//
// recupere la prochaine tache a executer
$firsttask=get_tache(array_shift($tasks));

// enregistre le context dans la tache principale
$taskcontext=unserialize($row[context]);
$taskcontext[tasks]=$tasks; // enregistre les taches dependantes de la tache principale

make_tache($row[nom],4,$taskcontext,$id);

header("location: publication.php?id=$firsttask[id]");
return;

///////////////////////////
// functions


function mkxmlpublication($nom,$titre,$type,$parent)

{
  global $tasks,$currentpublication;

  myquote($nom); myquote($titre); myquote($type);
  $nom=strip_tags($nom,"<I><B><U>");$titre=strip_tags($titre,"<I><B><U>");

  // creer effectivement la publication, mais de facon basique
  mysql_query ("INSERT INTO $GLOBALS[tableprefix]publications (parent,nom,titre,status,type) VALUES ('$parent','$nom','$titre','-1','$type')") or die (mysql_error());

  $currentpublication=mysql_insert_id();

  // demande de faire cette tache
  $taskid=make_tache("mkpublication",1,array("id"=>$currentpublication));
  array_push($tasks,$taskid);

  return $currentpublication;
}


function mkxmldocument($text)

{
  global $home,$dir,$tasks,$currentpublication;

  // ajoute les debuts et fins corrects
  $text='<r2r:article xmlns:r2r="http://www.lodel.org/xmlns/r2r" xmlns="http://www.w3.org/1999/xhtml">'.$text.'</r2r:article>';

  // cherche un nom de fichier
  do {
    $filename=$dir."/document-".rand();
  } while (file_exists($filename));

  if (!writefile("$filename.html",$text)) die ("probleme d'ecriture dans $dir");

  // extrainfo s'appelle en deux temps
  $row[publication]=$currentpublication;

  include_once("$home/extrainfofunc.php");
  $context=array();
  ei_pretraitement($filename,$row,$context,$text);
  $motcles=array();  $periodes=array();  $geographies=array();
  ei_edition($filename,$row,$context,$text,$motcles,$periodes,$geographies);
}

?>
