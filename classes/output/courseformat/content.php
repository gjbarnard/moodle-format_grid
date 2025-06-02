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
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @copyright &copy; 2022-onwards G J Barnard.
 * @author    G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_grid\output\courseformat;

use completion_info;
use context_course;
use core_courseformat\output\local\content as content_base;
use core\output\renderer_base;
use core\url;
use format_grid\toolbox;
use moodle_exception;
use stdClass;

/**
 * Base class to render a course content.
 *
 * @package   format_grid
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @copyright 2022 G J Barnard - gjbarnard at gmail dot com and {@link http://moodle.org/user/profile.php?id=442195}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {
    /** @var array sectioncompletionmarkup */
    private $sectioncompletionmarkup = [];
    /** @var array sectioncompletioncalculated */
    private $sectioncompletioncalculated = [];

    /**
     * @var bool Grid format does not add section after each topic.
     *
     * The responsible for the buttons is core_courseformat\output\local\content\section.
     */
    protected $hasaddsection = false;

    /**
     * @var int Are there stealth sections with content?
     */
    protected $hassteathwithcontent = 0;

    /**
     * Get the template name.
     *
     * @param renderer_base $output typically, the renderer that's calling this method.
     * @return string Mustache template name.
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_grid/local/content';
    }

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this method.
     * @return stdClass data context for a Mustache template.
     */
    public function export_for_template(renderer_base $output) {
        global $DB, $PAGE;
        $format = $this->format;
        $editing = $PAGE->user_is_editing();

        $data = (object)[
            'title' => $format->page_title(),
            'format' => $format->get_format(),
            'sectionreturn' => null,
        ];

        $singlesectionid = $this->format->get_sectionid();
        $sections = $this->export_sections($output);
        $initialsection = '';
        $course = $format->get_course();
        $currentsectionid = 0;
        $coursesettings = $format->get_settings();
        $sectionzeronotingrid = ($coursesettings['sectionzeroingrid'] == 1);

        if (!empty($sections)) {
            // Is first entry section 0?
            if ($sections[0]->num === 0) {
                if ((!$singlesectionid) && ($sectionzeronotingrid)) {
                    // Most formats uses section 0 as a separate section so we remove from the list.
                    $initialsection = array_shift($sections);
                    $data->initialsection = $initialsection;
                }
            }
            if (($editing) || ($singlesectionid)) { // This triggers the display of the standard list of section(s).
                $data->sections = $sections;
            }
            if (!empty($course->marker)) {
                foreach ($sections as $section) {
                    if ($section->num == $course->marker) {
                        $currentsectionid = $section->id;
                        break;
                    }
                }
            }
        }

        // The single section format has extra navigation.
        if ($singlesectionid) {
            $singlesectionno = $this->format->get_sectionnum();
            $sectionnavigation = new $this->sectionnavigationclass($format, $singlesectionno);
            $data->sectionnavigation = $sectionnavigation->export_for_template($output);

            $sectionselector = new $this->sectionselectorclass($format, $sectionnavigation);
            $data->sectionselector = $sectionselector->export_for_template($output);
            $data->hasnavigation = true;
            $data->singlesection = array_shift($data->sections);
            $data->sectionreturn = $singlesectionno;
            $data->maincoursepage = new url('/course/view.php', ['id' => $course->id]);
        } else {
            $toolbox = toolbox::get_instance();
            $coursesectionimages = $DB->get_records('format_grid_image', ['courseid' => $course->id]);
            if (!empty($coursesectionimages)) {
                $fs = get_file_storage();
                $coursecontext = context_course::instance($course->id);
                foreach ($coursesectionimages as $coursesectionimage) {
                    try {
                        $replacement = $toolbox->check_displayed_image(
                            $coursesectionimage,
                            $course->id,
                            $coursecontext->id,
                            $coursesectionimage->sectionid,
                            $format,
                            $fs
                        );
                        if (!empty($replacement)) {
                            $coursesectionimages[$coursesectionimage->id] = $replacement;
                        }
                    } catch (moodle_exception $me) {
                        $coursesectionimages[$coursesectionimage->id]->imageerror = $me->getMessage();
                    }
                }
            }

            // Justification.
            $data->gridjustification = $coursesettings['gridjustification'];

            // Image resize is crop.
            $data->imageresizemethodcrop = ($coursesettings['imageresizemethod'] == 2);

            // Section title in grid box.
            $data->sectiontitleingridbox = ($coursesettings['sectiontitleingridbox'] == 2);

            // Section badge in grid box.
            $data->sectionbadgeingridbox = ($coursesettings['sectionbadgeingridbox'] == 2);

            // Popup.
            if (!$editing) {
                $data->popup = false;
                if ((!empty($coursesettings['popup'])) && ($coursesettings['popup'] == 2)) {
                    $data->popup = true;
                    $data->popupsections = [];
                    $potentialpopupsections = [];
                    foreach ($sections as $section) {
                        $potentialpopupsections[$section->id] = $section;
                    }
                }
            }

            // Suitable array.
            $sectionimages = [];
            foreach ($coursesectionimages as $coursesectionimage) {
                $sectionimages[$coursesectionimage->sectionid] = $coursesectionimage;
            }

            // Now iterate over the sections.
            $data->gridsections = [];
            $sectionsforgrid = $this->get_grid_sections($output, $coursesettings, $editing);
            $displayediswebp = (get_config('format_grid', 'defaultdisplayedimagefiletype') == 2);

            $completionshown = false;
            $sectionheaderimages = false;
            if ($editing) {
                $datasectionmap = [];
                foreach ($data->sections as $datasectionkey => $datasection) {
                    $datasectionmap[$datasection->id] = $datasectionkey;
                }
            } else {
                // Visibility info for grid.
                $sectionvisiblity = [];
                foreach ($sections as $section) {
                    $sectionvisiblity[$section->id] = new stdClass;
                    $sectionvisiblity[$section->id]->ishidden = (!empty($section->ishidden));
                    $sectionvisiblity[$section->id]->visibility = $section->visibility;
                }
            }
            foreach ($sectionsforgrid as $section) {
                // Do we have an image?
                if (array_key_exists($section->id, $sectionimages)) {
                    if ($sectionimages[$section->id]->displayedimagestate >= 1) {
                        $sectionimages[$section->id]->imageuri = $toolbox->get_displayed_image_uri(
                            $sectionimages[$section->id],
                            $coursecontext->id,
                            $section->id,
                            $displayediswebp
                        );
                    } else if (empty($sectionimages[$section->id]->imageerror)) {
                        $sectionimages[$section->id]->imageerror =
                            get_string('cannotconvertuploadedimagetodisplayedimage', 'format_grid',
                                json_encode($sectionimages[$section->id]));
                    }
                } else {
                    // No.
                    $sectionimages[$section->id] = new stdClass();
                    $sectionimages[$section->id]->generatedimageuri = $output->get_generated_image_for_id($section->id);
                }
                // Number.
                $sectionimages[$section->id]->number = $section->num;

                // Alt text.
                $sectionformatoptions = $format->get_format_options($section);
                $sectionimages[$section->id]->imagealttext = $sectionformatoptions['sectionimagealttext'];

                // Current section?
                if ((!empty($currentsectionid)) && ($currentsectionid == $section->id)) {
                    $sectionimages[$section->id]->iscurrent = true;
                    $sectionimages[$section->id]->hasbadge = true;
                    $sectionimages[$section->id]->highlightedlabel = $format->get_section_highlighted_name();
                }

                if ($editing) {
                    if (!empty($data->sections[$datasectionmap[$section->id]])) {
                        // Add the image to the section content.
                        $data->sections[$datasectionmap[$section->id]]->gridimage = $sectionimages[$section->id];
                        $sectionheaderimages = true;
                    }
                } else {
                    // Section link.
                    $sectionimages[$section->id]->sectionurl = new url(
                        '/course/section.php',
                        ['id' => $section->id]
                    );
                    $sectionimages[$section->id]->sectionurl = $sectionimages[$section->id]->sectionurl->out(false);

                    // Section name.
                    $sectionimages[$section->id]->sectionname = $section->name;

                    // Visibility information.
                    $sectionimages[$section->id]->ishidden = $sectionvisiblity[$section->id]->ishidden;
                    if ($sectionimages[$section->id]->ishidden) {
                        $sectionimages[$section->id]->visibility = $sectionvisiblity[$section->id]->visibility;
                        $sectionimages[$section->id]->hiddenfromstudents =
                            (!empty($sectionimages[$section->id]->visibility->hiddenfromstudents));
                        $sectionimages[$section->id]->notavailable = (!empty($sectionimages[$section->id]->visibility->notavailable));
                        $sectionimages[$section->id]->hasbadge = true;
                    }

                    // Section break.
                    if ($sectionformatoptions['sectionbreak'] == 2) { // Yes.
                        $sectionimages[$section->id]->sectionbreak = true;
                        if (!empty($sectionformatoptions['sectionbreakheading'])) {
                            // Note:  As a PARAM_TEXT, then does need to be passed through 'format_string' for multi-lang or not?
                            $sectionimages[$section->id]->sectionbreakheading = format_text(
                                $sectionformatoptions['sectionbreakheading'],
                                FORMAT_HTML
                            );
                        }
                    }

                    // Completion?
                    if (!empty($section->sectioncompletionmarkup)) {
                        $sectionimages[$section->id]->sectioncompletionmarkup = $section->sectioncompletionmarkup;
                        $completionshown = true;
                    }

                    // For the template.
                    $data->gridsections[] = $sectionimages[$section->id];
                    if ($data->popup) {
                        $data->popupsections[] = $potentialpopupsections[$section->id];
                    }
                }
            }

            $data->hasgridsections = (!empty($data->gridsections)) ? true : false;
            if ($data->hasgridsections) {
                $data->coursestyles = $toolbox->get_displayed_image_container_properties($coursesettings);
                if ((!empty($coursesettings['showcompletion'])) && ($coursesettings['showcompletion'] == 2) && ($completionshown)) {
                    $data->showcompletion = true;
                }
                $gridsectionnums = [];
                foreach ($data->gridsections as $gridsection) {
                    $gridsectionnums[] = $gridsection->number;
                }
                $data->gridsectionnumbers = implode(',', $gridsectionnums);
            }

            if ($sectionheaderimages) {
                $data->hassectionheaderimages = true;
                $coursesettings['imagecontainerwidth'] = 144;
                $data->coursestyles = $toolbox->get_displayed_image_container_properties($coursesettings);
            }
        }

        if ($this->hassteathwithcontent) {
            $context = context_course::instance($course->id);
            if (has_capability('moodle/course:update', $context)) {
                $data->stealthwarning = get_string('stealthwarning', 'format_grid', $this->hassteathwithcontent);
            }
        }

        if ($this->hasaddsection) {
            $addsection = new $this->addsectionclass($format);
            $data->numsections = $addsection->export_for_template($output);
        }

        if ($format->show_editor()) {
            $bulkedittools = new $this->bulkedittoolsclass($format);
            $data->bulkedittools = $bulkedittools->export_for_template($output);
        }

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this method.
     * @param array $settings The settings for the format.
     * @param boolean $editing The user is editing.
     *
     * @return array data context for a mustache template
     */
    protected function get_grid_sections(renderer_base $output, $settings, $editing): array {
        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        // Generate section list.
        $sections = [];
        $numsections = $format->get_last_section_number_without_deligated();
        $sectioncount = 0;
        $sectioninfos = $modinfo->get_section_info_all();
        $deligatedsections = []; // Array of parent section number with an array of deligated section numbers if they have them.
        $sectioncompletion = []; // Array of sections that have been set to show their completion.

        foreach ($sectioninfos as $sectioninfokey => $sectioninfo) {
            $sectionformatoptions = $this->format->get_format_options($sectioninfo);
            if ((!empty($sectionformatoptions['showsectioncompletion'])) && ($sectionformatoptions['showsectioncompletion'] == 2)) {
                $sectioncompletion[$sectioninfo->id] = true;
            }

            if (!empty($sectioninfo->component)) {
                // Deligated section.  Note, that even if the deligated section does not show its completion then its parent may.
                if ((!$editing) && ((!empty($settings['showcompletion'])) && ($settings['showcompletion'] == 2))) {
                    // Work out the parent for completion.
                    foreach ($modinfo->delegatedbycm as $delegatedsectioninfokey => $delegatedsectioninfo) {
                        if ($sectioninfo->id == $delegatedsectioninfo->id) {
                            foreach ($modinfo->cms as $cmskey => $cminfo) {
                                if ($delegatedsectioninfokey == $cmskey) {
                                    if (empty($deligatedsections[$cminfo->sectionnum])) {
                                        $deligatedsections[$cminfo->sectionnum] = [];
                                    }
                                    $deligatedsections[$cminfo->sectionnum][] = $sectioninfo->sectionnum;
                                    break;
                                }
                            }
                            break;
                        }
                    }
                }
                unset($sectioninfos[$sectioninfokey]);
            }
        }

        $coursesettings = $format->get_settings();
        $sectionzeronotingrid = ($coursesettings['sectionzeroingrid'] == 1);
        if ($sectionzeronotingrid) {
            // Get rid of section 0.
            if (!empty($sectioninfos)) {
                array_shift($sectioninfos);
            }
        }
        foreach ($sectioninfos as $thissection) {
            /* The course/view.php check the section existence but the output can be called from other parts so we need to
               check it. */
            if (!$thissection) {
                throw new moodle_exception(
                    'unknowncoursesection',
                    'error',
                    '',
                    get_string(
                        'unknowncoursesection',
                        'error',
                        course_get_url($course) . ' - ' . format_string($course->fullname)
                    )
                );
            }

            $sectioncount++;
            if ($sectioncount > $numsections) {
                // Only count sections that are not deligated and have content.
                $temp = $thissection->hasactivities;
                if (!empty($modinfo->sectionmodules[$thissection->section])) {
                    $this->hassteathwithcontent++;
                }
                continue;
            }

            if (!$format->is_section_visible($thissection)) {
                continue;
            }

            $section = new stdClass();
            $section->id = $thissection->id;
            $section->num = $thissection->section;
            $section->name = $output->section_title_without_link($thissection, $course);
            if ((!$editing) &&
                (!empty($sectioncompletion[$thissection->id])) &&
                ((!empty($settings['showcompletion'])) &&
                ($settings['showcompletion'] == 2))) {
                $this->calculate_section_activity_completion(
                    $thissection->section, $course, $modinfo, $deligatedsections, $output);
                if (!empty($this->sectioncompletionmarkup[$thissection->section])) {
                    $section->sectioncompletionmarkup = $this->sectioncompletionmarkup[$thissection->section];
                }
            }
            $section->uservisible = $thissection->uservisible;
            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Calculate and generate the markup for completion of the activities in a section.
     *
     * @param int $sectionnum The section number.
     * @param stdClass $course the course.
     * @param course_modinfo $modinfo the course module information.
     * @param array $deligatedsections Array of sections that have deligated sections containing an array of their section numbers.
     * @param renderer_base $output typically, the renderer that's calling this method.
     */
    protected function calculate_section_activity_completion(
        $sectionnum, $course, $modinfo, $deligatedsections, renderer_base $output) {
        if (empty($this->sectioncompletioncalculated[$sectionnum])) {
            $this->sectioncompletionmarkup[$sectionnum] = '';
            if (empty($modinfo->sections[$sectionnum])) {
                $this->sectioncompletioncalculated[$sectionnum] = true;
                return;
            }

            // Generate array with count of activities in this section.
            $total = 0;
            $complete = 0;
            $cancomplete = isloggedin() && !isguestuser();
            $asectionisavailable = false;
            if ($cancomplete) {
                $completioninfo = new completion_info($course);

                $this->calculate_section_activity_completion_modules(
                    $sectionnum, $modinfo, $completioninfo, $total, $complete, $asectionisavailable);
                // Deligated sections.
                if (!empty($deligatedsections[$sectionnum])) {
                    foreach ($deligatedsections[$sectionnum] as $deligatedsectionnum) {
                        $this->calculate_section_activity_completion_modules(
                            $deligatedsectionnum, $modinfo, $completioninfo, $total, $complete, $asectionisavailable);
                    }
                }
            }

            if ((!$asectionisavailable) || (!$cancomplete)) {
                // No sections or no completion.
                $this->sectioncompletioncalculated[$sectionnum] = true;
                return;
            }

            // Output section completion data.
            if ($total > 0) {
                $this->sectioncompletionmarkup[$sectionnum] = $this->render_grid_completion($complete, $total, $output);
            }

            $this->sectioncompletioncalculated[$sectionnum] = true;
        }
    }

    /**
     * Generate the markup for completion of the activities in a section.
     *
     * @param int $complete The number of complete modules.
     *
     * @return string Markup if any.
     */
    public function render_grid_completion($complete, $total, renderer_base $output) {
        $markup = '';
        if ($total > 0) {
            $percentage = round(($complete / $total) * 100);

            $low = get_config('format_grid', 'defaultcompletionlowpercentagevalue');
            if (empty($low)) {
                $low = 50; // Default.
            }
            $medium = get_config('format_grid', 'defaultcompletionmediumpercentagevalue');
            if (empty($medium)) {
                $medium = 80; // Default.
            }
            $data = new stdClass();
            $data->percentagevalue = $percentage;
            if ($data->percentagevalue < $low) {
                $data->percentagecolour = 'low';
            } else if ($data->percentagevalue < $medium) {
                $data->percentagecolour = 'middle';
            } else {
                $data->percentagecolour = 'high';
            }
            if ($data->percentagevalue < 1) {
                $data->percentagequarter = 0;
            } else if ($data->percentagevalue < 26) {
                $data->percentagequarter = 1;
            } else if ($data->percentagevalue < 51) {
                $data->percentagequarter = 2;
            } else if ($data->percentagevalue < 76) {
                $data->percentagequarter = 3;
            } else {
                $data->percentagequarter = 4;
            }
            $markup = $output->render_from_template('format_grid/grid_completion', $data);
        }
        return $markup;
    }

    /**
     * Calculate the total and total complete modules in a section.
     *
     * @param int $sectionnum The section number.
     * @param course_modinfo $modinfo the course module information.
     * @param completion_info $completioninfo Completion information instance.
     * @param int $total The total number of modules.  Reference to called variable.
     * @param int $complete The total number of modules that are complete.  Reference to called variable.
     * @param boolean $asectionisavailable One of more modules are available in the section.  Reference to called variable.
     */
    protected function calculate_section_activity_completion_modules(
        $sectionnum, $modinfo, $completioninfo, &$total, &$complete, &$asectionisavailable) {
        foreach ($modinfo->sections[$sectionnum] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            if ($thismod->uservisible) {
                $asectionisavailable = true;
                if ($completioninfo->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $completiondata = $completioninfo->get_data($thismod, true);
                    if (
                        $completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS
                    ) {
                        $complete++;
                    }
                }
            }
        }
    }
}
