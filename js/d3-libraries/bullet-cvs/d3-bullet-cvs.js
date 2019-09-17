// https://github.com/call-learning/d3-bullet-cvs.git v1.0.0 Copyright 2019 Laurent David
    //  https://github.com/call-learning/d3-bullet-cvs v1.0.0. Copyright 2019 SAS CALL Learning
(function (global, factory) {
typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('d3-axis'), require('d3-scale'), require('d3-selection'), require('d3-timer'), require('d3-transition'), require('d3-scale-chromatic')) :
typeof define === 'function' && define.amd ? define(['exports', 'd3-axis', 'd3-scale', 'd3-selection', 'd3-timer', 'd3-transition', 'd3-scale-chromatic'], factory) :
(global = global || self, factory(global.d3 = global.d3 || {}, global.d3, global.d3, global.d3, global.d3, global.d3, global.d3));
}(this, function (exports, d3Axis, d3Scale, d3Selection, d3Timer, d3Transition, d3ScaleChromatic) { 'use strict';

/**
 * Make a data structure to hold several bullet charts in one row, so to have a global
 * progress bar
 * @return {bulCVS}
 */
function bulletCvs () {
  let maxresults = function (d) {return d.maxresults;};
  let results = function (d) {return d.results;};
  let rlabels = function (d) {return d.rlabels;};
  let data = {};
  let width = 960;
  let height = 100;
  let innerWidth = function () { return width - margins.left - margins.right; };
  let innerHeight = function () { return height - margins.top - margins.bottom; };
  let margins = { top: 10, right: 5, bottom: 20, left: 5 };
  let graphMarginH = 5;

  // For each small multiple…
  function bulCVS (svgitem) {

    let graphWidth = innerWidth() / data.length - graphMarginH * 2;
    let g = svgitem
      .attr('width', width)
      .attr('height', height)
      .selectAll('g') // Map data to an inner g in the svgitem
      .data(data)
      .enter()
      .append('g')
      .attr('class', 'bullet-cvs');

    g.each(function (d, i) {
      let cmaxresults = maxresults(d);
      let cresults = results(d);
      let crlabels = rlabels(d);
      let currentg = d3Selection.select(this);
      let extentX;
      let extentY;

      let wrap = currentg.select('g.wrap');
      if (wrap.empty()) wrap = currentg.append('g').attr('class', 'wrap');

      extentX = graphWidth;
      extentY = innerHeight();

      // Compute the new x-scale.
      const domainMaxRange = Math.max(Math.max.apply(null, cmaxresults), Math.max.apply(null, cresults));
      const scaleX = d3Scale.scaleLinear()
        .domain([0, domainMaxRange])
        .range([0, extentX]);

      let resultsColorScheme = d3ScaleChromatic.schemeSet1;
      let maxResultColorScheme = d3ScaleChromatic.schemeSet1;

      // Derive width-scales from the x-scales.
      let mstwidth = bulCVSWidth(scaleX); // End width

      let baseStrokeWidth = 2;
      let baseArrowSize = 10;
      let barHeight = function (index, factor) { return (extentY / (index / 4 + 1)) / factor; };
      let markHeight = function (index) { return extentY; };
      let middlePosition = function (currentHeight, totalHeight) { return totalHeight / 2 - currentHeight / 2; };

      // Draw the track background for max result markers
      wrap
        .selectAll('rect.maxresults')
        .data(cmaxresults)
        .enter()
        .append('rect')
        .attr('class', 'maxresults')
        .attr('width', mstwidth)
        .style('fill', (_d, index) => maxResultColorScheme[index])
        .style('fill-opacity', '0.3')
        .attr('height', extentY)
        .attr('x', 0)
        .attr('y', 0);

      // Draw the result rects
      // Build Tootipes
      let restip = d3Tooltip(function (d, index) {return crlabels[index]; });
      wrap
        .selectAll('rect.result')
        .data(cresults)
        .enter()
        .append('rect')
        .attr('class', 'result')
        .attr('width', mstwidth)
        .style('fill', (_d, index) => resultsColorScheme[index])
        .attr('height', function (_d, index) {return barHeight(index, 2);})
        .attr('x', 0)
        .attr('y', function (_d, index) { return middlePosition(barHeight(index, 2), extentY);})
        .on('mouseover', restip.mouseover)
        .on('mouseout', restip.mouseout)
        .on('mouseleave', restip.mouseleave);

      // Update the maxresultmarker marker lines.

      // Draw the markers
      wrap
        .selectAll('line.marker')
        .data(cresults)
        .enter().append('polygon')
        .attr('class', 'marker')
        .style('stroke', (_d, index) => resultsColorScheme[index])
        .style('stroke-width', (_d, index) => baseStrokeWidth * (index + 1))
        .attr('points', function (d, index) {
            const size = baseArrowSize / (index + 1);
            return pointerMaker(size, baseArrowSize, scaleX(d), innerHeight());
          }
        );

      const axis = wrap.append('g').attr('class', 'axis');
      axis.attr('transform', `translate(0,${extentY})`).call(d3Axis.axisBottom(scaleX));

      // Graph Marker

      wrap
        .selectAll('line.limits')
        .data([0, graphWidth])
        .enter()
        .append('line')
        .attr('class', 'limits')
        .style('stroke', '#000')
        .style('stroke-width', graphMarginH)
        .attr('x1', scaleX)
        .attr('x2', scaleX)
        .attr('y1', function (_d, index) {return (extentY / 2 - markHeight() / 2);})
        .attr('y2', function (_d, index) {return (extentY / 2 + markHeight() / 2);});

      // Add label in the middle
      wrap
        .selectAll('text.label')
        .data([d.groupname])
        .enter()
        .append('text')
        .attr('class', 'label')
        .attr('y', extentY / 2)
        .attr('x', extentX / 2)
        .text((d) => d);
      // Finally translate the graph so it appear next to the other

      currentg.attr('transform', 'translate(' + (margins.left + graphWidth * i + graphMarginH * i) + ',' + margins.top + ')');
    });
    d3Timer.timerFlush();
  }

  // maxresults (previous, goal)
  bulCVS.maxresults = function (_) {
    if (!arguments.length) return maxresults;
    maxresults = _;
    return this;
  };

  // results (actual, forecast)
  bulCVS.results = function (_) {
    if (!arguments.length) return results;
    results = _;
    return this;
  };

  bulCVS.width = function (_) {
    if (!arguments.length) return width;
    width = +_;
    return this;
  };

  bulCVS.height = function (_) {
    if (!arguments.length) return height;
    height = +_;
    return this;
  };

  bulCVS.margins = function (_) {
    if (!arguments.length) return margins;
    margins = _;
    return this;
  };

  bulCVS.data = function (_) {
    if (!arguments.length) return data;
    data = _;
    return this;
  };

  bulCVS.tickFormat = function (_) {
    if (!arguments.length) return xAxis.tickFormat();
    xAxis.tickFormat(_);
    return this;
  };

  return bulCVS;
}

function bulCVSWidth (x) {
  const x0 = x(0);
  return function (d) {
    return Math.abs(x(d) - x0);
  };
}

/**
 * Create a triangle oriented toward the bottom, point toward x,y
 * @param base
 * @param height
 * @param x
 * @param y
 */
function pointerMaker (base, height, x, y) {
  return `${x - base / 2} ${y - height}, ${x + base / 2} ${y - height}, ${x} ${y}`;
}

function d3Tooltip (displayFunction) {

  let tooltip = {};
  // create a tooltip
  let tid = 'd3tooltipdiv' + btoa(Math.random()).substring(0, 12);
  var Tooltip = d3Selection.select('body')
    .select('#' + tid)
    .data([0]) // Force add this
    .enter()
    .append('div')
    .style('position', 'absolute')
    .style('opacity', 0)
    .attr('class', 'tooltip')
    .attr('id', tid)
    .style('background-color', 'white')
    .style('border', 'solid')
    .style('border-width', '2px')
    .style('border-radius', '5px')
    .style('padding', '5px');

  // Three function that change the tooltip when user hover / move / leave a cell
  tooltip.mouseover = function (d, index) {
    Tooltip
      .style('stroke', 'black')
      .style('opacity', 1)
      .html(displayFunction(d, index))
      .style('left', (d3Selection.mouse(this)[0]) + 'px')
      .style('top', (d3Selection.mouse(this)[1]) + 'px');
  };
  tooltip.mousemove = function (d, index) {
    Tooltip
      .style('left', (d3Selection.mouse(this)[0]) + 'px')
      .style('top', (d3Selection.mouse(this)[1]) + 'px');

  };
  tooltip.mouseleave = function (d, index) {
    Tooltip
      .style('opacity', 0)
      .style('stroke', 'none');
  };
  return tooltip;

}

exports.bulletcvs = bulletCvs;

Object.defineProperty(exports, '__esModule', { value: true });

}));
//# sourceMappingURL=d3-bullet-cvs.js.map
