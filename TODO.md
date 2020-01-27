# WARNING - Format

The matrix should:
* have a well formatted content :
    - 1st column : the competency name with the form <SHORTNAME>. <DESCRIPTION> or <SHORTNAME>.<PATHS>. <DESCRIPTION>
    - All other columns are UR values (4 columns per UE)  
    - either UC or UE in the first row
    - Second and Third rows is ignored


# TODO

Question auto-évaluation
------------------------
* Génère les question à partir de la matrice (en prenant en compte la colonne D - niveau REF)
N'ai pas abordé (0)
A vu (30)
A fait (70)
Sait faire (100)
(On met 100 à partir du critère D)
* Echelle de Likert
* Gestion de la matrice <=> Banque de question 

* Echelle de likert

Impression
----------
Un tableau par étudiant en développé complet:
Grand tableau de récap de toutes les infos d'un étudiant (1 macro compétence par
page) -  1 tableau en PDF (joint au au fichier de note des étudiants).

Possiblement ignorer les habileté


Une impression par UC en développé complet.



Potentiels 
----------
Echelle de la barre de progression à changer

Ajouter le bloc ou l'on veut (voir le coté responsive + et un click pour aller sur une autre page)


Additional features:
* Spider graph
* Filtre pour cours (ue / competency)
* Test d'évaluation


Datamodel:
* Check foreign key constraints (matrixid should not be null for example) and other issue that can
happen with the matrix import (diagnostic function)
* Change matrix format to add calculation + split ref. and description
* Grouping
* Check for predefined settings such as semesters (vs generic grouping)
* UE vs UC

Testing:
* Add test for a small set  and a small matrix to check for right import
* Remove all types except xslx on the matrix upload form
* Unit + behat tests
* Perf tests

Documentation 
* Make sure graph can be themed and document it
* Other graph (spider)



