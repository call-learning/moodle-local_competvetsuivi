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
 * D3 JS initialization
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// See https://docs.moodle.org/dev/Guide_to_adding_third_party_jQuery_for_AMD
define(['core/config'], function (config) {
    window.requirejs.config({
        paths: {
            "d3": config.wwwroot + '/local/competvetsuivi/js/d3/d3',
            "d3-bullet-cvs": config.wwwroot + '/local/competvetsuivi/js/d3-libraries/bullet-cvs/d3-bullet-cvs',
            "d3-progress": config.wwwroot + '/local/competvetsuivi/js/d3-libraries/progress/d3-progress',
        },
        map: {
            'd3-bullet-cvs': {
                'd3-axis': 'd3',
                'd3-scale': 'd3',
                'd3-selection': 'd3',
                'd3-timer': 'd3',
                'd3-transition': 'd3',
                'd3-scale-chromatic': 'd3',
                'd3-format': 'd3',
            },
            'd3-progress': {
                'd3-axis': 'd3',
                'd3-scale': 'd3',
                'd3-format': 'd3',
                'd3-transition': 'd3',
            }
        },
        shim: {
            'd3': {
                exports: 'd3'
            },
            'd3-bullet-cvs': {
                deps: ['d3', 'd3-axis', 'd3-scale', 'd3-selection', 'd3-timer', 'd3-transition', 'd3-scale-chromatic'],
            },
            'd3-progress': {
                deps: ['d3', 'd3-axis', 'd3-scale', 'd3-format', 'd3-transition'],
            },

        }
    });
});


