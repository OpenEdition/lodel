<?

// la publication des publications et documents
// assure la coherence de la base de donnee


// -64  à la poubelle
// -32  brouillon non publiable
// -1   non publié
//  1   publié
// +32  publié protegé

include ("lodelconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_EDITEUR,NORECORDURL);
include ("$home/func.php");

include_once ("$home/connect.php");

if ($publication) {
  if (!publi_publication(intval($publication),$online)) { // publications protegees
    $context[publication]=$publication;
    // post-traitement
    posttraitement($context);

    function boucle_publications_protegees($generalcontext) {
      // cherche les ids
      $ids=join(",",$generalcontext[publication_protegee]);
      $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]publications WHERE id IN ($ids)") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$context=array_merge($generalcontext,$row);
	code_boucle_publications_protegees($context);
      }

    }
    include ("$home/status.php");
    include ("$home/calcul-page.php");
    calcul_page($context,"publications_protegees");
    return;
  }
} else {
  if (!publi_document(intval($id),$online)) { // documents proteges
    $context[id]=$id;
    // post-traitement
    posttraitement($context);

    function boucle_documents_protegees($generalcontext) {
      // cherche les ids
      $ids=join(",",$generalcontext[document_protege]);
      $result=mysql_query("SELECT * FROM $GLOBALS[tableprefix]documents WHERE id IN ($ids)") or die (mysql_error());
      while ($row=mysql_fetch_assoc($result)) {
	$context=array_merge($generalcontext,$row);
	code_boucle_documents_protegees($context);
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

function publi_publication ($id,$online)

{
  global $usergroupes,$admin,$context;
  $status=$online ? 1 : -1;

  // cherche les publis a publier ou depublier
  $ids=array($id);
  $idparents=array($id);

#ifndef LODELLIGHT
  lock_write("publications",
	     "documents",
	     "auteurs","documents_auteurs",
	     "indexls","documents_indexls",
	     "indexhs","documents_indexhs"); 
#else
#  lock_write("publications","documents","indexls","documents_indexls");
#endif

  $critere=$admin ? "" : " AND groupe IN ($usergroupes)";

  do {
    $idlist=join(",",$idparents);
    // cherche les fils de idparent
    $result=mysql_query("SELECT id,status FROM $GLOBALS[tableprefix]publications WHERE parent IN ($idlist) AND status>-32 $critere") or die(mysql_error());
    
    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
      if (!$online && $row[status]>=8) {
	if (!$context[publication_protegee]) { 
	  $context[publication_protegee]=array($row[id]); 
	} else { 
	  array_push($context[publication_protegee],$row[id]); 
	}
      }
    }
  } while ($idparents);

  if ($context[publication_protegee]) return FALSE; // on ne peut pas depublier

  // update toutes les publications
  $idlist=join(",",$ids);

  if (!publi_document("publication IN ($idlist)",$online,FALSE)) return FALSE;

  mysql_query("UPDATE $GLOBALS[tableprefix]publications SET status=$status WHERE id IN ($idlist)") or die(mysql_error());

  return TRUE;
}


// publi_document depend bcp de publi_publication
// si $critere est numerique -> on lock la base, et on verifie que les documents ont les droits
// sinon, on ne lock pas la base, et la verification des droits se fait plus tard

function publi_document ($critere,$online)

{
  global $usergroupes,$admin;
  // id ?
  $status=$online ? "abs(status)" : "-abs(status)";

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

  if (!$online) { // on veut mettre hors ligne
    // verifie que les documents ne sont pas proteges
    $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE $where AND status>=8");
    while ($row=mysql_fetch_assoc($result)) 
      if (!$context[document_protege]) {
	$context[document_protege]=array($row[id]);
      } else {
	array_push($context[document_protege],$row[id]);
      }
    if ($context[document_protege]) return FALSE;
  }

  mysql_query("UPDATE $GLOBALS[tableprefix]documents SET status=$status WHERE $where") or die(mysql_error());

  publi_table($critere,$online,"indexl");
#ifndef LODELLIGHT
  publi_table($critere,$online,"auteur");
  publi_table($critere,$online,"indexh");
#endif
  return TRUE;
}

function publi_table($critere,$online,$table)

{
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

  if ($online) {
    # dans ce cas c'est simple
    mysql_query ("UPDATE $GLOBALS[tableprefix]$tables SET status=abs(status) WHERE id IN ($idlist)") or die (mysql_error());
  } else {
    # la c'est plus complique, il faut selectionner les $tables qui n'ont pas de document online... celui qu'on publie

    $result=mysql_query ("SELECT id$table FROM $GLOBALS[tableprefix]documents_$tables, $GLOBALS[tableprefix]documents WHERE iddocument=$GLOBALS[tableprefix]documents.id AND $GLOBALS[tableprefix]documents.status>0 AND id$table IN ($idlist)") or die (mysql_error());
    $ids=array();
    while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
    if ($ids) $where="AND id NOT IN (".join(",",$ids).")";
    mysql_query("UPDATE $GLOBALS[tableprefix]$tables SET status=-abs(status) WHERE id IN ($idlist) $where") or die (mysql_error());
  }
}


?>


