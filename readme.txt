# Introduction
Le modèle de document pour OpenOffice ou LibreOffice est disponible sur le dépôt GitHub dans la branche suivante : <https://github.com/OpenEdition/lodel/tree/model-libreoffice> 
Des versions taguées sont également disponible dans le dépôt GitHub : model-libreoffice-vXX

Ce modèle permet d’appliquer les styles déclarés dans le modèle éditorial distribué avec la version 0.8, 0.9 et 1.0 de Lodel. Il fonctionne sous OpenOffice 3.x et LibreOffice 3.x.

Les évolutions de ce modèle seront annoncées sur le blog de Lodel : <http://blog.lodel.org>

L’archive zip contient 3 fichiers :
* modele_revuesorg_fr.ott : le modèle de document
* raccourcis_modele_revorg_fr.cfg : le fichier de configuration des raccourcis claviers permettant d’appliquer les styles au document.
* readme.txt

# Installation

* Dans LibreOffice writter, vérifier le niveau de sécurité pour l’exécution des macros :  menu Outils > Options > Libreoffice.org > Sécurité > Sécurité des macros > choisir “Niveau de sécurité moyen”.
* Un double clic sur modele_revuesorg_fr.ott ouvre un nouveau document basé sur le modèle (autoriser l’exécution des macros, bien-sûr).
* Le menu ”Lodel” disponible dans la barre des menus permet d’appliquer les styles déclarés dans Lodel.
* Pour attacher les raccourcis claviers permettant d’appliquer les styles : menu Outils > Personnaliser > Clavier > Charger : choisir le fichier “raccourcis_modele_revorg_fr.cfg” et valider. Les raccourcis clavier sont alors actifs. Ils sont affichés dans le menu “Lodel” en face des styles correspondants.

# Restrictions connues

La touche “alt” n’est disponible dans les raccrourcis clavier d’LibreOffice que depuis la version 3.2. Pour les versions antérieures, la plupart des raccourcis clavier ne sont pas disponibles.

Importation des documents dans Lodel 0.8 ou 0.9 (ServOO) :
* Les listes à puces sont interprétées par Servoo (Lodel 0.8 et 0.9) comme des listes ordonnées : les listes à puces seront affichées dans Lodel comme des listes numérotées. Les listes à puces sont correctement interprétées par OTX (Lodel 1.x)
* Il faut enregistrer le document au format sxw

Importation des documents dans Lodel 1.x (OTX) :
* Les listes à puces sont correctement interprétées par OTX (Lodel 1.x).
* Tous les formats de fichiers compatibles avec LibreOffice 3 sont reconnus par OTX à l'exception de rtf. Il est cependant préférable d'utiliser le format odt.

# Recommandations pour le stylage
## Mises en formes locales

Les mises en formes locales peuvent générer des mises en forme non souhaitées dans le document html produit dans Lodel. Pour les supprimer, utilisez la fonction "Formatage par défaut" (accessible depuis le menu Format, ou Ctrl+M). Elle supprime les mises en forme locales (ou formatage direct)et les styles de caractère mais conserve les styles de paragraphe. Les styles utilisés dans le modèle de document sont presque tous des styles de paragraphe.

Le formatage direct est un formatage que vous appliquez sans utiliser les styles, par exemple :
* lorsque vous spécifiez le style gras en cliquant sur l'icône Gras ;
* lorsque vous modifier la Police en la choisissant dans le cadre Nom de police de la barre de formatage.

## Stylage des images
Dans LibreOffice, les images ne sont pas nécessairement contenues dans un paragraphe distinct. Il faut veiller à insérer un paragraphe stylé en “Standard” ou en “Annexe” et contenant l’ancre de l’image et ancrer l'image comme caractère : options de l'image (double-clic sur l'image) : onglet type : ancrer comme caractère.

## Stylage des listes
### Importation des documents dans Lodel 0.8 ou 0.9 (ServOO)

Les listes doivent être stylées avec le style de paragraphe "puces" disponible dans le modèle de document.

### Importation des documents dans Lodel 1.x (OTX)

Dans Lodel 1.0, il ne faut plus utiliser le style "puces". Il faut utiliser les outils de liste (à puces et numérotées) "natifs" d'LibreOffice.

# Personnalisation du modèle
Il est bien-sûr possible d’ajouter d’autres styles correspondant à un autre modèle éditorial.

Le principe de ce modèle de document est le suivant :
* le modèle de document contient des styles de paragraphes dont les noms sont déclarés dans le modèle éditorial de Lodel ;
* le modèle contient des macros qui appliquent ces styles (un macro, très simple, par style) ;
* le modèle contient enfin un menu personnalisé qui permet d’exécuter ces macros.
Les raccourcis clavier permettant d’exécuter les macros ne peuvent être enregistrées dans le modèle. C’est pour cette raison qu’il faut les charger depuis un fichier différent.
Pour ajouter un style pour un autre modèle éditorial au menu Lodel :
* Ouvrez le modèle de document dans LibreOffice 3.2 (veillez à ouvrir le modèle de document, pas un nouveau document basé sur le modèle).
* Ajoutez un style dans le modèle de document (dans la fenêtre “Styles et formatage”).
* Enregistrez une nouvelle macro qui applique ce style : “Outils” > “Macros” > “Enregistrez un macro” puis appliquer le style et cliquez sur “Terminer l’enregistrement” et enregistrez cette macro dans le modèle : modele_revuesorg_fr.ott > Lodel > Module1 en lui donnant si possible un nom explicite.
* Pour ajouter cette macro au menu Lodel : Outils > Personnaliser > Menus. Choisissez le menu “Lodel” ou un de ses sous-menus. Cliquez sur “Ajouter” et sélectionnez la macro que vous venez de créer puis “Fermer” et Validez.
* Enregistrez votre modèle. C’est fait.

Si vous souhaitez associer un raccourci clavier à une macro, vous pouvez suivre ce guide très explicite : <http://wiki.services.openoffice.org/wiki/FR/Documentation/Writer_Guide/Assignation_raccourcis>

La sauvegarde des raccourcis semble ne pas fonctionner sans LibreOffice 3.2 (le fichier produit était vide). Elle fonctionne très bien dans LibreOffice 3.2.


# Licence
Ce modèle est distribué en licence GPL 2. Merci de faire état de vos essais, qu’ils soient fructueux ou non, sur la liste lodel-users <https://listes.cru.fr/sympa/info/lodel-users>.
Crédits : Matthieu Heuzé, Jean-François Rivière
