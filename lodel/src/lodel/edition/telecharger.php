<?phprequire("siteconfig.php");
include ("$home/auth.php");
authenticate(LEVEL_REDACTEUR,NORECORDURL);

$id=intval($id);
if ($id>0 && $type=="xml") {
 $filename="r2r-$id.xml";
  $rep="../txt";
} elseif ($id>0 && $type=="source") {
 $filename="r2r-$id.rtf";
  $rep="../rtf";
} else die ("type ou id inconnu");

if (!file_exists($rep."/".$filename)) die ("le fichier $filename n'existe pas");

telecharger ($rep,$filename);

function telecharger($rep,$filename)
{
	$path=$rep."/".$filename;
	header("Content-type: application/force-download");
	header("Content-Disposition: attachment; filename=$filename");
	header("Content-type: application/$type");
	readfile("$path"); 
}
?>