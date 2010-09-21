
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
alert('Recherche en cours \ntitre : '+titre+'\nISBN : '+isbn+'\nFournisseur : '+frs) ;
//window.open(urlplugin+'resultats.php?type='+typeRecherche+'&frs='+frs+'&isbn='+isbn+'&title='+titre,'','top=10,left=10,width=950,height=650,scrollbars=yes, menubar=yes');//+'&frs='+frs+'&isbn='+isbn+'&title='+title+'&url='+url
return false;
}

