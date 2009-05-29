<?php

if(!isset($_POST['site']) || !preg_match("/^[a-z0-9\-]+$/", $_POST['site']) || 
	in_array($_POST['site'], array('lodel-0.9', 'share-0.9', 'lodeladmin-0.9', 'lodel', 'share', 'lodeladmin')) || 
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

try
{
    require 'auth.php';
    // pas de log de l'url dans la base
    C::set('norecordurl', true);
    // accès seulement aux personnes autorisées
    if(!authenticate(LEVEL_VISITOR, null, true) || !C::get('visitor', 'lodeluser'))
    {
        echo 'auth';
        return;
    }
    
    $table = lq("#_TP_entities");
    $i=1;
    $tabIds = explode(',',C::get('tabids'));
    foreach($tabIds as $v) {
        $id = (int)str_replace('container_','',$v);
        if($id>0) {
            $db->execute("UPDATE {$table} SET rank = '{$i}' WHERE id='{$id}'") or trigger_error('', E_USER_ERROR);
        }
        $i++;
    }
    require 'cachefunc.php';
    removefilesincache('.', './lodel/edition/');
    echo 'ok';
}
catch(Exception $e)
{
	echo 'error';
	exit();
}
?>