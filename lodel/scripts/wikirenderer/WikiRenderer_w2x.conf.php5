<?php

/**
 * Éléments de configuration pour WikiRenderer 2.0, pour être compatible avec wiki2xhtml
 * @author Laurent Jouanneau <jouanneau@netcourrier.com>
 * @copyright 2003 Laurent Jouanneau
 * @module Wiki Renderer
 * @version 2.0.dev-php5
 * @since 28/11/2004
 *
 * le code de la fonction w2x_buildlink provient de la class wiki2xhtml sous licence MPL
 * http://www.neokraft.net/docs/wiki2xhtml/, copyright Olivier Meunier.
 *
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * -----------------------------------------------------------------------------
 * Adapatation PHP5 : Edouard Guérin <eguerin@icitrus.net>
 */


class Wiki2XhtmlConfig extends WikiRendererConfig {


    function __construct() {

        /**
         * @var array liste des tags inline
         */

        $this->inlinetags = array(
            'strong' =>array('__','__',      null,null),
            'em'     =>array('\'\'','\'\'',  null,null),
            'code'   =>array('@@','@@',      null,null),
            'q'      =>array('^^','^^',      array('lang','cite'),null),
            'acronym'=>array('??','??',      array('lang','title'),null),
            'link'   =>array('[',']',        array('href','hreflang','title'),'w2x_buildlink'),
            'anchor' =>array('~','~',      array('name'),'wikibuildanchor')
        );

        /**
         * liste des balises de type bloc autorisées.
         * Attention, ordre important (p en dernier, car c'est le bloc par defaut..)
         */

         $this->bloctags = array(
             'w2x_title'=>true,
             'w2x_list'=>true,
             'w2x_pre'=>true,
             'w2x_hr'=>true,
             'w2x_blockquote'=>true,
             'w2x_p'=>true
         );

    }

}

// ===================================== fonctions de générateur de code HTML spécifiques à certaines balises inlines


// une partie du code de cette fonction provient de la class wiki2xhtml http://www.neokraft.net/docs/wiki2xhtml/
function w2x_buildlink($contents, $attr){
   $cnt=count($contents);
   $attribut='';
   if($cnt > 1){
      $content=$contents[0];
      $url=$contents[1];
      $lang=(isset($contents[2])?$contents[2]:'');
      $title=(isset($contents[3])?$contents[3]:'');
   }else{
      $url=$contents[0];
      $lang='';
      $title='';
      $content='';
   }

   if (ereg('^(.+)[.](gif|jpg|jpeg|png)$', $url))
   {#
      # On ajoute les dimensions de l'image si locale
      # Idée de Stephanie
      $img_size = NULL;
      if (!ereg('[a-zA-Z]+://', $url))
      {
         if (ereg('^/',$url))
         {
            $path_img = $_SERVER['DOCUMENT_ROOT'] . $url;
         }
         else
         {
            $path_img = $url;
         }
         $img_size = @getimagesize($path_img);
      }

      $html= '<img src="' . $url . '" alt="' . $content . '"';
      $html .= ($lang) ? ' lang="' . $lang . '"' : '';
      $html .= ($title) ? ' title="' . $title . '"' : '';
      $html .= (is_array($img_size)) ? ' ' . $img_size[3] : '';
      $html .= ' />';
   }
   else
   {
      $html = '<a href="' . $url . '"';
      $html .= ($lang) ? ' hreflang="' . $lang . '"' : '';
      $html .= ($title) ? ' title="' . $title . '"' : '';
      $html .= '>' . $content;
      $html .= '</a>';
   }
   return $html;
}

//-------------
/**
 * traite les signes de types liste
 */
class WRB_w2x_list extends WikiRendererBloc {

    private $_previousTag;
    private $_firstItem;
    private $_firstTagLen;

    function __construct($wr) {
        parent::__construct($wr);
        $this->type='list';
        $this->regexp="/^([\*#]+)(.*)/i";
    }

    public function open() {
        $this->_previousTag = $this->_detectMatch[1];
        $this->_firstTagLen = strlen($this->_previousTag);
        $this->_firstItem=true;

        if(substr($this->_previousTag,-1,1) == '#')
            return "<ol>\n";
        else
            return "<ul>\n";
    }

    public function close() {
        $t=$this->_previousTag;
        $str='';

        for($i=strlen($t); $i >= $this->_firstTagLen; $i--){
            $str.=($t{$i-1}== '#'?"</li></ol>\n":"</li></ul>\n");
        }
        return $str;
    }

    public function getRenderedLine(){
        $d=strlen($this->_previousTag) - strlen($this->_detectMatch[1]);
        $str='';

        if( $d > 0 ) { // on remonte d'un cran dans la hierarchie...
            $str=(substr($this->_previousTag, -1, 1) == '#'?"</li></ol>\n</li>\n<li>":"</li></ul>\n</li>\n<li>");
            $this->_previousTag=substr($this->_previousTag,0,-1); // pour être sur...

        }
        elseif( $d < 0 ) { // un niveau de plus
            $c=substr($this->_detectMatch[1],-1,1);
            $this->_previousTag.=$c;
            $str=($c == '#'?"<ol>\n<li>":"<ul>\n<li>");
        }
        else {
            $str=($this->_firstItem ? '<li>':'</li><li>');
        }
        $this->_firstItem=false;
        return $str.$this->_renderInlineTag($this->_detectMatch[2]);

    }

}


//-------------
/**
 * traite les signes de types hr
 */
class WRB_w2x_hr extends WikiRendererBloc {

    function __construct($wr) {
        parent::__construct($wr);
        $this->type      = 'hr';
        $this->regexp    = "/^-{4,} *$/";
        $this->_closeNow = true;
    }

    public function getRenderedLine() {
        return '<hr />';
    }

}

//-------------
/**
 * traite les signes de types titre
 */
class WRB_w2x_title extends WikiRendererBloc {

    private $_minlevel = 1;

    function __construct($wr) {
        parent::__construct($wr);
        $this->type      = 'title';
        $this->regexp    = "/^(\!{1,3})(.*)/";
        $this->_closeNow = true;
        $this->_minlevel = $wr->getConfig()->minHeaderLevel;
    }

    public function getRenderedLine() {
        $hx= $this->_minlevel + 3-strlen($this->_detectMatch[1]);
        return '<h'.$hx.'>'.$this->_renderInlineTag($this->_detectMatch[2]).'</h'.$hx.'>';
    }
}

//-------------
/**
 * traite les signes de type paragraphe
 */
class WRB_w2x_p extends WikiRendererBloc {

    function __construct($wr) {
        parent::__construct($wr);
        $this->type      = 'p';
        $this->regexp    = "/(.*)/i";
        $this->_openTag  = '<p>';
        $this->_closeTag = '</p>';
    }
}

//-------------
/**
 * traite les signes de types pre (pour afficher du code..)
 */
class WRB_w2x_pre extends WikiRendererBloc {

    function __construct($wr) {
        parent::__construct($wr);
        $this->type      = 'pre';
        $this->regexp    = "/^ +(.*)/i";
        $this->_openTag  = '<pre>';
        $this->_closeTag = '</pre>';
    }

    public function getRenderedLine() {
        return $this->_renderInlineTag(substr($line,1));
    }

}


//-------------
/**
 * traite les signes de type blockquote
 */
class WRB_w2x_blockquote extends WikiRendererBloc {

    private $_hasLine=false;

    function __construct($wr) {
        parent::__construct($wr);
        $this->type      = 'bq';
        $this->regexp    = "/^(\>|;:)(.*)/i";
        $this->_openTag  = '<blockquote><p>';
        $this->_closeTag = '</p></blockquote>';
    }

    public function open() {
        $this->_hasLine=false;
        return $this->_openTag;
    }

    public function getRenderedLine() {
        if($this->_hasLine){
            return '<br />'.$this->_renderInlineTag($this->_detectMatch[2]);
        }
        else {
            $this->_hasLine=true;
            return $this->_renderInlineTag($this->_detectMatch[2]);
        }
    }
}

?>
