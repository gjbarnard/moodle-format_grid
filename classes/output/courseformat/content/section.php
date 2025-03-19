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
 * Contains the default section controls output class.
 *
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @copyright &copy; 2023-onwards G J Barnard based upon work done by Ferran Recio.
 * @author    G J Barnard - {@link https://gjbarnard.co.uk} and
 *                          {@link http://moodle.org/user/profile.php?id=442195}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output\courseformat\content;

use core_courseformat\base as course_format;
use core_courseformat\output\local\content\section as section_base;
use core\output\renderer_base;
use stdClass;

/**
 * Class to render a course section.
 *
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @copyright &copy; 2023-onwards G J Barnard based upon work done by Ferran Recio.
 * @author    G J Barnard - {@link https://gjbarnard.co.uk} and
 *                          {@link http://moodle.org/user/profile.php?id=442195}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {
    /** @var course_format the course format */
    protected $format;

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function.
     * @return array data context for a mustache template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $format = $this->format;

        $data = parent::export_for_template($output);

        if (!$this->format->get_sectionnum()) {
            $addsectionclass = $format->get_output_classname('content\\addsection');
            $addsection = new $addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
            $data->insertafter = true;
        }

        return $data;
    }
}
