/************************************************************************/
/*  Met la valeur "true" dans le champ caché "reload" 
/*  du formulaire, puis fait un submit du formulaire. 
/************************************************************************/
	
function reloadForm(){
	document.forms[0].reload.value="true";
	document.forms[0].submit();
}

/************************************************************************/
/*     Confirmation functions
/************************************************************************/

function confirm_delete(id)
{
window.status='go';
	var msg='[@EDITION.CONFIRMATION_DELETE] ?';
	if(confirm(msg)) window.location='index.php?do=delete&id='+id;
}

function popup(url) {
		var fenetre = window.open(url,"","scrollbars,width=400,height=300");
	}

function confirm_depublication()
{
	if(confirm("[@EDITION.CONFIRMATION_UNPUBLISH]")) return true;
	else return false; 
}

/************************************************************************/
/*    END Confirmation functions
/************************************************************************/

/************************************************************************/
/*    Rollover
/************************************************************************/

function rollover(id,nom_image) {
	document.images[id].src="[#SHAREURL]/images/"+nom_image;
}

/************************************************************************/
/*    Fonctions arborescence
/************************************************************************/

function change(obj) {
	var cat = document.getElementById(obj);
	var img = document.getElementById('img_' + obj);
	
	if(cat.style.display=='none') {
		cat.style.display='block';
		img.src="images/plie.png";
	}
	else {
		cat.style.display='none';
		img.src="images/deplie.png";					
	}
}

function developpe_tout(id) {
	var cat = document.getElementById(id);
	var img = document.getElementById('img_' + id);
	cat.style.display = "block";	
	img.src="images/plie.png";
	for(var i = 0 ; i < cat.childNodes.length; i++) {
		if(cat.childNodes[i].nodeName == "DIV" && cat.childNodes[i].id != "") {
			developpe_tout(cat.childNodes[i].id);
		}
	}
}

function reduit(id) {
	var cat = document.getElementById(id);
	var img = document.getElementById('img_' + id);
	cat.style.display = "none";
	img.src="images/deplie.png";				
	for(var i = 0 ; i < cat.childNodes.length; i++) {
		if(cat.childNodes[i].nodeName == "DIV" && cat.childNodes[i].id != "") {
			reduit(cat.childNodes[i].id);
		}
	}				
}

function affiche(id) {
	if(id=="")
		return;

	var cat = document.getElementById(id);			
	var img = document.getElementById('img_' + id);
	cat.style.display = "block";
	img.src="images/plie.png"
	if(cat.parentNode.nodeName == "DIV" && cat.parentNode.style.display == "none" && cat.parentNode.id!="") {
		affiche(cat.parentNode.id);
	}
}

/************************************************************************/
/*   END Fonctions arborescence
/************************************************************************/


/************************************************************************/
/*     functions for advanced feature in the edition of entities
/*
/************************************************************************/

function addlanginmltext(obj,name){
	var index = obj.selectedIndex;
	var lang = obj.options[index].value;
	if (lang=='--') return;
	var langfull = obj.options[index].childNodes[0].nodeValue;

	var block=document.getElementById('mltext_'+name+'_for_copy')
	var clone=block.cloneNode(true);
	clone.style.display="block";

	// label
	var label=clone.getElementsByTagName('label');
	label[0].childNodes[0].nodeValue=langfull+' :';

	// other fields
	clone.setAttribute('id','');
	changeNameAttributes(clone,lang);

	document.getElementById('mltext_'+name).appendChild(clone);
	obj.selectedIndex=0;
}

function deletelanginmltext(obj) 
{
	// get the edit box
	obj=obj.parentNode;
	var edit=obj.getElementsByTagName("input");
	if (edit.length<1) {
		edit=obj.getElementsByTagName("textarea");
	}
	if (edit[0].value.length>0) {
		if (!confirm("[@EDITION.CONFIRMATION_DELETE_MLTEXT]")) return;
	}
	obj.parentNode.removeChild(obj);
}

function addpersons(obj,name) {
	var block=document.getElementById('persons_'+name+'_for_copy');
	var clone=block.cloneNode(true);
	clone.style.display='block';

	degreeobj=document.getElementsByName('persons['+name+'][maxdegree]');

	// other fields
	clone.setAttribute('id','');
	degreeobj[0].value++;
	changeNameAttributes(clone,degreeobj[0].value);
	
	document.getElementById('persons_'+name).appendChild(clone);
	obj.selectedIndex=0;
}

function deletepersons(obj,name) 
{
	// get the edit box
	obj=obj.parentNode;
	var edit=obj.getElementsByTagName("input");
	var bconfirm=false;
	for(i=0; i<edit.length; i++) {
		if (edit[i].value.length>0) bconfirm=true;
	}
	edit=obj.getElementsByTagName("textarea");
	for(i=0; i<edit.length; i++) {
		if (edit[i].value.length>0) bconfirm=true;
	}
	if (edit.length<1) {
		edit=obj.getElementsByTagName("textarea");
	}
	if (bconfirm) {
		if (!confirm("[@EDITION.CONFIRMATION_DELETE_PERSONS]")) return;
	}
	obj.parentNode.removeChild(obj);
	// don't decrease the degree, so that we are sure it is always maximal
}                


function changeNameAttributes(obj,index)
{
	for(i=0; i<obj.childNodes.length; i++) {
		var child=obj.childNodes[i];
		if (child.getAttribute) {
		var name=child.getAttribute('name');
		if (name) {
			newname=name.replace(/__lodel_wildcard/gi,index);
			child.setAttribute('id',newname);
			child.setAttribute('name',newname);
		}
		}
	}
}	
/************************************************************************/
/*     END functions for advanced feature in the edition of entities
/************************************************************************/


/************************************************************************/
/* POOL functions
/* Ajout et édition des entrées d'index lors édition document
/************************************************************************/

function trim(inputString) {
   // Removes leading and trailing spaces from the passed string. Also removes
   // consecutive spaces and replaces it with one space. If something besides
   // a string is passed in (null, custom object, etc.) then return the input.
   if (typeof inputString != "string") { return inputString; }
   var retValue = inputString;
   var ch = retValue.substring(0, 1);
   while (ch == " ") { // Check for spaces at the beginning of the string
      retValue = retValue.substring(1, retValue.length);
      ch = retValue.substring(0, 1);
   }
   ch = retValue.substring(retValue.length-1, retValue.length);
   while (ch == " ") { // Check for spaces at the end of the string
      retValue = retValue.substring(0, retValue.length-1);
      ch = retValue.substring(retValue.length-1, retValue.length);
   }
   while (retValue.indexOf("  ") != -1) { // Note that there are two spaces in the string - look for multiple spaces within the string
      retValue = retValue.substring(0, retValue.indexOf("  ")) + retValue.substring(retValue.indexOf("  ")+1, retValue.length); // Again, there are two spaces in each of the strings
   }
   return retValue; // Return the trimmed string back to the user
} // Ends the "trim" function


function addmember(candidats, membres, textfield){	
	var cand = document.getElementById(candidats);
	var memb = document.getElementById(membres);
	for(i=0; i<cand.length; i++){
		if(cand.options[i].selected){
			opttxt = cand.options[i].text;
			optval = cand.options[i].value;
			dbl = false;
			if(memb.length > 0){
				for(j=0; j < memb.length; j++){
					if(memb.options[j].value == optval){
						dbl = true;
					}
				}
			}
			if(dbl == false){
				newopt = new Option(opttxt, optval);
				memb.options[memb.length] = newopt;
			}
			cand.options[i].selected=false;
		}
	}
	updatetxt(textfield, membres);
}

function removemember(membres, textfield){	
	var memb = document.getElementById(membres);
	if(memb.length>0){
		for(i=0; i < memb.length; i++){
			if(memb.options[i].selected){
				memb.options[i] = null;
				i--;
			}
		}
	}
	updatetxt(textfield, membres);
}


function addnewmember(membres, textfield) {

	var val = prompt("[@EDITION.ENTER_LIST_OF_WORDS]","");
	if (val==null) return;

	var memb = document.getElementById(membres);
	var tab = val.split(",");
	for(i=0; i<tab.length; i++){
		opttxt = trim(tab[i]);
		optval = trim(tab[i]);
		dbl = false;
		if(memb.length > 0){
			for(j=0; j < memb.length; j++){
				if(memb.options[j].value == optval){
					dbl = true;
				}
			}
		}
		if(dbl == false && optval){
			newopt = new Option(opttxt, optval);
			memb.options[memb.length] = newopt;
		}
	}
	updatetxt(textfield, membres);
}


function updatetxt(textfield, selectfield){
	var txt = document.getElementById(textfield);
	var slct = document.getElementById(selectfield);
	var str = ''
	if(slct.length > 0){
		for(i=0; i< slct.length; i++){
			str += slct.options[i].value + ',';
		}
	}
	txt.value = str.substring(0, str.length - 1);
}


function modifymember(members,textfield){
	memb = document.getElementById(members);
	if(memb.length>0){
		for(i=0; i < memb.length; i++){
			if(memb.options[i].selected){
				var val = prompt("",memb.options[i].value);
				if(val == null) break;//val=memb.options[i].value; // get out, stop editing!
				val=val.replace(/,/," ");
				if(val != ''){
					memb.options[i].value = val;
					memb.options[i].text = val;
				}else{
					memb.options[i]=null;
				}

			}
		}
	}
	updatetxt(textfield, members);
}

/************************************************************************/
/* END POOL functions
/************************************************************************/


/************************************************************************/
/* Miscellaneous functions
/************************************************************************/

function affiche_image(visible) {
	var dis;
	
	if(visible)
		dis = "inline";
	else
		dis = "none";
	
	for (i=0;i<document.getElementsByTagName("img").length; i++) {
		
		if (document.getElementsByTagName("img").item(i).className == "illus"){
			document.getElementsByTagName("img").item(i).style.display = dis;
		}
	}
}

function tronquer($long1, $long2, $texte) {
	$debut = "" ;
	$fin = substr($texte, -$long2);

	if(($off = strlen($texte)-$long2) > 0) {
		$debut = substr($texte, 0, $off);
		if(strlen($debut) > $long1 + 5) { // debut est suffisament long
			$debut = substr($debut, 0, $long1)."(...)";
		}
	}
	return $debut.$fin;
}

function popup(url,nom) {
// remove an attribute if you don't want it to show up
// options : menubar,toolbar,location,directories,status,scrollbars,resizable,width=640,height=480
	var fenetre = window.open(url,"","status,scrollbars,width=800,height=600");
}

function ajouter_entite(obj) {
	var index = obj.selectedIndex;
	window.location=obj.options[index].value;
}

/************************************************************************/
/* END Miscellaneous functions
/************************************************************************/
