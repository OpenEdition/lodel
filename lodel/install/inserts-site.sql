# ce fichier est en iso-latin. Il est converti en UTF-8 automatiquement. Si ce comportement ne convient pas (si vous avez besoin de caractere non iso-latin, contacter les developpeurs).



# type de publication
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('1','serie_lineaire','série linéaire','sommaire-lineaire','edition-lineaire','publication','1','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('2','serie_hierarchique','série hiérarchique','sommaire-hierarchique','edition-hierarchique','publication','2','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('3','numero','numéro','sommaire-numero','edition-numero','publication','3','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('4','theme','thème','sommaire-hierarchique','edition-theme','publication','4','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('5','regroupement','regroupement','','','publication','5','publications');

# type de document

REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe) VALUES(6,'article','article','article','chargement','1','documents');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe,status) VALUES(11,'objetdelarecension','objet de la recension','','-','2','documents',32);

# type de document annexe
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(7,'documentannexe-lienfichier','sur un fichier','documentannexe-lienfichier','2','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(8,'documentannexe-liendocument','sur un document interne','documentannexe-liendocument','3','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(9,'documentannexe-lienpublication','sur une publication interne','documentannexe-lienpublication','5','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(10,'documentannexe-lienexterne','sur un site externe','documentannexe-lienexterne','6','documents',32);

# le groupe pour tous

REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');

# type d'entree d'index

REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('1','periode','période','periode','chrono','chronos','1','0','0','1','ordre','2');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('4','geographie','géographie','geographie','geo','geos','1','0','0','1','ordre','3');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('2','motcle','mot clé','motscles','mot','mots','1','1','1','0','nom','1');

REPLACE INTO _PREFIXTABLE_typepersonnes (id,type,titre,style,tpl,tplindex,status,ordre) VALUES('1','auteur','auteur','auteurs','auteur','auteurs','1','1');

############# DOCUMENTS ###############
# groupes de champs

REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre) VALUES (1,'grtitre','Groupe titre','documents',1);
REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre) VALUES (2,'grtexte','Groupe du texte','documents',2);
REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre) VALUES (3,'grgestion','Gestion des documents','documents',3);

# champs du groupe titre
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (1,'titre',1,'titre','titre','text','','strip_tags("<i><b>")','text',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (2,'surtitre',1,'surtitre','surtitre','text','','strip_tags("<i><b>")','text',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (3,'soustitre',1,'soustitre','soustitre','text','','strip_tags("<i><b>")','text',3,32);

# champs du groupe texte

REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (6,'resume',2,'resumé','resume','text','','','textarea',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (7,'texte',2,'texte','texte','longtext','','','',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (8,'epigraphe',2,'épigraphe','epigraphe','text','','','',3,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (9,'notebaspage',2,'notes de bas de page','notebaspage','text','','','',3,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (10,'notefin',2,'notes de fin de document','notefin','text','','','',4,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (11,'bibliographie',2,'bibliographie','bibliographie','text','','','',5,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (12,'annexe',2,'annexe','annexe','text','','','',6,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (13,'droitsauteur',2,'droits d''auteur','droitsauteur','text','','','',7,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (14,'erratum',2,'erratum','erratum','text','','','',8,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (15,'ndlr',2,'ndlr','ndlr','text','','','',9,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (16,'historique',2,'historique','historique','text','','','',10,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (17,'pagination',2,'pagination','pagination','text','','','',11,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (18,'lien',2,'lien','lien','text','','','',12,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (19,'commentaire',2,'commentaire','commentaire','text','','','',13,32);

# groupe gestion

REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (31,'fichiersassocies',3,'fichiersassocies','','fichier','','','',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (32,'datepubli',3,'datepubli','datepubli','date','','','',2,32);

################# PUBLICATIONS #################
# groupes de champs

REPLACE INTO  _PREFIXTABLE_groupesdechamps (id,nom,titre,classe,ordre) VALUES (10,'grtitre','Groupe titre','publications',1);


# champs du groupe titre
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (51,'titre',10,'titre','titre','text','','strip_tags("<i><b>")','text',1,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (52,'surtitre',10,'surtitre','surtitre','text','','strip_tags("<i><b>")','text',2,32);
REPLACE INTO  _PREFIXTABLE_champs (id,nom,idgroupe,titre,style,type,condition,traitement,edition,ordre,status) VALUES (53,'soustitre',10,'soustitre','soustitre','text','','strip_tags("<i><b>")','text',3,32);

# champs du groupe texte




