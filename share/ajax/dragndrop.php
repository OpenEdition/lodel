<?php
$tabIds = explode(',', $_POST['tabids']);

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || in_array($_POST['site'], array('lodel-0.8', 'share-0.8', 'lodeladmin-0.8', 'lodel', 'share', 'lodeladmin'))) {
	// tentative ?
	echo 'error';
	exit();
} else {
	$GLOBALS['site'] = $_POST['site'];
	if(!is_dir('../../'.$GLOBALS['site'])) {
		echo 'error';
		exit();
	}
}
$GLOBALS['ajax'] = true;
require '../../'.$GLOBALS['site'].'/siteconfig.php';
require '../'.$home.'connect.php';
require '../'.$home.'auth.php';
authenticate(LEVEL_VISITOR);
$table = lq("#_TP_entities");
foreach($tabIds as $k=>$v) {
	$key = intval($k);
	$v = intval($v);
	if($key>0 && $v>0) {
		$key++;
		$db->execute(lq("UPDATE {$table} SET rank = '{$key}' WHERE id='{$v}'")) or dberror();
	}
}
require '../'.$home.'cachefunc.php';
removefilesincache('../../'.$GLOBALS['site'], '../../'.$GLOBALS['site'].'/lodel/edition/');
echo 'ok';
?>