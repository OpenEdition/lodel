<?

require("revueconfig.php");
include ($home."auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ($home."func.php");

$iddocument=intval($iddocument);
$context[id]=$id=intval($id);
$context[type]=$type;

//
// supression et restauration
//
if ($id>0 && ($delete || $restore)) { 
  include ($home."trash.php");
  treattrash("documentsannexes");
  return;
}

$critere="id='$id' AND status>0";

//
// ordre
//
if ($id>0 && $dir) {
  # cherche le parent
  $result=mysql_query ("SELECT iddocument FROM documentsannexes WHERE $critere") or die (mysql_error());
  list($iddocument)=mysql_fetch_row($result);
  chordre("documentsannexes",$id,"iddocument='$iddocument'",$dir);
  back();
}

//
// ajoute ou edit
//


if ($edit) { // modifie ou ajoute
  extract_post();

  // validation
  do {
    if ($type=="lienfichier") {
      // charge le fichier si necessaire
      if ($docfile && $docfile!="none") {
	// place le fichier en place
	// verifie que le repertoire du document existe
	$dir="docannexe/$iddocument";
	if (!file_exists("../../".$dir)) {
	  if (!@mkdir("../../".$dir,0755)) die("impossible de creer le repertoire $dir");
	}
	$lien=$dir."/".basename($docfile_name);
	copy($docfile,"../../$lien");
      } else {
	// recherche le lien
	include_once ($home."connect.php");
	$result=mysql_query("SELECT lien FROM documentsannexes WHERE $critere") or die (mysql_error());
	list($lien)=mysql_fetch_row($result);
      }
    } elseif ($type=="liendocument") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT id FROM documents WHERE id='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_documentnonexist]=1;
      } else {
	$lien="document.html?id=".$lien;
      }
    } elseif ($type=="lienpublication") {
      $lien=intval($context[lien]);
      // cherche si le documents existe
      $result=mysql_query("SELECT id FROM publications WHERE id='$lien'") or die (mysql_query());
      if (mysql_num_rows($result)<1) {
	$err=$context[erreur_publicationnonexist]=1;
      } else {
	$lien="sommaire.html?id=".$lien;
      }
    } elseif ($type=="lienexterne") {
      // verifie l'adresse
      $lien=$context[lien];
      if ($lien && !preg_match("/http:\/\//i",$lien)) $lien="http://".$lien;
      $url=parse_url($lien);
      if (!$url[host] || !preg_match("/^[\w-]+(\.[\w-]+)+$/",$url[host])) { $context[erreur_urlinvalide]=$err=1; }
    } else {
      die ("erreur type incorrecte");
    }

    if (!$lien) { $context[erreur_lieninexistant]=$err=1; }
    // fin de chargement

    if ($err) break;
    include_once ($home."connect.php");

    myquote($context);
    mysql_query ("REPLACE INTO documentsannexes (id,iddocument,titre,commentaire,lien,type) VALUES ('$id','$iddocument','$context[titre]','$context[commentaire]','$lien','$type')") or die ("invalid query replace");

    back();

  } while (0);
  // entre en edition
} elseif ($id>0) {
  $id=intval($id);
  include_once ($home."connect.php");
  $result=mysql_query("SELECT * FROM documentsannexes WHERE $critere") or die (mysql_error());
  $context=array_merge($context,mysql_fetch_assoc($result));
  if ($context[type]=="liendocument" || $context[type]=="lienpublication") {
    // recupere le numero
    preg_match("/id=(\d+)\b/",$context[lien],$result);
    $context[lien]=$result[1];
  }
} elseif ($iddocument) {
  $context[iddocument]=$iddocument;
} else {
  // il faut preciser un document auquel on veut ajouter le document annexe
  back();
}

// post-traitement
posttraitement($context);

include ($home."calcul-page.php");
calcul_page($context,"docannexe");


?>
