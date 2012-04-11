<?php

if (is_readable(C::get('home', 'cfg').'hookfunc_local.php'))
	include 'hookfunc_local.php';
if (is_readable(C::get('home', 'cfg').'hookfunc_local_calenda.php'))
	include 'hookfunc_local_calenda.php';

/**
 * Met à jour la date de publication
 *
 * @author Nahuel Angelinetti
 * @param array $context le contexte contenant l'article
 * @param string $field le champ actuellement parsé
 */
function updatedatepubli(&$context, $field){
	if($context['do'] == "publish" && $context['publishstatus'] == 1){
		$context['data']['datemisenligne'] = date("Y-m-d");
	}
}

?>
