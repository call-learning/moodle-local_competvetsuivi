# TODO

* Check foreign key constraints (matrixid should not be null for example)
* Add test for a small set  and a small matrix to check for right import
* Remove all types except xslx on the matrix upload form

# WARNING - Format

The matrix should:
* have a well formatted content :
    - 1st column : the competency name with the form <SHORTNAME>. <DESCRIPTION> or <SHORTNAME>.<PATHS>. <DESCRIPTION>
    - All other columns are UR values (4 columns per UE)  
    - either UC or UE in the first row
    - Second and Third rows is ignored
