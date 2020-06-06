@local @local_competvetsuivi @javascript @_file_upload
Feature: As an admin I can upload a new matrix and refresh its data

  Background:
    Given the following config values are set as admin:
      | enablecompetvetsuivi | 1 |

  @javascript
  Scenario: As an admin I disable the plugin and I should not see the menu option anymore in the site admin page
    Given I am on site homepage
    And I log in as "admin"
    And I set the following administration settings values:
      | enablecompetvetsuivi | 0 |
    Then I navigate to "Site administration" in site administration
    And I should not see "Compet Vetsuivi"
    Then I set the following administration settings values:
      | enablecompetvetsuivi | 1 |
    Then I navigate to "Site administration" in site administration
    And I should see "Compet Vetsuivi"


  @javascript @_file_upload
  Scenario: As an admin I upload a new matrix
    Given I am on site homepage
    And I log in as "admin"
    When I navigate to "Manage Competencies Matrix" in site administration
    And I click on "Add matrix" "button"
    And I wait until the page is ready
    Then I should see "Excel 2007 spreadsheet"
    And I should not see "Comma-separated values"
    And I should not see "OpenDocument Spreadsheet"
    Then I set the field "shortname" to "MatrixShortname"
    And I set the field "fullname" to "Matrix FullName"
    And I upload "local/competvetsuivi/tests/fixtures/matrix_sample.xlsx" file to "Matrix file" filemanager
    And I press "Save"
    Then I should see "MatrixShortname"
    And I should see "Matrix FullName"

  @javascript @_file_upload
  Scenario: As an admin I create a matrix with number in the shortname. There should be no error.
    Given I am on site homepage
    And I log in as "admin"
    When I navigate to "Manage Competencies Matrix" in site administration
    And I click on "Add matrix" "button"
    And I wait until the page is ready
    Then I should see "Excel 2007 spreadsheet"
    And I should not see "Comma-separated values"
    And I should not see "OpenDocument Spreadsheet"
    Then I set the field "shortname" to "MatrixShortname11"
    And I set the field "fullname" to "Matrix FullName"
    And I upload "local/competvetsuivi/tests/fixtures/matrix_sample.xlsx" file to "Matrix file" filemanager
    And I press "Save"
    Then I should see "MatrixShortname11"
    And I should see "Matrix FullName"
