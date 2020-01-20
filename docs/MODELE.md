# Modèle de données

Le modèle de données décrit ici est visible dans le fichier db/install.xml

Voici quelques remarques sur le modèle général.

La table au coeur du système est la table cvs_matrix_comp_ue qui croise les données
de cvs_matrix_comp (une ligne par compétence) et cvs_matrix_ue (une ligne par UE).

Les matrices ont leur paramètres stockés dans la table cvs_matrix.

En ce qui concerne les données utilisateurs (cvs_userdata) et comme ceci devait intégralement être chargé
en mémoire avant chaque calcul, nous avons stocké les données utilisateurs sous forme 
de json. Ce choix nous semble toujours raisonable au vu de la petite empreinte mémoire
(une ligne du fichier csv) et du fait qu'aucune requête spécifique ne devait être faite sur
les résultats étudiants autre que calculer leur résultats en fonction de la ligne entière.

La clé pour la table cvs_userdata est l'email, donc par construction cela doit être  l'identifiant unique pour
la table user de Moodle.

La correspondance utilisateur <=> matrice, se fait à travers les cohortes et donc à travers la table
cvs_matrix_cohorts.
Il existe des tables qui ne sont pas encore utilisées mais qui ont été laissée là pour des
amélioration futures:
 *  cvs_matrix_uegroup_as, cvs_matrix_uegroup: permettrait de grouper arbitrairement les UE
 par groupe autre que semestre. Pour l'instant on déduit le semestre en fonction du numéro d'UE/UC.



