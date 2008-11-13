<?php

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.8', 'share-0.8', 'lodeladmin-0.8', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
}
// chdir pour faciliter les include
chdir('../../'.$_POST['site']);
if(!file_exists('siteconfig.php')) {
	echo 'error';
	return;
}
require 'siteconfig.php';
require 'auth.php';
// pas de log de l'url dans la base
$GLOBALS['norecordurl'] = true;
// accès seulement aux personnes autorisées
authenticate(LEVEL_VISITOR);
if(!$lodeluser['visitor'])
	return;
require_once 'loginfunc.php';
echo updateDeskDisplayInSession();
return;
?>