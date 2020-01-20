# Calculs concernant les graphes

## Calcul de la progression étudiante

On calcule la progression étudiante sur les deux composantes connaissance et compétence (les deux premières colonnes de chaque UE/UC dans la matrice).

Pour chaque composante on parcours l'arbre des compétences et on calcule la valeur maximale possible pour chaque sous-compétence, on ramène ensuite
ce score (on l'additionne) au niveau de la compétence actuelle.

Par exemple:  pour Coprev., on calcule le score maximal possible pour toutes les sous-composantes. Ici pour Coprev1.1., Coprev1.1bis..., 
on aura un score maximal de 4.5.

Une fois le score maximal calculé pour une compétence, on multiple celui-ci par la note obtenue par l'étudiant sur cette UE (0 ou 1)et la contribution de l'UE (note de 1, 0,5 ou 0).
On obtient par la suite un résultat en pourcentage pour l'étudiant et le semestre  (note de l'étudiant/note maximale que l'on peut obtenir pour le semestre)

## Calcul de la progression étudiante par les résultats du tests

Le calcul se fait en regardant tous les quiz qui ont des questions appartenant à une banque de question donnée (ciblée par le nom de sa catégorie, voir paramétrage des tests 
d'évaluation dans la documentation).
Parmi ces quiz on prends toutes les questions qui ont un identifiant correspondant au nom d'une des compétences. On fait la correspondance entre le score obtenu par l'utilisateur 
et la compétence. Si l'utilisateur a répondu plusieurs fois à la même question dans plusieurs quiz, on prends la meilleure note.

Le système aussi va vérifier pour toutes les compétences, la valeur obtenue dans ses sous-compétences. On fait la moyenne des résultats  obtenus dans les sous-compétences pour 
avoir la valeur d'une compétence. Bien entendu si une macro-compétence est associée  à une question de quiz, le score sera celui obtenu par l'utilisateur sur cette 
compétence (on ignore les sous-compétences).

## Calcul de la contribution d'une UC/UE à l'ensemble du cursus ou un semestre

Il y a deux calculs. Le premier, plus simple correspond à la visualisation sous forme de barre de progression.
Ce calcul corresponds à obtenir pour toutes les compétences et sous-compétence le score maximal global que l'on peut obtenir (sur une année ou un semestre). 
Ce total est le score maximal.
Ensuite on voit que l'on peut l'on peut obtenir pour l'UE concernée (par exemple UC/UE 51), sur l'ensemble des compétences (score maximal pour l'UE).
Le pourcentage calculé et affiché dans la barre de progression sera donc le score maximal pour l'UE / le score maximal (global).

Le calcul n'est pas beaucoup plus compliqué mais la visualisation associée est un peu plus complexe. 
Il permet d'obtenir pour chaque compétences (ici on parle souvent des macro-compétences), le pourcentage que peut contribuer l'UE dans le cursus général.
Comme pour le premier cas on calcule une valeur maxmale par type (compétence, connaissances) et par compétence. La différence est que le total/maximal sera pris sur l'ensemble 
compétences/connaissances. On regarde donc pour une compétence donnée, la valeur maximale qu'elle peut avoir (ceci en additionnant tous les points
possible sur les compétences et connaissances). Cela nous permet d'obtenir le pourcentage que prendra cette connaissance dans l'anneau (la part
de l'arc de cercle): on fait le calcul valeur possible pour cette UE (competences+connaissances) / valeur max (competences + connaissance) 

Ensuite nous prenons pour chaque type (connaissance et compétence), la part qu'elle prendront dans ce morceau d'anneau (donc quel est le pourcentage pour la connaissance, 
et pour la compétence). Normalement on a un pourcentage de 100% pour la plus grande des valeurs.

