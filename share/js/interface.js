
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

/************************************************************************/
/* END Miscellaneous functions
/************************************************************************/
