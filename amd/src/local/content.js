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
 * Grid format Course index main component.
 *
 * @module     format_grid/local/content
 * @class      format_grid/local/content
 * @copyright  2023 G J Barnard based upon work done by:
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Component from 'core_courseformat/local/content';
// Course actions is needed for actions that are not migrated to components.
import GridDispatchActions from 'format_grid/local/content/actions';
import * as CourseEvents from 'core_course/events';

export default class GridComponent extends Component {

    /**
     * Constructor hook.
     *
     * @param {Object} descriptor the component descriptor
     */
    create(descriptor) {
        // Optional component name for debugging.
        this.name = 'grid_course_format';
        // Default query selectors.
        this.selectors = {
            SECTION: `[data-for='section']`,
            SECTION_ITEM: `[data-for='section_title']`,
            SECTION_CMLIST: `[data-for='cmlist']`,
            COURSE_SECTIONLIST: `[data-for='course_sectionlist']`,
            CM: `[data-for='cmitem']`,
            TOGGLER: `[data-action="togglecoursecontentsection"]`,
            COLLAPSE: `[data-toggle="collapse"]`,
            TOGGLEALL: `[data-toggle="toggleall"]`,
            // Formats can override the activity tag but a default one is needed to create new elements.
            ACTIVITYTAG: 'li',
            SECTIONTAG: 'li',
        };
        this.selectorGenerators = {
            cmNameFor: (id) => `[data-cm-name-for='${id}']`,
            sectionNameFor: (id) => `[data-section-name-for='${id}']`,
        };
        // Default classes to toggle on refresh.
        this.classes = {
            COLLAPSED: `collapsed`,
            // Course content classes.
            ACTIVITY: `activity`,
            STATEDREADY: `stateready`,
            SECTION: `section`,
        };
        // Array to save dettached elements during element resorting.
        this.dettachedCms = {};
        this.dettachedSections = {};
        // Index of sections and cms components.
        this.sections = {};
        this.cms = {};
        // The page section return.
        this.sectionReturn = descriptor.sectionReturn ?? null;
        this.debouncedReloads = new Map();
    }

    /**
     * Initial state ready method.
     *
     * @param {Object} state the state data
     */
    stateReady(state) {
        this._indexContents();
        // Activate section togglers.
        this.addEventListener(this.element, 'click', this._sectionTogglers);

        // Collapse/Expand all sections button.
        const toogleAll = this.getElement(this.selectors.TOGGLEALL);
        if (toogleAll) {

            // Ensure collapse menu button adds aria-controls attribute referring to each collapsible element.
            const collapseElements = this.getElements(this.selectors.COLLAPSE);
            const collapseElementIds = [...collapseElements].map(element => element.id);
            toogleAll.setAttribute('aria-controls', collapseElementIds.join(' '));

            this.addEventListener(toogleAll, 'click', this._allSectionToggler);
            this.addEventListener(toogleAll, 'keydown', e => {
                // Collapse/expand all sections when Space key is pressed on the toggle button.
                if (e.key === ' ') {
                    this._allSectionToggler(e);
                }
            });
            this._refreshAllSectionsToggler(state);
        }

        if (this.reactive.supportComponents) {
            // Actions are only available in edit mode.
            if (this.reactive.isEditing) {
                new GridDispatchActions(this);
            }

            // Mark content as state ready.
            this.element.classList.add(this.classes.STATEDREADY);
        }

        // Capture completion events.
        this.addEventListener(
            this.element,
            CourseEvents.manualCompletionToggled,
            this._completionHandler
        );

        // Capture page scroll to update page item.
        this.addEventListener(
            document,
            "scroll",
            this._scrollHandler
        );
    }
}
