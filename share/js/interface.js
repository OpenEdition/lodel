function getXMLHttpRequest() {
	var xhr=null;
	if(window.XMLHttpRequest) // Firefox et autres
		xhr = new XMLHttpRequest(); 
	else if(window.ActiveXObject){ // IE sux
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	}
	else { // XMLHttpRequest non supporté par le navigateur 
		xhr = false; 
	}
	return xhr;
}

function preserveDatas()
{
	if(document.getElementById('edit_ent'))
	{
		document.edit_ent.submit();
	}
}

function displayObj(item, obj){
	var obj = document.getElementById(obj);
	if(obj.className == "displayOff"){
		obj.className = "displayOn"
	}else{
		obj.className = "displayOff"
	}
}

function select_url(obj) {
		var index = obj.selectedIndex;
		if (obj.options[index].value) window.location=obj.options[index].value;
	}

function switchDisp(obj){
	var obj = document.getElementById(obj);
	//alert(obj.className);
	if(obj.className == "butTextOn"){
		obj.className = "butText";
	}else{
		obj.className = "butTextOn";
	}
}

function switchUploadType(item, name, act){
	var obj = document.getElementById(item);

	switch(act){
		case 'serverfile' :
		case 'upload' :
			obj.name = name;
			obj.value = act;
			obj.disabled = "";
		break;
		case 'delete' :
			obj.name = name;
		break;
	}
}

function change_language(obj) {
	var index = obj.selectedIndex;
	window.location="index.php?do=set&lo=users&lang="+obj.options[index].value;
}

function showFocus(obj) {  
	document.getElementById(obj).className = "focusOn";
}

function hideFocus(obj) {
	document.getElementById(obj).className = "focusOff";
}

function showProcessMsg(obj, msg){
	obj = document.getElementById(obj);
	obj.innerHTML = msg;
	obj.className = 'displayOn';
}

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

function deletelanginmltext(obj, msg) 
{
	// get the edit box
	obj=obj.parentNode;
	var edit=obj.getElementsByTagName("input");
	if (edit.length<1) {
		edit=obj.getElementsByTagName("textarea");
	}
	if (edit[0].value.length>0) {
		if (!confirm(msg)) return;
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

function deletepersons(obj,name,msg) 
{
	// get the edit box
	obj=obj.parentNode.parentNode;
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
		if (!confirm(msg)) return;
	}
	obj.parentNode.removeChild(obj);
	// don't decrease the degree, so that we are sure it is always maximal
}                


function changeNameAttributes(obj,index)
{
	for(i=0; i<obj.childNodes.length; i++) {
		var child=obj.childNodes[i];
		
		if (child.getAttribute) {
			
			if (child.innerHTML) {
				child.innerHTML = child.innerHTML.replace(/__lodel_wildcard/gi,index);
	 			//alert(child.innerHTML)
			}else{
				child.name = child.name.replace(/__lodel_wildcard/gi,index);
				//alert(child.name)
			}
		
		}
	}
}

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


function ajouter_entite(obj) {
			var index = obj.selectedIndex;
			window.location=obj.options[index].value;
}


// POOL

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

function addnewmember(membres, textfield, msg) {

	var val = prompt(msg,"");
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

// END POOL

function confirm_delete(id, msg, rootdir, idp)
{
	window.status='go';
	if(confirm(msg)) window.location=rootdir+'/index.php?do=delete&id='+id+'&idparent='+idp;
}
	
function popup(url) {
		var fenetre = window.open(url,"","scrollbars,width=400,height=300");
	}

function confirm_depublication(msg)
{
	return(confirm(msg));
}

function confirmation()
{
	if(confirm("[@COMMON.CONFIRMATION]")) {
		return true;
	}
	else {
		return false; 
	}
}

/* Focus sur page de login */

function focusOnLogin(){
	var loginS = document.getElementById('loginscreen');
	if(loginS){
		document.getElementById('login').focus();
	}
}

/* IM - select all messages */
function im_selectAll() 
{
	var table = document.getElementById('internal_messaging');
	if(table) {
		selects = table.getElementsByTagName('input');
		if(selects) {
			for(var i=0;i<selects.length;i++) {
			with(selects[i]) {
				if(type == 'checkbox') {
					if(name == "im_select_all") continue;
					checked = (checked == "") ? "checked" : "";
				}
			}
			}
		}
	}
}

function manageDesk(shareurl, msgHide, msgShow, site, errorXHR, errorSave) 
{
	var desk = document.getElementById('lodel-globalDesk');
	var img = document.getElementById('lodelGlobalDeskDisplayer-img');
	if(desk && img) {
		if(desk.style.display != 'none'){
			img.src = shareurl + '/images/fleche_bas_gris.png';
			img.alt = img.title = msgShow;
			desk.style.display='none';
		}else{
			img.src = shareurl + '/images/fleche_haut_gris.png';
			img.alt = img.title = msgHide;
			desk.style.display='block';
		}
		var xhr = getXMLHttpRequest();
		if(xhr) {
			xhr.open("POST", shareurl + '/ajax/desk.php', false);
			xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
			xhr.send('site='+site);
			if(xhr.readyState == 4 && xhr.status == 200 && xhr.responseText != 'ok') {
				alert(errorSave);
			}
		} else {
			alert(errorXHR);
		}
	}
}

/* IM - select action */
function im_action(action, dir, message)
{
	switch(action) {
	
	case 'view': window.location='index.php?do=view&lo=internal_messaging';
	break;
	case 'rest': document.getElementById('im_restore').value=1;document.getElementById('im_form').submit();
	break;
	
	case 'delSelected':
	if(confirm(message +' ?')) document.getElementById('im_form').submit();
	else document.getElementById('actions').selectedIndex = 0;
	break;
	
	case 'delAll':
	if(confirm(message +' ?')) window.location='index.php?do=delete&lo=internal_messaging&all=1&directory='+dir;
	else document.getElementById('actions').selectedIndex = 0;
	break;
	
	default: break;
	}
}

/************************************************************************/
/* END Miscellaneous functions
/************************************************************************/