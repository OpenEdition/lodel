# ce fichier est en iso-latin. Il est converti en UTF-8 automatiquement. Si ce comportement ne convient pas (si vous avez besoin de caractere non iso-latin, contacter les developpeurs).



# type de publication
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('1','serie_lineaire','série linéaire','sommaire-lineaire','edition-lineaire','publication','1','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('2','serie_hierarchique','série hiérarchique','sommaire-hierarchique','edition-hierarchique','publication','2','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('3','numero','numéro','sommaire-numero','edition-numero','publication','3','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('4','theme','thème','sommaire-hierarchique','edition-theme','publication','4','publications');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tpledit,tplcreation,ordre,classe) VALUES('5','regroupement','regroupement','','','publication','5','publications');

# type de document

REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe) VALUES(6,'article','article','article','chargement','1','documents');
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tpl,tplcreation,ordre,classe) VALUES(11,'objetdelarecension','objet de la recension','','-','2','documents');

# type de document annexe
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(7,'documentannexe-lienfichier','sur un fichier','documentannexe-lienfichier','2','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(8,'documentannexe-liendocument','sur un document interne','documentannexe-liendocument','3','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(9,'documentannexe-lienpublication','sur une publication interne','documentannexe-lienpublication','5','documents',32);
REPLACE INTO _PREFIXTABLE_types (id,type,titre,tplcreation,ordre,classe,status) VALUES(10,'documentannexe-lienexterne','sur un site externe','documentannexe-lien','6','documents',32);

# le groupe pour tous

REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');

# type d'entree d'index

REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('1','periode','période','periode','chrono','chronos','1','0','0','1','ordre','2');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('4','geographie','géographie','geographie','geo','geos','1','0','0','1','ordre','3');
REPLACE INTO _PREFIXTABLE_typeentrees (id,type,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('2','motcle','mot clé','motcle','mot','mots','1','1','1','0','nom','1');

REPLACE INTO _PREFIXTABLE_typepersonnes (id,type,titre,style,tpl,tplindex,status,ordre) VALUES('1','auteur','auteur','auteurs','auteur','auteurs','1','1');


##
##REPLACE INTO _PREFIXTABLE_types (type,tpl,tpledit) VALUES('album_photo','sommaire-album','edition-album');
##REPLACE INTO _PREFIXTABLE_types (type,tpl,tpledit) VALUES('theme_photo','sommaire-photo','edition-photo');
##REPLACE INTO _PREFIXTABLE_types (type,tpl,tpledit) VALUES('rubrique','sommaire-rubrique','edition-rubrique');
##REPLACE INTO _PREFIXTABLE_typedocs (type,tpl,status) VALUES('article','article','1');
##REPLACE INTO _PREFIXTABLE_typedocs (type,tpl,status) VALUES('photo','photo','1');
##REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');
##endif
