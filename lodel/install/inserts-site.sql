#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003-2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#
#  Home page: http://www.lodel.org
#
#  E-Mail: lodel@lodel.org
#
#                            All Rights Reserved
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation; either version 2 of the License, or
#     (at your option) any later version.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

# ce fichier est en iso-latin. Il est converti en UTF-8 automatiquement. Si ce comportement ne convient pas (si vous avez besoin de caractere non iso-latin, contacter les developpeurs).



# type de publication
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('1','serie_lineaire','série linéaire','sommaire-lineaire','edition-lineaire','creation-serie','1','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('2','serie_hierarchique','série hiérarchique','sommaire-hierarchique','edition-hierarchique','creation-serie','2','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('3','numero','numéro','sommaire-numero','edition-numero','creation-numero','3','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('4','rubrique','rubrique','sommaire-hierarchique','edition-rubrique','creation-rubrique','4','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('5','regroupement','regroupement','','','creation-regroupement','5','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledition,tplcreation,ordre,classe) VALUES('12','regroupement-documentsannexes','regroupement de documents annexes','','','creation-regroupement','6','publications');

# type de document
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe) VALUES(6,'article','article','article','document','1','documents');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe,statut) VALUES(11,'objetdelarecension','objet de la recension','','-','2','documents',32);

# type de document annexe
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,statut) VALUES(7,'documentannexe-lienfichier','sur un fichier','documentannexe-lienfichier','2','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,statut) VALUES(8,'documentannexe-liendocument','sur un document interne','documentannexe-liendocument','3','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,statut) VALUES(9,'documentannexe-lienpublication','sur une publication interne','documentannexe-lienpublication','5','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,statut) VALUES(10,'documentannexe-lienexterne','sur un site externe','documentannexe-lienexterne','6','documents',32);

# le groupe pour tous

REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');

# type d'entree d'index

REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('1','periode','période','periode','chrono','chronos','1','0','0','1','ordre','2');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('4','geographie','géographie','geographie','geo','geos','1','0','0','1','ordre','3');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,statut,lineaire,nvimportable,utiliseabrev,tri,ordre) VALUES('2','motcle','mot clé','motscles','mot','mots','1','1','1','0','nom','1');

REPLACE INTO _PREFIXTABLE_typepersonnes (id,type,titre,style,titredescription,styledescription,tpl,tplindex,statut,ordre) VALUES('1','auteur','auteur','auteur','description de l''auteur','descriptionauteur','auteur','auteurs','1','1');

############# DOCUMENTS ###############
# groupes de champs

REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES (1,'grtitre','Groupe titre','documents',1,32);
REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES (2,'grtexte','Groupe du texte','documents',2,32);
REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES (3,'grgestion','Gestion des documents','documents',3,32);

# champs du groupe titre
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (1,'titre',1,'titre','title','text','','strip_tags("<i><b>")','text',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (2,'surtitre',1,'surtitre','surtitre','text','','strip_tags("<i><b>")','text',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (3,'soustitre',1,'soustitre','subtitle','text','','strip_tags("<i><b>")','text',3,32);

# champs du groupe texte

REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (6,'resume',2,'resumé','resume','text','','','textarea',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (7,'texte',2,'texte','texte','longtext','','','',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (8,'epigraphe',2,'épigraphe','epigraphe','text','','','',3,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (9,'notebaspage',2,'notes de bas de page','notebaspage','text','','','',3,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (10,'notefin',2,'notes de fin de document','notefin','text','','','',4,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (11,'bibliographie',2,'bibliographie','bibliographie','text','','','',5,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (12,'annexe',2,'annexe','annexe','text','','','',6,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (13,'droitsauteur',2,'droits d''auteur','droitsauteur','text','','','',7,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (14,'erratum',2,'erratum','erratum','text','','','',8,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (15,'ndlr',2,'ndlr','ndlr','text','','','',9,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (16,'historique',2,'historique','historique','text','','','',10,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (17,'pagination',2,'pagination','pagination','text','','','',11,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (18,'lien',2,'lien','lien','text','','','',12,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (19,'commentaire',2,'commentaire','commentaire','text','','','',13,32);

# groupe gestion

REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (31,'fichiersassocies',3,'fichiersassocies','','fichier','','','',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (32,'datepubli',3,'datepubli','datepubli','date','','','',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (33,'image',3,'image','','image','','','',3,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (34,'fichiersource',3,'fichiersource','','fichier','','','',4,32);


################# PUBLICATIONS #################
# groupes de champs

REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES (10,'grtitre','Groupe titre','publications',1,32);
REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre,statut) VALUES (11,'grgestion','Gestion des publications','publications',2,32);

# champs du groupe titre
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (51,'titre',10,'titre','titre','text','','strip_tags("<i><b>")','text',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (52,'surtitre',10,'surtitre','surtitre','text','','strip_tags("<i><b>")','text',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (53,'soustitre',10,'soustitre','soustitre','text','','strip_tags("<i><b>")','text',3,32);

# groupe gestion

REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,statut) VALUES (61,'image',11,'image','','fichier','','','',1,32);

# champs du groupe texte




