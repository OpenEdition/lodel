# <model>
# <lodelversion>0.7</lodelversion>
# <date>2004-05-11</date>
# <description>
# 
# </description>
# <author>
# 
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
# # Database: 'lodeldevel_revorg'# 
#
# Dumping data for table 'champs'
#

INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('1', 'titre', '14', 'Titre du document', 'title', 'text', '+', 'Document sans titre', '', 'xhtml:fontstyle;xhtml:phrase;Lien;Appel de Note', '', 'text', 'Titre du document.', '32', '1', '20040305153037');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('2', 'surtitre', '14', 'Surtitre du document', 'surtitre', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien;Appel de Note', '', '', '', '32', '2', '20040305110612');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('3', 'soustitre', '14', 'Sous-titre du document', 'subtitle', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien;Appel de Note', '', 'text', '', '32', '3', '20040303165409');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('7', 'texte', '2', 'Texte du document', 'texte', 'longtext', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '2', '20040225110942');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('9', 'notebaspage', '2', 'Notes de bas de page', 'notebaspage', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections', '', '', '', '32', '5', '20040305153204');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('10', 'notefin', '2', 'Notes de fin de document', 'notefin', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections', '', '', '', '32', '6', '20040305153434');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('11', 'bibliographie', '2', 'Bibliographie du document', 'bibliographie', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '8', '20040305153449');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('12', 'annexe', '2', 'Annexes du document', 'annexe', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '7', '20040305153442');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('77', 'droitsauteur', '1', 'Droits d\'auteur', 'droitsauteur', 'tinytext', '*', 'Propriété intellectuelle', '', '', '', 'text', 'Droits relatifs au document.', '32', '6', '20040303160603');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('14', 'erratum', '16', 'Erratum', 'erratum', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '9', '20040305155858');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('15', 'ndlr', '16', 'Ndlr', 'ndlr', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '10', '20040305155846');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('16', 'historique', '16', 'Historique', 'historique', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '11', '20040305155929');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('76', 'pagination', '1', 'Pagination du document', 'pagination', 'tinytext', '*', '', '', '', '', 'text', 'Ne pas ajouter "pp."', '32', '5', '20040303160333');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('75', 'noticebiblio', '1', 'Notice bibliographique du document', 'noticebiblio', 'tinytext', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:block;Lien', '', '', 'Référence complète permettant de citer le document.', '32', '4', '20040305155049');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('79', 'commentaireinterne', '3', 'Commentaire interne sur le document', 'commentaire', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;texte Lodel;Sections', '', '', 'Commentaire destiné à l\'équipe rédactionnelle et ne devant pas être publié en ligne.', '32', '7', '20040305160154');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('31', 'fichiersassocies', '3', 'Fichiers associés au document', '', 'fichier', '*', '', '', '', '', '', 'Ce champ est un champ utilisé en interne par Lodel. Ne pas le modifier.', '32', '1', '20040305160048');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('32', 'datepubli', '1', 'Date de la publication électronique', 'datepubli', 'date', '*', 'today', '', '', '', 'editable', 'Date de publication du texte intégral en ligne', '32', '2', '20040305095316');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('51', 'titre', '10', 'Titre de la publication', 'title', 'text', '+', 'Publication sans titre', '', 'xhtml:fontstyle;xhtml:phrase;Lien', '', 'text', '', '32', '1', '20040305103053');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('52', 'surtitre', '10', 'Surtitre de la publication', 'surtitre', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien', '', '', '', '32', '2', '20040305115809');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('53', 'soustitre', '10', 'Sous-titre de la publication', 'soustitre', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;Lien', '', 'text', '', '32', '3', '20040303155344');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('89', 'icone', '17', 'Icône de la publication', '', 'image', '*', '', '', '', '', '', '', '32', '1', '20040305100306');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('65', 'resume', '15', 'Résumé', 'resume:fr, abstract:en, riassunto:it, extracto:es, zusammenfassung:de', 'mltext', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:block;Lien;Appel de Note;Sections', '', 'textarea10', '', '32', '1', '20040305095427');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('66', 'fichiersource', '3', 'Fichier source', '', 'fichier', '*', '', '', '', '', '', '', '32', '5', '20040223174419');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('67', 'introduction', '18', 'Introduction de la publication', '', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '1', '20040305100234');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('74', 'importversion', '3', 'Version de l\'importation', '', 'tinytext', '*', '', '', '', '', '', '', '32', '6', '20040223174408');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('83', 'langue', '1', 'Langue du document', 'langue', 'lang', '*', 'fr', '', '', '', 'editable', 'fr : français\ren : anglais\rit : italien\rru : russe\res : espagnol\rde : allemand', '32', '8', '20040305155702');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('86', 'datepublipapier', '1', 'Date de publication sur papier', 'datepublipapier', 'date', '*', '', '', '', '', 'editable', 'Date la publication du document sur papier.', '32', '3', '20040304100627');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('87', 'prioritaire', '1', 'Document prioritaire ?', '', 'boolean', '*', '', '', '', '', '', '', '32', '9', '20040305095025');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('88', 'icone', '1', 'Icône du document', '', 'image', '*', '', '', '', '', 'editable', '', '32', '10', '20040307115736');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('90', 'datepubli', '17', 'Date de publication électronique', '', 'date', '*', 'today', '', '', '', 'editable', '', '32', '2', '20040304133602');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('91', 'datepublipapier', '17', 'Date de publication papier', '', 'date', '*', '', '', '', '', 'editable', '', '32', '3', '20040305120250');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('92', 'noticebiblio', '17', 'Notice bibliographique décrivant la publication', '', 'tinytext', '*', '', '', 'xhtml:fontstyle;xhtml:phrase', '', '', '', '32', '4', '20040305100245');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('93', 'commentaireinterne', '13', 'Commentaire interne sur la publication', '', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '3', '20040305115905');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('94', 'erratum', '18', 'Erratum au sujet de la publication', '', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '2', '20040305100227');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('95', 'ndlr', '18', 'Note de la rédaction au sujet de la publication', '', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '3', '20040305100217');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('96', 'historique', '18', 'Historique de la publication', '', 'text', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections', '', '', '', '32', '4', '20040305100209');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('97', 'prioritaire', '13', 'Publication prioritaire ?', '', 'boolean', '*', '', '', '', '', '', '', '32', '4', '20040305115749');
INSERT INTO __LODELTP__champs (id, nom, idgroupe, titre, style, type, condition, defaut, traitement, balises, filtrage, edition, commentaire, statut, ordre, maj) VALUES ('98', 'lien', '3', 'Lien pour les documents annexes', '', 'tinytext', '*', '', '', 'xhtml:fontstyle;xhtml:phrase;xhtml:special;xhtml:block;Lien;Appel de Note;texte Lodel;Sections;0', '', '', 'Lien pour les documents annexes', '1', '8', '20040511174156');

#
# Dumping data for table 'groupesdechamps'
#

INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('1', 'grmeta', 'documents', 'Groupe métadonnées', '', '32', '4', '20040303175835');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('2', 'grtexte', 'documents', 'Groupe du texte', '', '32', '2', '20040223173046');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('3', 'grgestion', 'documents', 'Groupe gestion des documents', '', '32', '6', '20040303170953');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('10', 'grtitre', 'publications', 'Groupe titre', '', '32', '1', '20040305053528');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('13', 'grgestion', 'publications', 'Gestion des publications', '', '32', '2', '20040305053437');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('14', 'grtitre', 'documents', 'Groupe du titre', '', '1', '1', '20040223173058');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('15', 'grresumes', 'documents', 'Groupe résumés', 'Résumés du document', '32', '3', '20040303175102');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('16', 'graddenda', 'documents', 'Groupe addenda', 'Ensemble de remarques additionnelles', '32', '5', '20040303175057');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('17', 'grmetadonnees', 'publications', 'Groupe des métadonnées', '', '32', '3', '20040304133434');
INSERT INTO __LODELTP__groupesdechamps (id, nom, classe, titre, commentaire, statut, ordre, maj) VALUES ('18', 'graddenda', 'publications', 'Groupe addenda', '', '32', '4', '20040304135521');

#
# Dumping data for table 'types'
#

INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('19', 'breve', 'Brève', 'documents', 'article', 'document', '', '1', '11', '32', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('1', 'collection', 'Collection', 'publications', 'sommaire-hierarchique', 'creation-serie', 'edition-hierarchique', '0', '1', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('2', 'numero', 'Numéro', 'publications', 'sommaire-hierarchique', 'creation-rubrique', 'edition-rubrique', '0', '3', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('3', 'rubrique', 'Rubrique', 'publications', 'sommaire-hierarchique', 'creation-rubrique', 'edition-rubrique', '0', '4', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('4', 'regroupement', 'Regroupement', 'publications', '', 'creation-regroupement', '', '0', '6', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('5', 'compte rendu', 'Compte rendu', 'documents', 'article', 'document', '', '1', '12', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('6', 'chronique', 'Chronique', 'documents', 'article', 'document', '', '1', '15', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('7', 'note de lecture', 'Note de lecture', 'documents', 'article', 'document', '', '1', '13', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('8', 'presentation', 'Présentation', 'documents', 'article', 'document', '', '1', '14', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('17', 'volume', 'Volume', 'publications', 'sommaire-hierarchique', 'creation-rubrique', 'edition-rubrique', '0', '2', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('18', 'colloque', 'Colloque', 'publications', 'sommaire-hierarchique', 'creation-rubrique', 'edition-rubrique', '0', '5', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('9', 'regroupement-documentsannexes', 'Regroupement de documents annexes', 'publications', '', 'creation-regroupement', '', '0', '7', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('10', 'documentannexe-lienfichier', 'Lien vers un fichier', 'documents', '', 'documentannexe-lienfichier', '', '0', '16', '32', '20040413120252');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('11', 'documentannexe-liendocument', 'Lien vers un document interne au site', 'documents', '', 'documentannexe-liendocument', '', '0', '18', '32', '20040413120540');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('12', 'documentannexe-lienpublication', 'Lien vers une publication interne au site', 'documents', '', 'documentannexe-lienpublication', '', '0', '19', '32', '20040413120522');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('13', 'documentannexe-lienexterne', 'Lien vers un site externe', 'documents', '', 'documentannexe-lienexterne', '', '0', '17', '32', '20040413120311');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('14', 'article', 'Article', 'documents', 'article', 'document', '', '1', '9', '32', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('15', 'articlevide', 'Article vide', 'documents', 'article', 'document', '', '0', '20', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('16', 'objetdelarecension', 'Objet de la recension', 'documents', 'article', 'document', '', '1', '21', '1', '20040312102701');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('20', 'editorial', 'Editorial', 'documents', 'article', 'document', '', '1', '8', '32', '20040413115925');
INSERT INTO __LODELTP__types (id, type, titre, classe, tpl, tplcreation, tpledition, import, ordre, statut, maj) VALUES ('21', 'actualite', 'Annonce et actualité', 'documents', 'article', 'document', '', '1', '10', '32', '20040312102701');

#
# Dumping data for table 'typepersonnes'
#

INSERT INTO __LODELTP__typepersonnes (id, type, titre, style, titredescription, styledescription, tpl, tplindex, ordre, statut, maj) VALUES ('22', 'auteur', 'Auteur', 'auteur', 'description de l\'auteur', 'descriptionauteur', 'auteur', 'auteurs', '1', '32', '20040312102701');
INSERT INTO __LODELTP__typepersonnes (id, type, titre, style, titredescription, styledescription, tpl, tplindex, ordre, statut, maj) VALUES ('23', 'directeur de publication', 'Directeur de la publication', 'directeurdepublication', 'description de la personne', 'descriptionpersonne', 'auteur', 'auteurs', '2', '32', '20040312102701');

#
# Dumping data for table 'typeentrees'
#

INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('25', 'motcle', 'Index par mots clés', 'motscles:fr,keywords:en', 'mot', 'mots', '1', '32', '1', '1', '0', 'nom', '20040312102701');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('24', 'periode', 'Index chronologique', 'periode', 'chrono', 'chronos', '3', '32', '0', '1', '1', 'ordre', '20040312102701');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('26', 'geographie', 'Index géographique', 'geographie', 'geo', 'geos', '4', '32', '0', '1', '1', 'ordre', '20040312102701');
INSERT INTO __LODELTP__typeentrees (id, type, titre, style, tpl, tplindex, ordre, statut, lineaire, nvimportable, utiliseabrev, tri, maj) VALUES ('27', 'theme', 'Index thématique', 'themes', 'theme', 'themes', '5', '32', '0', '0', '0', 'ordre', '20040312102701');

#
# Dumping data for table 'typeentites_typeentites'
#

INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('17', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('17', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('7', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('7', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('6', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('6', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('6', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('5', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('5', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('5', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('17', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('3', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('17', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('18', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('18', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('18', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('18', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('18', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('17', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('2', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('3', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('4', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('4', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('7', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('2', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('2', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('9', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('9', '7', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('9', '5', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('9', '6', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('9', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('3', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('15', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('3', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('3', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('4', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('2', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('2', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('1', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('5', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('5', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('6', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('6', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('7', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('7', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('8', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('15', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('15', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('16', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('4', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('15', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('15', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('19', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('14', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '9', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('21', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('20', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '7', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '20', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '5', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '6', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '19', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '15', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '21', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('10', '0', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '9', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '7', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '20', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '5', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '6', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '19', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '15', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('13', '21', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '17', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '9', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '7', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '20', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '5', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '6', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '19', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '15', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('11', '21', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '21', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '14', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '15', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '19', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '6', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '5', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '20', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '7', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '8', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '1', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '18', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '2', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '4', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '9', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '3', '*');
INSERT INTO __LODELTP__typeentites_typeentites (idtypeentite, idtypeentite2, condition) VALUES ('12', '17', '*');

#
# Dumping data for table 'typeentites_typeentrees'
#

INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('5', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('7', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('6', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('19', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('15', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('14', '25', '*');
INSERT INTO __LODELTP__typeentites_typeentrees (idtypeentite, idtypeentree, condition) VALUES ('21', '25', '*');

#
# Dumping data for table 'typeentites_typepersonnes'
#

INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('8', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('17', '23', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('3', '23', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('16', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('7', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('2', '23', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('5', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('6', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('19', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('15', '22', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('18', '23', '*');
INSERT INTO __LODELTP__typeentites_typepersonnes (idtypeentite, idtypepersonne, condition) VALUES ('14', '22', '*');
# # Database: 'lodeldevel_revorg'# 
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
  bibliographie text,
  annexe text,
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
  lien tinytext,
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
