<?php
/**
 * LODEL - Logiciel d'Édition ÉLectronique.
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html) See COPYING file
 * @authors See COPYRIGHT file
 */

/**
 * Fichier logout
 */

if ($PHP_AUTH_PW) {
  Header("WWW-authenticate:  basic  realm=\"Revues.org\"");
  Header('HTTP/1.0  401  Unauthorized');
  echo "<html><head><title>Logout</title></head>
<body><h3>Pour se déloguer appuyez deux fois sur Ok et n'entrez aucun mot de passe</h3></body></html>\n";
  exit;
} else {
  Header('Location: index.html');
}
?>