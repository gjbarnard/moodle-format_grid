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

namespace format_grid\output\courseformat\content\section;

use stdClass;

/**
 * Class to render a section visibility inside a course format.
 *
 * @package    format_grid
 * @copyright  2025 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link https://about.me/gjbarnard} and
 *                           {@link https://moodle.org/user/profile.php?id=442195}
 * @copyright 2024 Laurent David <laurent.david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class visibility extends \core_courseformat\output\local\content\section\visibility {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output typically, the renderer that's calling this function
     * @return stdClass|null data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): ?stdClass {
        global $PAGE;
        $data = parent::export_for_template($output);

        if ($PAGE->user_is_editing()) {
            if (empty($data)) {
                $data = new stdClass;
            }
            $sectionformatoptions = $this->format->get_format_options($this->section);
            $data->sectionhideingrid = ($sectionformatoptions['sectionhideingrid'] == 2);
        }

        return $data;
    }
}
