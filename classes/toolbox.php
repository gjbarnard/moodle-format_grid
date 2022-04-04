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
 * @copyright  &copy; 2021-onwards G J Barnard based upon work done by Marina Glancy.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid;

/**
 * The format's toolbox.
 *
 * @copyright  &copy; 2021-onwards G J Barnard.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class toolbox {
    // CONTRIB-4099:....
    // Width constants - 128, 192, 210, 256, 320, 384, 448, 512, 576, 640, 704 and 768:...
    private static $imagecontainerwidths = array(128 => '128', 192 => '192', 210 => '210', 256 => '256', 320 => '320',
        384 => '384', 448 => '448', 512 => '512', 576 => '576', 640 => '640', 704 => '704', 768 => '768');
    // Ratio constants - 3-2, 3-1, 3-3, 2-3, 1-3, 4-3 and 3-4:...
    private static $imagecontainerratios = array(
        1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4');
    // Border width constants - 0 to 10:....
    private static $borderwidths = array(0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5', 6 => '6', 7 => '7',
        8 => '8', 9 => '9', 10 => '10');
    /* Image holder height and new activity position for all on the basis that once calculated the majority of courses
      will be the same. */
    private static $currentwidth = 210;
    private static $currentratio = 1; // 3-2.
    private static $currentborderwidth = 3;
    private static $currentheight = 140;
    private static $activitymargintop = 101;
    private static $activitymarginleft = 1118;
    // Opacity constants - 0 to 1:....
    private static $opacities = array('0' => '0.0', '.1' => '0.1', '.2' => '0.2', '.3' => '0.3', '.4' => '0.4',
       '.5' => '0.5', '.6' => '0.6', '.7' => '0.7', '.8' => '0.8', '.9' => '0.9', '1' => '1.0');
    private static $sectiontitlefontsizes = array(0 => '0', 12 => '12', 13 => '13', 14 => '14', 15 => '15', 16 => '16',
       17 => '17', 18 => '18', 19 => '19', 20 => '20', 21 => '21', 22 => '22', 23 => '23', 24 => '24');

    /**
     * This is a lonely object.
     */
    private function __construct() {
    }

    /**
     * Gets the toolbox singleton.
     *
     * @return toolbox The toolbox instance.
     */
    public static function get_instance() {
        if (!is_object(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of horizontal alignments.
     */
    public static function get_horizontal_alignments() {
        $imagecontaineralignments = array(
            'left' => get_string('left', 'format_grid'),
            'center' => get_string('centre', 'format_grid'),
            'right' => get_string('right', 'format_grid')
        );
        return $imagecontaineralignments;
    }

    /**
     * Gets the default image container width.
     * @return int Default image container alignment.
     */
    public static function get_default_image_container_alignment() {
        return 'center';
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of imagecontainer widths.
     */
    public static function get_image_container_widths() {
        return self::$imagecontainerwidths;
    }

    /**
     * Gets the default image container width.
     * @return int Default image container width.
     */
    public static function get_default_image_container_width() {
        return 210;
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of image container ratios.
     */
    public static function get_image_container_ratios() {
        return self::$imagecontainerratios;
    }

    /**
     * Gets the default image container ratio.
     * @return int Default image container ratio.
     */
    public static function get_default_image_container_ratio() {
        return 1; // Ratio of '3-2'.
    }

    /**
     * Gets the default image resize method.
     * @return int Default image resize method.
     */
    public static function get_default_image_resize_method() {
        return 1; // Scale.
    }

    /**
     * Gets the default image file type.
     * @return int Default image file type.
     */
    public static function get_default_image_file_type() {
        return 1; // Original.
    }

    /**
     * Gets the default border colour.
     * @return string Default border colour.
     */
    public static function get_default_border_colour() {
        return '#dddddd';
    }

    /**
     * Prevents ability to change a static variable outside of the class.
     * @return array Array of border widths.
     */
    public static function get_border_widths() {
        return self::$borderwidths;
    }

    /**
     * Gets the default border width.
     * @return int Default border width.
     */
    public static function get_default_border_width() {
        return 3; // Pixels.
    }

    /**
     * Gets the default border width.
     * @return int Default border width.
     */
    public static function get_default_border_radius() {
        return 2; // On.
    }

    /**
     * Gets the default image container background colour.
     * @return string Default image container background colour.
     */
    public static function get_default_image_container_background_colour() {
        return '#f1f2f2';
    }

    /**
     * Gets the default current selected section colour.
     * @return string Default current selected section colour.
     */
    public static function get_default_current_selected_section_colour() {
        return '#8E66FF';
    }

    /**
     * Gets the default current selected image container text colour.
     * @return string Default current selected image container text colour.
     */
    public static function get_default_current_selected_image_container_text_colour() {
        return '#3b53ad';
    }

    /**
     * Gets the default current selected image container colour.
     * @return string Default current selected image container colour.
     */
    public static function get_default_current_selected_image_container_colour() {
        return '#ffc540';
    }

    /**
     * Gets the default hide section title.
     * @return int Default default hide section title.
     */
    public static function get_default_hide_section_title() {
        return 1; // No.
    }

    /**
     * Gets the default section title grid length max option.
     * @return int Default default section title grid length max option.
     */
    public static function get_default_section_title_grid_length_max_option() {
        return 0; // No truncation.
    }

    /**
     * Gets the default section title box position.
     * @return int Default default section title box position.
     */
    public static function get_default_section_title_box_position() {
        return 2; // Outside.
    }

    /**
     * Gets the default section title box inside position.
     * @return int Default default section title box inside position.
     */
    public static function get_default_section_title_box_inside_position() {
        return 1; // Top.
    }

    /**
     * Gets the default section title box height.
     * @return int Default default section title box height.
     */
    public static function get_default_section_title_box_height() {
        return 0; // Calculated.
    }

    /**
     * Gets the default opacity for the section title box.
     * @return string Opacity of section title box.
     */
    public static function get_default_section_title_box_opacity() {
        return '.8';
    }

    /**
     * Gets the default opacities.
     * @return Array Opacities.
     */
    public static function get_default_opacities() {
        return self::$opacities;
    }

    /**
     * Gets the default font size for the section title.
     * @return int Font size of the section title.
     */
    public static function get_default_section_title_font_size() {
        return 0;
    }

    /**
     * Gets the default alignment for the section title.
     * @return string Alignment of the section title.
     */
    public static function get_default_section_title_alignment() {
        return 'center';
    }

    /**
     * Gets the default section title font sizes.
     * @return Array Font sizes.
     */
    public static function get_default_section_font_sizes() {
        return self::$sectiontitlefontsizes;
    }

    /**
     * Gets the default section title inside text colour.
     * @return string Default default section title inside text colour.
     */
    public static function get_default_section_title_inside_title_text_colour() {
        return '#000000';
    }

    /**
     * Gets the default section title inside background colour.
     * @return string Default default section title inside background colour.
     */
    public static function get_default_section_title_inside_title_background_colour() {
        return '#ffffff';
    }

    /**
     * Gets the default show section title summary.
     * @return int Default default show section title summary.
     */
    public static function get_default_show_section_title_summary() {
        return 2; // Yes.
    }

    /**
     * Gets the default set show section title summary position.
     * @return int Default default set show section title summary position.
     */
    public static function get_default_set_show_section_title_summary_position() {
        return 1; // Top.
    }

    /**
     * Gets the default section title summary max length.
     * @return int Default default section title summary max length.
     */
    public static function get_default_section_title_summary_max_length() {
        return 0; // No truncation.
    }

    /**
     * Gets the default section title summary on hover text colour.
     * @return string Default section title summary on hover text colour.
     */
    public static function get_default_section_title_summary_text_colour() {
        return '#3b53ad';
    }

    /**
     * Gets the default section title summary on hover background colour.
     * @return string Default section title summary on hover background colour.
     */
    public static function get_default_section_title_summary_background_colour() {
        return '#ffc540';
    }

    /**
     * Gets the default opacity for the section title summary on hover.
     * @return string Opacity of section title summary on hover.
     */
    public static function get_default_section_title_summary_opacity() {
        return '1';
    }

    /**
     * Gets the displayed image path for storage of the displayed image.
     * @return string The path.
     */
    public static function get_image_path() {
        return '/gridimage/';
    }

    public static function get_maximum_image_width() {
        return 768;
    }

    /**
     * Validates the opacity that was entered by the user.
     *
     * @param string $data the opacity string to validate.
     * @return true|false
     */
    public static function validate_opacity($data) {
        if ($data == '-') {
            return true;
        } else if (array_key_exists($data, self::$opacities)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Validates the font size that was entered by the user.
     *
     * @param string $data the font size integer to validate.
     * @return true|false
     */
    public static function validate_section_title_font_size($data) {
        if ($data == '-') {
            return true;
        } else if (array_key_exists($data, self::$sectiontitlefontsizes)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get summary visibility, if it doesn't exist create it.
     * The summary visibility is if section 0 is displayed (1) or in the grid(0).
     * @param int $courseid The course id.
     * @param format_grid $courseformat The course format.
     * @return stdClass The summary visibility for the course or false if not found.
     * @throws moodle_exception If cannot add a new record to the 'format_grid_summary' table.
     */
    public static function get_summary_visibility($courseid, $courseformat) {
        global $DB;
        // Only allow this code to be executed once at the same time for the given course id (the id is unique).
        $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
        if ($lock = $lockfactory->get_lock('courseid'.$courseid, 5)) {
            if (!$summarystatus = $DB->get_record('format_grid_summary', array('courseid' => $courseid))) {
                $newstatus = new \stdClass();
                $newstatus->courseid = $courseid;
                $newstatus->showsummary = 1;

                if (!$newstatus->id = $DB->insert_record('format_grid_summary', $newstatus)) {
                    $lock->release();
                    throw new \moodle_exception('invalidsummaryrecordid', 'format_grid', '', get_string('invalidsummaryrecordid', 'format_grid'));
                }
                $summarystatus = $newstatus;

                /* Technically this only happens once when the course is created, so we can use it to set the
                 * course format options for the first time.  This so that the defaults are set upon creation
                 * and therefore do not have to detect when they change in the global site settings.  Which
                 * cannot be detected and therefore the icons would look odd.  So here they are set and set once
                 * until course settings are reset or changed.
                 */
                $courseformat->update_course_format_options($courseformat->get_settings());
            }
            $lock->release();
        } else {
            throw new \moodle_exception('cannotgetsummarylock', 'format_grid', '', get_string('cannotgetsummarylock', 'format_grid'));
        }
        return $summarystatus;
    }

    /**
     * Returns a moodle_url to a grid format file.
     * @param string $url The URL to append.
     * @param array $params URL parameters.
     * @return moodle_url The created URL.
     */
    public static function grid_moodle_url($url, array $params = null) {
        return new \moodle_url('/course/format/grid/' . $url, $params);
    }

    /**
     * Gets the grid image entries for the given course.
     * @param int $courseid The course id to use.
     * @returns bool|array The records or false if the course id is 0 or the request failed.
     */
    public static function get_images($courseid) {
        global $DB;

        if (!$courseid) {
            return false;
        }

        if (!$sectionimagecontainers = $DB->get_records('format_grid_icon', array('courseid' => $courseid), '',
                'sectionid, image, displayedimageindex, updatedisplayedimage, alttext')) {
            $sectionimagecontainers = false;
        }
        return $sectionimagecontainers;
    }

    /**
     * Gets the grid image entry for the given course and section.  If an entry cannot be found then one is created
     * and returned.  If the course id is set to the default then it is updated to the one supplied as the value
     * will be accurate.
     * @param int $courseid The course id to use.
     * @param int $sectionid The section id to use.
     * @returns bool|array The record or false if the course id is 0 or section id is 0 or the request failed.
     */
    public static function get_image($courseid, $sectionid) {
        global $DB;

        if ((!$courseid) || (!$sectionid)) {
            return false;
        }

        // Only allow this code to be executed once at the same time for the given section id (the id is unique).
        $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
        if ($lock = $lockfactory->get_lock('sectionid'.$sectionid, 5)) {
            if (!$sectionimage = $DB->get_record('format_grid_icon', array('sectionid' => $sectionid),
                'courseid, sectionid, image, displayedimageindex, updatedisplayedimage, alttext')) {
                $newimagecontainer = new \stdClass();
                $newimagecontainer->sectionid = $sectionid;
                $newimagecontainer->courseid = $courseid;
                $newimagecontainer->displayedimageindex = 0;
                $newimagecontainer->updatedisplayedimage = 0;

                if (!$newimagecontainer->id = $DB->insert_record('format_grid_icon', $newimagecontainer, true)) {
                    $lock->release();
                    throw new \moodle_exception('invalidiconrecordid', 'format_grid', '', get_string('invalidiconrecordid', 'format_grid'));
                }
                $sectionimage = $newimagecontainer;
            } else if ($sectionimage->courseid == 1) { // 1 is the default and is the 'site' course so cannot be the Grid format.
                // Note: Using a double equals in the test and not a triple as the latter does not work for some reason.
                /* Course id is the default and needs to be set correctly.  This can happen with data created by versions prior to
                13/7/2012. */
                $DB->set_field('format_grid_icon', 'courseid', $courseid, array('sectionid' => $sectionid));
                $sectionimage->courseid = $courseid;
            }
            $lock->release();
        } else {
            throw new \moodle_exception('cannotgetimagelock', 'format_grid', '', get_string('cannotgetimagelock', 'format_grid'));
        }
        return $sectionimage;
    }

    // CONTRIB-4099 methods:....
    /**
     * Calculates the image container properties so that it can be rendered correctly.
     * @param int $imagecontainerwidth The width of the container.
     * @param int $imagecontainerratio The ratio array index of the container, see: $imagecontainerratios.
     * @param int $borderwidth The width of the border.
     * @return array with the key => value of 'height' for the container and 'margin-top' and 'margin-left' for the new activity
     *               image.
     */
    public static function calculate_image_container_properties($imagecontainerwidth, $imagecontainerratio, $borderwidth) {

        if (($imagecontainerwidth !== self::$currentwidth) || ($imagecontainerratio !== self::$currentratio) ||
                ($borderwidth !== self::$currentborderwidth)) {
            $height = self::calculate_height($imagecontainerwidth, $imagecontainerratio);
            // This is: margin-top = image holder height - ( image height - border width)).
            // This is: margin-left = (image holder width - image width) + border width.

            $result = array(
                'height' => $height,
                'margin-top' => $height - (42 - $borderwidth),
                'margin-left' => ($imagecontainerwidth - 95) + $borderwidth
            );

            // Store:...
            self::$currentwidth = $imagecontainerwidth;
            self::$currentratio = $imagecontainerratio;
            self::$currentborderwidth = $borderwidth;
            self::$currentheight = $result['height'];
            self::$activitymargintop = $result['margin-top'];
            self::$activitymarginleft = $result['margin-left'];
        } else {
            $result = array(
                'height' => self::$currentheight,
                'margin-top' => self::$activitymargintop,
                'margin-left' => self::$activitymarginleft
            );
        }

        return $result;
    }

    /**
     * Gets the image container properties given the settings.
     * @param array $settings Must have integer keys for 'imagecontainerwidth' and 'imagecontainerratio'.
     * @return array with the key => value of 'height' and 'width' for the container.
     */
    public static function get_displayed_image_container_properties($settings) {
        return array('height' => self::calculate_height($settings['imagecontainerwidth'], $settings['imagecontainerratio']),
            'width' => $settings['imagecontainerwidth']);
    }

    /**
     * Calculates height given the width and ratio.
     * @param int $width.
     * @param int $ratio.
     * @return int Height.
     */
    private static function calculate_height($width, $ratio) {
        $basewidth = $width;

        switch ($ratio) {
            // Ratios 1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4'.
            case 1: // 3-2.
            case 2: // 3-1.
            case 3: // 3-3.
            case 7: // 3-4.
                $basewidth = $width / 3;
                break;
            case 4: // 2-3.
                $basewidth = $width / 2;
                break;
            case 5: // 1-3.
                $basewidth = $width;
                break;
            case 6: // 4-3.
                $basewidth = $width / 4;
                break;
        }

        $height = $basewidth;
        switch ($ratio) {
            // Ratios 1 => '3-2', 2 => '3-1', 3 => '3-3', 4 => '2-3', 5 => '1-3', 6 => '4-3', 7 => '3-4'.
            case 2: // 3-1.
                $height = $basewidth;
                break;
            case 1: // 3-2.
                $height = $basewidth * 2;
                break;
            case 3: // 3-3.
            case 4: // 2-3.
            case 5: // 1-3.
            case 6: // 4-3.
                $height = $basewidth * 3;
                break;
            case 7: // 3-4.
                $height = $basewidth * 4;
                break;
        }

        return round($height);
    }

    public static function create_original_image_record($contextid, $sectionid, $filename) {
        $created = time();
        $storedfilerecord = array(
            'contextid' => $contextid,
            'component' => 'course',
            'filearea' => 'section',
            'itemid' => $sectionid,
            'filepath' => '/',
            // CONTRIB-5001 - Avoid clashes with the same image in the section summary by using a different name.
            'filename' => 'goi_' . $filename, // goi = tla = grid original image.
            'timecreated' => $created,
            'timemodified' => $created);

        return $storedfilerecord;
    }

    public static function delete_displayed_images($courseformat) {
        $sectionimages = self::get_images($courseformat->get_courseid());

        if (is_array($sectionimages)) {
            global $DB;
            $contextid = \format_grid::get_contextid($courseformat);
            $fs = get_file_storage();
            $gridimagepath = self::get_image_path();
            $t = $DB->start_delegated_transaction();

            foreach ($sectionimages as $sectionimage) {
                // Delete the displayed image.
                self::delete_displayed_image($contextid, $sectionimage, $gridimagepath, $fs);
            }
            $t->allow_commit();
        }
    }

    public static function delete_displayed_image($contextid, $sectionimage, $gridimagepath, $fs) {
        global $DB;

        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
            $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
            $file->delete();
            $DB->set_field('format_grid_icon', 'displayedimageindex', 0, array('sectionid' => $sectionimage->sectionid));
        }
        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
            $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
            $file->delete();
            $DB->set_field('format_grid_icon', 'displayedimageindex', 0, array('sectionid' => $sectionimage->sectionid));
        }
    }

    public static function create_section_image($tempfile, $storedfilerecord, $sectionimage, $courseformat) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gdlib.php');

        $fs = get_file_storage();

        try {
            $convertsuccess = true;

            // Ensure the right quality setting...
            $mime = $tempfile->get_mimetype();

            $imageinfo = $tempfile->get_imageinfo();
            $imagemaxwidth = self::get_maximum_image_width();
            if ($imageinfo['width'] > $imagemaxwidth) { // Maximum width as defined in lib.php.
                // Recalculate height:...
                $ratio = $imagemaxwidth / $imageinfo['width'];
                $imageinfo['height'] = $imageinfo['height'] * $ratio; // Maintain image's aspect ratio.
                // Set width:...
                $imageinfo['width'] = $imagemaxwidth;
            }

            $storedfilerecord['mimetype'] = $mime;

            // Note: It appears that this works with transparent Gif's to, so simplifying.
            $tmproot = make_temp_directory('gridformaticon');
            $tmpfilepath = $tmproot . '/' . $tempfile->get_contenthash();
            $tempfile->copy_content_to($tmpfilepath);

            $data = generate_image_thumbnail($tmpfilepath, $imageinfo['width'], $imageinfo['height']);
            if (!empty($data)) {
                $fs->create_file_from_string($storedfilerecord, $data);
            } else {
                $convertsuccess = false;
            }
            unlink($tmpfilepath);

            $tempfile->delete();
            unset($tempfile);

            if ($convertsuccess == true) {
                $DB->set_field('format_grid_icon', 'image', $storedfilerecord['filename'],
                    array('sectionid' => $storedfilerecord['itemid']));

                // Set up the displayed image:...
                $sectionimage->newimage = $storedfilerecord['filename'];
                $settings = $courseformat->get_settings();
                self::setup_displayed_image($sectionimage, $storedfilerecord['contextid'], $courseformat->get_courseid(), $settings, $mime);
            } else {
                print_error('imagecannotbeused', 'format_grid', $CFG->wwwroot . "/course/view.php?id=" . $courseformat->get_courseid());
            }
        } catch (Exception $e) {
            if (isset($tempfile)) {
                $tempfile->delete();
                unset($tempfile);
            }
            print('Grid Format Image Exception:...');
            debugging($e->getMessage());
        }
    }

    /**
     * Set up the displayed image.
     * @param array $sectionimage Section information from its row in the 'format_grid_icon' table.
     * @param int $contextid The context id to which the image relates.
     * @param int $courseid The course id to which the image relates.
     * @param array $settings The course settings to apply.
     * @param string $mime The mime type if already known.
     * @return array The updated $sectionimage data.
     */
    public static function setup_displayed_image($sectionimage, $contextid, $courseid, $settings, $mime = null) {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/repository/lib.php');
        require_once($CFG->libdir . '/gdlib.php');

        // Set up the displayed image:...
        $fs = get_file_storage();
        if ($imagecontainerpathfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, '/',
                $sectionimage->newimage)) {
            $gridimagepath = self::get_image_path();
            $convertsuccess = true;
            if (!$mime) {
                $mime = $imagecontainerpathfile->get_mimetype();
            }

            $displayedimageinfo = self::get_displayed_image_container_properties($settings);
            $tmproot = make_temp_directory('gridformatdisplayedimagecontainer');
            $tmpfilepath = $tmproot . '/' . $imagecontainerpathfile->get_contenthash();
            $imagecontainerpathfile->copy_content_to($tmpfilepath);

            if ($settings['imageresizemethod'] == 1) {
                $crop = false;
            } else {
                $crop = true;
            }
            $iswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);
            if ($iswebp) { // WebP.
                $newmime = 'image/webp';
            } else {
                $newmime = $mime;
            }
            $debugdata = array(
                'itemid' => $imagecontainerpathfile->get_itemid(),
                'filename' => $imagecontainerpathfile->get_filename(),
                'sectionimage_sectionid' => $sectionimage->sectionid,
                'sectionimage_image' => $sectionimage->image,
                'sectionimage_displayedimageindex' => $sectionimage->displayedimageindex,
                'sectionimage_newimage' => $sectionimage->newimage
            );
            $data = self::generate_image($tmpfilepath, $displayedimageinfo['width'], $displayedimageinfo['height'], $crop, $newmime, $debugdata);
            if (!empty($data)) {
                // Updated image.
                $sectionimage->displayedimageindex++;
                $created = time();
                $displayedimagefilerecord = array(
                    'contextid' => $contextid,
                    'component' => 'course',
                    'filearea' => 'section',
                    'itemid' => $sectionimage->sectionid,
                    'filepath' => $gridimagepath,
                    'filename' => $sectionimage->displayedimageindex . '_' . $sectionimage->newimage,
                    'timecreated' => $created,
                    'timemodified' => $created,
                    'mimetype' => $mime);

                self::remove_existing_new_displayed_image($displayedimagefilerecord, $fs);

                if ($iswebp) { // WebP.
                    // Displayed image is a webp image from the original, so change a few things.
                    $displayedimagefilerecord['filename'] = $sectionimage->displayedimageindex . '_' . $sectionimage->newimage.'.webp';
                    $displayedimagefilerecord['mimetype'] = $newmime;
                }
                $fs->create_file_from_string($displayedimagefilerecord, $data);
            } else {
                $convertsuccess = false;
            }
            unlink($tmpfilepath);

            if ($convertsuccess == true) {
                // Now safe to delete old file(s) if they exist.
                if ($oldfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                        ($sectionimage->displayedimageindex - 1) . '_' . $sectionimage->image)) {
                    $oldfile->delete();
                }
                if ($oldfile = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                        ($sectionimage->displayedimageindex - 1) . '_' . $sectionimage->image.'.webp')) {
                    $oldfile->delete();
                }
                $DB->set_field('format_grid_icon', 'displayedimageindex', $sectionimage->displayedimageindex,
                    array('sectionid' => $sectionimage->sectionid));
                if ($sectionimage->updatedisplayedimage == 1) {
                    $DB->set_field('format_grid_icon', 'updatedisplayedimage', 0,
                        array('sectionid' => $sectionimage->sectionid));
                    $sectionimage->updatedisplayedimage = 0;
                }
            } else {
                print_error('cannotconvertuploadedimagetodisplayedimage', 'format_grid',
                    $CFG->wwwroot."/course/view.php?id=".$courseid,
                    'SI: '.var_export($sectionimage, true).', DII: '.var_export($displayedimageinfo, true));
            }
        } else {
            $DB->set_field('format_grid_icon', 'image', null, array('sectionid' => $sectionimage->sectionid));
        }

        return $sectionimage;  // So that the caller can know the new value of displayedimageindex.
    }

    protected static function remove_existing_new_displayed_image($displayedimagefilerecord, $fs) {
        // Can happen if previously updating the section name did not delete the displayed image.
        if ($fs->file_exists($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
            $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
            $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'])) {
            /* This can happen with previous CONTRIB-4099 versions where it was possible for the backup file to
               have the 'gridimage' files too.  Therefore without this, then 'create_file_from_string' below will
               baulk as the file already exists.   Unfortunately has to be here as the restore mechanism restores
               the grid format data for the database and then the files.  And the Grid code is called at the 'data'
               stage. */
            if ($oldfile = $fs->get_file($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
                $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
                $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'])) {
                // Delete old file.
                $oldfile->delete();
            }
        }
        // WebP version.
        if ($fs->file_exists($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
            $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
            $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'].'.webp')) {
            /* This can happen with previous CONTRIB-4099 versions where it was possible for the backup file to
               have the 'gridimage' files too.  Therefore without this, then 'create_file_from_string' below will
               baulk as the file already exists.   Unfortunately has to be here as the restore mechanism restores
               the grid format data for the database and then the files.  And the Grid code is called at the 'data'
               stage. */
            if ($oldfile = $fs->get_file($displayedimagefilerecord['contextid'], $displayedimagefilerecord['component'],
                $displayedimagefilerecord['filearea'], $displayedimagefilerecord['itemid'],
                $displayedimagefilerecord['filepath'], $displayedimagefilerecord['filename'].'.webp')) {
                // Delete old file.
                $oldfile->delete();
            }
        }
    }

    public static function output_section_image($section, $sectionname, $sectionimage, $contextid, $thissection, $gridimagepath, $output, $iswebp) {
        $content = '';
        $alttext = isset($sectionimage->alttext) ? $sectionimage->alttext : '';

        if (is_object($sectionimage) && ($sectionimage->displayedimageindex > 0)) {
            $filename = $sectionimage->displayedimageindex . '_' . $sectionimage->image;
            if ($iswebp) {
                $filename .= '.webp';
            }
            $imgurl = \moodle_url::make_pluginfile_url(
                $contextid, 'course', 'section', $thissection->id, $gridimagepath,
                $filename
            );
            $content = \html_writer::empty_tag('img', array(
                'src' => $imgurl,
                'alt' => $alttext,
                'role' => 'img',
                'aria-label' => $sectionname));
        } else if ($section == 0) {
            $imgurl = $output->image_url('info', 'format_grid');
            $content = \html_writer::empty_tag('img', array(
                'src' => $imgurl,
                'alt' => $alttext,
                'class' => 'info',
                'role' => 'img',
                'aria-label' => $sectionname));
        }
        return $content;
    }

    public static function delete_image($sectionid, $contextid, $courseid) {
        $sectionimage = self::get_image($courseid, $sectionid);
        if ($sectionimage) {
            global $DB;
            if (!empty($sectionimage->image)) {
                $fs = get_file_storage();

                // Delete the image.
                if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, '/', $sectionimage->image)) {
                    $file->delete();
                    $DB->set_field('format_grid_icon', 'image', null, array('sectionid' => $sectionimage->sectionid));
                    // Delete the displayed image(s).
                    $gridimagepath = self::get_image_path();
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, $gridimagepath,
                            $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
                        $file->delete();
                    }
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionid, $gridimagepath,
                            $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
                        $file->delete();
                    }
                }
            }
            $DB->delete_records("format_grid_icon", array('courseid' => $courseid,
                'sectionid' => $sectionimage->sectionid));
        }
    }

    public static function delete_images($courseid) {
        $sectionimages = self::get_images($courseid);

        if (is_array($sectionimages)) {
            global $CFG, $DB;

            require_once($CFG->dirroot . '/course/format/lib.php'); // For 'course_get_format()'.
            $courseformat = course_get_format($courseid);

            $contextid = \format_grid::get_contextid($courseformat);
            $fs = get_file_storage();
            $gridimagepath = self::get_image_path();

            foreach ($sectionimages as $sectionimage) {
                // Delete the image if there is one.
                if (!empty($sectionimage->image)) {
                    if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, '/',
                            $sectionimage->image)) {
                        $file->delete();
                        // Delete the displayed image(s).
                        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image)) {
                            $file->delete();
                        }
                        if ($file = $fs->get_file($contextid, 'course', 'section', $sectionimage->sectionid, $gridimagepath,
                                $sectionimage->displayedimageindex . '_' . $sectionimage->image.'.webp')) {
                            $file->delete();
                        }
                    }
                }
            }
            $DB->delete_records("format_grid_icon", array('courseid' => $courseid));
        }
    }

    /**
     * Class instance update images callback.
     */
    public static function update_displayed_images_callback() {
        global $DB;
        if ($gridformatcourses = $DB->get_records('course', array('format' => 'grid'), '', 'id')) {
            foreach ($gridformatcourses as $gridformatcourse) {
                self::update_displayed_images($gridformatcourse->id);
            }
        }
    }

    /**
     * Updates the displayed images because the settings have changed.
     *
     * @param int $courseid The course id.
     */
    public static function update_displayed_images($courseid) {
        $sectionimages = self::get_images($courseid);
        if (is_array($sectionimages)) {
            global $DB;
            $t = $DB->start_delegated_transaction();
            foreach ($sectionimages as $sectionimage) {
                if ($sectionimage->displayedimageindex > 0) {
                    $DB->set_field('format_grid_icon', 'updatedisplayedimage', 1,
                        array('sectionid' => $sectionimage->sectionid));
                }
            }
            $t->allow_commit();
        }
    }

    /**
     * Generates a thumbnail for the given image
     *
     * If the GD library has at least version 2 and PNG support is available, the returned data
     * is the content of a transparent PNG file containing the thumbnail. Otherwise, the function
     * returns contents of a JPEG file with black background containing the thumbnail.
     *
     * @param string $filepath the full path to the original image file
     * @param int $requestedwidth the width of the requested image.
     * @param int $requestedheight the height of the requested image.
     * @param bool $crop false = scale, true = crop.
     * @param string $mime The mime type.
     * @param array $debugdata Debug data if the image generation fails.
     *
     * @return string|bool false if a problem occurs or the image data.
     */
    private static function generate_image($filepath, $requestedwidth, $requestedheight, $crop, $mime, $debugdata) {
        if (empty($filepath) or empty($requestedwidth) or empty($requestedheight)) {
            return false;
        }

        $imageinfo = getimagesize($filepath);

        if (empty($imageinfo)) {
            unlink($filepath);
            print_error('noimageinformation', 'format_grid', '', self::debugdata_decode($debugdata), 'generate_image');
            return false;
        }

        $originalwidth = $imageinfo[0];
        $originalheight = $imageinfo[1];

        if (empty($originalheight)) {
            unlink($filepath);
            print_error('originalheightempty', 'format_grid', '', self::debugdata_decode($debugdata), 'generate_image');
            return false;
        }
        if (empty($originalwidth)) {
            unlink($filepath);
            print_error('originalwidthempty', 'format_grid', '', self::debugdata_decode($debugdata), 'generate_image');
            return false;
        }

        $original = imagecreatefromstring(file_get_contents($filepath)); // Need to alter / check for webp support.

        switch ($mime) {
            case 'image/png':
                if (function_exists('imagepng')) {
                    $imagefnc = 'imagepng';
                    $quality = 9;
                } else {
                    unlink($filepath);
                    print_error('formatnotsupported', 'format_grid', '', 'PNG, '.self::debugdata_decode($debugdata), 'generate_image');
                    return false;
                }
                break;
            case 'image/jpeg':
                if (function_exists('imagejpeg')) {
                    $imagefnc = 'imagejpeg';
                    $quality = 90;
                } else {
                    unlink($filepath);
                    print_error('formatnotsupported', 'format_grid', '', 'JPG, '.self::debugdata_decode($debugdata), 'generate_image');
                    return false;
                }
                break;
            /* Moodle does not yet natively support webp as a mime type, but have here for us on the displayed image and
               not yet as a source image. */
            case 'image/webp':
                if (function_exists('imagewebp')) {
                    $imagefnc = 'imagewebp';
                    $quality = 90;
                } else {
                    unlink($filepath);
                    print_error('formatnotsupported', 'format_grid', '', 'WEBP, '.self::debugdata_decode($debugdata), 'generate_image');
                    return false;
                }
                break;
            case 'image/gif':
                if (function_exists('imagegif')) {
                    $imagefnc = 'imagegif';
                    $quality = null;
                } else {
                    unlink($filepath);
                    print_error('formatnotsupported', 'format_grid', '', 'GIF, '.self::debugdata_decode($debugdata), 'generate_image');
                    return false;
                }
                break;
            default:
                unlink($filepath);
                print_error('mimetypenotsupported', 'format_grid', '', $mime.', '.self::debugdata_decode($debugdata), 'generate_image');
                return false;
        }

        if ($crop) {
            $ratio = $requestedwidth / $requestedheight;
            $originalratio = $originalwidth / $originalheight;
            if ($originalratio < $ratio) {
                // Change the supplied height - 'resizeToWidth'.
                $ratio = $requestedwidth / $originalwidth;
                $width = $requestedwidth;
                $height = $originalheight * $ratio;
                $cropheight = true;
            } else {
                // Change the supplied width - 'resizeToHeight'.
                $ratio = $requestedheight / $originalheight;
                $width = $originalwidth * $ratio;
                $height = $requestedheight;
                $cropheight = false;
            }

            if (function_exists('imagecreatetruecolor')) {
                $tempimage = imagecreatetruecolor($width, $height);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($tempimage, false);
                    imagesavealpha($tempimage, true);
                }
            } else {
                $tempimage = imagecreate($width, $height);
            }

            // First step, resize.
            imagecopybicubic($tempimage, $original, 0, 0, 0, 0, $width, $height, $originalwidth, $originalheight);

            // Second step, crop.
            if ($cropheight) {
                // This is 'cropCenterHeight'.
                $srcoffset = ($height / 2) - ($requestedheight / 2);
                $height = $requestedheight;
            } else {
                // This is 'cropCenterWidth'.
                $srcoffset = ($width / 2) - ($requestedwidth / 2);
                $width = $requestedwidth;
            }

            if (function_exists('imagecreatetruecolor')) {
                $finalimage = imagecreatetruecolor($width, $height);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($finalimage, false);
                    imagesavealpha($finalimage, true);
                }
            } else {
                $finalimage = imagecreate($width, $height);
            }

            if ($cropheight) {
                // This is 'cropCenterHeight'.
                imagecopybicubic($finalimage, $tempimage, 0, 0, 0, $srcoffset, $width, $height, $width, $height);
            } else {
                // This is 'cropCenterWidth'.
                imagecopybicubic($finalimage, $tempimage, 0, 0, $srcoffset, 0, $width, $height, $width, $height);
            }
            imagedestroy($tempimage);
        } else { // Scale.
            $ratio = min($requestedwidth / $originalwidth, $requestedheight / $originalheight);

            if ($ratio < 1) {
                $targetwidth = floor($originalwidth * $ratio);
                $targetheight = floor($originalheight * $ratio);
            } else {
                // Do not enlarge the original file if it is smaller than the requested thumbnail size.
                $targetwidth = $originalwidth;
                $targetheight = $originalheight;
            }

            if (function_exists('imagecreatetruecolor')) {
                $finalimage = imagecreatetruecolor($targetwidth, $targetheight);
                if ($imagefnc === 'imagepng') {
                    imagealphablending($finalimage, false);
                    imagesavealpha($finalimage, true);
                }
            } else {
                $finalimage = imagecreate($targetwidth, $targetheight);
            }

            imagecopybicubic($finalimage, $original, 0, 0, 0, 0, $targetwidth, $targetheight, $originalwidth, $originalheight);
        }

        ob_start();
        if ($quality !== null) {
            $success = $imagefnc($finalimage, null, $quality);
        }
        else {
            $success = $imagefnc($finalimage, null);
        }
        if (!$success) {
            ob_end_clean();
            unlink($filepath);
            print_error('functionfailed', 'format_grid', '', $imagefnc.', '.self::debugdata_decode($debugdata), 'generate_image');
            return false;
        }
        $data = ob_get_clean();

        imagedestroy($original);
        imagedestroy($finalimage);

        return $data;
    }

    private static function debugdata_decode($debugdata) {
        $o = 'itemid > '.$debugdata['itemid'];
        $o .= ', filename > '.$debugdata['filename'];
        $o .= ', sectionimage_sectionid > '.$debugdata['sectionimage_sectionid'];
        $o .= ', sectionimage_image > '.$debugdata['sectionimage_image'];
        $o .= ', sectionimage_newimage > '.$debugdata['sectionimage_newimage'];
        $o .= ' and sectionimage_displayedimageindex > '.$debugdata['sectionimage_displayedimageindex'].'.  ';
        $o .= get_string('reporterror', 'format_grid');

        return $o;
    }

    /**
     * Returns the RGB for the given hex.
     *
     * @param string $hex
     * @return array
     */
    public static function hex2rgb($hex) {
        if ($hex[0] == '#') {
            $hex = substr($hex, 1);
        }
        if (strlen($hex) == 3) {
            $r = substr($hex, 0, 1);
            $r .= $r;
            $g = substr($hex, 1, 1);
            $g .= $g;
            $b = substr($hex, 2, 1);
            $b .= $b;
        } else {
            $r = substr($hex, 0, 2);
            $g = substr($hex, 2, 2);
            $b = substr($hex, 4, 2);
        }
        return array('r' => hexdec($r), 'g' => hexdec($g), 'b' => hexdec($b));
    }
}
