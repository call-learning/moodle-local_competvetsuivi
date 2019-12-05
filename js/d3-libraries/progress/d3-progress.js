// https://github.com/call-learning/d3-progress.git v1.0.0 Copyright 2019 Laurent David
    //  https://github.com/call-learning/d3-progress v1.0.0. Copyright 2019 SAS CALL Learning
(function (global, factory) {
typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('d3-selection'), require('d3-axis'), require('d3-scale'), require('d3-shape')) :
typeof define === 'function' && define.amd ? define(['exports', 'd3-selection', 'd3-axis', 'd3-scale', 'd3-shape'], factory) :
(global = global || self, factory(global.d3 = global.d3 || {}, global.d3, global.d3, global.d3, global.d3));
}(this, function (exports, d3Selection, d3Axis, d3Scale, d3Shape) { 'use strict';

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
  let marginW = 0.05;
  let marginH = 0.1;

  let ticksValues = [0, 0.25, 0.50, 0.75, 1];

  // For each small multiple…
  function progressCVS (svgitem) {

    // Prepare for patterns

    progressCVS.addPatternDefinition();
    progressCVS.addFilterDefinition(2,2,2, 'linear', 0.5);

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

    var triangleSize = Math.sqrt(graphWidth() / 25);
    var topPosition = function (index) {
      return (index % 2)? graphMargins.top + extentY + triangleSize: graphMargins.top - triangleSize;
    };

    const resultsmarker = wrap
      .selectAll('g.resultmarker')
      .data(resultsmarkervalues)
      .enter()
      .append('g')
      .attr('class', 'resultmarker')
      .attr('transform', function (r, index) {return `translate(${scaleX(r)},${topPosition(index)})`;});

    var symbolGenerator = d3Shape.symbol().size(triangleSize*triangleSize).type(d3Shape.symbolTriangle);

    //var resultsmarker = resultsmarkerouter.append('g');


    resultsmarker
      .append('path')
      .attr('class','value-arrow')
      .attr('d', function () {
        return symbolGenerator();
      })
      .attr('transform', function (r, index) {return `rotate(${180 * ((index + 1) % 2)}),translate(0,${
          ((index) % 2 ? -triangleSize : -triangleSize*0.6)})`;});


    resultsmarker.append('g').attr('class', 'resultmarker-bg')
      .attr('transform', function (_d, index) { return  `translate(0,${(index) % 2 ? triangleSize : 0})`;})
      .append('text')
      .attr('class', 'resultmarker-text')
      .attr('font-size', tickSize * 1.2)
      .text(function (d) {return `${Math.round(d * 100)} %`;});

    var markersbb = [];
    svgitem.selectAll('text.resultmarker-text').each(function (d, i) {
      markersbb[i] = this.getBBox(); // get bounding box of text field and store it in texts array
    });

    // Adjust rect so they are in the background
    // Margin of few px

    svgitem
      .selectAll('g.resultmarker-bg')
      .data(markersbb)
      .append('rect')
      .lower()
      .attr('class', 'resultmarker-bg')
      .attr('x', function (d) { return d.x-d.width*marginW/2; })
      .attr('y', function (d ) { return d.y-d.height*marginH/2; })
      .attr('width', function (d) { return d.width*(1+marginW/2); })
      .attr('height', function (d) { return d.height*(1+marginH/2); });

  }

  progressCVS.barCVS = function (currentData, wrapper, maxHeight, scaleX, extentX) {
    progressCVS.createBar(
      wrapper,
      'bar-gray-bg',
      maxHeight,
      function () {return extentX;}
    );

    progressCVS.createBar(
      wrapper,
      'bar-bg',
      maxHeight,
      function () {return scaleX(currentData.result.value);}
    );

    progressCVS.createBar(
      wrapper,
      'bar',
      maxHeight,
      function () {return scaleX(currentData.result.value);}
    );

    // Draw markers
    let circleRadius = progressCVS.barHeight(maxHeight) / 2.2;

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
      .attr('rx', maxHeight * marginH * 2)
      .attr('ry', maxHeight * marginH * 2)
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

  progressCVS.addPatternDefinition = function (_) {
    // See https://iros.github.io/patternfills/sample_d3.html
    var availablepatterns = [
      {
        pattername: 'crosshatch',
        imagedef: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPSc4JyBoZWlnaHQ9JzgnPgogIDxyZWN0IHdpZHRoPSc4JyBoZWlnaHQ9JzgnIGZpbGw9JyNmZmYnLz4KICA8cGF0aCBkPSdNMCAwTDggOFpNOCAwTDAgOFonIHN0cm9rZS13aWR0aD0nMC41JyBzdHJva2U9JyNhYWEnLz4KPC9zdmc+Cg=='
      },
      {
        pattername: 'diagonal-stripe-3',
        imagedef: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPScxMCcgaGVpZ2h0PScxMCc+CiAgPHJlY3Qgd2lkdGg9JzEwJyBoZWlnaHQ9JzEwJyBmaWxsPSd3aGl0ZScvPgogIDxwYXRoIGQ9J00tMSwxIGwyLC0yCiAgICAgICAgICAgTTAsMTAgbDEwLC0xMAogICAgICAgICAgIE05LDExIGwyLC0yJyBzdHJva2U9J2JsYWNrJyBzdHJva2Utd2lkdGg9JzMnLz4KPC9zdmc+'
      },
      {
        pattername: 'whitecarbon',
        imagedef: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHhtbG5zOnhsaW5rPSdodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rJyB3aWR0aD0nNicgaGVpZ2h0PSc2Jz4KICA8cmVjdCB3aWR0aD0nNicgaGVpZ2h0PSc2JyBmaWxsPScjZWVlZWVlJy8+CiAgPGcgaWQ9J2MnPgogICAgPHJlY3Qgd2lkdGg9JzMnIGhlaWdodD0nMycgZmlsbD0nI2U2ZTZlNicvPgogICAgPHJlY3QgeT0nMScgd2lkdGg9JzMnIGhlaWdodD0nMicgZmlsbD0nI2Q4ZDhkOCcvPgogIDwvZz4KICA8dXNlIHhsaW5rOmhyZWY9JyNjJyB4PSczJyB5PSczJy8+Cjwvc3ZnPg=='
      },
      {
        pattername: 'dots-7',
        imagedef: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHdpZHRoPScxMCcgaGVpZ2h0PScxMCc+CiAgPHJlY3Qgd2lkdGg9JzEwJyBoZWlnaHQ9JzEwJyBmaWxsPSd3aGl0ZScgLz4KICA8cmVjdCB4PScwJyB5PScwJyB3aWR0aD0nNycgaGVpZ2h0PSc3JyBmaWxsPSdibGFjaycgLz4KPC9zdmc+'
      },
    ];

    var svgpattern = d3Selection.select('body').select('svg#d3progresspatternsdef');
    if (svgpattern.empty()) {
      d3Selection.select('body')
        .append('svg')
        .attr('id', 'd3progresspatternsdef')
        .append('defs')
        .selectAll('pattern')
        .data(availablepatterns)
        .enter()
        .append('pattern')
        .attr('id', function (d) {return d.pattername;})
        .attr('patternUnits', 'userSpaceOnUse')
        .attr('width', 10)
        .attr('height', 10)
        .append('image')
        .attr('xlink:href', function (d) {return d.imagedef;})
        .attr('x', 0)
        .attr('y', 0)
        .attr('width', 10)
        .attr('height', 10);
    }
  };

  progressCVS.addFilterDefinition = function (dx, dy, stdDeviation, type, slope) {
    var svgpattern = d3Selection.select('body').select('svg#d3progressfiltersdef');
    if (svgpattern.empty()) {
      var defs = d3Selection.select('body')
        .append('svg')
        .attr('id', 'd3progressfiltersdef')
        .append('defs');
      var filter = defs.append('filter')
        .attr('id', 'd3std-dropshadow');
      filter.append('feGaussianBlur')
        .attr('in', 'SourceAlpha')
        .attr('stdDeviation', parseInt(stdDeviation));

      filter.append('feOffset')
        .attr('dx', dx)
        .attr('dy', dy);
      var feComponentTransfer = filter.append('feComponentTransfer');
      feComponentTransfer.append('feFuncA')
        .attr('type', type)
        .attr('slope', parseFloat(slope));

      var feMerge = filter.append('feMerge');
      feMerge.append('feMergeNode');
      feMerge.append('feMergeNode').attr('in', 'SourceGraphic');
    }
  };

  return progressCVS;
}

exports.progress = progress;

Object.defineProperty(exports, '__esModule', { value: true });

}));
//# sourceMappingURL=d3-progress.js.map
