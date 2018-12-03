<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

class_exists('ServOO_Client', false) || include "servooclient.php";

class ServOO extends ServOO_Client {
	
	private $options; // username / passwd / url
	public $status; // true ou false

	function __construct($other = "") {
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