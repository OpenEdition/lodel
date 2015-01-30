<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

function_exists('search') || require 'searchfunc.php';

/**
 * Results page script - Lodel part
 * 
 */
recordurl();
View::getView()->renderCached("search");
return;