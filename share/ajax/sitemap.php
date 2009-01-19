<?php

if(!preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.8', 'share-0.8', 'lodeladmin-0.8', 'lodel', 'share', 'lodeladmin')) || 
	!is_dir('../../'.$_POST['site'])) {
	// tentative ?
	echo 'error';
	exit();
}
// chdir pour faciliter les include
chdir('../../'.$_POST['site'].'/lodel/edition');
if(!file_exists('siteconfig.php')) {
	echo 'error';
	exit();
}
require 'siteconfig.php';
require 'auth.php';
// pas de log de l'url dans la base
$GLOBALS['norecordurl'] = true;
// accès seulement aux personnes autorisées
if(!authenticate(LEVEL_VISITOR, null, true) || !$lodeluser['visitor'])
{
	echo 'auth';
	return;
}
// if no childs, we just escape
$context['id'] = intval($_POST['id']);
if(!$db->getOne("SELECT count(id) FROM {$GLOBALS['tableprefix']}entities WHERE idparent = '{$context['id']}'")) exit();
// set the parameters
$GLOBALS['nodesk'] = true;
$context['folder'] = true;
$context['currenturl'] = "http://".$_SERVER['SERVER_NAME'].($_SERVER['SERVER_PORT'] != 80 ? ":". $_SERVER['SERVER_PORT'] : '').$GLOBALS['urlroot'].$GLOBALS['site'].'/lodel/edition/';
// get the block
require 'view.php';
$view =& View::getView();
echo _indent($view->renderTemplateFile($context, 'edition', '', '', false, 0, 1, true));
?>