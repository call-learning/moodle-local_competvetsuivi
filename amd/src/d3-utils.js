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
define(['jquery', 'core/config'],
    function($, cfg) {
        var requirejsd3 = require.config({
            paths: {
                "d3-progress": cfg.wwwroot
                    + '/lib/javascript.php/'
                    + cfg.jsrev
                    + '/local/competvetsuivi/js/d3-libraries/progress/d3-progress'
                    + (cfg.developerdebug ? '.min' : ''),
                "d3": cfg.wwwroot
                    + '/lib/javascript.php/'
                    + cfg.jsrev
                    + '/local/competvetsuivi/js/d3/d3'
                    + (cfg.developerdebug ? '.min' : ''),
            },
            map: {
                'd3-progress': {
                    'd3-axis': 'd3',
                    'd3-scale': 'd3',
                    'd3-format': 'd3',
                    'd3-transition': 'd3',
                    'd3-shape': 'd3',
                    'd3-selection': 'd3',
                }
            },
            shim: {
                'd3-progress': {
                    deps: ['d3', 'd3-axis', 'd3-scale', 'd3-format', 'd3-transition', 'd3-shape', 'd3-selection'],
                },
                'd3': {
                    exports: 'd3'
                },
            }
        });
        return {
            default_padding: {top: 10, right: 5, bottom: 1, left: 5},
            default_progress_chart_height: 125,
            default_doghnut_chart_height: 300,

            /**
             *
             * @param svgid SVG item to draw the graph into
             * @param data : an array of data
             * @param paddingandsize: optional padding and size parameter
             */
            progress_charts: function(svgid, data, paddingandsize) {
                this.load_css('/local/competvetsuivi/js/d3-libraries/progress/d3-progress.css');
                requirejsd3(['d3', 'd3-progress'], function(d3,d3progress) {
                    var svgselector = '#' + svgid;
                    var svgelement = $(svgselector).first();
                    var thisutils = this;
                    var display_chart = function () {
                        var width = svgelement.parent().innerWidth();
                        var height = thisutils.default_progress_chart_height;
                        if (paddingandsize) {
                            if (paddingandsize.size !== undefined) {
                                if (paddingandsize.size.height !== undefined && paddingandsize.size.height != 0) {
                                    // If set to 0 then autosize it.
                                    height = paddingandsize.size.height ? paddingandsize.size.height : svgelement.height();
                                }
                                if (paddingandsize.size.width !== undefined && paddingandsize.size.height != 0) {
                                    // If set to 0 then autosize it.
                                    width = paddingandsize.size.width ? paddingandsize.size.width : width;
                                }

                            }
                        }
                        var chart = d3progress.progress()
                            .width(width)
                            .height(height)
                            .data(data);
                        d3.select(svgselector)
                            .call(chart);
                    };
                    $(document).ready(display_chart);
                    $(window).bind('resize', function () {
                        $(svgelement).empty();
                        display_chart();
                    });
                });
            },

            /**
             *
             * @param svgid SVG item to draw the graph into
             * @param data : an array of data
             * @param paddingandsize: optional padding and size parameter
             */
            ring_charts: function(svgid, data, paddingandsize) {
                var thisutils = this;
                this.load_css('/local/competvetsuivi/js/d3-libraries/progress/d3-progress.css');
                requirejsd3(['d3'], function(d3) {
                    thisutils.add_doghnut_patterns_definitions(d3);
                    var svgselector = '#' + svgid;
                    var svgelement = $(svgselector).first();
                    var padding = thisutils.default_padding;
                    var display_chart = function () {

                        var width = svgelement.parent().innerWidth();
                        var height = svgelement.parent().innerHeight();

                        if (paddingandsize) {
                            if (paddingandsize.size !== undefined) {
                                if (paddingandsize.size.height !== undefined && paddingandsize.size.height != 0) {
                                    // If set to 0 then autosize it.
                                    height = paddingandsize.size.height ? paddingandsize.size.height : svgelement.height();
                                }
                                if (paddingandsize.size.width !== undefined && paddingandsize.size.width != 0) {
                                    // If set to 0 then autosize it.
                                    width = paddingandsize.size.width ? paddingandsize.size.width : width;
                                }
                            }
                            if (paddingandsize.padding !== undefined
                                && paddingandsize.padding.top !== undefined
                                && paddingandsize.padding.left !== undefined
                                && paddingandsize.padding.right !== undefined
                                && paddingandsize.padding.bottom !== undefined) {
                                padding = paddingandsize.padding;
                            }

                        }
                        var radius = Math.min(width - padding.left*2, height- padding.top*2) / 2;
                        var underLayerThickness = 2;
                        var arc = d3.arc()
                            .outerRadius(radius - underLayerThickness)
                            .innerRadius(radius / 1.8);
                        // Create the basic drawing / container.
                        var svg = d3.select(svgselector).attr("width", width)
                            .attr("height", height)
                            .append("g")
                            .attr("transform", "translate(" + (width / 2 + padding.left)
                                + "," + (height / 2 + padding.top) + ")");

                        // Pie chart return just the value.
                        var pie = d3.pie().value(function (d) {
                            return d.val;
                        }).sort(null);
                        // Rearrange data so we get big + small values interleaved, this is to make sure labels
                        // are not to close to each other.
                        var d3piedata = pie(Object.values(data.compsvalues));

                        var g = svg.selectAll(".arc")
                            .data(d3piedata)
                            .enter()
                            .append("g")
                            .attr("class", "arc");

                        // Draw the underlayer arc (make an effect).
                        var underLayerArc = d3.arc()
                            .outerRadius(radius)
                            .innerRadius(radius / 1.8 - underLayerThickness);

                        g.append("path")
                            .attr("d", underLayerArc)
                            .attr("class", function () {
                                return 'arc-bg-underlayer';
                            });
                        // Draw, Style and color the arc.
                        g.append("path")
                            .attr("d", arc)
                            .attr("class", function (d) {
                                return 'macrocomp-' + d.data.colorindex + ' arc-bg';
                            });

                        // Add the percent on the label for the total value.
                        g.append("text")
                            .attr("class", "doghnut-text-percent")
                            .attr("transform", function (d) {
                                var d = arc.centroid(d);
                                return "translate(" + d + ")";
                            })
                            .style("text-anchor", "middle")
                            .text(function (d) {
                                var rval = Math.round(d.value * 100);
                                if (rval < 5) {
                                    return '';
                                }
                                return rval + '%';
                            });
                    };
                    $(document).ready(display_chart);
                    $(window).bind('resize', function () {
                        $(svgelement).empty();
                        display_chart();
                    });
                });
            },
            load_css: function (path) {
                var globalpath = cfg.wwwroot + path;
                if ($("link[href='" + globalpath + "']").length === 0) {
                    var link = document.createElement('link');
                    link.type = 'text/css';
                    link.rel = 'stylesheet';
                    link.href = globalpath;
                    document.getElementsByTagName("head")[0].appendChild(link);
                }
            },
            add_doghnut_patterns_definitions: function(d3) {
                // See https://iros.github.io/patternfills/sample_d3.html
                var availablepatterns = [
                    {
                        pattername: 'doghnut-p1',
                        // eslint-disable-next-line max-len.
                        imagedef: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHhtbG5zOnhsaW5rPSdodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rJyB3aWR0aD0nNicgaGVpZ2h0PSc2Jz4KICA8cmVjdCB3aWR0aD0nNicgaGVpZ2h0PSc2JyBmaWxsPScjZWVlZWVlJy8+CiAgPGcgaWQ9J2MnPgogICAgPHJlY3Qgd2lkdGg9JzMnIGhlaWdodD0nMycgZmlsbD0nI2U2ZTZlNicvPgogICAgPHJlY3QgeT0nMScgd2lkdGg9JzMnIGhlaWdodD0nMicgZmlsbD0nI2Q4ZDhkOCcvPgogIDwvZz4KICA8dXNlIHhsaW5rOmhyZWY9JyNjJyB4PSczJyB5PSczJy8+Cjwvc3ZnPg=='
                    },
                ];

                var svgpattern = d3.select('body').select('svg#d3progresspatternsdef');
                if (svgpattern.empty()) {
                    d3.select('body')
                        .append('svg')
                        .attr('id', 'd3utilspatternsdef')
                        .append('defs')
                        .selectAll('pattern')
                        .data(availablepatterns)
                        .enter()
                        .append('pattern')
                        .attr('id', function(d) {
                            return d.pattername;
                        })
                        .attr('patternUnits', 'userSpaceOnUse')
                        .attr('width', 10)
                        .attr('height', 10)
                        .append('image')
                        .attr('xlink:href', function(d) {
                            return d.imagedef;
                        })
                        .attr('x', 0)
                        .attr('y', 0)
                        .attr('width', 10)
                        .attr('height', 10);
                }
            },

        };
    }
);
