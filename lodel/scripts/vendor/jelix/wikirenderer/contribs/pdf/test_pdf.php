<?php

   $txt="!!! titre\n!! sous-titre\n! sous-sous-titre\n\nLorem __ipsum dolor__ sit amet, ''consectetuer adipiscing'' elit. Ut scelerisque. Ut iaculis ultrices nulla. Cras viverra diam nec justo.\n\n";
   $txt.="* Phasellus non eros sit amet sem tristique laoreet. \n*# Nam mi wisi, pellentesque dictum, \n*# tristique in, tristique quis, erat. \n*## In in erat ut urna vulputate vestibulum. Aenean justo. \n*## In quis nisl. \n* Morbi justo libero, pharetra a, \n* malesuada eget, lacinia in, ligula.\n\n";
   $txt.="Mauris [sit amet massa|http://ljouanneau.com|fr|at neque] pretium dapibus.\n\ncursus et, @@vulputate in@@, eros. \n Phasellus ??placerat|semper neque??.\n";
   $txt.=" In hac habitasse platea dictumst. \n\nFusce sagittis, mi eu elementum lobortis, augue enim tristique ante, sed varius urna mauris sed erat.\n====\nPraesent pellentesque, ^^augue at| consectetuer imperdiet^^, mi metus {{dignissim arcu}}, sed sodales quam risus eu neque. \n\nPellentesque euismod. \n";
   $txt.="> Curabitur mi. Aenean vitae lectus vel turpis feugiat egestas. \n> Quisque diam. Maecenas tincidunt tortor sed neque. \n\nMauris nibh. Vivamus tempus est in urna. \n\n";
   $txt.=";Curabitur et arcu : non odio gravida varius. Vivamus fringilla, neque ac suscipit vehicula, libero metus laoreet libero, in gravida purus nunc quis orci. \n;Duis : non mi non lacus tincidunt iaculis. \n;Aliquam tempor : metus in cursus dapibus, purus ipsum consequat quam, et vehicula libero velit sit amet felis. Sed id leo. \n\n";
   $txt.="Vivamus orci leo, dictum et, <b>scelerisque sed</b>, <i>pretium et</i>, dolor. Aenean pharetra felis pellentesque dui. Donec neque. Duis tristique. Pellentesque at eros\n\n";
   $txt.="Lorem ipsum | dolor \\| sit\\\\ amet \n\n";
   $txt.="Pater \\[noster qui|est|in] @@caelis@@... \n";



define('FPDF_FONTPATH','font/');

require 'fpdf.php';
require 'WikiRenderer.lib.php';
require 'WikiRenderer_w2pdf.conf.php';

$ctr = new WikiRenderer(new WikiRenderer_w2pdf());

$string = '$pdf = new FPDF();
         $pdf->AddPage();
         $pdf->SetFont("Arial","",10);';
$string .= $ctr->render($txt);
$string .='$pdf->Output( );';

//eval ($string);
//exit;
?>
<html>
<head><meta/>
<title>test pdf</title></head>
<body>
<pre>
<?php echo $string; ?>

</pre>


</body>
</html>