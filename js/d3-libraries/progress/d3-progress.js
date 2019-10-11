// https://github.com/call-learning/d3-progress.git v1.0.0 Copyright 2019 Laurent David
    //  https://github.com/call-learning/d3-progress v1.0.0. Copyright 2019 SAS CALL Learning
(function (global, factory) {
typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('d3-axis'), require('d3-scale'), require('d3-shape')) :
typeof define === 'function' && define.amd ? define(['exports', 'd3-axis', 'd3-scale', 'd3-shape'], factory) :
(global = global || self, factory(global.d3 = global.d3 || {}, global.d3, global.d3, global.d3));
}(this, function (exports, d3Axis, d3Scale, d3Shape) { 'use strict';

/**
 * Make a data structure to hold a progress bar with markers
 * @return {progressChart}
 */
function progress () {
  let data = {};
  let width = 960;
  let height = 100;
  let graphMargins = { top: 20, right: 5, bottom: 30, left: 15 }; // Absolute margins
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

    // Then for each bar/results, we need to loop through and get the progress bar

    // This is just the graph without axis
    let extentX = graphWidth() * (1 - marginW * 2);
    let extentY = graphHeight() * (1 - marginH * 2);
    let graphWrap = wrap
      .append('g')
      .attr('width', extentX)
      .attr('height', extentY)
      .attr('transform', `translate(0,${graphMargins.top})`);

    // Scales
    const scaleX = d3Scale.scaleLinear()
      .domain([0, 1])
      .range([0, extentX]);

    // Draw grey background
    graphWrap.append('rect')
      .attr('class', 'background-bar')
      .attr('width', extentX)
      .attr('height', extentY)
      .attr('rx', extentY * marginH)
      .attr('ry', extentY * marginH)
      .attr('x', 0)
      .attr('y', 0);

    const tickFormat = function (val) {return Math.round(val * 100);};
    const tickSize = graphMargins.bottom / 3;

    // Add the bottom axes
    const axisgraph = wrap.append('g').attr('class', 'axisgraph');
    axisgraph.attr('transform', `translate(0,${extentY + graphMargins.top})`).call(
      d3Axis.axisBottom(scaleX)
        .tickValues(ticksValues)
        .tickFormat(tickFormat)
        .tickSize(tickSize)
    );
    // Fixup style
    axisgraph.select('.domain').remove();
    axisgraph.attr('font-size', tickSize); // 80% of the margin

    // Now the bars

    // Draw results
    data.forEach(function (currentData, index) {
      var wrapper = graphWrap.append('g')
        .attr('width', extentX)
        .attr('height', extentY / data.length)
        .attr('rx', extentY * marginH)
        .attr('ry', extentY * marginH)
        .attr('class', 'comptype-' + currentData.result.type)
        .attr('transform', `translate(0,${index * (extentY / data.length)})`);

      progressCVS.barCVS(currentData, wrapper, extentY / data.length, scaleX, extentX);
    });

    let resultsmarkervalues =
      data.map(function (r) {
        return r.result.value;
      });

    var triangleSize = graphWidth() / 50;
    var topPosition = function(index) {
      return extentY * (index % 2) + graphMargins.top;
    };

    const resultsmarkerouter = wrap
      .selectAll('g.resultmarker')
      .data(resultsmarkervalues)
      .enter()
      .append('g')
      .attr('class', 'resultmarker')
      .attr('transform', function (r, index) {return `translate(${scaleX(r)},${topPosition(index)})`;});

    var symbolGenerator = d3Shape.symbol().size(triangleSize).type(d3Shape.symbolTriangle);

    var  resultsmarker = resultsmarkerouter.append('g')
      .attr('transform', function (r, index) {return `rotate(${180 * ((index+1) % 2)})`;});

    resultsmarker
      .append('path')
      .attr('d', function () {
        return symbolGenerator();
      })
      .attr('y',  triangleSize * 0.8);


    resultsmarker
      .append('text')
      .attr('dy', function (_d, index) { return (index) % 2 ? triangleSize : -triangleSize*0.5;})
      .attr('transform', function (r, index) {return `rotate(${180 * ((index+1) % 2)})`;})
      .text(function (d) {return `${Math.round(d * 100)} %`;});

  }

  progressCVS.barCVS = function (currentData, wrapper, maxHeight, scaleX, extentX) {
    progressCVS.createBar(
      wrapper,
      'bar-bg',
      maxHeight,
      function () {return extentX;}
    );

    progressCVS.createBar(
      wrapper,
      'bar',
      maxHeight,
      function () {return scaleX(currentData.result.value);}
    );

    // Draw markers
    let circleRadius = progressCVS.barHeight(maxHeight) / 2;

    let marker = wrapper.selectAll('g.marker')
      .data(currentData.markers)
      .enter()
      .append('g')
      .attr('class', function (d) {return d.active ? 'marker active' : 'marker';})
      .attr('x', function (r) { return scaleX(r.value);})
      .attr('y', maxHeight / 2);

    marker.append('circle')
      .attr('r', circleRadius)
      .attr('cx', function (r) { return scaleX(r.value);})
      .attr('cy', maxHeight / 2);

    marker.append('text')
      .attr('x', function (r) { return scaleX(r.value);})
      .attr('y', maxHeight / 2)
      .text(function (d) {return d.label;})
      .attr('class', 'marker-text')
      .attr('font-size', maxHeight / 2);
  };

  progressCVS.createBar = function (item, classname, maxHeight, widthCallBack) {
    item.append('rect')
      .attr('class', classname)
      .attr('width', widthCallBack)
      .attr('height', function () {return progressCVS.barHeight(maxHeight);})
      .attr('rx', maxHeight * marginH)
      .attr('ry', maxHeight * marginH)
      .attr('x', 0)
      .attr('y', function () { return progressCVS.barMiddlePosition(maxHeight);});
  };

  progressCVS.barHeight = function (maxHeight) { return maxHeight * (1 - marginH * 2); };
  progressCVS.barMiddlePosition = function (maxHeight) { return maxHeight / 2 - progressCVS.barHeight(maxHeight) / 2; };

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
