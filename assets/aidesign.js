(() => {
  const prototype = document.querySelector("[data-stock-prototype]");
  if (!prototype) return;

  const TRADING_DAYS = 252;
  const PROJECTION_DAYS = 63;
  const SVG_NS = "http://www.w3.org/2000/svg";

  const stockProfiles = {
    AAPL: {
      name: "Apple Inc.",
      startPrice: 186.34,
      endPrice: 214.82,
      wave: 0.045,
      noise: 0.018,
      cycles: 2.2
    },
    MSFT: {
      name: "Microsoft Corporation",
      startPrice: 421.76,
      endPrice: 468.25,
      wave: 0.038,
      noise: 0.014,
      cycles: 1.8
    },
    NVDA: {
      name: "NVIDIA Corporation",
      startPrice: 91.48,
      endPrice: 135.62,
      wave: 0.095,
      noise: 0.04,
      cycles: 2.6
    },
    TSLA: {
      name: "Tesla, Inc.",
      startPrice: 182.6,
      endPrice: 248.1,
      wave: 0.12,
      noise: 0.045,
      cycles: 3.1
    },
    GOOGL: {
      name: "Alphabet Inc.",
      startPrice: 172.35,
      endPrice: 196.74,
      wave: 0.044,
      noise: 0.016,
      cycles: 2
    },
    AMZN: {
      name: "Amazon.com, Inc.",
      startPrice: 183.12,
      endPrice: 221.4,
      wave: 0.052,
      noise: 0.02,
      cycles: 2.4
    },
    META: {
      name: "Meta Platforms, Inc.",
      startPrice: 474.2,
      endPrice: 642.35,
      wave: 0.066,
      noise: 0.026,
      cycles: 2.7
    }
  };

  const form = prototype.querySelector("[data-stock-form]");
  const input = prototype.querySelector("#stockTickerInput");
  const message = prototype.querySelector("[data-stock-message]");
  const results = prototype.querySelector("[data-stock-results]");
  const chart = prototype.querySelector("[data-stock-chart]");

  const fields = {
    source: prototype.querySelector("[data-stock-source]"),
    dataNote: prototype.querySelector("[data-stock-data-note]"),
    name: prototype.querySelector("[data-stock-name]"),
    currentPrice: prototype.querySelector("[data-stock-current-price]"),
    startPrice: prototype.querySelector("[data-stock-start-price]"),
    endPrice: prototype.querySelector("[data-stock-end-price]"),
    percentChange: prototype.querySelector("[data-stock-percent-change]"),
    highPrice: prototype.querySelector("[data-stock-high-price]"),
    lowPrice: prototype.querySelector("[data-stock-low-price]"),
    volatility: prototype.querySelector("[data-stock-volatility]"),
    projectionRange: prototype.querySelector("[data-projection-range]"),
    projectionChange: prototype.querySelector("[data-projection-change]"),
    projectionConfidence: prototype.querySelector("[data-projection-confidence]"),
    projectionAssumptions: prototype.querySelector("[data-projection-assumptions]")
  };

  function normalizeTicker(value) {
    return String(value || "")
      .trim()
      .toUpperCase()
      .replace(/[^A-Z0-9.-]/g, "")
      .slice(0, 10);
  }

  function seedFromTicker(ticker) {
    return ticker.split("").reduce((hash, char) => {
      return (hash * 31 + char.charCodeAt(0)) >>> 0;
    }, 2166136261);
  }

  function mulberry32(seed) {
    let value = seed >>> 0;
    return () => {
      value += 0x6d2b79f5;
      let next = value;
      next = Math.imul(next ^ (next >>> 15), next | 1);
      next ^= next + Math.imul(next ^ (next >>> 7), next | 61);
      return ((next ^ (next >>> 14)) >>> 0) / 4294967296;
    };
  }

  function profileForTicker(ticker) {
    if (stockProfiles[ticker]) {
      return stockProfiles[ticker];
    }

    const seed = seedFromTicker(ticker);
    const startPrice = 40 + (seed % 190);
    const annualMove = ((seed % 70) - 24) / 100;

    return {
      name: `${ticker} demo company`,
      startPrice,
      endPrice: Math.max(8, startPrice * (1 + annualMove)),
      wave: 0.04 + ((seed % 9) / 100),
      noise: 0.016 + ((seed % 13) / 1000),
      cycles: 1.7 + ((seed % 18) / 10)
    };
  }

  function getTradingDates(count) {
    const dates = [];
    const cursor = new Date();
    cursor.setHours(12, 0, 0, 0);

    while (dates.length < count) {
      const day = cursor.getDay();
      if (day !== 0 && day !== 6) {
        dates.push(new Date(cursor));
      }
      cursor.setDate(cursor.getDate() - 1);
    }

    return dates.reverse();
  }

  function buildDemoSeries(ticker) {
    const profile = profileForTicker(ticker);
    const dates = getTradingDates(TRADING_DAYS);
    const random = mulberry32(seedFromTicker(ticker));
    const phase = random() * Math.PI * 2;
    const ratio = profile.endPrice / profile.startPrice;

    const prices = dates.map((date, index) => {
      if (index === 0) return { date, close: profile.startPrice };
      if (index === dates.length - 1) return { date, close: profile.endPrice };

      const progress = index / (dates.length - 1);
      const trend = profile.startPrice * Math.pow(ratio, progress);
      const wave =
        Math.sin(progress * profile.cycles * Math.PI * 2 + phase) *
        profile.startPrice *
        profile.wave;
      const dailyNoise = (random() - 0.5) * 2 * profile.startPrice * profile.noise;
      const price = Math.max(2, trend + wave + dailyNoise);

      return {
        date,
        close: Number(price.toFixed(2))
      };
    });

    return {
      ticker,
      companyName: profile.name,
      source: stockProfiles[ticker] ? "Curated demo data" : "Generated demo fallback",
      prices
    };
  }

  const stockDataProvider = {
    async getHistoricalPrices(ticker) {
      return buildDemoSeries(ticker);
    }
  };

  function dailyReturns(prices) {
    const returns = [];
    for (let index = 1; index < prices.length; index += 1) {
      const previous = prices[index - 1].close;
      const current = prices[index].close;
      if (previous > 0) {
        returns.push((current - previous) / previous);
      }
    }
    return returns;
  }

  function standardDeviation(values) {
    if (values.length < 2) return 0;
    const mean = values.reduce((sum, value) => sum + value, 0) / values.length;
    const variance =
      values.reduce((sum, value) => sum + Math.pow(value - mean, 2), 0) /
      (values.length - 1);
    return Math.sqrt(variance);
  }

  function analyzeSeries(prices) {
    const first = prices[0].close;
    const last = prices[prices.length - 1].close;
    const values = prices.map((point) => point.close);
    const returns = dailyReturns(prices);
    const volatility = standardDeviation(returns) * Math.sqrt(252);

    return {
      startPrice: first,
      endPrice: last,
      currentPrice: last,
      percentChange: ((last - first) / first) * 100,
      highPrice: Math.max(...values),
      lowPrice: Math.min(...values),
      volatility
    };
  }

  function linearRegression(values) {
    const count = values.length;
    const sumX = values.reduce((sum, _value, index) => sum + index, 0);
    const sumY = values.reduce((sum, value) => sum + value, 0);
    const sumXY = values.reduce((sum, value, index) => sum + index * value, 0);
    const sumXX = values.reduce((sum, _value, index) => sum + index * index, 0);
    const denominator = count * sumXX - sumX * sumX;
    const slope = denominator === 0 ? 0 : (count * sumXY - sumX * sumY) / denominator;
    const intercept = (sumY - slope * sumX) / count;

    return { slope, intercept };
  }

  function buildProjection(prices, metrics) {
    const closes = prices.map((point) => point.close);
    const regression = linearRegression(closes);
    const lastIndex = closes.length - 1;
    const lastPrice = metrics.endPrice;
    const dailyStdDev = metrics.volatility / Math.sqrt(252);
    const projection = [];

    for (let day = 1; day <= PROJECTION_DAYS; day += 1) {
      const midpoint = Math.max(1, regression.intercept + regression.slope * (lastIndex + day));
      const uncertainty = lastPrice * dailyStdDev * Math.sqrt(day) * 0.72;
      projection.push({
        day,
        midpoint: Number(midpoint.toFixed(2)),
        lower: Number(Math.max(1, midpoint - uncertainty).toFixed(2)),
        upper: Number((midpoint + uncertainty).toFixed(2))
      });
    }

    const finalPoint = projection[projection.length - 1];
    return {
      points: projection,
      range: [finalPoint.lower, finalPoint.upper],
      midpoint: finalPoint.midpoint,
      percentChange: ((finalPoint.midpoint - lastPrice) / lastPrice) * 100,
      confidence:
        "Low confidence. This linear scenario only extends the historical trend and volatility; it does not include earnings, news, rates, liquidity, or market shocks.",
      assumptions: [
        "Uses one year of demo historical prices in this prototype.",
        "Fits a basic linear trend across the historical closing prices.",
        "Projects 63 trading days, roughly three market months.",
        "Builds the range from historical daily return volatility.",
        "Does not use fundamentals, analyst estimates, news, macroeconomic signals, or portfolio context."
      ]
    };
  }

  function formatCurrency(value) {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
      maximumFractionDigits: 2
    }).format(value);
  }

  function formatPercent(value) {
    const sign = value > 0 ? "+" : "";
    return `${sign}${value.toFixed(1)}%`;
  }

  function setText(element, value) {
    if (element) element.textContent = value;
  }

  function formatDate(date) {
    return new Intl.DateTimeFormat("en-US", {
      month: "short",
      day: "numeric"
    }).format(date);
  }

  function svgElement(name, attributes = {}) {
    const element = document.createElementNS(SVG_NS, name);
    Object.entries(attributes).forEach(([key, value]) => {
      element.setAttribute(key, value);
    });
    return element;
  }

  function pathFromPoints(points) {
    return points
      .map((point, index) => `${index === 0 ? "M" : "L"}${point.x.toFixed(2)} ${point.y.toFixed(2)}`)
      .join(" ");
  }

  function clearChart() {
    while (chart.firstChild) {
      chart.removeChild(chart.firstChild);
    }

    chart.append(
      svgElement("title", { id: "stockChartTitle" }),
      svgElement("desc", { id: "stockChartDescription" })
    );
    chart.querySelector("title").textContent = "Stock performance chart";
    chart.querySelector("desc").textContent =
      "A responsive line chart showing historical demo prices and a modeled projection.";
  }

  function renderChart(stockData, projection) {
    clearChart();

    const width = 720;
    const height = 340;
    const padding = { top: 26, right: 28, bottom: 42, left: 58 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    const history = stockData.prices;
    const totalSteps = history.length + projection.points.length - 1;
    const projectedValues = projection.points.flatMap((point) => [point.lower, point.upper]);
    const allValues = [...history.map((point) => point.close), ...projectedValues];
    const min = Math.min(...allValues);
    const max = Math.max(...allValues);
    const spread = Math.max(1, max - min);
    const yMin = Math.max(0, min - spread * 0.1);
    const yMax = max + spread * 0.1;

    const xForIndex = (index) => padding.left + (index / totalSteps) * chartWidth;
    const yForValue = (value) =>
      padding.top + ((yMax - value) / Math.max(1, yMax - yMin)) * chartHeight;

    const grid = svgElement("g", { "aria-hidden": "true" });
    for (let index = 0; index < 5; index += 1) {
      const ratio = index / 4;
      const y = padding.top + ratio * chartHeight;
      const value = yMax - ratio * (yMax - yMin);

      grid.append(
        svgElement("line", {
          class: "stock-axis",
          x1: padding.left,
          x2: width - padding.right,
          y1: y,
          y2: y
        }),
        svgElement("text", {
          class: "stock-axis-label",
          x: 12,
          y: y + 4
        })
      );
      grid.lastChild.textContent = formatCurrency(value).replace(".00", "");
    }
    chart.append(grid);

    const historyPoints = history.map((point, index) => ({
      x: xForIndex(index),
      y: yForValue(point.close)
    }));

    const projectionMidpoints = [
      historyPoints[historyPoints.length - 1],
      ...projection.points.map((point, index) => ({
        x: xForIndex(history.length + index),
        y: yForValue(point.midpoint)
      }))
    ];

    const upperBand = [
      historyPoints[historyPoints.length - 1],
      ...projection.points.map((point, index) => ({
        x: xForIndex(history.length + index),
        y: yForValue(point.upper)
      }))
    ];
    const lowerBand = [
      historyPoints[historyPoints.length - 1],
      ...projection.points.map((point, index) => ({
        x: xForIndex(history.length + index),
        y: yForValue(point.lower)
      }))
    ].reverse();

    chart.append(
      svgElement("path", {
        class: "stock-range-band",
        d: `${pathFromPoints(upperBand)} ${pathFromPoints(lowerBand).replace(/^M/, "L")} Z`
      }),
      svgElement("path", {
        class: "stock-history-line",
        d: pathFromPoints(historyPoints)
      }),
      svgElement("path", {
        class: "stock-projection-line",
        d: pathFromPoints(projectionMidpoints)
      })
    );

    const labels = [
      {
        text: formatDate(history[0].date),
        x: padding.left,
        anchor: "start"
      },
      {
        text: formatDate(history[history.length - 1].date),
        x: xForIndex(history.length - 1),
        anchor: "middle"
      },
      {
        text: "3-mo scenario",
        x: width - padding.right,
        anchor: "end"
      }
    ];

    labels.forEach((label) => {
      const text = svgElement("text", {
        class: "stock-chart-label",
        x: label.x,
        y: height - 12,
        "text-anchor": label.anchor
      });
      text.textContent = label.text;
      chart.append(text);
    });
  }

  function renderProjection(projection) {
    setText(
      fields.projectionRange,
      `${formatCurrency(projection.range[0])} to ${formatCurrency(projection.range[1])}`
    );
    setText(fields.projectionChange, formatPercent(projection.percentChange));
    setText(fields.projectionConfidence, projection.confidence);

    if (fields.projectionAssumptions) {
      fields.projectionAssumptions.textContent = "";
      projection.assumptions.forEach((assumption) => {
        const item = document.createElement("li");
        item.textContent = assumption;
        fields.projectionAssumptions.append(item);
      });
    }
  }

  function renderMetrics(stockData, metrics, projection) {
    setText(fields.source, `${stockData.source} · ${stockData.ticker}`);
    setText(fields.dataNote, "Demo data fallback · API-ready provider");
    setText(fields.name, `${stockData.companyName} (${stockData.ticker})`);
    setText(fields.currentPrice, formatCurrency(metrics.currentPrice));
    setText(fields.startPrice, formatCurrency(metrics.startPrice));
    setText(fields.endPrice, formatCurrency(metrics.endPrice));
    setText(fields.percentChange, formatPercent(metrics.percentChange));
    setText(fields.highPrice, formatCurrency(metrics.highPrice));
    setText(fields.lowPrice, formatCurrency(metrics.lowPrice));
    setText(fields.volatility, formatPercent(metrics.volatility * 100).replace("+", ""));

    renderProjection(projection);
    renderChart(stockData, projection);
  }

  async function analyzeTicker(rawTicker) {
    const ticker = normalizeTicker(rawTicker);

    if (!ticker) {
      results.hidden = true;
      setText(message, "Enter a ticker symbol to run the demo analysis.");
      input.focus();
      return;
    }

    setText(message, `Analyzing ${ticker} with structured demo data...`);

    try {
      const stockData = await stockDataProvider.getHistoricalPrices(ticker);
      const metrics = analyzeSeries(stockData.prices);
      const projection = buildProjection(stockData.prices, metrics);

      renderMetrics(stockData, metrics, projection);
      results.hidden = false;
      setText(
        message,
        `${ticker} loaded with demo data. Treat the projection as an uncertain scenario, not advice.`
      );
    } catch (error) {
      console.error(error);
      results.hidden = true;
      setText(message, "The demo analysis could not be completed. Try another ticker symbol.");
    }
  }

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const ticker = normalizeTicker(input.value);
    input.value = ticker;
    analyzeTicker(ticker);
  });

  analyzeTicker(input.value || "AAPL");
})();
