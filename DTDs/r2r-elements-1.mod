<!-- ...................................................................... -->
<!-- r2r Elements Module ................................................... -->
<!-- file: r2r-elements-1.mod


     PUBLIC "-//MY COMPANY//ELEMENTS XHTML r2r Elements 1.0//EN"
     SYSTEM "http://www.lodel.org/DTDs/r2r-elements-1.mod"


     xmlns:r2r="http://www.lodel.org/xmlns/r2r"
     ...................................................................... -->


<!-- r2r Module



     This module defines a simple r2r item structure
-->


<!-- Define the global namespace attributes -->
<![%r2r.prefixed;[
<!ENTITY % r2r.xmlns.attrib
    "%NS.decl.attrib;"
>
]]>
<!ENTITY % r2r.xmlns.attrib
     "xmlns %URI.datatype;  #FIXED '%r2r.xmlns;'"
>


<!-- Define a common set of attributes for all module elements -->
<!ENTITY % r2r.Common.attrib
         "%r2r.xmlns.attrib;
      id               ID                   #IMPLIED"
>



<!-- Define the elements and attributes of the module -->


<!ENTITY % r2r.article.content
        "( %r2r.texte.qname;? , %r2r.resume.qname;* , %r2r.notebaspage.qname;? ,
        %r2r.grauteur.qname;? , %r2r.grgeographie.qname;?, %r2r.grperiode.qname;? ,
        %r2r.grmotcle.qname;?, %r2r.grtitre.qname;?, %r2r.meta.qname;? )" >


<!ELEMENT %r2r.article.qname; %r2r.article.content; >


<!--...... -->
<!-- Texte -->
<!ENTITY % r2r.texte.content
        "( #PCDATA | %Flow.mix; | %r2r.section1.qname; | %r2r.section2.qname;
        | %r2r.section3.qname; | %r2r.section4.qname;
        | %r2r.titredoc.qname; | %r2r.legendedoc.qname; | %r2r.citation.qname; )*" >


<!ELEMENT %r2r.texte.qname; %r2r.texte.content; >
<!ATTLIST %r2r.texte.qname; lang NMTOKEN #REQUIRED>


<!ENTITY % r2r.section.content "( #PCDATA | %Inline.mix;  )*">


<!ELEMENT %r2r.section1.qname; %r2r.section.content; >
<!ELEMENT %r2r.section2.qname; %r2r.section.content; >
<!ELEMENT %r2r.section3.qname; %r2r.section.content; >
<!ELEMENT %r2r.section4.qname; %r2r.section.content; >


<!ENTITY % r2r.titredoc.content "( #PCDATA | %Inline.mix;  )*">
<!ELEMENT %r2r.titredoc.qname; %r2r.titredoc.content; >


<!ENTITY % r2r.legendedoc.content "( #PCDATA | %Inline.mix;  )*">
<!ELEMENT %r2r.legendedoc.qname; %r2r.legendedoc.content; >


<!ENTITY % r2r.citation.content "( #PCDATA | %Inline.mix;  )*">
<!ELEMENT %r2r.citation.qname; %r2r.citation.content; >



<!-- Resume -->
<!ENTITY % r2r.resume.content "( #PCDATA | %Flow.mix;  )*">
<!ELEMENT %r2r.resume.qname; %r2r.resume.content; >
<!ATTLIST %r2r.resume.qname; lang NMTOKEN #REQUIRED>


<!-- Note de bas de page -->
<!ENTITY % r2r.notebaspage.content "( #PCDATA | %Flow.mix;  )*">
<!ELEMENT %r2r.notebaspage.qname; %r2r.notebaspage.content; >



<!-- Bibliographie       -->
<!ENTITY % r2r.bibliographie.content "( #PCDATA | %Flow.mix; | %r2r.divbiblio.qname; )* " >
<!ELEMENT %r2r.bibliographie.qname; %r2r.bibliographie.content; >


<!ENTITY % r2r.divbiblio.content "( #PCDATA | %Flow.mix;  )*">
<!ELEMENT %r2r.divbiblio.qname; %r2r.divbiblio.content; >



<!-- Groupe Auteur -->
<!ENTITY % r2r.grauteur.content "( %r2r.auteur.qname; )+ " >
<!ELEMENT %r2r.grauteur.qname; %r2r.grauteur.content; >


<!ENTITY % r2r.auteur.content "( %r2r.nompersonne.qname; , %r2r.affiliation.qname;? , %r2r.courriel.qname;?)" >


<!ELEMENT %r2r.auteur.qname; %r2r.auteur.content; >
<!ATTLIST %r2r.auteur.qname; ordre CDATA #REQUIRED >


<!ENTITY % r2r.nompersonne.content "( %r2r.prefix.qname;? , %r2r.nomfamille.qname; , %r2r.prenom.qname; )" >
<!ELEMENT %r2r.nompersonne.qname; %r2r.nompersonne.content; >



<!ENTITY % r2r.nomfamille.content "( #PCDATA )" >
<!ELEMENT %r2r.nomfamille.qname; %r2r.nomfamille.content; >


<!ENTITY % r2r.prenom.content "( #PCDATA )" >
<!ELEMENT %r2r.prenom.qname; %r2r.prenom.content; >


<!ENTITY % r2r.affiliation.content "(#PCDATA | %Flow.mix; )* " >
<!ELEMENT %r2r.affiliation.qname; %r2r.affiliation.content; >


<!ENTITY % r2r.courriel.content "( #PCDATA )" >
<!ELEMENT %r2r.courriel.qname; %r2r.courriel.content; >




<!-- Groupe Geographie -->
<!ENTITY % r2r.grgeographie.content "( %r2r.geographie.qname; )+" >
<!ELEMENT %r2r.grgeographie.qname; %r2r.grgeographie.content; >


<!ENTITY % r2r.geographie.content "( #PCDATA )" >
<!ELEMENT %r2r.geographie.qname; %r2r.geographie.content; >


<!-- Groupe Periode -->
<!ENTITY % r2r.grperiode.content "( %r2r.periode.qname; )+" >
<!ELEMENT %r2r.grperiode.qname; %r2r.grperiode.content; >


<!ENTITY % r2r.periode.content "( #PCDATA )" >
<!ELEMENT %r2r.periode.qname; %r2r.periode.content; >


<!-- Groupe MotCle -->


<!ENTITY % r2r.grmotcle.content "( %r2r.motcle.qname; )+" >
<!ELEMENT %r2r.grmotcle.qname; %r2r.grmotcle.content; >


<!ENTITY % r2r.motcle.content "( #PCDATA )" >
<!ELEMENT %r2r.motcle.qname; %r2r.motcle.content; >



<!-- Groupe Titre -->
<!ENTITY % r2r.grtitre.content "(%r2r.titre.qname; , %r2r.soustitre.qname;?)">
<!ELEMENT %r2r.grtitre.qname; %r2r.grtitre.content; >


<!ENTITY % r2r.titre.content "( #PCDATA | %Inline.mix; )*" >
<!ELEMENT %r2r.titre.qname; %r2r.titre.content; >



<!ENTITY % r2r.soustitre.content "( #PCDATA | %Inline.mix; )*" >
<!ELEMENT %r2r.soustitre.qname; %r2r.soustitre.content; >



<!-- Meta -->
<!ENTITY % r2r.meta.content "(%r2r.infoarticle.qname; )" >
<!ELEMENT %r2r.meta.qname; %r2r.meta.content; >


<!ENTITY % r2r.infoarticle.content "(%r2r.typedoc.qname; )" >
<!ELEMENT %r2r.infoarticle.qname; %r2r.infoarticle.content; >



<!ENTITY % r2r.typedoc.content "(#PCDATA)" >
<!ELEMENT %r2r.typedoc.qname; %r2r.typedoc.content; >



<!-- end of r2r-elements-1.mod -->
