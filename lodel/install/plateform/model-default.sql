# <model>
# <lodelversion>0.7</lodelversion>
# <date>2004-03-16</date>
# <description>
# Modele de Revues.org
Modele par defaut de Lodel 0.7
# </description>
# <author>
# Marin Dacos
# </author>
# </model>
#  
#------------

DELETE FROM __LODELTP__champs;
DELETE FROM __LODELTP__groupesdechamps;
DELETE FROM __LODELTP__types;
DELETE FROM __LODELTP__typepersonnes;
DELETE FROM __LODELTP__typeentrees;
DELETE FROM __LODELTP__typeentites_typeentites;
DELETE FROM __LODELTP__typeentites_typeentrees;
DELETE FROM __LODELTP__typeentites_typepersonnes;
# # Database: 'lodeldevel_lodelia'# 
#
# Dumping data for table 'champs'
#

INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('1', 'titre', '14', 'Titre du document', 'title', 'text', '+', '', '', 'text', '1', '1', '20040315225936', 'Document sans titre', 'Titre du document.', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;Lien;Appel de Note');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('2', 'surtitre', '14', 'Surtitre du document', 'surtitre', 'text', '*', '', '', '', '1', '2', '20040305110612', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien;Appel de Note');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('3', 'soustitre', '14', 'Sous-titre du document', 'subtitle', 'text', '*', '', '', 'text', '1', '3', '20040303165409', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien;Appel de Note');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('7', 'texte', '2', 'Texte du document', 'texte', 'longtext', '*', '', '', '', '1', '2', '20040225110942', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('9', 'notebaspage', '2', 'Notes de bas de page', 'notebaspage', 'text', '*', '', '', '', '32', '5', '20040315230247', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('10', 'notefin', '2', 'Notes de fin de document', 'notefin', 'text', '*', '', '', '', '32', '6', '20040305153434', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('11', 'bibliographie', '2', 'Bibliographie du document', 'bibliographie', 'longtext', '*', '', '', '', '1', '8', '20040316093618', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('12', 'annexe', '2', 'Annexes du document', 'annexe', 'longtext', '*', '', '', '', '1', '7', '20040316093630', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('77', 'droitsauteur', '1', 'Droits d\'auteur', 'droitsauteur', 'tinytext', '*', '', '', 'text', '1', '6', '20040303160603', 'Propriété intellectuelle', 'Droits relatifs au document.', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('14', 'erratum', '16', 'Erratum', 'erratum', 'text', '*', '', '', '', '1', '9', '20040305155858', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('15', 'ndlr', '16', 'Ndlr', 'ndlr', 'text', '*', '', '', '', '1', '10', '20040305155846', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('16', 'historique', '16', 'Historique', 'historique', 'text', '*', '', '', '', '1', '11', '20040305155929', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('76', 'pagination', '1', 'Pagination du document', 'pagination', 'tinytext', '*', '', '', 'text', '1', '5', '20040303160333', '', 'Ne pas ajouter "pp."', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('75', 'noticebiblio', '1', 'Notice bibliographique du document', 'noticebiblio', 'tinytext', '*', '', '', '', '1', '4', '20040305155049', '', 'Référence complète permettant de citer le document.', 'xhtml:fontstyle;xhtml:phrase;xhtml:block;Lien');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('79', 'commentaireinterne', '3', 'Commentaire interne sur le document', 'commentaire', 'text', '*', '', '', '', '32', '7', '20040315230548', '', 'Commentaire destiné à l\'équipe rédactionnelle et ne devant pas être publié en ligne.', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('31', 'fichiersassocies', '3', 'Fichiers associés au document', '', 'fichier', '*', '', '', '', '32', '1', '20040316093545', '', 'Ce champ est un champ utilisé en interne par Lodel. Ne pas le modifier.', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('32', 'datepubli', '1', 'Date de la publication électronique', 'datepubli', 'date', '*', '', '', 'editable', '32', '2', '20040305095316', 'today', 'Date de publication du texte intégral en ligne', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('51', 'titre', '10', 'Titre de la publication', 'title', 'text', '+', '', '', 'text', '1', '1', '20040305103053', 'Publication sans titre', '', 'xhtml:fontstyle;xhtml:phrase;Lien');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('52', 'surtitre', '10', 'Surtitre de la publication', 'surtitre', 'text', '*', '', '', '', '1', '2', '20040305115809', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('53', 'soustitre', '10', 'Sous-titre de la publication', 'soustitre', 'text', '*', '', '', 'text', '1', '3', '20040303155344', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('89', 'icone', '17', 'Icône de la publication', '', 'image', '*', '', '', '', '1', '1', '20040305100306', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('65', 'resume', '15', 'Résumé', 'resume:fr, abstract:en, riassunto:it, extracto:es, zusammenfassung:de', 'mltext', '*', '', '', 'textarea10', '1', '1', '20040305095427', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:block;Lien;Appel de Note;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('66', 'fichiersource', '3', 'Fichier source', '', 'fichier', '*', '', '', '', '32', '5', '20040223174419', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('67', 'introduction', '18', 'Introduction de la publication', '', 'text', '*', '', '', '', '1', '1', '20040305100234', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('74', 'importversion', '3', 'Version de l\'importation', '', 'tinytext', '*', '', '', '', '32', '6', '20040223174408', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('83', 'langue', '1', 'Langue du document', 'langue', 'lang', '*', '', '', 'editable', '1', '8', '20040305155702', 'fr', 'fr : français\ren : anglais\rit : italien\rru : russe\res : espagnol\rde : allemand', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('86', 'datepublipapier', '1', 'Date de publication sur papier', 'datepublipapier', 'date', '*', '', '', 'editable', '1', '3', '20040304100627', '', 'Date la publication du document sur papier.', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('87', 'prioritaire', '1', 'Document prioritaire ?', '', 'boolean', '*', '', '', '', '1', '9', '20040305095025', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('88', 'icone', '1', 'Icône du document', '', 'image', '*', '', '', 'editable', '1', '10', '20040307115736', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('90', 'datepubli', '17', 'Date de publication électronique', '', 'date', '*', '', '', 'editable', '32', '2', '20040304133602', 'today', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('91', 'datepublipapier', '17', 'Date de publication papier', '', 'date', '*', '', '', 'editable', '1', '3', '20040305120250', '', '', '');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('92', 'noticebiblio', '17', 'Notice bibliographique décrivant la publication', '', 'tinytext', '*', '', '', '', '1', '4', '20040305100245', '', '', 'xhtml:fontstyle;xhtml:phrase');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('93', 'commentaireinterne', '13', 'Commentaire interne sur la publication', '', 'text', '*', '', '', '', '32', '3', '20040305115905', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('94', 'erratum', '18', 'Erratum au sujet de la publication', '', 'text', '*', '', '', '', '1', '2', '20040305100227', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('95', 'ndlr', '18', 'Note de la rédaction au sujet de la publication', '', 'text', '*', '', '', '', '1', '3', '20040305100217', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('96', 'historique', '18', 'Historique de la publication', '', 'text', '*', '', '', '', '1', '4', '20040305100209', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, traitement, filtrage, edition, statut, ordre, maj, defaut, commentaire, balises) VALUES ('97', 'prioritaire', '13', 'Publication prioritaire ?', '', 'boolean', '*', '', '', '', '1', '4', '20040305115749', '', '', '');

#
# Dumping data for table 'groupesdechamps'
#

INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('1', 'grmeta', 'documents', 'Groupe métadonnées', '32', '4', '20040303175835', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('2', 'grtexte', 'documents', 'Groupe du texte', '32', '2', '20040223173046', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('3', 'grgestion', 'documents', 'Groupe gestion des documents', '32', '6', '20040303170953', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('10', 'grtitre', 'publications', 'Groupe titre', '32', '1', '20040305053528', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('13', 'grgestion', 'publications', 'Gestion des publications', '32', '2', '20040305053437', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('14', 'grtitre', 'documents', 'Groupe du titre', '1', '1', '20040223173058', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('15', 'grresumes', 'documents', 'Groupe résumés', '32', '3', '20040303175102', 'Résumés du document');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('16', 'graddenda', 'documents', 'Groupe addenda', '32', '5', '20040303175057', 'Ensemble de remarques additionnelles');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('17', 'grmetadonnees', 'publications', 'Groupe des métadonnées', '32', '3', '20040304133434', '');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, statut, ordre, maj, commentaire) VALUES ('18', 'graddenda', 'publications', 'Groupe addenda', '32', '4', '20040304135521', '');

#
# Dumping data for table 'types'
#

INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1073', 'breve', 'article', '', '1', '20040309174230', 'documents', 'document', '11', 'Brève', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1055', 'collection', 'sommaire-hierarchique', 'edition-hierarchique', '1', '20040309174230', 'publications', 'creation-serie', '1', 'Collection', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1056', 'numero', 'sommaire-hierarchique', 'edition-rubrique', '1', '20040309174230', 'publications', 'creation-rubrique', '3', 'Numéro', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1057', 'rubrique', 'sommaire-hierarchique', 'edition-rubrique', '1', '20040309174230', 'publications', 'creation-rubrique', '4', 'Rubrique', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1058', 'regroupement', '', '', '1', '20040309174230', 'publications', 'creation-regroupement', '6', 'Regroupement', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1059', 'compte rendu', 'article', '', '1', '20040309174230', 'documents', 'document', '12', 'Compte rendu', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1060', 'chronique', 'article', '', '1', '20040309174230', 'documents', 'document', '15', 'Chronique', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1061', 'note de lecture', 'article', '', '1', '20040309174230', 'documents', 'document', '13', 'Note de lecture', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1062', 'presentation', 'article', '', '1', '20040309174230', 'documents', 'document', '14', 'Présentation', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1071', 'volume', 'sommaire-hierarchique', 'edition-rubrique', '1', '20040309174230', 'publications', 'creation-rubrique', '2', 'Volume', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1072', 'colloque', 'sommaire-hierarchique', 'edition-rubrique', '1', '20040309174230', 'publications', 'creation-rubrique', '5', 'Colloque', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1063', 'regroupement-documentsannexes', '', '', '1', '20040309174230', 'publications', 'creation-regroupement', '7', 'Regroupement de documents annexes', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1064', 'documentannexe-lienfichier', '', '', '32', '20040309174230', 'documents', 'documentannexe-lienfichier', '16', 'Vers un fichier', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1065', 'documentannexe-liendocument', '', '', '32', '20040309174230', 'documents', 'documentannexe-liendocument', '18', 'Vers un document interne au site', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1066', 'documentannexe-lienpublication', '', '', '32', '20040309174230', 'documents', 'documentannexe-lienpublication', '19', 'Vers une publication interne au site', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1067', 'documentannexe-lienexterne', '', '', '32', '20040309174230', 'documents', 'documentannexe-lienexterne', '17', 'Vers un site externe', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1068', 'article', 'article', '', '1', '20040309174230', 'documents', 'document', '9', 'Article', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1069', 'articlevide', 'article', '', '1', '20040309174230', 'documents', 'document', '20', 'Article vide', '0');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1070', 'objetdelarecension', 'article', '', '1', '20040309174230', 'documents', 'document', '21', 'Objet de la recension', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1074', 'editorial', 'article', '', '1', '20040309174230', 'documents', 'document', '8', 'Editorial', '1');
INSERT INTO __LODELTP__types (id, type, tpl, tpledition, statut, maj, classe, tplcreation, ordre, titre, import) VALUES ('1075', 'actualite', 'article', '', '1', '20040309174230', 'documents', 'document', '10', 'Annonce et actualité', '1');

#
# Dumping data for table 'typepersonnes'
#

INSERT INTO __LODELTP__typepersonnes (id, type, titre, style, titredescription, styledescription, tpl, tplindex, ordre, statut, maj) VALUES ('1076', 'auteur', 'Auteur', 'auteur', 'description de l\'auteur', 'descriptionauteur', 'auteur', 'auteurs', '1', '1', '20040309174230');
INSERT INTO __LODELTP__typepersonnes (id, type, titre, style, titredescription, styledescription, tpl, tplindex, ordre, statut, maj) VALUES ('1077', 'directeur de publication', 'Directeur de la publication', 'directeurdepublication', 'description de la personne', 'descriptionpersonne', 'auteur', 'auteurs', '2', '1', '20040309174230');

#
# Dumping data for table 'typeentrees'
#

INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('1079', 'motcle', 'Index par mots clés', 'motscles:fr,keywords:en', 'mot', 'mots', '1', '1', '1', '1', '0', 'nom', '20040309174230');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('1078', 'periode', 'Index chronologique', 'periode', 'chrono', 'chronos', '3', '1', '0', '1', '1', 'ordre', '20040309174230');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('1080', 'geographie', 'Index géographique', 'geographie', 'geo', 'geos', '4', '1', '0', '1', '1', 'ordre', '20040309174230');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('1081', 'theme', 'Index thématique', 'themes', 'theme', 'themes', '5', '1', '0', '0', '0', 'ordre', '20040311193703');

#
# Dumping data for table 'typeentites_typeentites'
#

INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1071', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1071', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1061', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1061', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1060', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1060', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1060', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1059', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1059', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1059', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1071', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1057', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1071', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1072', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1072', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1072', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1072', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1072', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1071', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1056', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1057', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1058', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1058', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1061', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1056', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1056', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1063', '1062', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1063', '1061', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1063', '1059', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1063', '1060', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1063', '1068', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1057', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1069', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1057', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1057', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1058', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1056', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1056', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1055', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1059', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1059', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1060', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1060', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1061', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1061', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1062', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1069', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1069', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1070', '1068', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1058', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1069', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1069', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1073', '1062', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1068', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1074', '1071', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1062', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1055', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1072', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1056', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1058', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1057', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1075', '1071', '*');

#
# Dumping data for table 'typeentites_typeentrees'
#

INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1059', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1061', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1060', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1073', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1069', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1068', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentree, idtypeentite, condition) VALUES ('1079', '1075', '*');

#
# Dumping data for table 'typeentites_typepersonnes'
#

INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1062', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1077', '1071', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1077', '1057', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1070', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1061', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1077', '1056', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1059', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1060', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1073', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1069', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1077', '1072', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypepersonne, idtypeentite, condition) VALUES ('1076', '1068', '*');
# # Database: 'lodeldevel_lodelia'# 
# --------------------------------------------------------

#
# Table structure for table 'documents'
#

DROP TABLE IF EXISTS __LODELTP__documents;
CREATE TABLE __LODELTP__documents (
  identite int(10) unsigned NOT NULL default '0',
  titre text,
  soustitre text,
  intro text NOT NULL,
  langresume varchar(64) NOT NULL default '',
  lang varchar(64) NOT NULL default '',
  meta text,
  datepubli date default NULL,
  surtitre text,
  texte longtext,
  notebaspage text,
  notefin text,
  bibliographie longtext,
  annexe longtext,
  erratum text,
  ndlr text,
  historique text,
  fichiersassocies tinytext,
  resume text,
  fichiersource tinytext,
  importversion tinytext,
  noticebiblio tinytext,
  pagination tinytext,
  droitsauteur tinytext,
  commentaireinterne text,
  langue char(2) default NULL,
  datepublipapier date default NULL,
  prioritaire tinyint(4) default NULL,
  icone tinytext,
  PRIMARY KEY  (identite),
  UNIQUE KEY identite (identite)
) TYPE=MyISAM;

# --------------------------------------------------------

#
# Table structure for table 'publications'
#

DROP TABLE IF EXISTS __LODELTP__publications;
CREATE TABLE __LODELTP__publications (
  identite int(10) unsigned NOT NULL default '0',
  titre text,
  soustitre text,
  introduction text,
  meta text,
  date date default NULL,
  surtitre text,
  image tinytext,
  introdution text,
  icone tinytext,
  datepubli date default NULL,
  datepublipapier date default NULL,
  noticebiblio tinytext,
  commentaireinterne text,
  erratum text,
  ndlr text,
  historique text,
  prioritaire tinyint(4) default NULL,
  PRIMARY KEY  (identite),
  UNIQUE KEY identite (identite)
) TYPE=MyISAM;
