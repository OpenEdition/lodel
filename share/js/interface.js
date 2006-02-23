function displayObj(item, obj){
	var obj = document.getElementById(obj);
	if(obj.className == "displayOff"){
		obj.className = "displayOn"
	}else{
		obj.className = "displayOff"
	}
}



function showFocus(obj) {  
	document.getElementById(obj).className = "focusOn";
}

function hideFocus(obj) {
	document.getElementById(obj).className = "focusOff";
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
		var name=child.getAttribute('name');
		if (name) {
			newname=name.replace(/__lodel_wildcard/gi,index);
			child.setAttribute('id',newname);
			child.setAttribute('name',newname);
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

function confirm_delete(id, msg)
{
	window.status='go';
	if(confirm(msg)) window.location='index.php?do=delete&id='+id;
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

/************************************************************************/
/* END Miscellaneous functions
/************************************************************************/
