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
 * Grid Information
 *
 * @package    format_grid
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link https://about.me/gjbarnard} and
 *                           {@link https://moodle.org/user/profile.php?id=442195}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/format/grid/lib.php');

/**
 * Restore plugin class that provides the necessary information
 * needed to restore one grid format course.
 */
class restore_format_grid_plugin extends restore_format_plugin {
    /** @var int */
    protected $originalnumsections = 0;

    /**
     * Checks if backup file was made on Moodle before 5.0 and we should respect the 'numsections'
     * and potential "orphaned" sections in the end of the course.
     *
     * @return bool
     */
    protected function need_restore_numsections() {
        $backupinfo = $this->step->get_task()->get_info();
        $backuprelease = $backupinfo->backup_release; // The major version: 2.9, 3.0, 3.10...
        return version_compare($backuprelease, '5.0', '<');
    }

    /**
     * Returns the paths to be handled by the plugin at course level.
     * I think this is only called when the course format settings change.
     */
    protected function define_course_plugin_structure() {
        /* Since this method is executed before the restore we can do some pre-checks here.
           In case of merging backup into existing course find the current number of sections. */
        $task = $this->step->get_task();
        $target = $task->get_target();
        if (($target == backup::TARGET_CURRENT_ADDING || $target == backup::TARGET_EXISTING_ADDING)) {
            global $DB;
            $maxsection = $DB->get_field_sql(
                'SELECT max(section) FROM {course_sections} WHERE course = ?',
                [$task->get_courseid()]
            );
            $this->originalnumsections = (int)$maxsection;
        }

        // Nop path element is needed in order for after_restore_course() to be called.
        return [new restore_path_element('grid', $this->get_pathfor('/'))];
    }

    /**
     * Process grid course format options method.
     *
     * @return void
     */
    public function process_grid($data) {
        if (!empty($data['name'])) {
            if (($data['name'] == 'numsections') || ($data['name'] == 'gnumsections')) {
                $this->originalnumsections = $data['value'];
            }
        }
    }

    /**
     * Executed after course restore is complete
     *
     * This method is only executed if course configuration was overridden
     */
    public function after_restore_course() {
        global $DB;

        $task = $this->step->get_task();
        $courseid = $task->get_courseid();

        /* We only process this information if the course we are restoring to
           has 'grid' format (target format can change depending of restore options). */
        $format = $DB->get_field('course', 'format', ['id' => $courseid]);
        if ($format !== 'grid') {
            return;
        }

        $data = $this->connectionpoint->get_data();
        $backupinfo = $task->get_info();
        if ($backupinfo->original_course_format !== 'grid') {
            // Backup from another course format.
            return;
        }

        // Sort out the files if old backup.  Grid image records already created with the section restore.
        $backuprelease = $backupinfo->backup_release; // The major version: 2.9, 3.0, 3.10...
        if (version_compare($backuprelease, '4.0', '<')) {
            $fs = get_file_storage();
            $coursecontext = context_course::instance($courseid);
            $files = $fs->get_area_files($coursecontext->id, 'course', 'section');
            foreach ($files as $file) {
                if (!$file->is_directory()) {
                    $filename = $file->get_filename();
                    $filesectionid = $file->get_itemid();
                    $gridimage = $DB->get_record('format_grid_image', ['sectionid' => $filesectionid], 'image');
                    if (($gridimage) && ($gridimage->image == $filename)) { // Ensure the correct file.
                        $filerecord = new stdClass();
                        $filerecord->contextid = $coursecontext->id;
                        $filerecord->component = 'format_grid';
                        $filerecord->filearea = 'sectionimage';
                        $filerecord->itemid = $filesectionid;
                        $filerecord->filename = $filename;
                        $newfile = $fs->create_file_from_storedfile($filerecord, $file);
                        if ($newfile) {
                            $DB->set_field(
                                'format_grid_image',
                                'contenthash',
                                $newfile->get_contenthash(),
                                ['sectionid' => $filesectionid]
                            );
                        }
                    }
                }
            }
        }

        if (!$this->need_restore_numsections()) {
            // Backup file was made in Moodle 5.0 or later, we don't need to process 'numsections' or
            // 'gnumsections'.
            return;
        }

        if (!isset($data['tags']['numsections']) ||
            !isset($data['tags']['gnumsections'])) {
            // Backup file does not have '(g)numsections'.
            return;
        }

        if ($this->originalnumsections) {
            if (isset($data['tags']['numsections'])) {
                $numsections = (int)$data['tags']['numsections'];
            } else {
                $numsections = (int)$data['tags']['gnumsections'];
            }
            foreach ($backupinfo->sections as $key => $section) {
                // For each section from the backup file check if it was restored and if was "orphaned" in the original
                // course and mark it as hidden. This will leave all activities in it visible and available just as it was
                // in the original course.
                // Exception is when we restore with merging and the course already had a section with this section number,
                // in this case we don't modify the visibility.
                if ($task->get_setting_value($key . '_included')) {
                    $sectionnum = (int)$section->title;
                    if ($sectionnum > $numsections && $sectionnum > $this->originalnumsections) {
                        $DB->execute("UPDATE {course_sections} SET visible = 0 WHERE course = ? AND section = ?",
                            [$task->get_courseid(), $sectionnum]);
                    }
                }
            }
        }
    }

    /**
     * Returns the paths to be handled by the plugin at section level
     */
    protected function define_section_plugin_structure() {

        $paths = [];

        // Add own format stuff.
        $elepath = $this->get_pathfor('/');  // Note: $this->get_recommended_name() gets! -> section/the name.
        $paths[] = new restore_path_element('gridsection', $elepath);

        return $paths; // And we return the interesting paths.
    }

    /**
     * Process the 'plugin_format_grid_section' element within the 'section' element in the 'section.xml' file in the
     * '/sections/section_sectionid' folder of the zipped backup 'mbz' file.
     */
    public function process_gridsection($data) {
        global $DB;

        $data = (object) $data;
        $task = $this->step->get_task();
        $target = $task->get_target();
        if (
            ($target == backup::TARGET_NEW_COURSE) ||
            // TARGET_CURRENT_ADDING used by both import and CSV course creation with template course, go figure!
            ($target == backup::TARGET_CURRENT_ADDING) ||
            ($target == backup::TARGET_CURRENT_DELETING) ||
            ($target == backup::TARGET_EXISTING_DELETING)
        ) {
            /* This ensures that when a course is created from an uploaded CSV file that the number of sections is correct.
               Thus when an existing course or course file is used but the course restore code is not called.
               Because the backup file / course being restored from has the correct 'sections', i.e. that will be in the
               'course_sections' table. */
            $courseid = $task->get_courseid();

            // We don't know how many more sections there is and also don't know if this is the last.
            $courseformat = course_get_format($courseid);
            if ($courseformat->get_format() == 'grid') {
                // Not calling the format's 'restore_gnumsections' as '-1' fails validation.
                $DB->set_field('course_format_options', 'value', -1,
                    [
                        'courseid' => $courseid,
                        'name' => 'gnumsections',
                    ]
                );
            }
        }
        /* Allow this to process even if not in the grid format so that our event observer on 'course_restored'
           can perform a clean up of restored grid image files after all the data is in place in the database
           for this to happen properly. */
        if (
            ($target == backup::TARGET_NEW_COURSE) ||
            ($target == backup::TARGET_CURRENT_DELETING) ||
            ($target == backup::TARGET_EXISTING_DELETING) ||
            ($target == backup::TARGET_CURRENT_ADDING) ||
            ($target == backup::TARGET_EXISTING_ADDING)
        ) { // All of them, but just in case a new one is added!
            if (empty($data->contenthash)) {
                // Less than M4.0 backup file.
                if (!empty($data->imagepath)) {
                    $data->image = $data->imagepath;
                    unset($data->imagepath);
                } else if (empty($data->image)) {
                    $data->image = null;
                }
            }
            if (!empty($data->image)) {
                $newsectionid = $this->task->get_sectionid();
                $existinggridimage = false;
                if (
                    ($target == backup::TARGET_CURRENT_ADDING) ||
                    ($target == backup::TARGET_EXISTING_ADDING)
                ) {
                    $existinggridimage = $DB->get_record('format_grid_image', ['sectionid' => $newsectionid], 'image');
                }
                if (!$existinggridimage) {
                    // No image, so add the one from the backup file.
                    $courseid = $this->task->get_courseid();

                    $newimagecontainer = new \stdClass();
                    $newimagecontainer->sectionid = $newsectionid;
                    $newimagecontainer->courseid = $courseid;
                    $newimagecontainer->image = $data->image;
                    $newimagecontainer->displayedimagestate = 0;
                    if (!empty($data->contenthash)) {
                        $oldsectionid = $data->sectionid;
                        $this->set_mapping('gridimage', $oldsectionid, $newsectionid, true);
                        $this->add_related_files('format_grid', 'sectionimage', 'gridimage');
                        $newimagecontainer->contenthash = $data->contenthash;
                    } // Else contenthash later!
                    $newid = $DB->insert_record('format_grid_image', $newimagecontainer, true);
                }
            }
        }
    }
}
