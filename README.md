# Compet Vet Suivi Local Plugin

Ce plugin permet de calculer les compétences acquises pour un utilisateur en fonction des UE complététees et d'une matrice de compétences.

Les données concernant les utilisateurs (completion des UEs) peuvent être synchronées en pointant sur un répertoire dans
lequel la liste des utilisateurs et UE complétées sont stockées.

Pour les matrices de compétence, il suffit de les télécharger dans la section de paramétrage du plugin. Chaque matrice pourra être appliquée
à une cohorte d'utilisateur.

Les paramètres du plugin se situent dans le menu adminstration ("Administration du Site") dans la partie générale (Notifications, ...)

Notez bien que cet outil a besoin de Bootstrap 4 pour fonctionner parfaitement.
Il faut donc que votre thème inclue les classes Bootstrap ou soit un thème enfant
du thème Boost (de base de Moodle).

# Synchronisation des données utilisateurs

La synchronisation se fait de manière périodique par une tâche Moodle. Dans les paramètre du plugin on spécifie
un répertoire dans lequel on versera le fichier contenant la liste des utilisateur et la complétion de leur UE.
Le fichier une fois traité est effacé. Ce qui permettra d'en verser un autre. Si aucun fichier n'est présent dans
le répertoire, aucune action n'est menée.
Cette tâche se déroule toute les 5 minutes. Cela conviendra si le volume de données n'est pas trop grand.
Dans tous les cas cela peut être changé dans les paramètres de la tâche. 

Alternativement on peut synchroniser la liste des utilisateurs par le script suivant:

    php local/competvetsuivi/cli/uploaduserdata.php --file=<csv file>  

# Gestion des matrices de compétences

Une matrice peut être rajoutée au Système dans la section de paramétrage du plugin
Elle pourra:
 * être mise à jour en chargeant un nouveau fichier
 * assignée à une ou plusieurs cohortes d'utilisateurs
 * Affichée : afin de voir les compétences 
 * effacée du système
 
 
# Format des données d'entrée

## Format des données utilisateurs

Le fichier contenant les données utilisateur est un fichier CSV. Attention Excel est connu pour ne pas gérer tout à fait de manière standard ce type de fichier. Il est recommandé d’utiliser OpenOffice/LibreOffice pour cela.
Le format est celui classique d’un CSV (séparé par une virgule) avec une colonne appelée “Mail” et qui est juste placée avant la première colonne des UC.
Par exemple voici la première ligne d’un fichier type

``
  
    Prénom Nom;Identifiant scolarité;Identifiant Moodle;Mail;UE51;UE52;UE53;UE54;UE55;UE61;UE62;UE63;UE64;UE65;UE66;UE71;UE72;UE73;UE74;UE75;UE76;UE77;UE78;UE81;UE82;UE83;UE84;UE85;UE86;....
``

Pour chaque étudiant identifié par son email, on a une liste de valeurs (1, 0 ou vide) qui corresponds à chaque UE. Une UE est validée si la valeur est 1.

## Format des données de matrice

La matrice de compétences est un fichier Excel (compatible Excel 2007). Sa disposition est la suivante:
Une feuille de calcul dont le nom commence par “Matrice”, par exemple “Matrice XXXX” (Matrice Enva par exemple).
Dans cette feuille:
 * La première colonne est le nom court de chaque compétence
 * La deuxième est le nom long/description de la compétence
 * Un ensemble de colonnes qui sont ignorées
 * Une colonne vide (ceci est le marqueur permettant de savoir que l’on commence à lister les valeurs par UC/UE)
    * En colonne les UC (4 colonnes par UC/UE)
    * Dans les colonnes des UC, les valeurs sont 1, 2, 3 (et leurs multiples de 10).


## Visualisation des données (temporaire)

Pour voir les données attachées à une matrice, deux pages sont présentes:

* [URLDUSITE]/local/competvetsuivi/viewuserdata.php?id=[IDUTILISATEUR]&matrixid=[IDMATRICE]
* [URLDUSITE]/local/competvetsuivi/viewusergraph.php?id=[IDUTILISATEUR]&matrixid=[IDMATRICE]

L'identifiant de la matrice peut s'obtenir sur la liste des matrice en examinant la matrice:
* [URLDUSITE]/local/competvetsuivi/admin/matrix/list.php et en cliquant sur la matrice on a l'URL suivante:
[URLDUSITE]/local/competvetsuivi/admin/matrix/view.php?id=[IDMATRICE]
* Si on va sur la liste des utilisateur et on clique sur un utilisateur pour voir le profil on peut obtenir l'ID de l'utilisateur
en regardant son profil.
 
## Unit testing

Pour initialiser l'environnement de test (attention de bien mettre la configuration souhaitée dans config.php):

``

     php admin/tool/phpunit/cli/init.php     
     php admin/tool/phpunit/cli/util.php --buildcomponentconfigs
     
``

Après l'initialisation de l'environnement de test, l'ensemble des tests unitaires
peut être passé par la ligne de commande suivante:

``

     ./vendor/phpunit/phpunit/phpunit --testsuite local_competvetsuivi_testsuite
     
``

## Problèmes connus

Voici quelques un des problèmes non encore résolus qui n’empêchent pas le module de fonctionner mais qui peuvent être présent dans certains cas.
  * Affectation de plusieurs matrices à la même cohorte: pour l’utilisateur  la première matrice sera sélectionnée (dans l’ordre chronologique d’affectation). Cela peut avoir des effets de bord peu compréhensible pour l’utilisateur final. Le mieux sera de vérifier de manière générale qu’une matrice est affectée à une seule cohorte.
  * Dépassements des libellés dans le graphe en anneau. Selon la taille et le contenu des libellés, certains textes peuvent dépasser en dehors de la fenêtre visible. Pour l’instant ce problème n’est pas résolu car il dépend fortement des contraintes imposées par le thème (testé sur lambda, fordson et boost).

## Dépendences

Le chargement de la matrice dépends de PHPExcel qui a été remplacé en 3.8 par PHPSpreadsheet (
MDL-65741). La bibliothèque PHPExcel a été mise en mode "deprecated" en 2015 et donc devra être
remplacée par PHPSpreadsheet dans le futur proche.

## License ##

2019 CALL Learning <laurent@call-learning.fr>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
