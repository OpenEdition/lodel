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


<!-- ...................................................................... -->
<!-- r2r Qname Module ................................................... -->
<!-- file: r2r-qname-1.mod


     PUBLIC "-//MY COMPANY//ELEMENTS XHTML r2r Qnames 1.0//EN"
     SYSTEM "http://www.lodel.org/DTDs/r2r-qname-1.mod"


     xmlns:r2r="http://www.lodel.org/xmlns/r2r"
     ...................................................................... -->


<!-- Declare the default value for prefixing of this module's elements -->
<!-- Note that the NS.prefixed will get overridden by the XHTML Framework or
     by a document instance. -->
<!ENTITY % NS.prefixed "IGNORE" >
<!ENTITY % r2r.prefixed "%NS.prefixed;" >


<!-- Declare the actual namespace of this module -->
<!ENTITY % r2r.xmlns "http://www.lodel.org/xmlns/r2r" >


<!-- Declare the default prefix for this module -->
<!ENTITY % r2r.prefix "r2r" >


<!-- Declare the prefix for this module -->
<![%r2r.prefixed;[
<!ENTITY % r2r.pfx "%r2r.prefix;:" >
]]>
<!ENTITY % r2r.pfx "" >


<!-- Declare the xml namespace attribute for this module -->
<![%r2r.prefixed;[
<!ENTITY % r2r.xmlns.extra.attrib
    "xmlns:%r2r.prefix;   %URI.datatype;  #FIXED  '%r2r.xmlns;'" >
]]>
<!ENTITY % r2r.xmlns.extra.attrib "" >


<!-- Declare the extra namespace that should be included in the XHTML
     elements -->
<!ENTITY % XHTML.xmlns.extra.attrib  "%r2r.xmlns.extra.attrib;" >


<!-- ..........................................................-->
<!-- Now declare the qualified names for all of the elements in the
     module -->


<!ENTITY % r2r.article.qname "%r2r.pfx;article" >


<!-- Texte -->
<!ENTITY % r2r.texte.qname "%r2r.pfx;texte" >
<!ENTITY % r2r.section1.qname "%r2r.pfx;section1" >
<!ENTITY % r2r.section2.qname "%r2r.pfx;section2" >
<!ENTITY % r2r.section3.qname "%r2r.pfx;section3" >
<!ENTITY % r2r.section4.qname "%r2r.pfx;section4" >
<!ENTITY % r2r.titredoc.qname "%r2r.pfx;titredoc" >
<!ENTITY % r2r.legendedoc.qname "%r2r.pfx;legendedoc" >
<!ENTITY % r2r.citation.qname "%r2r.pfx;citation" >


<!-- Resume -->
<!ENTITY % r2r.resume.qname "%r2r.pfx;resume" >


<!-- Note de bas de page -->
<!ENTITY % r2r.notebaspage.qname "%r2r.pfx;notebaspage" >


<!-- Bibliographie       -->
<!ENTITY % r2r.bibliographie.qname "%r2r.pfx;bibliographie" >
<!ENTITY % r2r.divbiblio.qname "%r2r.pfx;divbiblio" >


<!-- Groupe Auteur -->
<!ENTITY % r2r.grauteur.qname "%r2r.pfx;grauteur" >
<!ENTITY % r2r.auteur.qname "%r2r.pfx;auteur" >
<!ENTITY % r2r.affiliation.qname "%r2r.pfx;affiliation" >
<!ENTITY % r2r.nompersonne.qname "%r2r.pfx;nompersonne" >
<!ENTITY % r2r.prefix.qname "%r2r.pfx;prefix" >
<!ENTITY % r2r.nomfamille.qname "%r2r.pfx;nomfamille" >
<!ENTITY % r2r.prenom.qname "%r2r.pfx;prenom" >
<!ENTITY % r2r.courriel.qname "%r2r.pfx;courriel" >


<!-- Groupe Geographie -->
<!ENTITY % r2r.grgeographie.qname "%r2r.pfx;grgeographie" >
<!ENTITY % r2r.geographie.qname "%r2r.pfx;geographie" >


<!-- Groupe Periode -->
<!ENTITY % r2r.grperiode.qname "%r2r.pfx;grperiode" >
<!ENTITY % r2r.periode.qname "%r2r.pfx;periode" >


<!-- Groupe MotCle -->
<!ENTITY % r2r.grmotcle.qname "%r2r.pfx;grmotcle" >
<!ENTITY % r2r.motcle.qname "%r2r.pfx;motcle" >


<!-- Groupe Titre -->
<!ENTITY % r2r.grtitre.qname "%r2r.pfx;grtitre" >
<!ENTITY % r2r.titre.qname "%r2r.pfx;titre" >
<!ENTITY % r2r.soustitre.qname "%r2r.pfx;soustitre" >


<!-- Meta -->
<!ENTITY % r2r.meta.qname "%r2r.pfx;meta" >
<!ENTITY % r2r.infoarticle.qname "%r2r.pfx;infoarticle" >
<!ENTITY % r2r.typedoc.qname "%r2r.pfx;typedoc" >






<!-- ....................................................................... -->
<!-- R2R DTD  ............................................................. -->
<!-- file: r2r-xhtml-1.dtd -->


<!-- This is the DTD driver for r2r 1.0.


     Please use this formal public identifier to identify it:


         "-//MY COMPANY//DTD XHTML R2R 1.0//EN"


     And this namespace for r2r-unique elements:


         xmlns:r2r="http://www.example.com/xmlns/r2r"
-->
<!ENTITY % XHTML.version  "-//MY COMPANY//DTD XHTML R2R 1.0//EN" >


<!-- reserved for use with document profiles -->
<!ENTITY % XHTML.profile  "" >


<!-- prefix obligatoire -->
<!ENTITY % r2r.prefixed "INCLUDE" >


<!-- Tell the framework to use our qualified names module as an extra qname
driver -->
<!ENTITY % xhtml-qname-extra.mod
     SYSTEM "r2r-qname-1.mod" >


<!-- Define the Content Model for the framework to use -->
<!ENTITY % xhtml-model.mod
     SYSTEM "r2r-model-1.mod" >


<!-- Disable bidirectional text support -->
<!ENTITY % XHTML.bidi  "IGNORE" >


<!-- Bring in the XHTML Framework -->
<!ENTITY % xhtml-framework.mod
     PUBLIC "-//W3C//ENTITIES XHTML Modular Framework 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-framework-1.mod"  
"http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-framework-1.mod" >
%xhtml-framework.mod;



<!-- Text Module (Required)  ............................... -->
<!ENTITY % xhtml-text.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Text 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-text-1.mod" >
%xhtml-text.mod;


<!-- Hypertext Module (required) ................................. -->
<!ENTITY % xhtml-hypertext.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Hypertext 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-hypertext-1.mod"  
"http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-hypertext-1.mod" >
%xhtml-hypertext.mod;


<!-- Lists Module (required)  .................................... -->
<!ENTITY % xhtml-list.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Lists 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-list-1.mod" >
%xhtml-list.mod;


<!-- My Elements Module   ........................................ -->
<!ENTITY % r2r-elements.mod
     SYSTEM "r2r-elements-1.mod" >
%r2r-elements.mod;


<!-- Inline Presentation ........................................ -->
<!ENTITY % xhtml-inlpres.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Inline Presentation 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-inlpres-1.mod"  
"http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-inlpres-1.mod" >
%xhtml-inlpres.mod;


<!-- XHTML Images module  ........................................ -->
<!ENTITY % xhtml-image.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Images 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-image-1.mod"  
"http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-image-1.mod" >
%xhtml-image.mod;



<!-- Document Metainformation Module  ............................ -->
<!ENTITY % xhtml-meta.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Metainformation 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-meta-1.mod" >
<!-- %xhtml-meta.mod; -->


<!-- Document Structure Module (required)  ....................... -->
<!ENTITY % xhtml-struct.mod
     PUBLIC "-//W3C//ELEMENTS XHTML Document Structure 1.0//EN"
            "http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-struct-1.mod"  
"http://www.w3.org/TR/xhtml-modularization/DTD/xhtml-struct-1.mod" >
<!-- %xhtml-struct.mod; -->





<!-- ajout venant de xhtml-legacy-1 .......................-->
<!-- il faut decider si on continue a accepter ce genre d'attribut -->



<!ENTITY % align.attrib
     "align        ( left | center | right | justify ) #IMPLIED"
>


<!ENTITY % name.attrib
     "name           ID                       #IMPLIED"
>



<!ATTLIST %div.qname;
      %align.attrib;
>


<!ATTLIST %p.qname;
      %align.attrib;
>

