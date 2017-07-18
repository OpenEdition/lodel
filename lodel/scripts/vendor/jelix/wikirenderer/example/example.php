<?php
require('../tests/wikirenderer/WikiRenderer.lib.php');

$texte='';

if(isset($_POST['texte'])){
   if(get_magic_quotes_gpc())
      $texte=stripslashes($_POST['texte']);
   else
      $texte=$_POST['texte'];
}
   $ctr=new WikiRenderer();
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" xml:lang="fr">
<head>
   <title>WikiRenderer</title>
   <link rel="stylesheet" href="exemple.css" media="all" type="text/css" />
</head>
<body>

<script type="text/javascript">
<!--
function exemple(){
   document.test.texte.value="!!! titre\n!! sous-titre\n! sous-sous-titre\n\nLorem __ipsum dolor__ sit amet, ''consectetuer adipiscing'' elit. Ut scelerisque. Ut iaculis ultrices nulla. Cras viverra diam nec justo.\n\n* Phasellus non eros sit amet sem tristique laoreet. \n*# Nam mi wisi, pellentesque dictum, \n*# tristique in, tristique quis, erat. \n*## In in erat ut urna vulputate vestibulum. Aenean justo. \n*## In quis nisl. \n* Morbi justo libero, pharetra a, \n* malesuada eget, lacinia in, ligula.\n\nMauris [sit amet massa|http://ljouanneau.com|fr|at neque] pretium dapibus.\n\n| Nulla metus felis | tristique non\n| 1 | 2\n| 5 | 7\n\ncursus et, @@vulputate in@@, eros. \n Phasellus ??placerat|semper neque??.\n In hac habitasse platea dictumst. \n\nFusce sagittis, mi eu elementum lobortis, augue enim tristique ante, sed varius urna mauris sed erat.\n====\nPraesent pellentesque, ^^augue at| consectetuer imperdiet^^, mi metus {{dignissim arcu}}, sed sodales quam risus eu neque. \n\nPellentesque euismod. \n> Curabitur mi. Aenean vitae lectus vel turpis feugiat egestas. \n> Quisque diam. Maecenas tincidunt tortor sed neque. \n\nMauris nibh. Vivamus tempus est in urna. \n\n;Curabitur et arcu : non odio gravida varius. Vivamus fringilla, neque ac suscipit vehicula, libero metus laoreet libero, in gravida purus nunc quis orci. \n;Duis : non mi non lacus tincidunt iaculis. \n;Aliquam tempor : metus in cursus dapibus, purus ipsum consequat quam, et vehicula libero velit sit amet felis. Sed id leo. \n\nVivamus orci leo, dictum et, scelerisque sed, pretium et, dolor. Aenean pharetra felis pellentesque dui. Donec neque. Duis tristique. Pellentesque at eros";
}
//-->
</script>

<h2>Tester Wikirenderer <?echo $ctr->getVersion() ?></h2>
<form action="exemple.php#resultats" method="POST" id="test" name="test">
<fieldset><legend>Saisissez un texte wiki</legend>
<label>texte :
<textarea style="border:1px solid;" name="texte" cols="50" rows="20"><?echo $texte?></textarea></label>
<br />
<input type="button" value="editer un exemple" onclick="exemple()" />
<input type="submit" value="Valider et voir la transformation" />
</fieldset>
</form>
<h2>Aide</h2>
<h3>signes de formatage de types bloc&nbsp;:</h3>
<ul class="aide">
<li>Paragraphe       : 2 sauts de lignes</li>
<li>Trait HR          : <code>====</code> (4 signes "égale" ou plus) + saut de ligne</li>
<li>Liste             : une ou plusieurs <code>*</code> ou  <code>-</code> (liste simple) ou <code>#</code> (liste numérotée) par item + saut de ligne</li>
<li>Tableaux          : <span><code>|</code>texte<code>|</code>texte</span>  ( <code>|</code> = caractere séparateur de colonne, chaque ligne &crite = une ligne de tableau</li>
<li>sous titre niveau 1 : <span><code>!!!</code>titre</span> + saut de ligne</li>
<li>sous titre niveau 2 : <span><code>!!</code>titre</span> + saut de ligne</li>
<li>sous titre niveau 3 : <span><code>!</code>titre</span> + saut de ligne</li>
<li>texte préformaté :  un espace + texte + saut de ligne</li>
<li>citation (blockquote) :  un ou plusieurs <code>&gt;</code> + <span>texte</span> + saut de ligne</li>
<li>Définitions : <span><code>;</code>terme<code>:</code>définition</span> + saut de ligne</li>
</ul>

<h3>signes de formatage de type inline:</h3>
<ul class="aide">
<li>emphase forte (gras)   : <span><code>__</code>texte<code>__</code></span> (2 underscores)</li>
<li>emphase simple (italique) : <span><code>''</code>texte<code>''</code></span> (deux apostrophes)</li>
<li>Retour à la ligne forcée    : <code>%%%</code> </li>
<li>Lien    : <span><code>[</code>nomdulien<code>|</code>lien<code>|</code>langue<code>|</code>déscription (title)<code>]</code></span> </li>
<li>images : <span><code>((</code>url image<code>|</code>texte alternatif<code>|</code>position<code>|</code>longue description<code>))</code></span>  position = G, D ( aligné à Gauche, Droite)</li>
<li>code            : <span><code>@@</code>code<code>@@</code></span></li>
<li>citation         : <span><code>^^</code>phrase<code>|</code>lien source<code>^^</code></span></li>
<li>reférence (cite)      : <span><code>{{</code>reference<code>}}</code></span></li>
<li>acronym         : <span><code>??</code>acronyme<code>|</code>signification<code>??</code></span></li>
<li>ancre : <span><code>~~</code>monancre</span><code>~~</code></li>
</ul>


<?
if($texte!=''){

   echo '<h2 id="resultats">Source du resultat:</h2>';

   $texte=$ctr->render($texte);
   if($ctr->errors){
      echo '<p style="color:red;">Il y a ';
      if(count($ctr->errors)>1)
         echo 'des erreurs wiki aux lignes : ',implode(',',array_keys($ctr->errors)),'</p>' ;
      else{
         list($num,$l)=each($ctr->errors);
         echo 'une erreur wiki à la ligne ', $num,'</p>';
     }
   }

   $texte2=htmlspecialchars($texte);
   echo '<pre style="overflow:auto">';
   echo $texte2;
   echo '</pre>';
    echo '<h2>Résultat:</h2>';
    echo $texte;

}

?>

</body>
</html>
