@format @format_grid
Feature: Section break
  As a teacher I need to break the grid with a section break.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email          |
      | daisy    | Daisy     | Grid     | daisy@grid.com |
    And the following "courses" exist:
      | fullname | shortname | format  | numsections |
      | Grid     | GD        | grid    | 10           |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | daisy    | GD     | editingteacher |
    And I am on the "GD" "Course" page logged in as "daisy"

  @javascript
  Scenario: Create a section 4 break
    When I turn editing mode on
    And I open section "4" edit menu
    And I click on "Edit settings" "link" in the "Section 4" "section"
    And I select "Yes" from the "sectionbreak" singleselect
    And I set the field "Section break heading" to "Section four break"
    And I press "Save changes"
    And I turn editing mode off
    Then I should see "Section four break" in the "#gridsectionbreak-4" "css_element"
