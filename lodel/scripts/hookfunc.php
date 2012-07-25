<?php

if (is_readable(C::get('home', 'cfg').'hookfunc_local.php'))
	include 'hookfunc_local.php';

/**
 * Met à jour la date de publication
 *
 * @author Nahuel Angelinetti
 * @param array $context le contexte contenant l'article
 * @param string $field le champ actuellement parsé
 */
function updatedatepubli(&$context, $field){
	if($context['do'] == "publish" && $context['status'] == 1){
		global $db;
		$id = $context['id'];
		$class = $context['class'];
		$date = date("Y-m-d");
		$db->execute(lq("UPDATE #_TP_$class SET datemisenligne='$date' WHERE identity=$id")) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);
	}
}

?>
