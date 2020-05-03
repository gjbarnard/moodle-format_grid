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
/* Imports */
require_once('../../../config.php');
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/course/format/grid/editimage_form.php');
require_once($CFG->dirroot . '/course/format/grid/lib.php');

/* Page parameters */
$contextid = required_param('contextid', PARAM_INT);
$sectionid = required_param('sectionid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

/* No idea, copied this from an example. Sets form data options but I don't know what they all do exactly */
$formdata = new stdClass();
$formdata->userid = required_param('userid', PARAM_INT);
$formdata->offset = optional_param('offset', null, PARAM_INT);
$formdata->forcerefresh = optional_param('forcerefresh', null, PARAM_INT);
$formdata->mode = optional_param('mode', null, PARAM_ALPHA);
$formdata->alttext = optional_param('alttext', null, PARAM_TEXT);

$url = new moodle_url('/course/format/grid/editimage.php', array(
    'contextid' => $contextid,
    'id' => $id,
    'offset' => $formdata->offset,
    'forcerefresh' => $formdata->forcerefresh,
    'userid' => $formdata->userid,
    'mode' => $formdata->mode));

/* Not exactly sure what this stuff does, but it seems fairly straightforward */
list($context, $course, $cm) = get_context_info_array($contextid);

require_login($course, true, $cm);
if (isguestuser()) {
    die();
}

$PAGE->set_url($url);
$PAGE->set_context($context);

/* Functional part. Create the form and display it, handle results, etc */
$options = array(
    'subdirs' => 0,
    'maxfiles' => 1,
    'accepted_types' => array('gif', 'jpe', 'jpeg', 'jpg', 'png'),
    'return_types' => FILE_INTERNAL);

// Fetch the existing grid image record if it exists.
$alttext = $DB->get_field('format_grid_icon', 'alttext', array('courseid'=>$course->id, 'sectionid'=>$sectionid), IGNORE_MISSING);

$mform = new grid_image_form(null, array(
    'contextid' => $contextid,
    'userid' => $formdata->userid,
    'sectionid' => $sectionid,
    'options' => $options,
    'alttext' => $alttext));

if ($mform->is_cancelled()) {
    // Someone has hit the 'cancel' button.
    redirect(new moodle_url($CFG->wwwroot . '/course/view.php?id=' . $course->id));
} else if ($formdata = $mform->get_data()) { // Form has been submitted.
    if ($formdata->deleteimage == 1) {
        // Delete the old images....
        $courseformat = course_get_format($course);
        $courseformat->delete_image($sectionid, $context->id);
    } else {
        if ($newfilename = $mform->get_new_filename('imagefile')) {
            $fs = get_file_storage();

            // We have a new file so can delete the old....
            $courseformat = course_get_format($course);
            $sectionimage = $courseformat->get_image($course->id, $sectionid);
            if (isset($sectionimage->image)) {
                if ($file = $fs->get_file($context->id, 'course', 'section', $sectionid, '/', $sectionimage->image)) {
                    $file->delete();
                }
            }

            // Resize the new image and save it...
            $storedfilerecord = $courseformat->create_original_image_record($contextid, $sectionid, $newfilename);

            $tempfile = $mform->save_stored_file(
                    'imagefile',
                    $storedfilerecord['contextid'],
                    $storedfilerecord['component'],
                    $storedfilerecord['filearea'],
                    $storedfilerecord['itemid'],
                    $storedfilerecord['filepath'],
                    'temp.' . $storedfilerecord['filename'],
                    true);

            $courseformat->create_section_image($tempfile, $storedfilerecord, $sectionimage);
        }

        // Set alt text value regardless of whether image has changed.
        $conditionsarray = array('courseid' => $course->id, 'sectionid' => $sectionid);
        $DB->set_field('format_grid_icon', 'alttext', $formdata->alttext, $conditionsarray);
    }

    redirect($CFG->wwwroot . "/course/view.php?id=" . $course->id);
}

/* Draw the form */
echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
