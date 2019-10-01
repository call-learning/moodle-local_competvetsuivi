# WARNING - Format

The matrix should:
* have a well formatted content :
    - 1st column : the competency name with the form <SHORTNAME>. <DESCRIPTION> or <SHORTNAME>.<PATHS>. <DESCRIPTION>
    - All other columns are UR values (4 columns per UE)  
    - either UC or UE in the first row
    - Second and Third rows is ignored


# TODO
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

