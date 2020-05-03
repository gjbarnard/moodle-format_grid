<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Grid Format - A topics based format that uses a grid of user selectable images to popup a light box of the section.
 *
 * @package    format_grid
 * @version    See the value of '$plugin->version' in version.php.
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['alttext'] = 'Image alt text';
$string['alttext_help'] = 'This text will be set as the image alt attribute';
$string['display_summary'] = 'Move out of grid';
$string['display_summary_alt'] = 'Move this section out of the grid';
$string['editimage'] = 'Change image';
$string['editimage_alt'] = 'Set or change image';
$string['formatgrid'] = 'Grid format'; // Name to display for format.
$string['general_information'] = 'General Information';  // No longer used kept for legacy versions.
$string['hidden_topic'] = 'This section has been hidden';
$string['hide_summary'] = 'Move section into grid';
$string['hide_summary_alt'] = 'Move this section into the grid';
$string['namegrid'] = 'Grid view';
$string['title'] = 'Section title';
$string['topic'] = 'Section';
$string['topic0'] = 'General';
$string['topicoutline'] = 'Section';  // No longer used kept for legacy versions.

// Moodle 2.0 Enhancement - Moodle Tracker MDL-15252, MDL-21693 & MDL-22056 - http://docs.moodle.org/en/Development:Languages.
$string['sectionname'] = 'Section';
$string['pluginname'] = 'Grid format';
$string['section0name'] = 'General';

// WAI-ARIA - http://www.w3.org/TR/wai-aria/roles.
$string['gridimagecontainer'] = 'Grid images';
$string['closeshadebox'] = 'Close shade box';
$string['previoussection'] = 'Previous section';
$string['nextsection'] = 'Next section';
$string['shadeboxcontent'] = 'Shade box content';

// MDL-26105.
$string['page-course-view-grid'] = 'Any course main page in the grid format';
$string['page-course-view-grid-x'] = 'Any course page in the grid format';

$string['addsection'] = 'Add section';
$string['hidefromothers'] = 'Hide section'; // No longer used kept for legacy versions.
$string['showfromothers'] = 'Show section'; // No longer used kept for legacy versions.
$string['currentsection'] = 'This section'; // No longer used kept for legacy versions.
$string['markedthissection'] = 'This section is highlighted as the current section';
$string['markthissection'] = 'Highlight this section as the current section';

// Moodle 3.0 Enhancement.
$string['editsection'] = 'Edit section';
$string['deletesection'] = 'Delete section';

// MDL-51802.
$string['editsectionname'] = 'Edit section name';
$string['newsectionname'] = 'New name for section {$a}';

// Moodle 2.4 Course format refactoring - MDL-35218.
$string['numbersections'] = 'Number of sections';

// Exception messages.
$string['cannotfinduploadedimage'] = 'Cannot find the uploaded original image.  Please report error details and the information contained in the php.log file to developer.  Refresh the page and upload a fresh copy of the image.';
$string['cannotconvertuploadedimagetodisplayedimage'] = 'Cannot convert uploaded image to displayed image.  Please report error details and the information contained in the php.log file to developer.';
$string['cannotgetimagelock'] = 'Cannot get image lock.  Please report error details to developer.';
$string['cannotgetimagesforcourse'] = 'Cannot get images for course.  Please report error details to developer.';
$string['cannotgetsummarylock'] = 'Cannot get summary lock.  Please report error details to developer.';
$string['formatnotsupported'] = 'Format is not supported at this server, please fix the system configuration to have the GD PHP extension installed - {$a}.';
$string['functionfailed'] = 'Function failed on image - {$a}.';
$string['imagecannotbeused'] = 'Image cannot be used, must be a PNG, JPG or GIF and the GD PHP extension must be installed.';
$string['invalidiconrecordid'] = 'Could not create image container.  Grid format database is not ready.  An admin must visit the notifications section.';
$string['invalidsummaryrecordid'] = 'Could not set summary status.  Grid format database is not ready.  An admin must visit the notifications section.';
$string['mimetypenotsupported'] = 'Mime type is not supported as an image format in the Grid format - {$a}.';
$string['originalheightempty'] = 'Original height is empty - {$a}.';
$string['originalwidthempty'] = 'Original width is empty - {$a}.';
$string['noimageinformation'] = 'Image information is empty - {$a}.';
$string['reporterror'] = 'Please report error details and the information contained in the php.log file to developer';

// CONTRIB-4099 Image container size change improvement.
$string['off'] = 'Off';
$string['on'] = 'On';
$string['scale'] = 'Scale';
$string['crop'] = 'Crop';
$string['original'] = 'Original';
$string['webp'] = 'WebP';
$string['imagefile'] = 'Upload an image';
$string['imagefile_help'] = 'Upload an image of type PNG, JPG or GIF.  WEBP needs Moodle core support.';
$string['deleteimage'] = 'Delete image';
$string['deleteimage_help'] = "Delete the image for the section being edited.  If you've uploaded an image then it will not replace the deleted image.";
$string['gfreset'] = 'Grid reset options';
$string['gfreset_help'] = 'Reset to Grid defaults.';
$string['defaultimagecontaineralignment'] = 'Default alignment of the image containers';
$string['defaultimagecontaineralignment_desc'] = 'The default alignment of the image containers.';
$string['defaultimagecontainerwidth'] = 'Default width of the image container';
$string['defaultimagecontainerwidth_desc'] = 'The default width of the image container.';
$string['defaultimagecontainerratio'] = 'Default ratio of the image container relative to the width';
$string['defaultimagecontainerratio_desc'] = 'The default ratio of the image container relative to the width.';
$string['defaultimageresizemethod'] = 'Default image resize method';
$string['defaultimageresizemethod_desc'] = 'The default method of resizing the image to fit the container.';
$string['defaultbordercolour'] = 'Default image container border colour';
$string['defaultbordercolour_desc'] = 'The default image container border colour.';
$string['defaultborderradius'] = 'Default border radius';
$string['defaultborderradius_desc'] = 'The default border radius on / off.';
$string['defaultborderwidth'] = 'Default border width';
$string['defaultborderwidth_desc'] = 'The default border width.';
$string['defaultdisplayedimagefiletype'] = 'Displayed image type';
$string['defaultdisplayedimagefiletype_desc'] = 'Set the displayed image type.';
$string['defaultimagecontainerbackgroundcolour'] = 'Default image container background colour';
$string['defaultimagecontainerbackgroundcolour_desc'] = 'The default image container background colour.';
$string['defaultcurrentselectedsectioncolour'] = 'Default current selected section colour';
$string['defaultcurrentselectedsectioncolour_desc'] = 'The default current selected section colour.';
$string['defaultcurrentselectedimagecontainertextcolour'] = 'Default current selected image container text colour';
$string['defaultcurrentselectedimagecontainertextcolour_desc'] = 'The default current selected image container text colour.';
$string['defaultcurrentselectedimagecontainercolour'] = 'Default current selected image container colour';
$string['defaultcurrentselectedimagecontainercolour_desc'] = 'The default current selected image container colour.';

$string['defaultcoursedisplay'] = 'Course display default';
$string['defaultcoursedisplay_desc'] = "Either show all the sections on a single page or section zero and the chosen section on page.";

$string['defaultfitsectioncontainertowindow'] = 'Fit section container to window by default';
$string['defaultfitsectioncontainertowindow_desc'] = 'The default setting for \'Fit section container to window\'.';

$string['defaultnewactivity'] = 'Show new activity notification image default';
$string['defaultnewactivity_desc'] = "Show the new activity notification image when a new activity or resource are added to a section default.";

$string['setimagecontaineralignment'] = 'Set the image container alignment';
$string['setimagecontaineralignment_help'] = 'Set the image container width to one of: Left, Centre or Right';
$string['setimagecontainerwidth'] = 'Set the image container width';
$string['setimagecontainerwidth_help'] = 'Set the image container width to one of: 128, 192, 210, 256, 320, 384, 448, 512, 576, 640, 704 or 768';
$string['setimagecontainerratio'] = 'Set the image container ratio relative to the width';
$string['setimagecontainerratio_help'] = 'Set the image container ratio to one of: 3-2, 3-1, 3-3, 2-3, 1-3, 4-3 or 3-4.';
$string['setimageresizemethod'] = 'Set the image resize method';
$string['setimageresizemethod_help'] = "Set the image resize method to: 'Scale' or 'Crop' when resizing the image to fit the container.";
$string['setbordercolour'] = 'Set the border colour';
$string['setbordercolour_help'] = 'Set the border colour in hexidecimal RGB.';
$string['setborderradius'] = 'Set the border radius on / off';
$string['setborderradius_help'] = 'Set the border radius on or off.';
$string['setborderwidth'] = 'Set the border width';
$string['setborderwidth_help'] = 'Set the border width between 1 and 10.';
$string['setimagecontainerbackgroundcolour'] = 'Set the image container background colour';
$string['setimagecontainerbackgroundcolour_help'] = 'Set the image container background colour in hexidecimal RGB.';
$string['setcurrentselectedsectioncolour'] = 'Set the current selected section colour';
$string['setcurrentselectedsectioncolour_help'] = 'Set the current selected section colour in hexidecimal RGB.';
$string['setcurrentselectedimagecontainertextcolour'] = 'Set the current selected image container text colour';
$string['setcurrentselectedimagecontainertextcolour_help'] = 'Set the current selected image container text colour in hexidecimal RGB.';
$string['setcurrentselectedimagecontainercolour'] = 'Set the current selected image container colour';
$string['setcurrentselectedimagecontainercolour_help'] = 'Set the current selected image container colour in hexidecimal RGB.';

$string['setnewactivity'] = 'Show new activity notification image';
$string['setnewactivity_help'] = "Show the new activity notification image when a new activity or resource are added to a section.";

$string['setfitsectioncontainertowindow'] = 'Fit the section popup to the window';
$string['setfitsectioncontainertowindow_help'] = 'If enabled, the popup box with the contents of the section will fit to the size of the window and will scroll inside if necessary.  If disabled, the entire page will scroll instead.';

$string['colourrule'] = "Please enter a valid RGB colour, six hexadecimal digits.";
$string['opacityrule'] = "Please enter a valid opacity, between 0 and 1 with 0.1 increments.";
$string['sectiontitlefontsizerule'] = "Please enter a valid section title font size, between 12 and 24 (pixels) or 0 for 'do not set'.";

// Section title text format options.
$string['hidesectiontitle'] = 'Hide section title option';
$string['hidesectiontitle_help'] = 'Hide the section title.';
$string['defaulthidesectiontitle'] = 'Hide section title option';
$string['defaulthidesectiontitle_desc'] = 'Hide the section title.';
$string['sectiontitlegridlengthmaxoption'] = 'Section title grid length option';
$string['sectiontitlegridlengthmaxoption_help'] = 'Set the maximum length of the section title in the grid box.  Enter \'0\' for no truncation.';
$string['defaultsectiontitlegridlengthmaxoption'] = 'Section title grid length option';
$string['defaultsectiontitlegridlengthmaxoption_desc'] = 'Set the default maximum length of the section title in the grid box.  Enter \'0\' for no truncation.';
$string['sectiontitlegridlengthmaxoptionrule'] = 'The maximum length of the section title in the grid box must not be zero.  Enter \'0\' for no truncation.';
$string['sectiontitleboxposition'] = 'Section title box position option';
$string['sectiontitleboxposition_help'] = 'Set the position of the section title within the grid box to one of: \'Inside\' or \'Outside\'.';
$string['defaultsectiontitleboxposition'] = 'Section title box position option';
$string['defaultsectiontitleboxposition_desc'] = 'Set the position of the section title within the grid box to one of: \'Inside\' or \'Outside\'.';
$string['sectiontitleboxpositioninside'] = 'Inside';
$string['sectiontitleboxpositionoutside'] = 'Outside';
$string['sectiontitleboxinsideposition'] = 'Section title box position when \'Inside\' option';
$string['sectiontitleboxinsideposition_help'] = 'Set the position of the section title when \'Inside\' the grid box to one of: \'Top\', \'Middle\' or \'Bottom\'.';
$string['defaultsectiontitleboxinsideposition'] = 'Section title box position when \'Inside\' option';
$string['defaultsectiontitleboxinsideposition_desc'] = 'Set the position of the section title when \'Inside\' the grid box to one of: \'Top\', \'Middle\' or \'Bottom\'.';
$string['sectiontitleboxinsidepositiontop'] = 'Top';
$string['sectiontitleboxinsidepositionmiddle'] = 'Middle';
$string['sectiontitleboxinsidepositionbottom'] = 'Bottom';
$string['sectiontitleboxheight'] = 'Section title box height';
$string['sectiontitleboxheight_help'] = 'Section title box height in pixels or 0 for calculated.  When the box position is \'Inside\'.';
$string['defaultsectiontitleboxheight'] = 'Section title box height';
$string['defaultsectiontitleboxheight_desc'] = 'Section title box height in pixels or 0 for calculated.  When the box position is \'Inside\'.';
$string['sectiontitleboxopacity'] = 'Section title box opacity';
$string['sectiontitleboxopacity_help'] = 'Section title box opacity between 0 and 1 in 0.1 increments.  When the box position is \'Inside\'.';
$string['defaultsectiontitleboxopacity'] = 'Section title box opacity';
$string['defaultsectiontitleboxopacity_desc'] = 'Section title box opacity between 0 and 1 in 0.1 increments.  When the box position is \'Inside\'.';
$string['sectiontitlefontsize'] = 'Section title font size';
$string['sectiontitlefontsize_help'] = 'Section title font size between 12 and 24 pixels where 0 represents \'do not set but inherit from theme or any other applying CSS\'.';
$string['defaultsectiontitlefontsize'] = 'Section title font size';
$string['defaultsectiontitlefontsize_desc'] = 'Section title font size between 12 and 24 pixels where 0 represents \'do not set but inherit from theme or any other applying CSS\'.';
$string['sectiontitlealignment'] = 'Section title alignment';
$string['sectiontitlealignment_help'] = 'Set the section title alignment to one of \'Left\', \'Centre\' or \'Right\'.';
$string['defaultsectiontitlealignment'] = 'Section title alignment';
$string['defaultsectiontitlealignment_desc'] = 'Set the section title alignment to one of \'Left\', \'Centre\' or \'Right\'.';
$string['sectiontitleinsidetitletextcolour'] = 'Section title text colour when \'Inside\' option';
$string['sectiontitleinsidetitletextcolour_help'] = 'Set title text colour when it is \'Inside\' the grid box.';
$string['defaultsectiontitleinsidetitletextcolour'] = 'Section title text colour when \'Inside\' option';
$string['defaultsectiontitleinsidetitletextcolour_desc'] = 'Set title text colour when it is \'Inside\' the grid box.';
$string['sectiontitleinsidetitlebackgroundcolour'] = 'Section title background colour when \'Inside\' option';
$string['sectiontitleinsidetitlebackgroundcolour_help'] = 'Set title background colour when it is \'Inside\' the grid box.';
$string['defaultsectiontitleinsidetitlebackgroundcolour'] = 'Section title background colour when \'Inside\' option';
$string['defaultsectiontitleinsidetitlebackgroundcolour_desc'] = 'Set title background colour when it is \'Inside\' the grid box.';
$string['showsectiontitlesummary'] = 'Show section title summary on hover option';
$string['showsectiontitlesummary_help'] = 'Show the section title summary when hovering over the grid box.';
$string['defaultshowsectiontitlesummary'] = 'Show the section title summary on hover option';
$string['defaultshowsectiontitlesummary_desc'] = 'Show the section title summary when hovering over the grid box.';
$string['setshowsectiontitlesummaryposition'] = 'Set the section title summary on hover position option';
$string['setshowsectiontitlesummaryposition_help'] = 'Set the the section title summary position when hovering over the grid box to one of: \'top\', \'bottom\', \'left\' or \'right\'.';
$string['defaultsetshowsectiontitlesummaryposition'] = 'Set the section title summary on hover position option';
$string['defaultsetshowsectiontitlesummaryposition_desc'] = 'Set the the section title summary position when hovering over the grid box to one of: \'top\', \'bottom\', \'left\' or \'right\'.';
$string['sectiontitlesummarymaxlength'] = 'Set the section title summary maximum length on hover';
$string['sectiontitlesummarymaxlength_help'] = 'Set the the section title summary maxium length when hovering over the grid box.  Enter \'0\' for no truncation.';
$string['defaultsectiontitlesummarymaxlength'] = 'Set the section title summary maximum length on hover';
$string['defaultsectiontitlesummarymaxlength_desc'] = 'Set the the section title summary maxium length when hovering over the grid box.  Enter \'0\' for no truncation.';
$string['sectiontitlesummarytextcolour'] = 'Set the section title summary text colour on hover';
$string['sectiontitlesummarytextcolour_help'] = 'Set the the section title summary text colour when hovering over the section title in the grid box.';
$string['defaultsectiontitlesummarytextcolour'] = 'Set the section title summary text colour on hover';
$string['defaultsectiontitlesummarytextcolour_desc'] = 'Set the the section title summary text colour when hovering over the section title in the grid box.';
$string['sectiontitlesummarybackgroundcolour'] = 'Set the section title summary background colour on hover';
$string['sectiontitlesummarybackgroundcolour_help'] = 'Set the the section title summary background colour when hovering over the section title in the grid box.';
$string['defaultsectiontitlesummarybackgroundcolour'] = 'Set the section title summary background colour on hover';
$string['defaultsectiontitlesummarybackgroundcolour_desc'] = 'Set the the section title summary background colour when hovering over the section title in the grid box.';
$string['sectiontitlesummarybackgroundopacity'] = 'Set the section title summary background opacity on hover';
$string['sectiontitlesummarybackgroundopacity_help'] = 'Set the the section title summary background opacity, between 0 and 1 in 0.1 increments, when hovering over the section title in the grid box.';
$string['defaultsectiontitlesummarybackgroundopacity'] = 'Set the section title summary background opacity on hover';
$string['defaultsectiontitlesummarybackgroundopacity_desc'] = 'Set the the section title summary background opacity, between 0 and 1 in 0.1 increments, when hovering over the section title in the grid box.';
$string['top'] = 'Top';
$string['bottom'] = 'Bottom';
$string['centre'] = 'Centre';
$string['left'] = 'Left';
$string['right'] = 'Right';

// Reset.
$string['resetgrp'] = 'Reset:';
$string['resetallgrp'] = 'Reset all:';
$string['resetimagecontaineralignment'] = 'Image container alignment';
$string['resetimagecontaineralignment_help'] = 'Resets the image container alignment to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallimagecontaineralignment'] = 'Image container alignments';
$string['resetallimagecontaineralignment_help'] = 'Resets the image container alignmentss to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetimagecontainersize'] = 'Image container size';
$string['resetimagecontainersize_help'] = 'Resets the image container size to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallimagecontainersize'] = 'Image container sizes';
$string['resetallimagecontainersize_help'] = 'Resets the image container sizes to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetimageresizemethod'] = 'Image resize method';
$string['resetimageresizemethod_help'] = 'Resets the image resize method to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallimageresizemethod'] = 'Image resize methods';
$string['resetallimageresizemethod_help'] = 'Resets the image resize methods to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetimagecontainerstyle'] = 'Image container style';
$string['resetimagecontainerstyle_help'] = 'Resets the image container style to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallimagecontainerstyle'] = 'Image container styles';
$string['resetallimagecontainerstyle_help'] = 'Resets the image container styles to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetsectiontitleoptions'] = 'Section title options';
$string['resetsectiontitleoptions_help'] = 'Resets the section title options to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallsectiontitleoptions'] = 'Section title options';
$string['resetallsectiontitleoptions_help'] = 'Resets the section title options to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetnewactivity'] = 'New activity';
$string['resetnewactivity_help'] = 'Resets the new activity notification image to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallnewactivity'] = 'New activities';
$string['resetallnewactivity_help'] = 'Resets the new activity notification images to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetfitpopup'] = 'Fit section popup to the window';
$string['resetfitpopup_help'] = 'Resets the \'Fit section popup to the window\' to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallfitpopup'] = 'Fit section popups to the window';
$string['resetallfitpopup_help'] = 'Resets the \'Fit section popup to the window\' to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';
$string['resetgreyouthidden'] = 'Grey out unavailable';
$string['resetgreyouthidden_desc'] = 'Resets the property \'Grid display show unavailable section images in grey and unlinked.\'';
$string['resetgreyouthidden_help'] = 'Resets the property \'In Grid display show unavailable section images in grey and unlinked.\'';

// Section 0 on own page when out of the grid and course layout is 'Show one section per page'.
$string['setsection0ownpagenogridonesection'] = 'Section 0 on its own page when out of the grid and on a single section page';
$string['setsection0ownpagenogridonesection_help'] = 'Have section 0 on its own page when it is out of the grid and the \'Course layout\' setting is \'One section per page\'.';
$string['defaultsection0ownpagenogridonesection'] = 'Section 0 on its own page when out of the grid and on a single section page';
$string['defaultsection0ownpagenogridonesection_desc'] = 'Have section 0 on its own page when it is out of the grid and the \'Course layout\' setting is \'One section per page\'.';
$string['resetimagecontainernavigation'] = 'Image container navigation';
$string['resetimagecontainernavigation_help'] = 'Resets the image container navigation to the default value so it will be the same as a course the first time it is in the Grid format.';
$string['resetallimagecontainernavigation'] = 'Image container navigations';
$string['resetallimagecontainernavigation_help'] = 'Resets the image container navigation to the default value for all courses so it will be the same as a course the first time it is in the Grid format.';

// Capabilities.
$string['grid:changeimagecontaineralignment'] = 'Change or reset the image container alignment';
$string['grid:changeimagecontainernavigation'] = 'Change or reset the image container navigation';
$string['grid:changeimagecontainersize'] = 'Change or reset the image container size';
$string['grid:changeimageresizemethod'] = 'Change or reset the image resize method';
$string['grid:changeimagecontainerstyle'] = 'Change or reset the image container style';
$string['grid:changesectiontitleoptions'] = 'Change or reset the section title options';

// Other.
$string['greyouthidden'] = 'Grey out unavailable';
$string['greyouthidden_desc'] = 'In Grid display show unavailable section images in grey and unlinked.';
$string['greyouthidden_help'] = 'In Grid display show unavailable section images in grey and unlinked.';

$string['custommousepointers'] = 'Use custom mouse pointers';
$string['custommousepointers_desc'] = 'In Grid use custom mouse pointers.';

// Privacy.
$string['privacy:nop'] = 'The Grid format stores lots of settings that pertain to its configuration.  None of the settings are related to a specific user.  It is your responsibilty to ensure that no user data is entered in any of the free text fields.  Setting a setting will result in that action being logged within the core Moodle logging system against the user whom changed it, this is outside of the formats control, please see the core logging system for privacy compliance for this.  When uploading images, you should avoid uploading images with embedded location data (EXIF GPS) included or other such personal data.  It would be possible to extract any location / personal data from the images.  Please examine the code carefully to be sure that it complies with your interpretation of your privacy laws.  I am not a lawyer and my analysis is based on my interpretation.  If you have any doubt then remove the format forthwith.';
