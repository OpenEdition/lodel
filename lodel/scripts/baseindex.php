<?php
 require_once("siteconfig.php");
include ($home."auth.php");
authenticate();


function loop_alphabet(&$context,$funcname)

{
  for($l="A"; $l!="AA"; $l++) {
    $context[lettre]=$l;
    call_user_func("code_do_$funcname",$context);
  }
}

include ($home."cache.php");

?>
