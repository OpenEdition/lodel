<?

touch("CACHE/maj");

if ($PHP_AUTH_PW) {
  Header( "WWW-authenticate:  basic  realm=\"Revues.org\"");
  Header( "HTTP/1.0  401  Unauthorized");

#  Header("Location: logout.php");
  echo "<HTML><HEAD><TITLE>Logout</TITLE></HEAD>
<BODY><H3>Pour se deloguer appuyez deux fois sur Ok et n'entrez aucun mot de passe</H3><P></BODY></HTML>\n";
  exit;
} else {
  Header("Location: index.html");
}

?>
