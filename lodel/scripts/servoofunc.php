<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *
 *  Home page: http://www.lodel.org
 *
 *  E-Mail: lodel@lodel.org
 *
 *                            All Rights Reserved
 *
 *     This program is free software; you can redistribute it and/or modify
 *     it under the terms of the GNU General Public License as published by
 *     the Free Software Foundation; either version 2 of the License, or
 *     (at your option) any later version.
 *
 *     This program is distributed in the hope that it will be useful,
 *     but WITHOUT ANY WARRANTY; without even the implied warranty of
 *     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *     GNU General Public License for more details.
 *
 *     You should have received a copy of the GNU General Public License
 *     along with this program; if not, write to the Free Software
 *     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.*/


require_once($home."servooclient.php");

class ServOO extends ServOO_Client {

  function ServOO() {

    //
    // servoo parameters
    //

    $options=getoption(array("servoourl","servoousername","servoopasswd"),"");
    if (!$options || !$options['servoourl']) { // get form the lodelconfig file
      $options['servoourl']=$GLOBALS['servoourl'];
      $options['servoousername']=$GLOBALS['servoousername'];
      $options['servoopasswd']=$GLOBALS['servoopasswd'];
    }
    if (!$options['servoourl'] || !$options['servoousername'] || !$options['servoopasswd']) {
      $this->error_message="No servoo";
      return;
    }

    //
    // proxy
    //
    if (!$options['proxyhost']) $options['proxyhost']=$GLOBALS['proxyhost'];
    if ($options['proxyhost']) {
      if (!$options['proxyport']) $options['proxyport']=$GLOBALS['proxyport'];
      if (!$options['proxyport']) $options['proxyport']="8080";
    }

    $this->ServOO_Client($options['servoourl']);

    $this->setauth($options['servoousername'],$options['servoopasswd']);

    if ($options['proxyhost']) {
      $this->setProxy($options['proxyhost'],$options['proxy_port']);
    }

  } // constructor
}



?>
