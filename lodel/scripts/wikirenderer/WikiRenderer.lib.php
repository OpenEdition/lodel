<?php

/**
 * Bibliotheque d'objets permettant de tranformer un texte, contenant des signes de formatages
 * simples de type wiki, en un autre format tel que XHTML 1.0/strict
 * @author Laurent Jouanneau <jouanneau@netcourrier.com>
 * @copyright 2003-2004 Laurent Jouanneau
 * @module Wiki Renderer
 * @version 2.0dev-php5
 * @since 28/11/2004
 * http://ljouanneau.com/softs/wikirenderer/
 * Thanks to all users who found bugs : Loic, Edouard Guerin, Sylvain, Ludovic L.
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
 * Contributeurs :
 *    Edouard Guérin <eguerin@icitrus.net> (Adapatation PHP5)
 */

define('WIKIRENDERER_PATH', dirname(__FILE__).'/');
define('WIKIRENDERER_VERSION', '2.0dev-php5');

/**
 * Implémente les propriétés d'un tag inline wiki et le fonctionnement pour la génération
 * du code html correspondant
 */

class WikiTag {

    private $name;
    private $beginTag;
    private $endTag;
    private $useSeparator = true;
    private $attribute = array();
    private $builderFunction = null;

    private $contents = array();
    private $separatorCount = 0;
    private $isDummy = false;

    function __construct($name, $properties){
        $this->name=$name;
        $this->beginTag=$properties[0];
        $this->endTag=$properties[1];
        if($this->name == 'dummie')
            $this->isDummy=true;

        if(is_null($properties[2])) {
            $this->attribute=array();
            $this->useSeparator=false;
        }
        else {
            $this->attribute=$properties[2];
            $this->useSeparator=(count($this->attribute)>0);
        }
        $this->builderFunction=$properties[3];
    }

    public function addContent($string, $escape=true){
        if(!isset($this->contents[$this->separatorCount]))
            $this->contents[$this->separatorCount]='';

        if($escape)
            $this->contents[$this->separatorCount] .= htmlspecialchars($string);
        else
            $this->contents[$this->separatorCount] .= $string;
    }

    public function addSeparator() {
        $this->separatorCount++;
    }

    public function getBeginTag() {
        return $this->beginTag;
    }

    public function getEndTag() {
        return $this->endTag;
    }

    public function getNumberSeparator() {
        return $this->separatorCount;
    }

    public function useSeparator() {
        return $this->useSeparator;
    }

    public function isDummy() {
        return $this->isDummy;
    }

    public function getHtmlContent() {
        if(is_null($this->builderFunction)) {
            $attr='';
            if($this->useSeparator) {
                $cntattr=count($this->attribute);
                $count=($this->separatorCount > $cntattr?$cntattr:$this->separatorCount);
                for($i=1;$i<=$count;$i++) {
                   $attr.=' '.$this->attribute[$i-1].'="'.$this->contents[$i].'"';
                }
             }
             if(isset($this->contents[0]))
                 return '<'.$this->name.$attr.'>'.$this->contents[0].'</'.$this->name.'>';
             else
                 return '<'.$this->name.$attr.' />';
          }
          else {
             $fct=$this->builderFunction;
             return $fct($this->contents, $this->attribute);
          }
    }

}

/**
 * Moteur permettant de transformer les tags wiki inline d'une chaine en équivalent HTML
 */
class WikiInlineParser {

    private $resultline='';
    private $error=false;
    private $listTag=array();
    private $str=array();
    private $splitPattern='';
    private $checkWikiWord=false;
    private $checkWikiWordFunction=null;
    private $simpletags = null;
    private $_separator;
    private $escapeHtml=true;
    private $end=0;

    /**
     * constructeur
     * @param   array    $inlinetags liste des tags permis
     * @param   string   caractère séparateur des différents composants d'un tag wiki
     */

    function __construct(
                         $inlinetags,
                         $simpletags,
                         $separator='|',
                         $checkWikiWord=false,
                         $funcCheckWikiWord=null,
                         $escapeHtml=true
                        ) {

        foreach($inlinetags as $name=>$prop){
            $this->listTag[$prop[0]]=new WikiTag($name,$prop);

            $this->splitPattern.=preg_replace ( '/([^\w\s\d])/', '\\\\\\1',$prop[0]).')|(';
            if($prop[1] != $prop[0])
                $this->splitPattern.=preg_replace ( '/([^\w\s\d])/', '\\\\\\1',$prop[1]).')|(';
        }
        foreach($simpletags as $tag=>$html){
            $this->splitPattern.=preg_replace ( '/([^\w\s\d])/', '\\\\\\1',$tag).')|(';
        }

        $this->simpletags=$simpletags;
        $this->_separator=$separator;
        $this->checkWikiWord=$checkWikiWord;
        $this->checkWikiWordFunction=$funcCheckWikiWord;
        $this->escapeHtml=$escapeHtml;
    }

    /**
     * fonction principale du parser.
     * @param   string   $line avec des eventuels tag wiki
     * @return  string   chaine $line avec les tags wiki transformé en HTML
     */
    public function parse($line) {
        $this->error=false;

        $this->str=preg_split('/('.$this->splitPattern.'\\'.$this->_separator.')|(\\\\)/',$line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $this->end=count($this->str);
        if($this->end > 1) {
            $firsttag=new WikiTag('dummie',array('','', null,'wikibuilddummie'));
            $pos=-1;
            return $this->_parse($firsttag, $pos);
        }
        else {
            if($this->escapeHtml) {
                if($this->checkWikiWord && $this->checkWikiWordFunction !== null)
                    return  $this->_doCheckWikiWord(htmlspecialchars($line));
                else
                    return htmlspecialchars($line);
            }
            else {
                if($this->checkWikiWord && $this->checkWikiWordFunction !== null)
                    return  $this->_doCheckWikiWord($line);
                else
                    return $line;
            }

        }
    }

    /**
     * coeur du parseur. Appelé récursivement
     */

    private function _parse($tag, &$posstart) {

        $checkNextTag=true;
        $checkBeginTag=true;

        // on parcours la chaine,  morceau aprés morceau
        for($i=$posstart+1; $i < $this->end; $i++) {

            $t=&$this->str[$i];
            // a t-on un antislash ?
            if($t=='\\'){
                if($checkNextTag){
                    $t=''; // oui -> on l'efface et on ignore le tag (on continue)
                    $checkNextTag=false;
                }
                else {
                    $tag->addContent('\\',false);
                }

                // est-ce un séparateur ?
            }
            elseif($t == $this->_separator) {
                if($tag->isDummy() || !$checkNextTag)
                    $tag->addContent($this->_separator,false);
                elseif($tag->useSeparator()) {
                    $checkBeginTag=false;
                    $tag->addSeparator();
                }
                else {
                    $tag->addContent($this->_separator,false);
                }
                // a-t-on une balise de fin du tag ?
            }
            elseif($checkNextTag && $tag->getEndTag() == $t && !$tag->isDummy()) {
                $posstart=$i;
                return $tag->getHtmlContent();

            // a-t-on une balise de debut de tag quelconque ?
            }
            elseif($checkBeginTag && $checkNextTag && isset($this->listTag[$t]) ) {

                $content = $this->_parse(clone $this->listTag[$t],$i); // clone indispensable sinon plantage !!!
                if($content)
                    $tag->addContent($content,false);
                else {
                    if($tag->getNumberSeparator() == 0 && $this->checkWikiWord && $this->checkWikiWordFunction !== null) {
                        if($this->escapeHtml)
                            $tag->addContent($this->_doCheckWikiWord(htmlspecialchars($t)),false);
                        else
                            $tag->addContent($this->_doCheckWikiWord($t),false);
                    }
                    else
                        $tag->addContent($t,$this->escapeHtml);
                }

            // a-t-on un saut de ligne forcé ?
            }
            elseif($checkNextTag && $checkBeginTag && isset($this->simpletags[$t])) {
                $tag->addContent($this->simpletags[$t],false);
            }
            else {
                if($tag->getNumberSeparator() == 0 && $this->checkWikiWord && $this->checkWikiWordFunction !== null) {
                    if($this->escapeHtml)
                        $tag->addContent($this->_doCheckWikiWord(htmlspecialchars($t)),false);
                    else
                        $tag->addContent($this->_doCheckWikiWord($t),false);
                }
                else
                    $tag->addContent($t,$this->escapeHtml);
                    $checkNextTag=true;
            }
        }
        if(!$tag->isDummy()) {
            //--- on n'a pas trouvé le tag de fin
            // on met en erreur
            $this->error=true;
            return false;
        }
        else
            return $tag->getHtmlContent();
    }

    private function _doCheckWikiWord($string) {
        if(preg_match_all("/(?<=\b)[A-Z][a-z]+[A-Z0-9]\w*/", $string, $matches)){
            $fct=$this->checkWikiWordFunction;
            $match = array_unique($matches[0]); // il faut avoir une liste sans doublon, à cause du str_replace plus loin...
            $string=str_replace($match, $fct($match), $string);
        }
        return $string;
    }

    public function getError() {
        return $this->error;
    }

}



/**
 * classe de base pour la transformation des élements de type bloc
 * @abstract
 */
class WikiRendererBloc {

    /**
     * @var string code identifiant le type de bloc
     * @access protected
     */
    protected $type='';

    /**
     * @var string  chaine contenant le tag XHTML d'ouverture du bloc
     * @access protected
     */
    protected $_openTag='';

    /**
     * @var string  chaine contenant le tag XHTML de fermeture du bloc
     * @access protected
     */
    protected $_closeTag='';

    /**
     * @var boolean indique si le bloc doit être immediatement fermé aprés détection
     * @access protected
     */
    protected $_closeNow=false;

    /**
     * @var WikiRenderer      référence à la classe principale
     * @access protected
     */
    protected $engine=null;

    /**
     * @var array liste des élements trouvés par l'expression régulière regexp
     * @access protected
     */
    protected $_detectMatch=null;

    /**
     * @var string expression régulière permettant de reconnaitre le bloc
     * @access protected
     */
    protected $regexp='';

    /**
     * constructeur à surcharger pour définir les valeurs des différentes proprietés
     * @param WikiRender $wr l'objet moteur wiki
     */

    function __construct($wr) {
        $this->engine = $wr;
    }

    /**
     * renvoi une chaine correspondant à l'ouverture du bloc
     * @return string
     * @access public
     */

    public function open() {
        return $this->_openTag;
    }

    /**
     * renvoi une chaine correspondant à la fermeture du bloc
     * @return string
     * @access public
     */

    public function close() {
        return $this->_closeTag;
    }

    /**
     * indique si le bloc doit etre immédiatement fermé
     * @return string
     * @access public
     */

    public function closeNow() {
        return $this->_closeNow;
    }

    /**
     * test si la chaine correspond au debut ou au contenu d'un bloc
     * @param string $string
     * @return boolean true: appartient au bloc
     * @access public
     */

    public function detect($string) {
        return preg_match($this->regexp, $string, $this->_detectMatch);
    }

    /**
     * renvoi la ligne, traitée pour le bloc. A surcharger éventuellement.
     * @return string
     * @access public
     */

    public function getRenderedLine() {
        return $this->_renderInlineTag($this->_detectMatch[1]);
    }

    /**
     * renvoi le type du bloc en cours de traitement
     * @return string
     * @access public
     */

    public function getType() {
        return $this->type;
    }

    /**
     * définit la liste des élements trouvés par l'expression régulière regexp
     * @return array
     * @access public
     */

    public function setMatch($match) {
        $this->_detectMatch = $match;
    }

    /**
     * renvoi la liste des élements trouvés par l'expression régulière regexp
     * @return array
     * @access public
     */

    public function getMatch() {
        return $this->_detectMatch;
    }

    /**
     * traite le rendu des signes de type inline (qui se trouvent necessairement dans des blocs
     * @param   string  $string une chaine contenant une ou plusieurs balises wiki
     * @return  string  la chaine transformée en XHTML
     * @access protected
     * @see WikiRendererInline
     */

    protected function _renderInlineTag($string) {
        return $this->engine->getInlineParser()->parse($string);
    }

    /**
     * détection d'attributs de bloc (ex:  >°°attr1|attr2|attr3°° la citation )
     * @todo à terminer pour une version ulterieure
     * @access protected
     */

    protected function _checkAttributes(&$string) {
        $bat=$this->engine->config->blocAttributeTag;
        if(preg_match("/^$bat(.*)$bat(.*)$/",$string,$result)) {
            $string=$result[2];
            return explode($this->engine->config->inlineTagSeparator,$result[1]);
        } else
            return false;
    }

}

require(WIKIRENDERER_PATH . 'WikiRenderer.conf.php5');



/**
 * Moteur de rendu. Classe principale à instancier pour transformer un texte wiki en texte XHTML.
 * utilisation :
 *      $ctr = new WikiRenderer();
 *      $monTexteXHTML = $ctr->render($montexte);
 */

class WikiRenderer {

    /**
     * @var string contient la version HTML du texte analysé
     * @access private
     */

    private $_newtext;

    /**
     * @var boolean
     * @access private
     */

    private $_isBlocOpen=false;

    /**
     * @var WikiRendererBloc element bloc ouvert en cours
     * @access private
     */

    private $_currentBloc;

    /**
     * @var array liste des differents types de blocs disponibles
     * @access private
     */

    private $_blocList= array();

    /**
     * @var array liste de paramètres pour le moteur
     * @access private
     */

    private $params=array();

    /**
     * @var WikiInlineParser analyseur pour les tags wiki inline
     * @access private
     */

    private $inlineParser=null;

    /**
     * liste des lignes où il y a une erreur wiki
     * @access private
     */

    private $errors;

    /**
     * @var WikiRendererConfig objet de configuration, permet de modifier
     * @see WikiRendererConfig
     * @access private
     */

    private $config=null;

    /**
     * instancie les différents objets pour le rendu des elements inline et bloc.
     */

    function __construct($config=null) {
        if(is_null($config))
            $this->config = new WikiRendererConfig();
        else
            $this->config=$config;

        $this->_currentBloc = new WikiRendererBloc($this); // bloc 'fantome'
        $this->inlineParser = new WikiInlineParser(
                                                   $this->config->inlinetags,
                                                   $this->config->simpletags,
                                                   $this->config->inlineTagSeparator,
                                                   $this->config->checkWikiWord,
                                                   $this->config->checkWikiWordFunction,
                                                   $this->config->escapeSpecialChars
                                                  );

        foreach($this->config->bloctags as $name=>$ok) {
            $name='WRB_'.$name;
            if($ok) $this->_blocList[] = new $name($this);
        }
    }

    /**
     * Methode principale qui transforme les tags wiki en tag XHTML
     * @param   string  $texte le texte à convertir
     * @return  string  le texte converti en XHTML
     * @access public
     */
    public function render($texte) {

        // on remplace les \r (mac), les \n (unix) et les \r\n (windows) par un autre caractère pour découper proprement
        $lignes=preg_split("/\015\012|\015|\012/",$texte);

        $this->_newtext=array();
        $this->_isBlocOpen=false;
        $this->errors=false;
        $this->_currentBloc = new WikiRendererBloc($this);

        // parcours de l'ensemble des lignes du texte
        foreach($lignes as $num=>$ligne){

            if($ligne == '') { // pas de trim à cause des pre
                // ligne vide
                $this->_closeBloc();
            }
            else {

                // detection de debut de bloc (liste, tableau, hr, titre)
                foreach($this->_blocList as $bloc) {
                    if($bloc->detect($ligne))
                        break;
                }

                // c'est le debut d'un bloc (ou ligne d'un bloc en cours)
                if($bloc->getType() != $this->_currentBloc->getType()) {
                    $this->_closeBloc(); // on ferme le precedent si c'etait un different
                    $this->_currentBloc= $bloc;
                    if($this->_openBloc()) {
                        $this->_newtext[]=$this->_currentBloc->getRenderedLine();
                    }
                    else {
                        $this->_newtext[]=$this->_currentBloc->getRenderedLine();
                        $this->_newtext[]=$this->_currentBloc->close();
                        $this->_isBlocOpen = false;
                        $this->_currentBloc = new WikiRendererBloc($this);
                    }

                }
                else {
                    $this->_currentBloc->setMatch($bloc->getMatch());
                    $this->_newtext[]=$this->_currentBloc->getRenderedLine();
                }
                if($this->inlineParser->getError()) {
                    $this->errors[$num+1]=$ligne;
                }
            }
        }

       $this->_closeBloc();
       return implode("\n",$this->_newtext);
    }

    /**
     * renvoi l'objet de configuration
     * @access public
     * @see WikiRendererConfig
     * @return WikiRendererConfig
     */

    public function getConfig() {
        return $this->config;
    }

    /**
     * Retourne l'objet inlineParser (WikiInlineParser) utilisé dans le moteur
     * @access public
     * @see WikiInlineParser
     * @return WikiInlineParser
     */

    public function getInlineParser() {
        return $this->inlineParser;
    }

    /**
     * renvoi la liste des erreurs detectées par le moteur
     * @access public
     * @return array
     */

    public function getErrors() {
        return $this->errors;
    }

    /**
     * renvoi la version de wikirenderer
     * @access public
     * @return string   version
     */
    public function getVersion(){
       return WIKIRENDERER_VERSION;
    }

    /**
     * ferme un bloc
     * @access private
     */

    private function _closeBloc() {
        if($this->_isBlocOpen) {
            $this->_isBlocOpen=false;
            $this->_newtext[]=$this->_currentBloc->close();
            $this->_currentBloc = new WikiRendererBloc($this);
        }
    }

    /**
     * ouvre un bloc et le referme eventuellement suivant sa nature
     * @return boolean  indique si le bloc reste ouvert ou pas
     * @access private
     */

    private function _openBloc() {
        if(!$this->_isBlocOpen) {
            $this->_newtext[]=$this->_currentBloc->open();
            $this->_isBlocOpen=true;
            return !$this->_currentBloc->closeNow();
        }
        else
            return true;
    }

}


?>
