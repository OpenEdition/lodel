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




require_once($home."func.php");

function checkmailforattachments()

{
  $options=getoption(array("mailhostname","mailuser","mailpasswd"),"");
  if (count($options)!=3) {
    die("ERROR: You must fill the options mailhostname, mailuser and mailpasswd in the administration interface to use this feature");
  }

  list($host,$port)=explode(":",$options['mailhostname']);
  $mailserver="{".$host.":".($port ? $port : "110")."/pop3}INBOX";
  $passwd=$options['mailpasswd'];
  $user=$options['mailuser'];

  $mbox=imap_open($mailserver,$user,$passwd);

  if ($mbox===false) {
    die(imap_last_error());
    return;
  }

  $nbattachment=0;
  $nbmsg=imap_num_msg($mbox);
    
  for($msgno=1; $msgno<=$nbmsg; $msgno++) {
    $nbattachment+=extractattachments($mbox,$msgno,"(je?pg|png|gif|tiff|sxw|doc|rtf|html?)");
    imap_delete($mbox,$msgno);
  }
  imap_expunge($mbox);

  return $nbattachment;
}


function extractattachments ($mbox, $mnum, $extre ,$struct=0, $pno ="")
{
  $nbattachment=0;

  if ($struct===0) $struct = imap_fetchstructure($mbox, $mnum);

  switch ($struct->type)
    {
    case 1: // multipart
      // look for the subpart
      $partno = 1;
      if ($pno) $pno.=".";
      while (list($j) = each ($struct->parts)) {
	$nbattachment+=extractattachments($mbox, $mnum, $extre, $struct->parts[$j], $pno.$partno);
	$partno++;
      }
      break;
    case 2: // message
      // decode
      $nbattachment+=extractattachments($mbox, $mnum, $extre, $struct->parts[0], $pno);
      break;
    case 5:
    default: // other
      // fetch the body of the part
      $body = imap_fetchbody($mbox, $mnum, $pno);
          
      // décode
      if($struct->encoding == 3)
	$body = imap_base64($body);
      elseif($struct->encoding == 4)
	$body = imap_qprint($body);

      // get the filename
      if ($struct->parameters[0]->attribute == "NAME") {
	$filename=$struct->parameters[0]->value;
      } else {
	return; // no filename don't download
      }
      $filename=preg_replace("/[^\w\.]/","_",$filename);
      $extpos=strrpos($filename,".");
      $ext=substr($filename,$extpos);

      // check the extension is valid
      if (!preg_match("/^\.".$extre."$/i",$ext)) return;

      if (strlen($filename)>127) { // limit the length of the filename
	$filename=substr($filename,0,127-strlen($ext)).$ext;
      }

      // save the attachment as $filename
      writefile(SITEROOT."CACHE/upload/".$filename,$body);
      $nbattachment++;
      break;
    }
  return $nbattachment;
}

?>