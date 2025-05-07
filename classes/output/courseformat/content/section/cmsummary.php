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
 * Contains the default section controls output class.
 *
 * @package    format_grid
 * @copyright  &copy; 2025 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link http://about.me/gjbarnard} and
 *                           {@link http://moodle.org/user/profile.php?id=442195}
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @author     Based on code originally written by Paul Krix and Julian Ridden.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output\courseformat\content\section;

use core_courseformat\output\local\content\section\cmsummary as cmsummary_base;
use completion_info;
use stdClass;

/**
 * Base class to render a course section summary.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cmsummary extends cmsummary_base {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        global $PAGE;

        $notediting = !$PAGE->user_is_editing();
        $singlesectionid = $this->format->get_sectionid();
        $data = new stdClass;
        if (($singlesectionid) && ($notediting)) {
            $showcompletion = false;
            $coursesettings = $this->format->get_settings();
            $sectionformatoptions = $this->format->get_format_options($this->section);
            if (((!empty($coursesettings['showcompletion'])) && ($coursesettings['showcompletion'] == 2)) &&
                ((!empty($sectionformatoptions['showsectioncompletion'])) && ($sectionformatoptions['showsectioncompletion'] == 2))) {
                $showcompletion = true;
            }

            // Only calculate on a single section page when not editing.  Many section page already has alternate code.
            list($mods, $complete, $total, $showcompletion) = $this->calculate_section_stats($showcompletion);

            $totalactivities = array_reduce($mods, fn($carry, $item) => $carry + ($item["count"] ?? 0), 0);
            $data = (object)[
                'showcompletion' => $showcompletion,
                'total' => $total,
                'complete' => $complete,
                'mods' => array_values($mods),
                'totalactivities' => $totalactivities,
            ];

            $contentclass = $this->format->get_output_classname('content');
            $widget = new $contentclass($this->format);
            $data->modprogress = $widget->render_grid_completion($complete, $total, $output);
        }

        return $data;
    }

    /**
     * Calculate the activities count of the current section.
     *
     * @param int $showcompletion Do we want to determine if completion is to be shown?
     * @return array with [[count by activity type], completed activities, total of activitites]
     */
    private function calculate_section_stats($showcompletion): array {
        $format = $this->format;
        $course = $format->get_course();
        $section = $this->section;
        $modinfo = $format->get_modinfo();
        $completioninfo = new completion_info($course);

        $mods = [];
        $total = 0;
        $complete = 0;

        $cmids = $modinfo->sections[$section->section] ?? [];

        // We determine if completion can be shown if indeed we want it to be so.
        $cancomplete = isloggedin() && !isguestuser() && $showcompletion;
        $showcompletion = false;
        foreach ($cmids as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->uservisible) {
                if (isset($mods[$thismod->modname])) {
                    $mods[$thismod->modname]['name'] = $thismod->modplural;
                    $mods[$thismod->modname]['count']++;
                } else {
                    $mods[$thismod->modname]['name'] = $thismod->modfullname;
                    $mods[$thismod->modname]['count'] = 1;
                }
                if ($cancomplete && $completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $showcompletion = true;
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                            $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        return [$mods, $complete, $total, $showcompletion];
    }
}
