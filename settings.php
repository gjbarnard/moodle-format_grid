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
 * @copyright  &copy; 2013 G J Barnard in respect to modifications of standard topics format.
 * @author     G J Barnard - {@link https://about.me/gjbarnard} and
 *                           {@link https://moodle.org/user/profile.php?id=442195}
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

use format_grid\admin_setting_information;
use format_grid\admin_setting_markdown;

require_once($CFG->dirroot . '/course/format/grid/lib.php'); // For format_grid static constants.

$settings = null;
$ADMIN->add('formatsettings', new admin_category('format_grid', get_string('pluginname', 'format_grid')));

// Information.
$page = new admin_settingpage(
    'format_grid_information',
    get_string('information', 'format_grid')
);

if ($ADMIN->fulltree) {
    $page->add(new admin_setting_heading(
        'format_grid_information',
        '',
        format_text(get_string('informationsettingsdesc', 'format_grid'), FORMAT_MARKDOWN)
    ));

    // Information.
    $page->add(new admin_setting_information('format_grid/formatinformation', '', '', 403));

    // Support.md.
    $page->add(new admin_setting_markdown('format_grid/formatsupport', '', '', 'Support.md'));

    // Changes.md.
    $page->add(new admin_setting_markdown(
        'format_grid/formatchanges',
        get_string('informationchanges', 'format_grid'),
        '',
        'Changes.md'
    ));
}
$ADMIN->add('format_grid', $page);

// Settings.
$page = new admin_settingpage(
    'format_grid_settings',
    get_string('settings', 'format_grid')
);
if ($ADMIN->fulltree) {
    $page->add(new admin_setting_heading(
        'format_grid_settings',
        '',
        format_text(get_string('settingssettingsdesc', 'format_grid'), FORMAT_MARKDOWN)
    ));

    // Popup.
    $name = 'format_grid/defaultpopup';
    $title = get_string('defaultpopup', 'format_grid');
    $description = get_string('defaultpopup_desc', 'format_grid');
    $default = 1;
    $choices = [
        1 => new lang_string('no'),
        2 => new lang_string('yes'),
    ];
    $page->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Justification.
    $name = 'format_grid/defaultgridjustification';
    $title = get_string('defaultgridjustification', 'format_grid');
    $description = get_string('defaultgridjustification_desc', 'format_grid');
    $default = 'space-between';
    $choices = [
        'start' => new lang_string('start', 'format_grid'),
        'center' => new lang_string('centre', 'format_grid'),
        'end' => new lang_string('end', 'format_grid'),
        'space-around' => new lang_string('spacearound', 'format_grid'),
        'space-between' => new lang_string('spacebetween', 'format_grid'),
        'space-evenly' => new lang_string('spaceevenly', 'format_grid'),
    ];
    $page->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Icon width.
    $name = 'format_grid/defaultimagecontainerwidth';
    $title = get_string('defaultimagecontainerwidth', 'format_grid');
    $description = get_string('defaultimagecontainerwidth_desc', 'format_grid');
    $default = \format_grid\toolbox::get_default_image_container_width();
    $choices = \format_grid\toolbox::get_image_container_widths();
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);

    // Icon ratio.
    $name = 'format_grid/defaultimagecontainerratio';
    $title = get_string('defaultimagecontainerratio', 'format_grid');
    $description = get_string('defaultimagecontainerratio_desc', 'format_grid');
    $default = \format_grid\toolbox::get_default_image_container_ratio();
    $choices = \format_grid\toolbox::get_image_container_ratios();
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);

    // Resize method - 1 = scale, 2 = crop.
    $name = 'format_grid/defaultimageresizemethod';
    $title = get_string('defaultimageresizemethod', 'format_grid');
    $description = get_string('defaultimageresizemethod_desc', 'format_grid');
    $default = 1; // Scale.
    $choices = [
        1 => new lang_string('scale', 'format_grid'),
        2 => new lang_string('crop', 'format_grid'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);

    // Displayed image file type - 1 = original, 2 = webp.
    $name = 'format_grid/defaultdisplayedimagefiletype';
    $title = get_string('defaultdisplayedimagefiletype', 'format_grid');
    $description = get_string('defaultdisplayedimagefiletype_desc', 'format_grid');
    $default = 1; // Original.
    $choices = [
        1 => new lang_string('original', 'format_grid'),
        2 => new lang_string('webp', 'format_grid'),
    ];
    $setting = new admin_setting_configselect($name, $title, $description, $default, $choices);
    $setting->set_updatedcallback('format_grid::update_displayed_images_callback');
    $page->add($setting);

    // Completion.
    $name = 'format_grid/defaultshowcompletion';
    $title = get_string('defaultshowcompletion', 'format_grid');
    $description = get_string('defaultshowcompletion_desc', 'format_grid');
    $default = 1;
    $choices = [
        1 => new lang_string('no'),
        2 => new lang_string('yes'),
    ];
    $page->add(new admin_setting_configselect($name, $title, $description, $default, $choices));

    // Show the grid image in the section summary on a single page.
    $name = 'format_grid/defaultsinglepagesummaryimage';
    $title = get_string('defaultsinglepagesummaryimage', 'format_grid');
    $description = get_string('defaultsinglepagesummaryimage_desc', 'format_grid');
    $default = 1;
    $choices = [
        1 => new lang_string('off', 'format_grid'),
        2 => new lang_string('left', 'format_grid'),
        3 => new lang_string('centre', 'format_grid'),
        4 => new lang_string('right', 'format_grid'),
    ];
    $page->add(new admin_setting_configselect($name, $title, $description, $default, $choices));
}
$ADMIN->add('format_grid', $page);
