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
 * D3 Utils
 *
 * @package     local_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/config', 'local_competvetsuivi/config', 'd3', 'd3-bullet-cvs', 'd3-progress'],
    function ($, cfg, d3config, d3, d3bulletcvs, d3progress) {
        return {
            default_padding: {top: 20, right: 20, bottom: 20, left: 20},
            /**
             *
             * @param svgid SVG item to draw the graph into
             * @param data : an array of data composed of
             * { categorysn: "S1",categorydesc:"Semestre 1", data: [value1, value2, value...], markers:[value4, value5...],
             * padding:{top:, right...} }
             */

            bullet_charts: function (svgid, data) {
                this.load_css('/local/competvetsuivi/js/d3-libraries/bullet-cvs/d3-bullet-cvs.css');

                var svgselector = '#' + svgid;
                var svgelement = $(svgselector).first();
                var padding = Object.assign(data.padding || {}, this.default_padding);
                var width = svgelement.width() - padding.left - padding.right;
                var height = svgelement.height() - padding.top - padding.bottom;

                var chart = d3bulletcvs.bulletcvs()
                    .width(width)
                    .height(height)
                    .data(data);
                d3.select(svgselector)
                    .call(chart);

            },
            progress_charts: function (svgid, data) {
                this.load_css('/local/competvetsuivi/js/d3-libraries/progress/d3-progress.css');
                var thisutils = this;
                $(document).ready( function() {
                        var svgselector = '#' + svgid;
                        var svgelement = $(svgselector).first();
                        var padding = Object.assign(data.padding || {}, thisutils.default_padding);
                        var width = svgelement.width() - padding.left - padding.right;
                        var height = svgelement.height() - padding.top - padding.bottom;

                        var chart = d3progress.progress()
                            .width(width)
                            .height(height)
                            .data(data);
                        d3.select(svgselector)
                            .call(chart);
                    }
                );
            },
            load_css: function (path) {
                var link = document.createElement('link');
                link.type = 'text/css';
                link.rel = 'stylesheet';
                link.href = cfg.wwwroot + path;
                document.getElementsByTagName("head")[0].appendChild(link);
            }
        };
    }
);
