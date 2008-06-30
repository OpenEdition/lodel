<?php
$tabIds = explode(',', $_POST['tabids']);

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
} else {
	$GLOBALS['site'] = $_POST['site'];
}

require '../../lodelconfig.php';
require '../../'.$home.'connect.php';
require '../../'.$home.'auth.php';
authenticate(LEVEL_VISITOR);

while(list($k,$v) = each($tabIds)) {
	$key = intval($k) + 1;
	if($key>0) {
		$v = intval($v);
		$db->execute(lq("UPDATE #_TP_entities SET rank = '{$key}' WHERE id='{$v}'")) or dberror();
	}
}
require '../../'.$home.'cachefunc.php';
removefilesincache('../../'.$GLOBALS['site'], '../../'.$GLOBALS['site'].'/lodel/edition/', '../../'.$GLOBALS['site'].'/lodel/admin/');
echo 'ok';
?>