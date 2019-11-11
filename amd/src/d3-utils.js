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
            default_padding: {top: 10, right: 5, bottom: 10, left: 5},
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
                var width = svgelement.parent().width() - padding.left - padding.right;
                var height = svgelement.parent().height() - padding.top - padding.bottom;

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
                $(document).ready(function () {
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

            ring_charts: function (svgid, data) {
                var thisutils = this;
                $(document).ready(function () {
                    var svgselector = '#' + svgid;
                    var svgelement = $(svgselector).first();
                    var padding = thisutils.default_padding;
                    var width = svgelement.parent().width() - padding.left - padding.right;
                    var height = svgelement.parent().height() - padding.top - padding.bottom;
                    var radius = Math.min(width, height) / 2;

                    // Hack we just do the graph on the strand 1

                    var color = d3.scaleOrdinal().range(d3.schemeDark2);

                    var arc = d3.arc()
                        .outerRadius(radius - 10)
                        .innerRadius(radius / 2);

                    var outerArc = d3.arc()
                        .outerRadius(radius )
                        .innerRadius(radius - 10 );

                    // Create the basic drawing / container
                    var svg = d3.select(svgselector).attr("width", width)
                        .attr("height", height)
                        .append("g")
                        .attr("transform", "translate(" + width / 2 + "," + height / 2 + ")");

                    // Pie chart return just the value
                    var pie = d3.pie().value(function (d) {
                        return d.val;
                    });
                    var d3piedata =  pie(Object.values(data.compsvalues));
                    var g = svg.selectAll(".arc")
                        .data(d3piedata)
                        .enter()
                        .append("g")
                        .attr("class", "arc");

                    //Draw, Style and color the arc
                    g.append("path")
                        .attr("d", arc)
                        .style("fill", function (_d,i) {
                            return color(i);
                        })
                        .style('stroke', "black");
                    // Add the polylines between chart and labels (see https://www.d3-graph-gallery.com/graph/donut_label.html)
                    svg.append('g').classed('lines',true);
                    svg.append('g').classed('labels',true);
                    svg
                        .select('.lines')
                        .selectAll('polyline')
                        .data(d3piedata)
                        .enter()
                        .append('polyline')
                        .attr("stroke", "black")
                        .style("fill", "none")
                        .attr("stroke-width", 1)
                        .attr('points', function (d) {
                            var posA = arc.centroid(d); // line insertion in the slice
                            var posB = outerArc.centroid(d);
                            var posC = outerArc.centroid(d);
                            var midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
                            posC[0] = radius * 0.95 * (midangle < Math.PI ? 1 : -1);
                            return [posA, posB, posC];
                        });

                    // Add the polylines between chart and labels:
                    var labels = svg
                        .select('.labels')
                        .selectAll('text')
                        .data(d3piedata)
                        .enter()
                        .append('text')
                        .attr('transform', function (d) {
                            var pos = outerArc.centroid(d);
                            var midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
                            pos[0] = radius * 0.99 * (midangle < Math.PI ? 1 : -1);
                            return 'translate(' + pos + ')';
                        });
                    labels.append('tspan')
                        .text(function (d) {
                            return d.data.shortname + ' - ' + (Math.round(d.data.val * 100)) + '%';
                        })
                        .attr('class','sn-label')
                        .style('text-anchor', function (d) {
                            var midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
                            return (midangle < Math.PI ? 'start' : 'end');
                        });
                    labels.append('tspan')
                        .text(function (d) {
                            return d.data.fullname;
                        })
                        .attr('class','fn-label')
                        .attr('dy','1.2em')
                        .attr('x','0')
                        .style('text-anchor', function (d) {
                            var midangle = d.startAngle + (d.endAngle - d.startAngle) / 2;
                            return (midangle < Math.PI ? 'start' : 'end');
                        });


                });
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
