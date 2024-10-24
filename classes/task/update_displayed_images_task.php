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
 * Update displayed images task.
 *
 * @package    format_grid
 * @copyright  2024 G J Barnard.
 * @author     G J Barnard -
 *               {@link https://moodle.org/user/profile.php?id=442195}
 *               {@link https://gjbarnard.co.uk}
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
namespace format_grid\task;

use core_courseformat\base;
use core\task\manager;

/**
 * Grid update displayed images task.
 *
 * @package    format_grid
 * @copyright  2024 G J Barnard.
 * @author     G J Barnard -
 *               {@link https://moodle.org/user/profile.php?id=442195}
 *               {@link https://gjbarnard.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class update_displayed_images_task {
    /**
     * Queue the tasks for each grid format course.
     */
    public static function update_displayed_images_imageresizemethod() {
        global $DB;

        $gridcourses = $DB->get_records('course', ['format' => 'grid'], '', 'id');

        foreach ($gridcourses as $gridcourse) {
            // Instead of course_get_format() for CLI usage.
            $format = base::instance($gridcourse->id);
            $imageresizemethod = $format->get_format_options()['imageresizemethod'];
            if ($imageresizemethod != 0) {
                $task = new update_displayed_images_adhoc();
                $task->set_custom_data($gridcourse->id);
                manager::queue_adhoc_task($task);
            }
        }
    }
}
