<?php
 
class noticebiblio extends Plugins
{
    // pas besoin d'initialiser quoique ce soit à l'activation/désactivation du plugin
    // il faut toutefois les déclarer pour respecter la cohérence avec la classe parente
    public function enableAction(&$context, &$error) 
	 {
    if(!parent::_checkRights(LEVEL_ADMINLODEL)) { return; }
	 }

    public function disableAction(&$context, &$error) 
	 {
    if(!parent::_checkRights(LEVEL_ADMINLODEL)) { return; }
	 }
/*
    public function preview(&$context)
    {
	 // vérification que l'on est positionné ni dans l'interface d'administration ni dans le template d'affichage des stats
 	 if(!defined('backoffice') || $context['view']['tpl'] != 'jawstatstpl') return;
    if(!parent::_checkRights(LEVEL_REDACTOR)) { return; }
	 $site = C::get('site', 'cfg') ;
	 $param = '' ;
	 $typeGroupe = C::get('typeGroupe') ; 
	 $month = C::get('month') ; 
	 $year = C::get('year') ; 
	 if (!(empty ($month))) { $param .= "&month=".$month ; }
	 if (!(empty ($year)))  { $param .= "&year=".$year ; }
	 // http://statistiques.cleo.cnrs.fr/jawstats/?config=vertigo&year=2010&month=4&view=thismonth.all&lang=fr
	 //C::set('urlstats', "http://statistiques.cleo.cnrs.fr/jawstats/?config=".$site."&view=thismonth.all&lang=fr&clearcache=oui&identifiants=cmV2dWVzc3RhdHM6c3RhdHNfZWRpdG8=".$param);

	 // ajout du fichier de template dans la classe qui gère le contexte	 
	 //C::set('view.base_rep.jawstatstpl', 'statsjawstats');
	
    }
*/
    public function postview(&$context)
    {
/*
    //if (!defined('backoffice') || empty($context['page']) || !(in_array($context['page'], array('dashboard_statistics', 'dashboard_information', 'dashboard_me')))) return;
    if(!parent::_checkRights(LEVEL_REDACTOR)) { return; }		
	 $stats_menu = "<li><a href=\"index.php?do=_statsjawstats_list\">Statistiques éditoriales</a></li>" ;
	 View::$page = preg_replace('/(<ul id="deskContext">\s*<li>.+?<\/li>)/s', "\\1".$stats_menu, View::$page);
*/
 	 if(!defined('backoffice-edition')) return;
    if(!parent::_checkRights(LEVEL_REDACTOR)) { return; }		
	 // temporairement, on positionne le plugin dans Outils
  	 // View::$page = preg_replace('/(Plugins<\/a><\/li>)/', '\\1<li><a href="./?do=_statsjawstats_list">Statistiques éditoriales</a></li>', View::$page);

	if ( (false !== strpos(C::get('do'), 'view')) && (isset($_GET['idparent']))&& ($context['type']['id'] == 33) ) //permet de vérifier que l'on est sur la page de saisie
		{
			$languages['Enrichissement automatique'] = getlodeltextcontents('Enrichissement automatique', 'plugins');
			$languages['Rechercher sur'] = getlodeltextcontents('Rechercher sur', 'plugins');
			$languages['Fournisseur'] = getlodeltextcontents('Fournisseur', 'plugins');
			$languages['le titre'] = getlodeltextcontents('le titre', 'plugins');
			$languages['lisbn'] = getlodeltextcontents("l'ISBN", 'plugins');
			$languages['BUTTON_SEARCH'] = getlodeltextcontents('BUTTON_SEARCH', 'edition');
//              <input type="text" onfocus="if(this.value == '{$languages['BUTTON_SEARCH']}')this.value=''" value="{$languages['BUTTON_SEARCH']}" name="query" class="text">

			$urlplugin = $context['shareurl'].'/plugins/custom/noticebiblio/' ;
			$replace = <<<HTML
						<script type="text/javascript" src="{$context['shareurl']}/js/mootools-1.2.3-core.js"></script>
						<script type="text/javascript" src="{$context['shareurl']}/js/mootools-1.2.3.1-more.js"></script>
						<script type="text/javascript" >
						
						var auteurs=[];//variable global permettant de conserver les noms d'auteurs déjà insérer
						
						var ouvrirRs = function() {
						// type de recherche : titre ou ISBN dans la variable input
						var input = null;
						if($('_title').get('value')) {
							input = 'title';
						} else {
							if($('_isbn').get('value')) {
								input = 'isbn';
							}
						}
	
						if(input) {
							// récupération du fournisseur
							var el = {};
							$$('input[id^=frs]').each(function(item) {
								if(item.get('checked')) {
									this.value = item.get('value');
								}
							}.bind(el));

						// construction de la requête
						var r = new Request.JSON({ 
							onSuccess:function(json, text) {
									var list0 = new Element('ul');
								// suppression des éléments du container pour ajouter les résultats
									$('resultsContainer').empty() ;

								\$each(json, function(item, numero) {
		
								// numero pourrait être utilisé pour donner un id à l'item
									
								// liste des éléments de réponse de l'item courant
									var div = new Element('div', {'styles': {'display': 'block', 'background-color': '#EEEEEE','margin-top':'10px','padding':'5px'}});
									var list = new Element('ul');
									\$each(item, function(it, key) {
										var li = new Element('li', {'text':key+': '+it});
										if ((key=='url') || (key=='ndlr'))
											{
											var li = new Element('li');
											}
											else {var li = new Element('li', {'text':key+': '+it}); }
										li.store('value', it);
										li.store('key', key.toLowerCase());
										this.adopt(li);
									}.bind(list));
									
								
								// inclusion du div contenant une réponse dans le container		
									$('resultsContainer').adopt(div.adopt(list).adopt(new Element('input', {type:'button', value:'Sélectionner cet élément'}).addEvent('click', function() {
										
										
												/*var c=$$('div[class="delete"]')[0].getChildren('a')[0];//les 2 lignes doivent permettrent la suppression
												c.onclick();*/
												
										
										this.getParent().getChildren('ul')[0].getChildren().each(function(item) {
											var value = item.retrieve('value');
											var key = item.retrieve('key');
											//if (key == 'auteur') {
											if (key.indexOf('auteur'.toLowerCase()) == 0) {//permet l'insertion de plusieurs auteurs
											
												if(auteurs.indexOf(value)==-1){
													var t = $$('label[for="auteuroeuvre"]')[0].getNext().getNext().getChildren('a')[0]; // bouton ajouter
													t.onclick(); // déclenchement du onclick pour ajout nouvel auteur
													var nb = document.getElementsByName('persons[auteuroeuvre][maxdegree]')[0].get('value'); // récupération numéro auteur
										  
													var nom=value.substring(value.lastIndexOf(" ",value.length)+1, value.length);//récupération du nom de famille de l'auteur
													var prenom=value.substring(0,value.lastIndexOf(" ",value.length));//récupération du prénom de l'auteur
													
													auteurs.push(value);
													
													$('persons[39]['+nb+'][data][prenom]').set('value',prenom);//insertion du prénom dans le champ
													$('persons[39]['+nb+'][data][nomfamille]').set('value',nom);//insertion du nom de famille dans le champ
													
												} 
											}
											else if($(key)) {
												$(key).set('value', value); //formulaire rempli avec cette ligne pour les autres champs trouvés
												
											}
										});
									})));
									
								});
							}
						});
		r.get('{$urlplugin}resultats.php?type=_'+input+'&frs='+el.value+'&isbn='+$('_isbn').get('value')+'&title='+$('_title').get('value'));
// 		window.open('{$urlplugin}resultats.php?type=_'+input+'&frs='+el.value+'&isbn='+$('isbn').get('value')+'&title='+$('title').get('value'),'','top=10,left=10,width=950,height=650,scrollbars=yes, menubar=yes');//+'&frs='+frs+'&isbn='+isbn+'&title='+title+'&url='+url
	}
};

/*
 function testRadio(radio) {
 	var val="";
   for (var i=0; i<radio.length;i++) {
       if (radio[i].checked) {
   	    // alert("radio cochée = " + radio[i].value)
			 val=radio[i].value;
          }
       }
	return val;  
}

function ouvrirRs(form,urlplugin) {
// on crée un nouvelle fenêtre
//if (form.type[0].checked) { titre = form.title.value ; typeRecherche = form.type[0].value ; isbn = '' ; alert(title);}
//	else { titre = '' ; typeRecherche = form.type[1].value ; isbn = form.isbn.value ; alert(isbn); }
console.log(form);
frs = testRadio(form.frs);
typeRecherche = testRadio(form.type);
isbn = '' ; titre = '' ;
if (typeRecherche == '_title') { titre = form.titre.value ; } else { isbn=form.isbn.value ; }
//isbn=form.isbn.value;
//title=form.title.value;
//alert('Recherche en cours \ntitre : '+titre+'\nISBN : '+isbn+'\nFournisseur : '+frs) ;
//window.open(urlplugin+'resultats.php?type='+typeRecherche+'&frs='+frs+'&isbn='+isbn+'&title='+titre,'','top=10,left=10,width=950,height=650,scrollbars=yes, menubar=yes');//+'&frs='+frs+'&isbn='+isbn+'&title='+title+'&url='+url
return false;
}*/
</script> 

<div id="noticebiblio" class="advancedFunc">
<h4>{$languages['Enrichissement automatique']}</h4>
<dl>
<dt>{$languages['Rechercher sur']}</dt>
<br><br>
<dd>{$languages['le titre']} <input type="text" name="titre" id="_title" /></dd> 
<dd>{$languages['lisbn']}&nbsp; <input type="text" name="isbn" id="_isbn"/></dd>
<br>
<dt>{$languages['Fournisseur']}</dt>
<br><br>
<dd><input name="frs" type="radio" value="aws" id="frs1" checked="checked" onclick="javascript:window.location.reload();"/> Amazon</dd>
<!--<dd><input name="frs" type="radio" value="wcs" id="frs2" "/> Worldcat Basic API</dd>-->
<dd><input name="frs" type="radio" value="wxisbn" id="frs3" onclick="javascript:window.location.reload();"/> Worldcat XISBN</dd> 
<dd><input name="frs" type="radio" value="dws" id="frs4" onclick="javascript:window.location.reload();"  /> Decitre</dd>
<br>
<input type="button" value="{$languages['BUTTON_SEARCH']}" onclick="return ouvrirRs(this.form,'{$urlplugin}');" />
</dl>
<div id="resultsContainer"></div>
</div>
HTML;

	// ajout du bloc contenant le formulaire de recherche biblio à la suite du bloc repéré par advancedFunc
	View::$page = preg_replace('/(<div class="advancedFunc">.+?<\/div>)/s', "\\1".$replace, View::$page);

    }
}

/*
	public function listAction (&$context, &$error)
	{ 

 	View::getView()->renderCached('jawstatstpl') ;

	return "_ajax" ;  // pour sortir le plus facilement voir les sorties dans controller.php

	}

*/

}

