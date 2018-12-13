<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

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

function check_isbn(&$context, $field, &$errors)
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

function check_issn(&$context, $field, &$errors)
{
    if (isset($context['do']) && $context['do'] == "edit" && !empty($context['data'][$field])) {
        $value = preg_replace('/[ -]/', '', $context['data'][$field]);
        $checksum = substr($value, -1, 1);
        $values   = str_split(substr($value, 0, -1));
        $check    = 0;
        $multi    = 8;
        foreach($values as $token) {
            if ($token == 'X') {
                $token = 10;
            }

            $check += ($token * $multi);
            --$multi;
        }

        $check %= 11;
        if ($check != 0) $check  = 11 - $check;

        if ($check == $checksum || (($check == 10) && ($checksum == 'X'))) {
            $context['data'][$field] = wordwrap($value,4,'-', true);
            return;
        }else{
            $errors[$field] = 'tablefield';
            return;
        }

    }
}

/**
 * Filtre permettant de vérifier que l'extension du fichier uploadé correspond à celle que l'on attend
 *
 * @author Pierre-Alain Mignot
 */
class Lodel_Filter_File_Type
{
    /**
    * Vérifie que l'extension du fichier correspond à ce que l'on attend
    * On fait un appel à la méthode statique Lodel_Filter_File_Type::PDF pour valider l'extension .pdf
    *
    * @author Pierre-Alain Mignot
    * @param string $name le nom de la méthode appellée, convertie en nom de l'extension à valider
    * @param array $args les arguments passés à la fonction, array(context, champ, erreurs)
    */
    public static function __callStatic($name, $args)
    {
        $fileExt = strtolower($name);

        $context = $args[0];
        $field = $args[1];
        $errors =& $args[2];

        if(empty($context['data'][$field]) || !is_array($context['data'][$field]) || empty($context['data'][$field]['radio'])) return;

    switch($context['data'][$field]['radio'])
        {
            case 'upload':
                $file = $_FILES['data']['name'][$field]['upload'];
                break;

            case 'delete': return;
                break;

            case 'serverfile':
                $file = $context['data'][$field]['localfilename'];
                break;

            case '':
                $file = isset($context['data'][$field]['previousvalue']) ? $context['data'][$field]['previousvalue'] : '';
                if(empty($file)) return;
                break;

            default: return;
        }

        if($fileExt !== substr(strtolower(strrchr($file,'.')), 1))
            $errors[$field] = 'tablefield';
    }
}
