<?
require("lodelconfig.php");
include ("$home/auth.php");
authenticate();


function boucle_alphabet(&$context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    call_user_func("code_boucle_$funcname",$context);
  }
}

include ("$home/cache.php");

?>
