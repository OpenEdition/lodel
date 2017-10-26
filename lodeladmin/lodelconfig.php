<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

$cfg['version']="1.0";

##########################

define("LODELROOT","../");

require(LODELROOT."lodelconfig.php");
ini_set('include_path',LODELROOT. "lodel/scripts" .PATH_SEPARATOR . ini_get("include_path"));

$cfg['home']=LODELROOT.$cfg['home'];
$cfg['sharedir']=LODELROOT.$cfg['sharedir'];

$cfg['site']="";

require 'context.php';
C::setCfg($cfg);
require 'class.errors.php';

