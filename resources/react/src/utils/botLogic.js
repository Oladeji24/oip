// src/utils/botLogic.js
// Core trading bot logic (trend detection, trade management)
// Placeholder for EMA, MACD, RSI, Volume calculations

/**
 * Calculates Exponential Moving Average (EMA)
 * @param {Array<number>} prices
 * @param {number} period
 * @returns {Array<number>}
 */
export function calculateEMA(prices, period) {
  const k = 2 / (period + 1);
  let emaArray = [];
  let ema = prices[0];
  emaArray.push(ema);
  for (let i = 1; i < prices.length; i++) {
    ema = prices[i] * k + ema * (1 - k);
    emaArray.push(ema);
  }
  return emaArray;
}

/**
 * Calculates Relative Strength Index (RSI)
 * @param {Array<number>} prices
 * @param {number} period
 * @returns {Array<number>}
 */
export function calculateRSI(prices, period) {
  let rsiArray = [];
  let gains = 0,
    losses = 0;
  for (let i = 1; i <= period; i++) {
    const diff = prices[i] - prices[i - 1];
    if (diff >= 0) gains += diff;
    else losses -= diff;
  }
  gains /= period;
  losses /= period;
  let rs = gains / (losses || 1);
  rsiArray[period] = 100 - (100 / (1 + rs));
  for (let i = period + 1; i < prices.length; i++) {
    const diff = prices[i] - prices[i - 1];
    if (diff >= 0) {
      gains = (gains * (period - 1) + diff) / period;
      losses = (losses * (period - 1)) / period;
    } else {
      gains = (gains * (period - 1)) / period;
      losses = (losses * (period - 1) - diff) / period;
    }
    rs = gains / (losses || 1);
    rsiArray[i] = 100 - (100 / (1 + rs));
  }
  return rsiArray;
}

/**
 * Calculates MACD (Moving Average Convergence Divergence)
 * @param {Array<number>} prices
 * @param {number} fastPeriod
 * @param {number} slowPeriod
 * @param {number} signalPeriod
 * @returns {{macd: Array<number>, signal: Array<number>, histogram: Array<number>}}
 */
export function calculateMACD(prices, fastPeriod = 12, slowPeriod = 26, signalPeriod = 9) {
  const emaFast = calculateEMA(prices, fastPeriod);
  const emaSlow = calculateEMA(prices, slowPeriod);
  const macd = emaFast.map((val, i) => val - (emaSlow[i] ?? 0));
  const signal = calculateEMA(macd, signalPeriod);
  const histogram = macd.map((val, i) => val - (signal[i] ?? 0));
  return { macd, signal, histogram };
}

/**
 * Volume-based trend detection (simple)
 * @param {Array<{close: number, volume: number}>} priceData
 * @returns {string} 'buy' | 'sell' | 'hold'
 */
export function detectVolumeTrend(priceData) {
  if (priceData.length < 10) return 'hold';
  const recent = priceData.slice(-5);
  const avgVol = priceData.slice(-20).reduce((a, p) => a + (p.volume ?? 0), 0) / 20;
  const lastVol = recent[recent.length - 1].volume ?? 0;
  if (lastVol > 1.5 * avgVol && recent[recent.length - 1].close > recent[0].close) return 'buy';
  if (lastVol > 1.5 * avgVol && recent[recent.length - 1].close < recent[0].close) return 'sell';
  return 'hold';
}

/**
 * Calculate Profit Factor (gross profit / gross loss)
 * @param {Array<{profit: number}>} trades
 * @returns {number}
 */
export function calculateProfitFactor(trades) {
  if (!trades || trades.length === 0) return 0;
  let grossProfit = 0, grossLoss = 0;
  trades.forEach(t => {
    if ((t.profit ?? 0) > 0) grossProfit += t.profit;
    else grossLoss -= t.profit;
  });
  if (grossLoss === 0) return grossProfit > 0 ? Infinity : 0;
  return grossProfit / grossLoss;
}

/**
 * Calculate Maximum Drawdown (max peak-to-trough equity drop)
 * @param {Array<{profit: number}>} trades
 * @returns {number} max drawdown as negative percent (e.g. -0.25 for -25%)
 */
export function calculateMaxDrawdown(trades) {
  if (!trades || trades.length === 0) return 0;
  let equity = 0;
  let peak = 0;
  let maxDrawdown = 0;
  for (let i = 0; i < trades.length; i++) {
    equity += trades[i].profit ?? 0;
    if (equity > peak) peak = equity;
    const drawdown = (equity - peak) / (peak || 1);
    if (drawdown < maxDrawdown) maxDrawdown = drawdown;
  }
  return maxDrawdown;
}

/**
 * Calculates Triple EMA (fast, mid, slow)
 * @param {Array<number>} prices
 * @param {number} fastPeriod
 * @param {number} midPeriod
 * @param {number} slowPeriod
 * @returns {{emaFast: Array<number>, emaMid: Array<number>, emaSlow: Array<number>}}
 */
export function calculateTripleEMA(prices, fastPeriod = 5, midPeriod = 15, slowPeriod = 30) {
  const emaFast = calculateEMA(prices, fastPeriod);
  const emaMid = calculateEMA(prices, midPeriod);
  const emaSlow = calculateEMA(prices, slowPeriod);
  return { emaFast, emaMid, emaSlow };
}

// Refactored strategy map for easier extension
const strategyMap = {
  'macd': (closes, priceData, params) => {
    const { macdFast, macdSlow, macdSignal } = params;
    if (closes.length < Math.max(macdFast, macdSlow, macdSignal)) return 'hold';
    const { macd, signal } = calculateMACD(closes, macdFast, macdSlow, macdSignal);
    const last = closes.length - 1;
    if (macd[last] > signal[last]) return 'buy';
    if (macd[last] < signal[last]) return 'sell';
    return 'hold';
  },
  'volume': (closes, priceData, params) => detectVolumeTrend(priceData),
  'ema-rsi': (closes, priceData, params) => {
    const { emaFastPeriod, emaSlowPeriod, rsiPeriod } = params;
    if (closes.length < Math.max(emaFastPeriod, emaSlowPeriod, rsiPeriod)) return 'hold';
    const emaFast = calculateEMA(closes, emaFastPeriod);
    const emaSlow = calculateEMA(closes, emaSlowPeriod);
    const rsi = calculateRSI(closes, rsiPeriod);
    const last = closes.length - 1;
    if (emaFast[last] > emaSlow[last] && rsi[last] < 70) return 'buy';
    if (emaFast[last] < emaSlow[last] && rsi[last] > 30) return 'sell';
    return 'hold';
  },
  'triple-ema': (closes, priceData, params) => {
    const { tripleFast, tripleMid, tripleSlow } = params;
    if (closes.length < Math.max(tripleFast, tripleMid, tripleSlow)) return 'hold';
    const { emaFast, emaMid, emaSlow } = calculateTripleEMA(closes, tripleFast, tripleMid, tripleSlow);
    const last = closes.length - 1;
    // Buy: fast > mid > slow, Sell: fast < mid < slow
    if (emaFast[last] > emaMid[last] && emaMid[last] > emaSlow[last]) return 'buy';
    if (emaFast[last] < emaMid[last] && emaMid[last] < emaSlow[last]) return 'sell';
    return 'hold';
  },
  'hybrid': (closes, priceData, params) => {
    // Example: Buy if MACD > signal, RSI < 60, and volume trend is 'buy'
    const { macdFast, macdSlow, macdSignal, rsiPeriod } = params;
    if (closes.length < Math.max(macdFast, macdSlow, macdSignal, rsiPeriod)) return 'hold';
    const { macd, signal } = calculateMACD(closes, macdFast, macdSlow, macdSignal);
    const rsi = calculateRSI(closes, rsiPeriod);
    const last = closes.length - 1;
    const volTrend = detectVolumeTrend(priceData);
    if (macd[last] > signal[last] && rsi[last] < 60 && volTrend === 'buy') return 'buy';
    if (macd[last] < signal[last] && rsi[last] > 40 && volTrend === 'sell') return 'sell';
    return 'hold';
  }
  // Placeholder for custom/hybrid strategies
  // 'custom': (closes, priceData, params) => {
  //   // Implement custom logic here, e.g., combine MACD, RSI, volume, etc.
  //   // Example: Only buy if MACD > signal AND RSI < 60 AND volume spike
  //   const { macdFast, macdSlow, macdSignal, rsiPeriod } = params;
  //   if (closes.length < Math.max(macdFast, macdSlow, macdSignal, rsiPeriod)) return 'hold';
  //   const { macd, signal } = calculateMACD(closes, macdFast, macdSlow, macdSignal);
  //   const rsi = calculateRSI(closes, rsiPeriod);
  //   const last = closes.length - 1;
  //   const volTrend = detectVolumeTrend(priceData);
  //   if (macd[last] > signal[last] && rsi[last] < 60 && volTrend === 'buy') return 'buy';
  //   if (macd[last] < signal[last] && rsi[last] > 40 && volTrend === 'sell') return 'sell';
  //   return 'hold';
  // }
};

/**
 * Detects trading trend using selected strategy (EMA/RSI, MACD, Volume, Triple EMA, etc)
 * @param {Array} priceData - Array of {close: number, volume?: number}
 * @param {Object} params - { strategy, emaFastPeriod, emaSlowPeriod, rsiPeriod, macdFast, macdSlow, macdSignal, riskLevel, tripleFast, tripleMid, tripleSlow }
 * @returns {string} 'buy' | 'sell' | 'hold'
 */
export function detectTrend(priceData, params = {}) {
  const {
    strategy = 'ema-rsi',
    emaFastPeriod = 7,
    emaSlowPeriod = 14,
    rsiPeriod = 14,
    macdFast = 12,
    macdSlow = 26,
    macdSignal = 9,
    riskLevel = 1,
    tripleFast = 5,
    tripleMid = 15,
    tripleSlow = 30
  } = params;
  const closes = priceData.map((p) => p.close);

  // Strategy map for extensibility
  const strategyMap = {
    'ema-rsi': () => {
      if (closes.length < Math.max(emaFastPeriod, emaSlowPeriod, rsiPeriod)) return 'hold';
      const emaFast = calculateEMA(closes, emaFastPeriod);
      const emaSlow = calculateEMA(closes, emaSlowPeriod);
      const rsi = calculateRSI(closes, rsiPeriod);
      const last = closes.length - 1;
      if (emaFast[last] > emaSlow[last] && rsi[last] < 70) return 'buy';
      if (emaFast[last] < emaSlow[last] && rsi[last] > 30) return 'sell';
      return 'hold';
    },
    'macd': () => {
      if (closes.length < Math.max(macdFast, macdSlow, macdSignal)) return 'hold';
      const { macd, signal } = calculateMACD(closes, macdFast, macdSlow, macdSignal);
      const last = closes.length - 1;
      if (macd[last] > signal[last]) return 'buy';
      if (macd[last] < signal[last]) return 'sell';
      return 'hold';
    },
    'volume': () => detectVolumeTrend(priceData),
    'triple-ema': () => {
      if (closes.length < Math.max(tripleFast, tripleMid, tripleSlow)) return 'hold';
      const { emaFast, emaMid, emaSlow } = calculateTripleEMA(closes, tripleFast, tripleMid, tripleSlow);
      const last = closes.length - 1;
      // Buy: fast > mid > slow, Sell: fast < mid < slow
      if (emaFast[last] > emaMid[last] && emaMid[last] > emaSlow[last]) return 'buy';
      if (emaFast[last] < emaMid[last] && emaMid[last] < emaSlow[last]) return 'sell';
      return 'hold';
    },
    'hybrid': () => {
      // Example: Buy if MACD > signal, RSI < 60, and volume trend is 'buy'
      const { macdFast, macdSlow, macdSignal, rsiPeriod } = params;
      if (closes.length < Math.max(macdFast, macdSlow, macdSignal, rsiPeriod)) return 'hold';
      const { macd, signal } = calculateMACD(closes, macdFast, macdSlow, macdSignal);
      const rsi = calculateRSI(closes, rsiPeriod);
      const last = closes.length - 1;
      const volTrend = detectVolumeTrend(priceData);
      if (macd[last] > signal[last] && rsi[last] < 60 && volTrend === 'buy') return 'buy';
      if (macd[last] < signal[last] && rsi[last] > 40 && volTrend === 'sell') return 'sell';
      return 'hold';
    }
    // Add more strategies here as needed
  };

  return (strategyMap[strategy] || strategyMap['ema-rsi'])();
}

/**
 * Checks if target profit is reached
 * @param {number} entryPrice
 * @param {number} currentPrice
 * @param {number} targetPercent
 * @returns {boolean}
 */
export function isTargetProfit(entryPrice, currentPrice, targetPercent = 5) {
  return ((currentPrice - entryPrice) / entryPrice) * 100 >= targetPercent;
}

/**
 * Calculates dynamic position size based on balance and risk level
 * @param {number} balance
 * @param {number} riskLevel (1-5)
 * @param {number} stopLossPercent (e.g. 2 for 2%)
 * @returns {number} position size
 */
export function calculatePositionSize(balance, riskLevel = 1, stopLossPercent = 2) {
  // Risk 1-5% of balance per trade depending on riskLevel
  const riskPerc = Math.min(Math.max(riskLevel, 1), 5);
  const riskAmount = (balance * riskPerc) / 100;
  // Position size = risk amount / stop loss percent
  return riskAmount / (stopLossPercent / 100);
}

/**
 * Trailing stop logic
 * @param {number} entryPrice
 * @param {number} currentPrice
 * @param {number} trailingPercent
 * @param {number} highestSinceEntry
 * @returns {boolean} true if trailing stop hit
 */
export function isTrailingStopHit(entryPrice, currentPrice, trailingPercent, highestSinceEntry) {
  const trailStop = highestSinceEntry * (1 - trailingPercent / 100);
  return currentPrice <= trailStop && currentPrice > entryPrice;
}

/**
 * Calculate Sharpe Ratio for trade results
 * @param {Array<{profit: number}>} trades
 * @param {number} riskFreeRate
 * @returns {number}
 */
export function calculateSharpeRatio(trades, riskFreeRate = 0) {
  if (!trades || trades.length < 2) return 0;
  const returns = trades.map(t => t.profit ?? 0);
  const avg = returns.reduce((a, b) => a + b, 0) / returns.length;
  const std = Math.sqrt(returns.reduce((a, b) => a + Math.pow(b - avg, 2), 0) / (returns.length - 1));
  if (std === 0) return 0;
  return (avg - riskFreeRate) / std;
}

/**
 * Calculate Sortino Ratio for trade results
 * @param {Array<{profit: number}>} trades
 * @param {number} riskFreeRate
 * @returns {number}
 */
export function calculateSortinoRatio(trades, riskFreeRate = 0) {
  if (!trades || trades.length < 2) return 0;
  const returns = trades.map(t => t.profit ?? 0);
  const avg = returns.reduce((a, b) => a + b, 0) / returns.length;
  const downside = returns.filter(r => r < riskFreeRate);
  if (downside.length === 0) return 0;
  const downsideDev = Math.sqrt(downside.reduce((a, b) => a + Math.pow(b - riskFreeRate, 2), 0) / downside.length);
  if (downsideDev === 0) return 0;
  return (avg - riskFreeRate) / downsideDev;
}

/**
 * Calculate volatility (standard deviation of returns)
 * @param {Array<{profit: number}>} trades
 * @returns {number}
 */
export function calculateVolatility(trades) {
  if (!trades || trades.length < 2) return 0;
  const returns = trades.map(t => t.profit ?? 0);
  const avg = returns.reduce((a, b) => a + b, 0) / returns.length;
  const std = Math.sqrt(returns.reduce((a, b) => a + Math.pow(b - avg, 2), 0) / (returns.length - 1));
  return std;
}

/**
 * Calculate trade duration histogram (in minutes)
 * @param {Array<{opened_at: string, closed_at: string}>} trades
 * @param {number[]} bins - e.g. [5, 15, 30, 60, 120, 240, 1440]
 * @returns {Object} { bin: count }
 */
export function tradeDurationHistogram(trades, bins = [5, 15, 30, 60, 120, 240, 1440]) {
  const hist = {};
  bins.forEach(b => { hist[b] = 0; });
  trades.forEach(t => {
    if (!t.opened_at || !t.closed_at) return;
    const start = new Date(t.opened_at).getTime();
    const end = new Date(t.closed_at).getTime();
    const mins = Math.max(0, Math.round((end - start) / 60000));
    for (let i = 0; i < bins.length; i++) {
      if (mins <= bins[i]) {
        hist[bins[i]]++;
        break;
      }
    }
  });
  return hist;
}

// List of major pairs for crypto and forex
export const MAJOR_PAIRS = {
  crypto: [
    'BTC-USDT', 'ETH-USDT', 'BNB-USDT', 'SOL-USDT', 'ADA-USDT',
    'XRP-USDT', 'DOGE-USDT', 'AVAX-USDT', 'MATIC-USDT', 'DOT-USDT'
  ],
  forex: [
    'EURUSD', 'USDJPY', 'GBPUSD', 'USDCHF', 'AUDUSD',
    'USDCAD', 'NZDUSD', 'EURJPY', 'GBPJPY', 'EURGBP'
  ]
};

// Utility: Check if a symbol is a major pair
export function isMajorPair(market, symbol) {
  return MAJOR_PAIRS[market]?.includes(symbol);
}
