<?

##########  Fonction de suppression ##############

include_once ("$home/func.php");

function supprime_publication ($id)

{
#ifndef LODELLIGHT
  lock_write("publications",
	     "documents",
	     "documentsannexes",
	     "auteurs","documents_auteurs",
	     "indexls","documents_indexls",
	     "indexhs","documents_indexhs"); 
#else
#  lock_write("publications","documents","documentsannexes","indexls","documents_indexls"); 
#endif

  // cherche les publis a publier ou depublier
  $ids=array($id);
  $idparents=array($id);

  // cherche les publis a detruire...
  do {
    $idlist=join(",",$idparents);
    // cherche les fils de idparents
    $result=mysql_query("SELECT id,groupe FROM $GLOBALS[tableprefix]publications WHERE parent IN ($idlist) AND status>-64") or die(mysql_error());

    $idparents=array();
    while ($row=mysql_fetch_assoc($result)) {
      array_push ($ids,$row[id]);
      array_push ($idparents,$row[id]);
    }
  } while ($idparents);

  // update toutes les publications
  $idlist=join(",",$ids);

  mysql_query("DELETE FROM $GLOBALS[tableprefix]publications WHERE id IN ($idlist)") or die(mysql_error());
  # cherche les ids

  $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]documents WHERE publication IN $id") or die (mysql_error());

  $ids=array();
  while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
  if ($ids) {
    supprime_document($ids,FALSE);
  } else {
    unlock();
  }
}

function supprime_document ($ids,$mklock=TRUE)

{
#ifndef LODELLIGHT
  if ($mklock)  lock_write("documents","documentsannexes","auteurs","indexls","indexhs","documents_auteurs","documents_indexls","documents_indexhs"); 
#else
#  if ($mklock)  lock_write("documents","documentsannexes","indexls","documents_indexls"); 
#endif
  if (is_numeric($ids)) {
    $where="=".$ids;
  } else {
    $where=" IN (".join(",",$ids).")";
  }

  mysql_query("DELETE FROM $GLOBALS[tableprefix]documents WHERE id $where") or die(mysql_error());
  mysql_query("DELETE FROM $GLOBALS[tableprefix]documentsannexes WHERE iddocument $where") or die (mysql_error());

  supprime_table($ids,"indexl");
#ifndef LODELLIGHT
  supprime_table($ids,"auteur");
  supprime_table($ids,"indexh",FALSE);
#endif
  unlock();
}

# $deletetable doit etre FALSE pour les tables qu'il ne faut pas effacer comme les indexhs par exemple
function supprime_table($ids,$table,$deletetable=TRUE)

{
  $tables=$table."s";

  if (is_numeric($ids)) { # on a un seul document
    $critere="iddocument=".$ids;
  } else {
    $critere="iddocument IN (".join(",",$ids).")";
  }
  mysql_query("DELETE FROM $GLOBALS[tableprefix]documents_$tables WHERE $critere") or die (mysql_error());

  if (!$deletetable) return;

  # efface tous les items qui ne sont pas dans documents_items
  $result=mysql_query("SELECT id FROM $GLOBALS[tableprefix]$tables LEFT JOIN $GLOBALS[tableprefix]documents_$tables ON id=id$table WHERE id$table is NULL") or die (mysql_error());

  $ids=array();
  while ($row=mysql_fetch_row($result)) { array_push ($ids,$row[0]); }
  if ($ids) { # efface les auteurs
    mysql_query("DELETE FROM $GLOBALS[tableprefix]$tables WHERE id IN (".join(",",$ids).")") or die (mysql_error());
  }
}


###############

?>
