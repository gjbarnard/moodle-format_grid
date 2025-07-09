@format @format_grid
Feature: Image upload
  As a teacher I need to upload an image to a section.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email          |
      | daisy    | Daisy     | Grid     | daisy@grid.com |
    And the following "courses" exist:
      | fullname | shortname | format  | numsections |
      | Grid     | GD        | grid    | 5            |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | daisy    | GD     | editingteacher |
    And I am on the "GD" "Course" page logged in as "daisy"

  @_file_upload @javascript
  Scenario: Upload an image to section 2 - note: The duckling image is copyright 'Gareth J Barnard 2020' use only for this test without permission.
    When I turn editing mode on
    And I open section "2" edit menu
    And I click on "Edit settings" "link" in the "Section 2" "section"
    And I upload "course/format/grid/tests/fixtures/Duckling.jpg" file to "Section image" filemanager
    And I set the field "Image alt text" to "Duckling"
    And I press "Save changes"
    And I turn editing mode off
    Then "//img[contains(@src, 'Duckling.jpg')]" "xpath_element" should exist in the "#section-2 .grid-image" "css_element"
    And "//img[contains(@alt, 'Duckling')]" "xpath_element" should exist in the "#section-2 .grid-image" "css_element"
