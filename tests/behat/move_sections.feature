@format @format_grid
Feature: Sections can be moved - adapted from core test of the same name.
  In order to rearrange my course contents
  As a teacher
  I need to move sections up and down

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | daisy    | Daisy     | Grid     | daisy@grid.com |
    And the following "courses" exist:
      | fullname | shortname | format | coursedisplay | numsections |
      | Grid     | GD        | grid   | 0             | 5            |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | daisy | GD     | editingteacher |
    And the following "activities" exist:
      | activity | name               | intro                       | course | idnumber | section |
      | forum    | Test forum name    | Test forum name description | GD     | forum1   | 1       |
    And I log in as "daisy"
    And I am on "Grid" course homepage with editing mode on

  @javascript
  Scenario: Move section with javascript
    When I open section "1" edit menu
    And I click on "Move" "link" in the "Section 1" "section"
    And I click on "Section 3" "link" in the ".modal-body" "css_element"
    Then I should see "Test forum name" in the "Section 3" "section"

  @_file_upload @javascript
  Scenario: Move section with an image - note: The duckling image is copyright 'Gareth J Barnard 2020' use only for this test without permission.
    When I open section "2" edit menu
    And I click on "Edit settings" "link" in the "Section 2" "section"
    And I upload "course/format/grid/tests/fixtures/Duckling.jpg" file to "Section image" filemanager
    And I set the field "Image alt text" to "Duckling"
    And I press "Save changes"
    And I open section "2" edit menu
    And I click on "Move" "link" in the "Section 2" "section"
    And I click on "Section 3" "link" in the ".modal-body" "css_element"
    And I turn editing mode off
    Then "//img[contains(@src, 'Duckling.jpg')]" "xpath_element" should exist in the "#section-3 .grid-image" "css_element"
    And "//img[contains(@alt, 'Duckling')]" "xpath_element" should exist in the "#section-3 .grid-image" "css_element"
