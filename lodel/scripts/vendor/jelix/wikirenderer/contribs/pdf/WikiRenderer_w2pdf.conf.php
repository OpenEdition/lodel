<?php


/**
* Éléments de configuration pour WikiRenderer
*/

class WikiRenderer_w2pdf extends WikiRendererConfig {

   /**
   * @var array   liste des tags inline
   */
   var $inlinetags = array(
      'bold'      => array('__', '__',   null, 'wikibuild_bold_w2p'),
      'italic'   => array('\'\'', '\'\'',   null, 'wikibuild_italic_w2p'),
      'code'      => array('@@', '@@',   null, 'wikibuild_code_w2p'),
      'q'         => array('^^', '^^',   array('lang', 'cite'), 'wikibuild_q_w2p'),
      'cite'      => array('{{', '}}',   array('title'), 'wikibuild_cite_w2p'),
      'acronym'   => array('??', '??',   array('title'), 'wikibuild_acronym_w2p'),
      'link'      => array('[', ']',      array('href', 'lang', 'title'), 'wikibuild_link_w2p'),
      'image'      => array('((', '))',   array('src', 'alt', 'align', 'longdesc'), 'wikibuild_image_w2p'),
      'anchor'   => array('~~', '~~',   array('name'), 'wikibuild_anchor_w2p')
   );

   /**
   * liste des balises de type bloc autorisées.
   * Attention, ordre important (p en dernier, car c'est le bloc par defaut..)
   */
   var $bloctags = array(   'title_w2p'=>true,
                        'list_w2p'=>true,
                     'pre_w2p'=>true,
                     'hr_w2p'=>true,
                     'blockquote_w2p'=>true,
                     'definition_w2p'=>true,
                     'table_w2p'=>true,
                     'p_w2p'=>true);

   var $simpletags = array('%%%'=>'"); $pdf->Ln(5); $pdf->Write(5, "');

   /**
   * @var   integer   niveau minimum pour les balises titres
   */
   var $minHeaderLevel=3;

   /**
   * indique le sens dans lequel il faut interpreter le nombre de signe de titre
   * true -> ! = titre , !! = sous titre, !!! = sous-sous-titre
   * false-> !!! = titre , !! = sous titre, ! = sous-sous-titre
   */
   var $headerOrder=false;
    var $escapeSpecialChars=false;

}


function wikibuild_bold_w2p($contents, $attr) {

   $str = '");
         $pdf->SetFont( "Arial", "B");
         $pdf->Write( 5, "'. $contents[0] .'");
         $pdf->SetFont("");
         $pdf->Write(5, "';

   return $str;
}


function wikibuild_italic_w2p($contents, $attr) {

   $str = '");
         $pdf->SetFont( "Arial", "I");
         $pdf->Write( 5, "'. $contents[0] .'");
         $pdf->SetFont("");
         $pdf->Write(5, "';

   return $str;
}

function wikibuild_code_w2p($contents, $attr) {

   $str = '");
         $pdf->SetFont( "Courier" );
         $pdf->Write( 5, "'. $contents[0] .'" );
         $pdf->SetFont("Arial");
         $pdf->Write(5, "';

   return $str;
}


function wikibuild_q_w2p($contents, $attr) {
      $str = '");
      $pdf->Write( 5, "'. $contents[0].'" );
      ';
   if ( count($contents) > 2 ) {
      $str .= '
      $pdf->Write( 5, " ('. str_replace('"','\"',$contents[2]).')" );
      ';
   }
   $str .= ' $pdf->Write( 5, "';
   return $str;
}

function wikibuild_cite_w2p($contents, $attr) {
   $str = '");
      $pdf->Write( 5, "'. $contents[0].'" );
      ';
   if ( count($contents) > 1 ) {
      $str .= '
      $pdf->Write( 5, " ('. str_replace('"','\"',$contents[1]).')" );
      ';
   }
   $str .= ' $pdf->Write( 5, "';
   return $str;

}

function wikibuild_acronym_w2p($contents, $attr) {
   $str = '");
      $pdf->Write( 5, "'. $contents[0].'" );
      ';
   if ( count($contents) > 1 ) {
      $str .= '
      $pdf->Write( 5, " ('. str_replace('"','\"',$contents[1]).')" );
      ';
   }
   $str .= ' $pdf->Write( 5, "';
   return $str;
}


function wikibuild_link_w2p($contents, $attr) {

   $cnt = count( $contents );
   $str = '");
         $pdf->SetTextColor( 0, 0, 255 );
         $pdf->SetFont( "Arial", "U" );';

   if ( $cnt > 1 ) {
      if ( $cnt > count( $attr ) ) {
         $cnt = count($attr) + 1;
      }
      if ( strpos( $contents[1], 'javascript:' ) !== false ) { // for security reason
         $contents[1]='#';
      }
      $str.= '$pdf->Write( 5, "'. $contents[0] .'", "'. str_replace('"','\"',$contents[0]) .'" );';
   } else {
      if ( strpos( $contents[0], 'javascript:' ) !== false ) { // for security reason
         $contents[0]='#';
      }
      $str.= '$pdf->Write( 5, "'. $contents[0] .'", "'. str_replace('"','\"',$contents[0]) .'" );';
   }
   $str.= '$pdf->SetFont( "Arial", "" );
         $pdf->SetTextColor( 0 );
         $pdf->Write( 5, "';

   return $str;
}


/** je verrais plus tard... :) **/
function wikibuild_anchor_w2p($contents, $attr){
   return '';
}


function wikibuild_image_w2p($contents, $attr) {

   list($width, $height, $type, $attr) = getimagesize($contents[0]);
   $cnt = count($contents);

   switch( $cnt ) {
      default:
         $str = '");
               $pdf->Ln();
               $y = $pdf->GetY();
               $x = $pdf->GetX();
               $w = '. $width .' / $pdf->k;
               $h = '. $height .' / $pdf->k;
               $x = ($pdf->w - $w) / 2;
               $pdf->Image("'. $contents[0] .'", $x, $y, $w, $h );
               $pdf->ln($h);
               $pdf->Write( 5, "';
         break;
   }
   return $str;
}



// ===================================== déclaration des différents bloc wiki
// on declare des blocs dérivant des blocs initiaux
// comme on n'autorise pas le texte preformaté (debutant par des espaces)
// on autorise des espaces en début de ligne pour les blocs.

/**
 * traite les signes de types liste
 */
class WRB_list_w2p extends WikiRendererBloc {

   var $_previousTag;
   var $_firstItem;
   var $_firstTagLen;
   var $type = 'list';
   var $regexp = "/^([\*#-]+)(.*)/";

   function open(){
      $this->_previousTag = $this->_detectMatch[1];
      $this->_firstTagLen = strlen($this->_previousTag);
      $this->_firstItem = true;

      $str = '$pdf->Ln();';

      return $str;
   }

   function close(){
      $t = $this->_previousTag;
      $str = '';

      for ( $i = strlen($t); $i >= $this->_firstTagLen; $i--) {
         $str.= '$pdf->Ln();
               $pdf->SetLeftMargin( $pdf->lMargin - 7 );';
      }

      return $str;
   }

   function getRenderedLine() {
      $d = strlen($this->_previousTag) - strlen($this->_detectMatch[1]);
      $str = '';

      if ( $d > 0 ) { // on remonte d'un cran dans la hierarchie...
         $str = '$pdf->SetLeftMargin( $pdf->lMargin - 7 );
               $pdf->Ln();';
         $this->_previousTag = substr($this->_previousTag,0,-1); // pour être sur...
      } elseif ( $d < 0 ) { // un niveau de plus
         $c = substr($this->_detectMatch[1],-1,1);
         $this->_previousTag.= $c;
         $str = '$pdf->Ln();
               $pdf->SetLeftMargin( $pdf->lMargin + 7 );';
      } else {
         $str = ($this->_firstItem ? '$pdf->SetLeftMargin( $pdf->lMargin + 10 );':'$pdf->Ln();');
      }
      $this->_firstItem = false;
      $str.= '$pdf->Write(5, "'. chr(149) .' '. $this->_renderInlineTag($this->_detectMatch[2]) .'");';

      return $str;
   }
}


/**
 * traite les signes de types table
 */

 /** à voir plus tard **/
class WRB_table_w2p extends WikiRendererBloc {
   var $type='table';
   var $regexp="/^\| ?(.*)/";
   var $_openTag="--------------------------------------------";
   var $_closeTag="--------------------------------------------\n";

   var $_colcount=0;

   function open(){
      $this->_colcount=0;
      return $this->_openTag;
   }


   function getRenderedLine(){

      $result=explode(' | ',trim($this->_detectMatch[1]));
      $str='';
      $t='';

      if((count($result) != $this->_colcount) && ($this->_colcount!=0))
         $t="--------------------------------------------\n";
      $this->_colcount=count($result);

      for($i=0; $i < $this->_colcount; $i++){
         $str.=$this->_renderInlineTag($result[$i])."\t| ";
      }
      $str=$t."| ".$str;

      return $str;
   }

}

/**
 * traite les signes de types hr
 */
 /** à voir plus tard **/
class WRB_hr_w2p extends WikiRendererBloc {

   var $type='hr';
   var $regexp='/^={4,} *$/';
   var $_closeNow=true;

   function getRenderedLine(){
      return "=======================================================\n";
   }

}

/**
 * traite les signes de types titre
 */
class WRB_title_w2p extends WikiRendererBloc {

   var $type = 'title';
   var $regexp = "/^(\!{1,3})(.*)/";
   var $_closeNow = true;
   var $_minlevel = 1;
   var $_order = true;


   function getRenderedLine() {

      if ( $this->_order ) {
         $hx = $this->_minlevel + strlen($this->_detectMatch[1]) - 1;
      } else {
         $hx = $this->_minlevel + 3 - strlen($this->_detectMatch[1]);
      }
      switch ( $hx ) {
         case 3:
            $str = '$pdf->Ln(3);
                  $pdf->SetX(10);
                  $pdf->SetFont( "Arial", "B", 10 );
                  $pdf->Write( 5, "'. $this->_renderInlineTag($this->_detectMatch[2]) .'" );
                  $pdf->Ln(5);';
                  break;
         case 2:
            $str = '$pdf->Ln(3);
                  $pdf->SetFont( "Arial", "B", 10 );
                  $pdf->Write( 5, "'. $this->_renderInlineTag($this->_detectMatch[2]) .'" );
                  $pdf->Ln(7);';
                  break;
         case 1:
            $str = '$pdf->Ln(15);
                  $pdf->SetTextColor(164, 151, 130);
                  $pdf->SetFont( "Arial", "B", 11 );
                  $pdf->Write( 5, "'. $this->_renderInlineTag($this->_detectMatch[2]) .'" );
                  $pdf->SetTextColor(0);
                  $pdf->Ln(7);';
                  break;
      }
      $str.= '$pdf->SetFont( "Arial", "", 12 );';

      return $str;
   }
}

/**
 * traite les signes de types pre (pour afficher du code..)
 */
 /** à voir plus tard **/
class WRB_pre_w2p extends WikiRendererBloc {

   var $type='pre';
   var $regexp="/^ (.*)/";
   var $_openTag='';
   var $_closeTag='';

   function getRenderedLine() {

      return ' '.$this->_renderInlineTag($this->_detectMatch[1]);
   }
}

/**
 * traite les signes de type paragraphe
 */
class WRB_p_w2p extends WikiRendererBloc {

   var $type='p';
   var $regexp="/(.*)/";
   var $_openTag = '$pdf->Ln(0); $pdf->Write( 5, "';
   var $_closeTag = '"); $pdf->Ln(0);';
}



/**
 * traite les signes de type blockquote
 */
  /** à voir plus tard **/
class WRB_blockquote_w2p extends WikiRendererBloc {
   var $type='bq';
   var $regexp="/^(\>+)(.*)/";


   function getRenderedLine(){
      return $this->_detectMatch[1].$this->_renderInlineTag($this->_detectMatch[2]);
   }
}

/**
 * traite les signes de type blockquote
 */
class WRB_definition_w2p extends WikiRendererBloc {

   var $type='dfn';
   var $regexp="/^;(.*) : (.*)/i";

   function getRenderedLine(){
      $dt=$this->_renderInlineTag($this->_detectMatch[1]);
      $dd=$this->_renderInlineTag($this->_detectMatch[2]);
      return "$dt :\n\t$dd";
   }
}




?>
