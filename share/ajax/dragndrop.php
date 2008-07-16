<?php
$tabIds = explode(',', $_POST['tabids']);

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.8', 'share-0.8', 'lodeladmin-0.8', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
} else {
	$GLOBALS['site'] = $_POST['site'];
}
$GLOBALS['ajax'] = true;
require '../../'.$GLOBALS['site'].'/siteconfig.php';
require '../'.$home.'connect.php';
require '../'.$home.'auth.php';
// accès seulement aux personnes autorisées
// pas de log de l'url dans la base
$GLOBALS['norecordurl'] = true;
authenticate(LEVEL_VISITOR);
if(!$lodeluser['visitor'])
	return;

$table = lq("#_TP_entities");
foreach($tabIds as $k=>$v) {
	$key = (0 === $k) ? 1 : intval($k)+1;
	$v = intval($v);
	if($key>0 && $v>0) {
		$db->execute("UPDATE {$table} SET rank = '{$key}' WHERE id='{$v}'") or dberror();
	}
}
require '../'.$home.'cachefunc.php';
removefilesincache('../../'.$GLOBALS['site'], '../../'.$GLOBALS['site'].'/lodel/edition/');
echo 'ok';
?>