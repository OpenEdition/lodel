<?
include ("lodelconfig.php");
include ("$home/auth.php");
authenticate();


function boucle_alphabet(&$context)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    code_boucle_alphabet($context);
  }
}

include ("$home/cache.php");

?>
