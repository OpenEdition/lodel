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
	if(isset($context['do']) && $context['do'] == "publish" && $context['status'] == 1){
		global $db;
		$id = $context['id'];
		$class = $context['class'];
		$date = date("Y-m-d");
		$db->execute(lq("UPDATE #_TP_$class SET $field = " . $db->quote($date) . " WHERE identity= " . $db->quote($id))) or trigger_error("SQL ERROR :<br />".$GLOBALS['db']->ErrorMsg(), E_USER_ERROR);

		if(!function_exists("update_childs")){
			function update_childs( $field, $id, $date ){
				global $db;
				foreach( $db->getArray(lq("SELECT id, idtype FROM #_TP_entities WHERE idparent = " . $db->quote($id) )) as $entity){
					$type = $db->getRow(lq("SELECT class FROM #_TP_types WHERE id = " . $db->quote($entity['idtype'])));
					$db->execute(lq("UPDATE #_TP_{$type['class']} SET $field = " . $db->quote($date) . " WHERE identity = " . $db->quote($entity['id'])));
					
					update_childs($field, $entity['id'], $date);
				}
			}
		}

		update_childs($field, $id, $date);
	}
}

function check_isbn($context, $field, &$errors)
{
	if(isset($context['do']) && $context['do'] == "edit" && !empty($context['data'][$field]))
	{
		require_once 'ISBN.php';
		$isbn = preg_replace('/[ -]/', '', $context['data'][$field]);
		$validator = new ISBN($isbn);

		if(!$validator->isValid()){
			$errors[$field] = 'tablefield';
			return;
		}
		$context['data'][$field] = $validator->get13();
	}
}

?>
