<?php


require_once ('siteconfig.php');
		require_once($home . 'auth.php');
		#authenticate(LEVEL_ADMINLODEL,NORECORDURL);
		authenticate();
		require_once($home . 'func.php');
		require_once($home . 'champfunc.php');
		require_once($home . 'connect.php');
require_once ($home . '07to08.php');
authenticate(LEVEL_ADMINLODEL, NORECORDURL);

$exportfor08 = new exportfor08();

//$exportfor08->init_db;
$exportfor08->cp_07_to_08();
$exportfor08->create_classes();
$exportfor08->update_fields();
$exportfor08->update_types();
$exportfor08->insert_index_data();
$exportfor08->update_ME();
?>