<?

// la publication des publications et documents
// assure la coherence de la base de donnee


// -64  à la poubelle
// -32  brouillon non publiable
// -1   non publié
//  1   publié
// +32  publié protegé


// pour les publications dans l'url on peut recevoir
// online: si vrai met le status a 1 si faux met le status a 0
// confirmation: si vrai alors depublie meme si les publications sont protegees

// pour les documents dans l'url on peut recevoir
// online


include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ("$home/func.php");

include_once ("$home/connect.php");

if ($cancel) back();

$status=$online ? 1 : -1;

// l'utilisation dans ce script d'un status de +32 ou -32 n'est pas recommander parce qu'il opere de facon recurrente.
// utiliser plutot status.php pour ajuster le status.

if ($publication) {
  if (!publi_publication(intval($publication),$status,$confirmation)) { // publications protegees ?
    $context[publication]=$publication;
    // post-traitement
    posttraitement($context);

    function boucle_publications_protegees(&$context,$funcname) {
      // cherche les ids
      $ids=join(",",$context[publication_protegee]);
      $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id IN ($ids)") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$localcontext=array_merge($context,$row);
	call_user_func("code_boucle_$funcname",$localcontext);
      }

    }
    include ("$home/calcul-page.php");
    calcul_page($context,"publications_protegees");
    return;
  }
} else {
  if (!publi_document(intval($id),$status)) { // documents proteges
    die("la protection des documents n'est pas supportees. Decision du 29/03/03");
    $context[id]=$id;
    // post-traitement
    posttraitement($context);

    function boucle_documents_protegees($context,$funcname) {
      // cherche les ids
      $ids=join(",",$context[document_protege]);
      $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]documents WHERE id IN ($ids)") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$localcontext=array_merge($context,$row);
	call_user_func("code_boucle_$funcname",$localcontext);
      }
    }

    include ("$home/status.php");
    include ("$home/calcul-page.php");
    calcul_page($context,"documents_protegees");
    return;
  }
}
unlock();

back();
return;


//
// publi les publications de facon recurrente
//

function publi_publication ($id,$status,$confirmation)

{
  global $usergroupes,$admin,$context;



#ifndef LODELLIGHT
  lock_write("publications",
	     "documents",
	     "auteurs","documents_auteurs",
	     "indexls","documents_indexls",
	     "indexhs","documents_indexhs"); 
#else
#  lock_write("publications","documents","indexls","documents_indexls");
#endif

  //
  // cherche les publis a publier ou depublier
  //

  $critere=$admin ? "" : " AND groupe IN ($usergroupes)";

  // cherche le status (et l'id) de la publication courante
  $result=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]publications WHERE id='$id' AND status>-32 $critere") or die(mysql_error());

  $ids=array();
  while (mysql_num_rows($result)) {
    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);

      // verifie que la publication est depubliable
      // elle n'est pas depubliable si on n'a pas confirme et si son status est 32 ou plus.
      if ($status<0 && !$confirmation && $row[status]>=32) {
	// ajoute au tableau des publication_protegee cet id
	if (!$context[publication_protegee]) {  
	  $context[publication_protegee]=array($row[id]); // cree le tableau
	} else { 
	  array_push($context[publication_protegee],$row[id]);  // ajoute au tableau
	}
      }
    }
    // cherche les fils des idparent
    $idlist=join(",",$idparents);
    $result=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]publications WHERE parent IN ($idlist) AND status>-32 $critere") or die(mysql_error());
  }
  if ($context[publication_protegee] || !$ids) return FALSE; // on ne peut pas depublier

  // update toutes les publications
  $idlist=join(",",$ids);

  if (!publi_document("publication IN ($idlist)",$status,FALSE)) return FALSE;

  $critere=$status>0 ? "AND status<$status" : ""; // pour ne pas reduire le status quand on publie
  mysql_query("UPDATE $GLOBALS[tableprefix]publications SET status=$status WHERE id IN ($idlist) $critere") or die(mysql_error());

  return TRUE;
}


// publi_document depend bcp de publi_publication
// si $critere est numerique -> on lock la base, et on verifie que les documents ont les droits
// sinon, on ne lock pas la base, et la verification des droits se fait plus tard

function publi_document ($critere,$status)

{
  global $usergroupes,$admin;
  // id ?

  if (is_numeric($critere)) {
    $where="id=".$critere." AND status>-32";
#ifndef LODELLIGHT
    lock_write("documents","auteurs","indexls","indexhs","documents_auteurs","documents_indexls","documents_indexhs"); 
#else
#   lock_write("documents","indexls","documents_indexls"); 
#endif
    if (!$admin) {
      // verifie que le document est dans le groupe
      $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE $where AND groupe IN ($usergroupes)") or die (mysql_error());
      if (!mysql_num_rows($result)) die ("Vous n'avez pas les droits");
    }
  } else {
    $where=$critere;
    if (!$admin) $where.=" AND groupe IN ($usergroupes) AND status>-32";
  }

  if ($status<0) { // on veut mettre hors ligne
    // verifie que les documents ne sont pas proteges
    $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE $where AND status>=32") or die (mysql_error());
    while ($row=mysql_fetch_assoc($result)) 
      if (!$context[document_protege]) {
	$context[document_protege]=array($row[id]);
      } else {
	array_push($context[document_protege],$row[id]);
      }
    if ($context[document_protege]) return FALSE;
  }

  mysql_query("UPDATE $GLOBALS[tableprefix]documents SET status=$status WHERE $where") or die(mysql_error());

  publi_table($critere,$status,"indexl");
#ifndef LODELLIGHT
  publi_table($critere,$status,"auteur");
  publi_table($critere,$status,"indexh");
#endif
  return TRUE;
}

function publi_table($critere,$status,$table)

{
  $status=$status>0 ? 1 : -1; // dans les tables le status est seulement a +1 ou -1

# on pourrait utiliser le status comme un compteur du nombre de document qui y 
# font reference, et ainsi mettre hors ligne facilement les auteurs,
# mais cette procedure risque de buguer a terme.
  $tables=$table."s";
# cherches les id$tables a changer
  # iddocument ?
  if (is_numeric($critere)) { # on a un seul document
    $iddocument=intval($critere);
    $result=mysql_query("SELECT id$table FROM $GLOBALS[tableprefix]documents_$tables WHERE iddocument=$iddocument") or die (mysql_error());
  } else { # on a une condition sur les documents
    $result=mysql_query("SELECT id$table FROM $GLOBALS[tableprefix]documents_$tables,$GLOBALS[tableprefix]documents WHERE iddocument=id AND $critere AND status>-32") or die (mysql_error());
  }
  $ids=array();
  while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
  if (!$ids) return; # il n'y a rien a modifier
  $idlist=join(",",$ids);

  if ($status>0) {
    # dans ce cas c'est simple
    mysql_query ("UPDATE $GLOBALS[tableprefix]$tables SET status=abs(status) WHERE id IN ($idlist)") or die (mysql_error());

  } else { // status<0
    # la c'est plus complique, il faut selectionner les $tables qui n'ont pas de document online... celui qu'on publie

    $result=mysql_query ("SELECT id$table FROM $GLOBALS[tableprefix]documents_$tables, $GLOBALS[tableprefix]documents WHERE iddocument=$GLOBALS[tableprefix]documents.id AND $GLOBALS[tableprefix]documents.status>0 AND id$table IN ($idlist)") or die (mysql_error());
    $ids=array();
    while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
    if ($ids) $where="AND id NOT IN (".join(",",$ids).")";
    mysql_query("UPDATE $GLOBALS[tableprefix]$tables SET status=-abs(status) WHERE id IN ($idlist) $where") or die (mysql_error());
  }
}


?>
