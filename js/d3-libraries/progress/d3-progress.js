// https://github.com/call-learning/d3-progress.git v1.0.0 Copyright 2019 Laurent David
    //  https://github.com/call-learning/d3-progress v1.0.0. Copyright 2019 SAS CALL Learning
(function (global, factory) {
typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('d3-axis'), require('d3-scale'), require('d3-format'), require('d3-transition')) :
typeof define === 'function' && define.amd ? define(['exports', 'd3-axis', 'd3-scale', 'd3-format', 'd3-transition'], factory) :
(global = global || self, factory(global.d3 = global.d3 || {}, global.d3, global.d3, global.d3));
}(this, function (exports, d3Axis, d3Scale, d3Format) { 'use strict';

/**
 * Make a data structure to hold a progress bar with markers
 * @return {progressChart}
 */
function progress () {
  let data = {};
  let width = 960;
  let height = 100;
  let graphMargins = { top: 10, right: 5, bottom: 20, left: 15 }; // Absolute margins
  let graphWidth = function () { return width - graphMargins.left - graphMargins.right; };
  let graphHeight = function () { return height - graphMargins.top - graphMargins.bottom; };
  let marginW = 0.1;
  let marginH = 0.1;

  let ticksValues = [0, 0.25, 0.50, 0.75, 1];
  // For each small multiple…
  function progressCVS (svgitem) {

    // This wrapper contains the graph and the axis
    let wrap = svgitem
      .attr('width', width)
      .attr('height', height)
      .append('g')
      .attr('class', 'progress-cvs')
      .attr('transform', `translate(${graphMargins.left},0)`);

    // This is just the graph without axis
    let extentX = graphWidth() * (1 - marginW * 2);
    let extentY = graphHeight() * (1 - marginH * 2);
    let graphWrap = wrap
      .append('g')
      .attr('width', extentX)
      .attr('height', extentY);

    // Scales
    const scaleX = d3Scale.scaleLinear()
      .domain([0, 1])
      .range([0, extentX]);

    // Draw xscale (tick 0.25)


    let displayedValues = new Set(ticksValues);
    data.markers.forEach(function(d) {
      displayedValues.add(d.value);
    });
    const axis = wrap.append('g').attr('class', 'axis');
    axis.attr('transform', `translate(0,${extentY})`).call(
      d3Axis.axisBottom(scaleX)
        .tickValues(Array.from(displayedValues).sort())
        .tickFormat(d3Format.format('.0%'))
    );
    axis.attr('font-size', graphMargins*0.8); // 80% of the margin
    // Draw grey background
    graphWrap.append('rect')
      .attr('class', 'background-bar')
      .attr('width', extentX)
      .attr('height', extentY)
      .attr('x', 0)
      .attr('y', 0);

    // Draw results
    let barHeight = function (index) { return extentY * (1 - marginH * index * 4); };
    let barMiddlePosition = function (index) { return extentY / 2 - barHeight(index) / 2; };

    graphWrap.selectAll('rect.results')
      .data(data.results)
      .enter()
      .append('rect')
      .attr('class', function (_d, index) { return 'results bar-' + index;})
      .attr('width', function (r) { return scaleX(r.value);})
      .attr('height', function (_d, index) {return barHeight(index);})
      .attr('x', 0)
      .attr('y', function (_d, index) { return barMiddlePosition(index);});
    // Draw markers
    let circleRadius = function (data) { return extentY * (1 - marginH * (data.active ? 2 : 4)) / 2; };

    let marker = graphWrap.selectAll('g.marker')
      .data(data.markers)
      .enter()
      .append('g')
      .attr('class', function (d) {return d.active ? 'marker active' : 'marker';})
      .attr('x', function (r) { return scaleX(r.value);})
      .attr('y', 0);

    marker.append('circle')
      .attr('r', circleRadius)
      .attr('cx', function (r) { return scaleX(r.value);})
      .attr('cy', extentY / 2);

    marker.append('text')
      .attr('x', function (r) { return scaleX(r.value);})
      .attr('y', extentY / 2)
      .text(function (d) {return d.label;})
      .attr('font-size',extentY/2);
  }

  progressCVS.width = function (_) {
    if (!arguments.length) return width;
    width = +_;
    return this;
  };

  progressCVS.height = function (_) {
    if (!arguments.length) return height;
    height = +_;
    return this;
  };

  progressCVS.margins = function (_) {
    if (!arguments.length) return graphMargins;
    graphMargins = _;
    return this;
  };

  progressCVS.data = function (_) {
    if (!arguments.length) return data;
    data = _;
    return this;
  };

  return progressCVS;
}

exports.progress = progress;

Object.defineProperty(exports, '__esModule', { value: true });

}));
//# sourceMappingURL=d3-progress.js.map