# ce fichier est en iso-latin. Il est converti en UTF-8 automatiquement. Si ce comportement ne convient pas (si vous avez besoin de caractere non iso-latin, contacter les developpeurs).


#ifndef LODELLIGHT
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('serie_lineaire','sommaire-lineaire','edition-lineaire');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('serie_hierarchique','sommaire-hierarchique','edition-hierarchique');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('numero','sommaire-numero','edition-numero');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('theme','sommaire-hierarchique','edition-theme');
REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('regroupement','','');
REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('article','article','1');
REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');
REPLACE INTO _PREFIXTABLE_typeentrees (id,nom,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('1','periode','période','periodes','chrono','chronos','1','0','0','1','ordre','2');
REPLACE INTO _PREFIXTABLE_typeentrees (id,nom,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('4','geographie','géographie','geographies','geo','geos','1','0','0','1','ordre','3');
REPLACE INTO _PREFIXTABLE_typeentrees (id,nom,titre,style,tpl,tplindex,status,lineaire,newimportable,useabrev,tri,ordre) VALUES('2','motcle','mot clé','motscles','mot','mots','1','1','1','0','nom','1');
REPLACE INTO _PREFIXTABLE_typepersonnes (id,nom,titre,style,tpl,tplindex,status,ordre) VALUES('1','auteur','auteur','auteurs','auteur','auteurs','1','1');
#else
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('album_photo','sommaire-album','edition-album');
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('theme_photo','sommaire-photo','edition-photo');
#REPLACE INTO _PREFIXTABLE_typepublis (nom,tpl,tpledit) VALUES('rubrique','sommaire-rubrique','edition-rubrique');
#REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('article','article','1');
#REPLACE INTO _PREFIXTABLE_typedocs (nom,tpl,status) VALUES('photo','photo','1');
#REPLACE INTO _PREFIXTABLE_groupes (id,nom) VALUES('1','tous');
#endif
