<!--

   LODEL - Logiciel d'Edition ELectronique.
   Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
   Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
   Home page: http://www.lodel.org
   E-Mail: lodel@lodel.org
                             All Rights Reserved
      This program is free software; you can redistribute it and/or modify
      it under the terms of the GNU General Public License as published by
      the Free Software Foundation; either version 2 of the License, or
      (at your option) any later version.
      This program is distributed in the hope that it will be useful,
      but WITHOUT ANY WARRANTY; without even the implied warranty of
      MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
      GNU General Public License for more details.
      You should have received a copy of the GNU General Public License
      along with this program; if not, write to the Free Software
      Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.-->


<!-- ...................................................................... -->
<!-- r2r Model Module  ................................................... -->
<!-- file: r2r-model-1.mod


     PUBLIC "-//MY COMPANY//ELEMENTS XHTML r2r Model 1.0//EN"
     SYSTEM "http://www.lodel.org/DTDs/r2r-model-1_0.mod"


     xmlns:r2r="http://www.lodel.org/xmlns/r2r"
     ...................................................................... -->


<!-- Define the content model for Misc.extra -->
<!ENTITY % Misc.class
     "| %r2r.article.qname; ">


<!-- ....................  Inline Elements  ...................... -->


<!ENTITY % HeadOpts.mix  
     "( %meta.qname; )*" >


<!ENTITY % I18n.class "" >


<!ENTITY % InlStruct.class "%br.qname; | %span.qname;" >


<!ENTITY % InlPhras.class
     "| %em.qname; | %strong.qname; | %dfn.qname; | %code.qname; 
      | %samp.qname; | %kbd.qname; | %var.qname; | %cite.qname; 
      | %abbr.qname; | %acronym.qname; | %q.qname; " >


<!ENTITY % InlPres.class
     "| %b.qname; | %big.qname; | %i.qname; | %small.qname; 
      | %sub.qname; | %sup.qname; | %tt.qname; " >


<!ENTITY % Anchor.class "| %a.qname; " >


<!ENTITY % InlSpecial.class "| %img.qname; " >


<!ENTITY % Inline.extra "" >


<!-- %Inline.class; includes all inline elements,
     used as a component in mixes
-->
<!ENTITY % Inline.class
     "%InlStruct.class;
      %InlPhras.class;
      %InlPres.class;
      %Anchor.class;
      %InlSpecial.class;"
>


<!-- %InlNoAnchor.class; includes all non-anchor inlines,
     used as a component in mixes
-->
<!ENTITY % InlNoAnchor.class
     "%InlStruct.class;
      %InlPhras.class;
      %InlPres.class;
      %InlSpecial.class;"
>


<!-- %InlNoAnchor.mix; includes all non-anchor inlines
-->
<!ENTITY % InlNoAnchor.mix
     "%InlNoAnchor.class;
      %Misc.class;"
>


<!-- %Inline.mix; includes all inline elements, including %Misc.class;
-->
<!ENTITY % Inline.mix
     "%Inline.class;
      %Misc.class;"
>


<!-- .....................  Block Elements  ...................... -->


<!ENTITY % Heading.class 
     "%h1.qname; | %h2.qname; | %h3.qname; 
      | %h4.qname; | %h5.qname; | %h6.qname;" >


<!ENTITY % List.class "%ul.qname; | %ol.qname; | %dl.qname;" >


<!ENTITY % BlkStruct.class "%p.qname; | %div.qname;" >


<!ENTITY % BlkPhras.class 
     "| %pre.qname; | %blockquote.qname; | %address.qname;" >


<!ENTITY % BlkPres.class "" >


<!ENTITY % Block.extra "" >


<!-- %Block.class; includes all block elements,
     used as an component in mixes
-->
<!ENTITY % Block.class
     "%BlkStruct.class;
      %BlkPhras.class;
      %BlkPres.class;
      %Block.extra;"
>


<!-- %Block.mix; includes all block elements plus %Misc.class;
-->
<!ENTITY % Block.mix
     "%Heading.class;
      | %List.class;
      | %Block.class;
      %Misc.class;"
>


<!-- ................  All Content Elements  .................. -->


<!-- %Flow.mix; includes all text content, block and inline
-->
<!ENTITY % Flow.mix
     "%Heading.class;
      | %List.class;
      | %Block.class;
      | %Inline.class;
      %Misc.class;"
>


<!-- special content model for pre element -->
<!ENTITY % pre.content
    "( #PCDATA
     | %Inline.class; )*"
>


<!-- end of r2r-model-1.mod -->
