<?php
/*
 *
 *  LODEL - Logiciel d'Edition ELectronique.
 *
 *  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 *  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 *  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno Cénou
 *  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy
 *  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno Cénou, Jean Lamy, Mikaël Cixous, Sophie Malafosse
 *  Copyright (c) 2007, Marin Dacos, Bruno Cénou, Sophie Malafosse, Pierre-Alain Mignot
 *  Copyright (c) 2008, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
 *  Copyright (c) 2009, Marin Dacos, Bruno Cénou, Pierre-Alain Mignot, Inès Secondat de Montesquieu, Jean-François Rivière
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

class_exists('ServOO_Client', false) || include "servooclient.php";

class ServOO extends ServOO_Client {
	
	private $options; // username / passwd / url
	public $status; // true ou false

	function ServOO($other = "") {
		if(!empty($other)) {
			if(FALSE === $this->SelectOtherServer($other)) {
				$this->status = FALSE;
				return;
			}
		} else {
			// servoo parameters
			$this->options=getoption(array("servoo.url","servoo.username","servoo.passwd",
						"servoo.proxyhost","servoo.proxyport"),"");
			
			if (!$this->options || !$this->options['servoo.url']) { // get form the lodelconfig file
				$this->options['servoo.url']=C::get('servoourl', 'cfg');
				$this->options['servoo.username']=C::get('servoousername', 'cfg');
				$this->options['servoo.passwd']=C::get('servoopasswd', 'cfg');
			}
			if (!$this->options['servoo.url'] || !$this->options['servoo.username'] || !$this->options['servoo.passwd']) {
				$this->error_message="No servoo";
				return;
			}
			$proxyhost = C::get('proxyhost', 'cfg');
			// proxy
			if (empty($this->options['servoo.proxyhost']) && !empty($proxyhost)) $this->options['servoo.proxyhost']=$proxyhost;
			if (!empty($this->options['servoo.proxyhost'])) {
				if (empty($this->options['servoo.proxyport'])) 
				{
					$proxyport = C::get('proxyport', 'cfg');
					if(!empty($proxyhost))
						$this->options['servoo.proxyport']=$proxyport;
					else $this->options['servoo.proxyport']="8080";
				}
			}
		}

		$this->ServOO_Client($this->options['servoo.url']);
			
		$this->setauth($this->options['servoo.username'],$this->options['servoo.passwd']);
			
		if ($this->options['servoo.proxyhost']) {
			$this->setProxy($this->options['servoo.proxyhost'],$this->options['servoo.proxyport']);
		}
		$this->status = TRUE;
	} // constructor

	private function SelectOtherServer($i) {
		$this->options=getoption(array("servoo$i.url","servoo$i.username","servoo$i.passwd",
				"servoo$i.proxyhost","servoo$i.proxyport"),"");
		$servoourl = C::get('servoourl'.$i, 'cfg');
		if ((!$this->options || empty($this->options['servoo.url'])) && !empty($servoourl)) { // get form the lodelconfig file
			$this->options['servoo.url']=$servoourl;
			$this->options['servoo.username']=C::get('servoousername'.$i, 'cfg');
			$this->options['servoo.passwd']=C::get('servoopasswd'.$i, 'cfg');
		}
		$proxyhost = C::get('proxyport'.$i, 'cfg');
		// proxy
		if (empty($this->options['servoo.proxyhost']) && !empty($proxyhost)) $this->options['servoo.proxyhost']=$proxyhost;
		if (!empty($this->options['servoo.proxyhost'])) {
			if (empty($this->options['servoo.proxyport'])) 
			{
				$proxyport = C::get('proxyport'.$i, 'cfg');
				if(!empty($proxyport))
					$this->options['servoo.proxyport']=$proxyport;
				else $this->options['servoo.proxyport']="8080";
			}
		}
		if(!empty($this->options['servoo.url']) && !empty($this->options['servoo.username']) && !empty($this->options['servoo.passwd'])) {
			$this->error_message = "";
			return TRUE;
		} else {
			$this->error_message = "No ServOO parameters found.";
			return FALSE;
		}
	}
}
?>