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
 * Grid Format.
 *
 * @package    format_grid
 * @copyright  &copy; 2012 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Upgrade processes.
 *
 * @return bool Success.
 */
function xmldb_format_grid_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022072200) {
        // Define table format_grid_image to be created.
        $table = new xmldb_table('format_grid_image');
        $somethingbroke = false;

        // Has the script been executed already and broken?
        if ($dbman->table_exists($table)) {
            $somethingbroke = true;
            $dbman->drop_table($table);
        }

        // Adding fields to table format_grid_image.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('image', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('contenthash', XMLDB_TYPE_CHAR, '40', null, XMLDB_NOTNULL, null, null);
        $table->add_field('displayedimagestate', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sectionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table format_grid_image.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table format_grid_image.
        $table->add_index('section', XMLDB_INDEX_UNIQUE, ['sectionid']);
        $table->add_index('course', XMLDB_INDEX_NOTUNIQUE, ['courseid']);

        // Create table for format_grid_image.
        $dbman->create_table($table);

        $lock = true;
        if (!defined('BEHAT_SITE_RUNNING')) {
            $lockfactory = \core\lock\lock_config::get_lock_factory('format_grid');
            $lock = $lockfactory->get_lock('gridupgradelock2022072200', 5);
        }
        if ($lock) {
            try {
                $oldtable = new xmldb_table('format_grid_icon');
                if ($dbman->table_exists($oldtable)) {
                    // Upgrade from old images.
                    $oldimages = $DB->get_records('format_grid_icon');
                    if (!empty($oldimages)) {
                        $newimages = [];
                        foreach ($oldimages as $oldimage) {
                            if (!empty($oldimage->image)) {
                                try {
                                    context_course::instance($oldimage->courseid);
                                } catch (\Exception $ex) {
                                    // Course does not exist for this image, skip.
                                    continue;
                                }

                                $newimagecontainer = new \stdClass();
                                $newimagecontainer->sectionid = $oldimage->sectionid;
                                $newimagecontainer->courseid = $oldimage->courseid;
                                $newimagecontainer->image = $oldimage->image;
                                $newimagecontainer->displayedimagestate = 0;
                                // Contenthash later!
                                $DB->insert_record('format_grid_image', $newimagecontainer, true);
                                if (!array_key_exists($newimagecontainer->courseid, $newimages)) {
                                    $newimages[$newimagecontainer->courseid] = [];
                                }
                                $newimages[$newimagecontainer->courseid][$newimagecontainer->sectionid] = $newimagecontainer;
                            }
                        }

                        $fs = get_file_storage();
                        foreach ($newimages as $currentcourseid => $newimagecoursearray) {
                            $coursecontext = context_course::instance($currentcourseid);
                            $files = $fs->get_area_files($coursecontext->id, 'course', 'section');
                            foreach ($files as $file) {
                                if (!$file->is_directory()) {
                                    if ($file->get_filepath() == '/gridimage/') {
                                        $file->delete();
                                    } else {
                                        $filename = $file->get_filename();
                                        $filesectionid = $file->get_itemid();
                                        // Ensure we know about this section.
                                        if (array_key_exists($filesectionid, $newimagecoursearray)) {
                                            $gridimage = $newimagecoursearray[$filesectionid];
                                            // Ensure the correct file.
                                            if (($gridimage) && ($gridimage->image == $filename)) {
                                                $filerecord = new stdClass();
                                                $filerecord->contextid = $coursecontext->id;
                                                $filerecord->component = 'format_grid';
                                                $filerecord->filearea = 'sectionimage';
                                                $filerecord->itemid = $filesectionid;
                                                $filerecord->filepath = '/';
                                                $filerecord->filename = $filename;
                                                $thefile = false;
                                                if ($somethingbroke) {
                                                    // Check to see if the file is already there.
                                                    $thefile = $fs->get_file(
                                                        $filerecord->contextid,
                                                        $filerecord->component,
                                                        $filerecord->filearea,
                                                        $filerecord->itemid,
                                                        $filerecord->filepath,
                                                        $filerecord->filename
                                                    );
                                                }
                                                if ($thefile === false) {
                                                    $thefile = $fs->create_file_from_storedfile($filerecord, $file);
                                                }
                                                if ($thefile !== false) {
                                                    $DB->set_field(
                                                        'format_grid_image',
                                                        'contenthash',
                                                        $thefile->get_contenthash(),
                                                        ['sectionid' => $filesectionid]
                                                    );
                                                    // Don't delete the section file in case used in the summary.
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                // Delete 'format_grid_icon' and 'format_grid_summary' tables....
                $dbman->drop_table($oldtable);
                $oldsummarytable = new xmldb_table('format_grid_summary');
                $dbman->drop_table($oldsummarytable);

                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
            } catch (\Exception $e) {
                if (!defined('BEHAT_SITE_RUNNING')) {
                    $lock->release();
                }
                throw $e;
            }
        } else {
            throw new moodle_exception('cannotgetupgradelock', 'format_grid', '', 'Cannot get upgrade lock');
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2022072200, 'format', 'grid');
    }

    if ($oldversion < 2023051001) {
        // Has the upgrade already happened?  Thus in versions for Moodle 4.1.
        $records = $DB->get_records(
            'course_format_options',
            [
                'format' => 'grid',
                'name' => 'gnumsections',
            ],
            '',
            'id'
        );

        if (empty($records)) {
            $records = $DB->get_records(
                'course_format_options',
                [
                    'format' => 'grid',
                    'name' => 'numsections',
                ],
                '',
                'id'
            );

            $records = array_keys($records);
            foreach ($records as $id) {
                $DB->set_field('course_format_options', 'name', 'gnumsections', ['id' => $id]);
            }
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2023051001, 'format', 'grid');
    }

    if ($oldversion < 2024032904) {
        // Has the upgrade already happened?  Thus in versions for Moodle 4.1+ ?
        $codebase = get_config('format_grid', 'codebase');
        if ((empty($codebase)) || ((!empty($codebase)) && ($codebase < 2024092900))) {
            // No!
            \format_grid\task\update_displayed_images_task::update_displayed_images_imageresizemethod();
            set_config('codebase', 2024092900, 'format_grid');
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2024032904, 'format', 'grid');
    }

    if ($oldversion < 2024101503) {
        $records = $DB->get_records(
            'course_format_options',
            [
                'format' => 'grid',
                'name' => 'gnumsections',
            ],
            '',
            'id'
        );

        if (!empty($records)) {
            // Remove 'gnumsections'.
            $DB->delete_records(
                'course_format_options',
                [
                    'format' => 'grid',
                    'name' => 'gnumsections',
                ]
            );
        }

        // Grid savepoint reached.
        upgrade_plugin_savepoint(true, 2024101503, 'format', 'grid');
    }

    // Automatic 'Purge all caches'....
    purge_all_caches();

    return true;
}
