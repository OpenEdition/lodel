<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier de fonction IMAP
 */

/**
 * Retire les pièces jointes des mails sur une boîte mail donnée
 *
 * Cette fonction utilise les options lodelmail.host, lodelmail.user, lodelmail.passwd.
 *
 * @return le nombre de pièces jointes
 */
function checkmailforattachments()
{
	$options = getoption(array ("lodelmail.host", "lodelmail.user", "lodelmail.passwd"), "");
	if (count($options) != 3 || !$options['lodelmail.host']) {
		trigger_error('ERROR: To use this feature, you must create and fill the options host, user and passwd in the group lodelmail. See in the administration interface ', E_USER_ERROR);
	}

	list ($host, $port) = explode(":", $options['lodelmail.host']);
	$mailserver = "{".$host.":". ($port ? $port : "110")."/pop3}INBOX";
	$passwd = $options['lodelmail.passwd'];
	$user = $options['lodelmail.user'];

	$mbox = imap_open($mailserver, $user, $passwd);

	if ($mbox === false) {
		trigger_error(imap_last_error(), E_USER_ERROR);
		return;
	}

	$nbattachment = 0;
	$nbmsg = imap_num_msg($mbox);

	for ($msgno = 1; $msgno <= $nbmsg; $msgno ++) {
		$nbattachment += extractattachments($mbox, $msgno, "(je?pg|png|gif|tiff|sxw|doc|rtf|html?)");
		imap_delete($mbox, $msgno);
	}
	imap_expunge($mbox);

	return $nbattachment;
}

/**
 * Extrait les pièces jointes des mails d'une boîte donnée
 *
 * @param object $mbox la boîte mail
 * @param integer $mnum le numéro du mail
 * @param string $extre extension acceptées
 * @param integer $struct par défaut 0.la structure des pièces jointes
 * @param integer $pno par défaut vide la partie des attachements (cas des mails multiparts)
 *
 */
function extractattachments($mbox, $mnum, $extre, $struct = 0, $pno = "")
{
	$nbattachment = 0;
	if ($struct === 0) {
		$struct = imap_fetchstructure($mbox, $mnum);
	}
	switch ($struct->type) {
	case 1 : // multipart
		// look for the subpart
		$partno = 1;
		if ($pno) {
			$pno .= ".";
		}
		foreach ($struct->parts as $j) {
			$nbattachment += extractattachments($mbox, $mnum, $extre, $struct->parts[$j], $pno.$partno);
			$partno ++;
		}
		break;
	case 2 : // message
		// decode
		$nbattachment += extractattachments($mbox, $mnum, $extre, $struct->parts[0], $pno);
		break;
	case 5 :
	default : // other
		// fetch the body of the part
		$body = imap_fetchbody($mbox, $mnum, $pno);

		// dcode
		if ($struct->encoding == 3) {
			$body = imap_base64($body);
		}
		elseif ($struct->encoding == 4) {
			$body = imap_qprint($body);
		}

		// get the filename
		if ($struct->parameters[0]->attribute == "NAME") {
			$filename = $struct->parameters[0]->value;
		}	else {
			return; // no filename don't download
		}
		$filename = preg_replace("/[^\w\.]/", "_", $filename);
		$extpos = strrpos($filename, ".");
		$ext = substr($filename, $extpos);

		// check the extension is valid
		if (!preg_match("/^\.".$extre."$/i", $ext)) {
			return;
		}

		if (strlen($filename) > 127) { // limit the length of the filename
			$filename = substr($filename, 0, 127 - strlen($ext)).$ext;
		}

		// save the attachment as $filename
		writefile(SITEROOT."upload/".$filename, $body);
		$nbattachment ++;
		break;
	}
	return $nbattachment;
}
?>
