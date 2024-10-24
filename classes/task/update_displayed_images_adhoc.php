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
 * Grid update displayed image adhoc task.
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

use core\output\progress_trace\text_progress_trace;
use format_grid\toolbox;

/**
 * Grid update displayed image adhoc task class.
 *
 * @package    format_grid
 * @copyright  2024 G J Barnard.
 * @author     G J Barnard -
 *               {@link https://moodle.org/user/profile.php?id=442195}
 *               {@link https://gjbarnard.co.uk}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later.
 */
class update_displayed_images_adhoc extends \core\task\adhoc_task {
    /**
     *  Run the task.
     */
    public function execute() {
        $trace = new text_progress_trace();
        $courseid = $this->get_custom_data();
        self::do_update_displayed_images_task($trace, $courseid);
    }

    /**
     * Do the task.
     *
     * @param progress_trace $trace The trace object.
     * @param $courseid Course id.
     */
    protected static function do_update_displayed_images_task(\progress_trace $trace, $courseid) {
        $trace->output('Executing Grid update displayed images adhoc task on course id ' . $courseid . '.');
        toolbox::update_displayed_images($courseid);
    }
}
