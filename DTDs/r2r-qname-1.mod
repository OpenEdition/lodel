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
<!ENTITY % r2r.titreillustration.qname "%r2r.pfx;titreillustration" >
<!ENTITY % r2r.legendeillustration.qname "%r2r.pfx;legendeillustration" >


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
<!ENTITY % r2r.nompersonne.qname "%r2r.pfx;nompersonne" >
<!ENTITY % r2r.prefix.qname "%r2r.pfx;prefix" >
<!ENTITY % r2r.nomfamille.qname "%r2r.pfx;nomfamille" >
<!ENTITY % r2r.prenom.qname "%r2r.pfx;prenom" >
<!ENTITY % r2r.description.qname "%r2r.pfx;description" >


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
<!ENTITY % r2r.surtitre.qname "%r2r.pfx;surtitre" >
<!ENTITY % r2r.titre.qname "%r2r.pfx;titre" >
<!ENTITY % r2r.soustitre.qname "%r2r.pfx;soustitre" >


<!-- Meta -->
<!ENTITY % r2r.meta.qname "%r2r.pfx;meta" >
<!ENTITY % r2r.infoarticle.qname "%r2r.pfx;infoarticle" >
<!ENTITY % r2r.typedoc.qname "%r2r.pfx;typedoc" >

<!-- end of r2r-qname-1.mod -->
