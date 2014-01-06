<?php
/**	
 * Script modifiant la base d'un site en 0.7 pour qu'elle puisse être utilisée par un site en 0.8
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Home page: http://www.lodel.org
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Sophie Malafosse
 * @author Pierre-Alain Mignot
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno Cénou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @since Fichier ajouté depuis la version 0.8
 */

require_once ('siteconfig.php');
require_once($home . 'auth.php');
require_once($home . 'func.php');
require_once($home . 'champfunc.php');
require_once($home . 'connect.php');
require_once ($home . '07to08.php');
authenticate(LEVEL_ADMINLODEL, NORECORDURL);
require_once ($home."calcul-page.php");

$context['actions'] = &$_POST;
$context['error'] = 0;
set_time_limit(0);

if($context['actions']['valid'] === "Valider" && !empty($context['actions']['defaultgtitle']) && !empty($context['actions']['defaultgname'])) {
	$exportfor08 = new exportfor08($context['actions']['defaultgtitle'], $context['actions']['defaultgname']);
	$context['action'] = 1;
		if($context['actions']['save_and_dump'] && !$context['error']) {
			$context['dump_before'] = $exportfor08->dump_before_changes();
			if($context['dump_before'] != "Ok") {
				$context['error'] = 1;
				$context['msg_error'] = $context['dump_before'];
			}
			$context['action'] = 2;
			calcul_page($context,"index_migration");
			exit;
		}
		$context['init_db'] = $exportfor08->init_db();
		if($context['init_db'] != "Ok") {
			$context['error'] = 2;
			$context['msg_error'] = $context['init_db'];
		}

		if(!$context['error']) {
			$context['cp_datas'] = $exportfor08->cp_07_to_08();
			if($context['cp_datas'] != "Ok") {
				$context['error'] = 3;
				$context['msg_error'] = $context['cp_datas'];
			}
		}
		if(!$context['error']) {
			$context['create_classes'] = $exportfor08->create_classes();
			if($context['create_classes'] != "Ok") {
				$context['error'] = 4;
				$context['msg_error'] = $context['create_classes'];
			}
		}
		if(!$context['error']) {
			$context['up_fields'] = $exportfor08->update_fields($context['actions']['default_field']);
			if($context['up_fields'] != "Ok") {
				$context['error'] = 5;
				$context['msg_error'] = $context['up_fields'];
			}
		}
		if(!$context['error']) {
			$context['up_types'] = $exportfor08->update_types();
			if($context['up_types'] != "Ok") {
				$context['error'] = 6;
				$context['msg_error'] = $context['up_fields'];
			}
		}
		if(!$context['error']) {
			$context['up_docannexes'] = $exportfor08->cp_docs07_to_08();
			if($context['up_docannexes'] != "Ok") {
				$context['error'] = 7;
				$context['msg_error'] = $context['up_fields'];
			}
		}
/*
		if($context['actions']['update_me'] && !$context['error']) {
			$context['up_me'] = $exportfor08->update_ME();
			if($context['up_me'] != "Ok") {
				$context['error'] = 8;
				$context['msg_error'] = $context['up_me'];
			}
		}
*/
		if(!$context['error']) {
			$context['cp_index'] = $exportfor08->insert_index_data($context['actions']['update_me']);
			if($context['cp_index'] != "Ok") {
				$context['error'] = 9;
				$context['msg_error'] = $context['cp_index'];
			}
		}
/*		if(!$context['error']) {
			$context['cp_docannexes'] = $exportfor08->datas_copy("docannexe", $context['actions']['new_dir']."/docannexe");
			if($context['cp_docannexes'] != "Ok") {
				$context['error'] = 10;
				$context['msg_error'] = $context['cp_docannexes'];
			}
		}
		if(!$context['error']) {
			$context['cp_sources'] = $exportfor08->datas_copy("lodel/sources", $context['actions']['new_dir']."/lodel/sources");
			if($context['cp_sources'] != "Ok") {
				$context['error'] = 11;
				$context['msg_error'] = $context['cp_sources'];
			}
		}
		if(!$context['error']) {
			$context['cp_images'] = $exportfor08->datas_copy("images", $context['actions']['new_dir']."/images");
			if($context['cp_images'] != "Ok") {
				$context['error'] = 12;
				$context['msg_error'] = $context['cp_images'];
			}
		}
		if(!$context['error']) {
			$context['cp_css'] = $exportfor08->datas_copy("css", $context['actions']['new_dir']."/css");
			if($context['cp_css'] != "Ok") {
				$context['error'] = 13;
				$context['msg_error'] = $context['cp_css'];
			}
		}
		if(!$context['error']) {
			$context['up_tpl'] = $exportfor08->update_tpl($context['actions']['new_dir']);
			if($context['up_tpl'] != "Ok") {
				$context['error'] = 14;
				$context['msg_error'] = $context['up_tpl'];
			}
		}/*
		if($context['actions']['do_and_dump'] && !$context['error']) {
			$context['dump_after'] = $exportfor08->dump_changes_to08();
			if($context['dump_after'] != "Ok") {
				$context['error'] = 15;
				$context['msg_error'] = $context['dump_after'];
			}
		}
		if($context['actions']['dump_after'] && !$context['error']) {
			$context['dump_after_changes'] = $exportfor08->dump_after_changes_to08();
			if($context['dump_after_changes'] != "Ok") {
				$context['error'] = 16;
				$context['msg_error'] = $context['dump_after_changes'];
			}
		}*/
} elseif($context['actions']['valid'] === "Valider") {
	$context['error'] = 1;
}
$context['mysql_errors'] = $exportfor08->mysql_errors;
calcul_page($context,"index_migration");
?>
