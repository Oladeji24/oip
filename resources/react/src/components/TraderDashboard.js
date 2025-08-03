import React, { useEffect, useState } from 'react';
import { Card, Button, Row, Col, Table, Dropdown, DropdownButton, Spinner, Modal } from 'react-bootstrap';
import { getWalletBalance, getWalletLogs, deposit, withdraw } from '../api/wallet';
import { requestWithdrawal } from '../api/withdrawal';
import { startBot, stopBot, switchBotMarket, getBotParams, updateBotParams, getBotAdvancedAnalytics } from '../api/bot';
import BotStatus from './BotStatus';
import { useToast } from '../utils/ToastContext';
import TradeDetailModal from './TradeDetailModal';
import { getUserAnalytics } from '../api/analytics';
import { Line, Bar, Pie } from 'react-chartjs-2';
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend } from 'chart.js';
import { MAJOR_PAIRS } from '../utils/botLogic';
import { useAuth } from '../context/AuthContext';
import { Navigate } from 'react-router-dom';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend);

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { hasError: false };
  }
  static getDerivedStateFromError(error) {
    return { hasError: true };
  }
  componentDidCatch(error, errorInfo) {
    // Log error to monitoring service if needed
  }
  render() {
    if (this.state.hasError) {
      return <div className="alert alert-danger">Something went wrong. Please refresh the page.</div>;
    }
    return this.props.children;
  }
}

const TraderDashboard = () => {
  const { user, loading: authLoading } = useAuth();
  const [balance, setBalance] = useState({ ngn: 0, usd: 0 });
  const [logs, setLogs] = useState([]);
  const [analytics, setAnalytics] = useState(null);
  const [advancedAnalytics, setAdvancedAnalytics] = useState(null);
  const [tradeHistory, setTradeHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [botStatus, setBotStatus] = useState('stopped');
  const [botMarket, setBotMarket] = useState('crypto'); // Default to crypto
  const [botSymbol, setBotSymbol] = useState('BTC-USDT'); // Default to BTC-USDT
  const [depositing, setDepositing] = useState(false);
  const [withdrawing, setWithdrawing] = useState(false);
  const [showWithdrawConfirm, setShowWithdrawConfirm] = useState(false);
  const [showBotConfirm, setShowBotConfirm] = useState(false);
  const [pendingBotAction, setPendingBotAction] = useState(null);
  const [selectedTrade, setSelectedTrade] = useState(null);
  const [showTradeModal, setShowTradeModal] = useState(false);
  const [showBotSettings, setShowBotSettings] = useState(false);
  const [botParams, setBotParams] = useState({
    strategy: 'ema-rsi',
    emaFastPeriod: 7,
    emaSlowPeriod: 14,
    rsiPeriod: 14,
    macdFast: 12,
    macdSlow: 26,
    macdSignal: 9,
    riskLevel: 1
  });
  const { showToast } = useToast();

  useEffect(() => {
    async function fetchData() {
      setLoading(true);
      try {
        const balRes = await getWalletBalance();
        if (balRes.data && balRes.data.balance) setBalance(balRes.data.balance);
        const logsRes = await getWalletLogs();
        if (logsRes.data && logsRes.data.logs) setLogs(logsRes.data.logs);
        // Fetch analytics
        const analyticsRes = await getUserAnalytics();
        if (analyticsRes.data) setAnalytics(analyticsRes.data);
        // Fetch bot params
        const paramsRes = await getBotParams();
        if (paramsRes.data) {
          setBotParams({
            strategy: paramsRes.data.strategy ?? 'ema-rsi',
            emaFastPeriod: paramsRes.data.emaFast ?? 7,
            emaSlowPeriod: paramsRes.data.emaSlow ?? 14,
            rsiPeriod: paramsRes.data.rsiPeriod ?? 14,
            macdFast: paramsRes.data.macdFast ?? 12,
            macdSlow: paramsRes.data.macdSlow ?? 26,
            macdSignal: paramsRes.data.macdSignal ?? 9,
            riskLevel: paramsRes.data.riskLevel ?? 1
          });
        }
        // Fetch advanced analytics
        const advRes = await getBotAdvancedAnalytics();
        if (advRes.data) {
          setAdvancedAnalytics(advRes.data);
          // Fetch trade history for charts
          // (Assume backend returns trade list in advanced analytics for now)
          if (advRes.data && advRes.data.trades) setTradeHistory(advRes.data.trades);
        }
      } catch (e) {}
      setLoading(false);
    }
    fetchData();
  }, []);

  const handleDeposit = async () => {
    setDepositing(true);
    try {
      await deposit(10000, 'NGN');
      showToast('Deposit initiated!', 'success');
    } catch (e) {
      showToast('Deposit failed.', 'danger');
    }
    setDepositing(false);
  };
  const handleWithdraw = async () => {
    setWithdrawing(true);
    try {
      await requestWithdrawal('user@example.com', 5000, 'NGN');
      showToast('Withdrawal requested! Check your email for OTP.', 'success');
    } catch (e) {
      showToast('Withdrawal failed.', 'danger');
    }
    setWithdrawing(false);
    setShowWithdrawConfirm(false);
  };
  const handleBotAction = async (action) => {
    if (action === 'stop') {
      setPendingBotAction(action);
      setShowBotConfirm(true);
      return;
    }
    await doBotAction(action);
  };
  const doBotAction = async (action) => {
    try {
      if (action === 'crypto' || action === 'forex') {
        await startBot(botMarket, botSymbol);
        setBotMarket(botMarket);
        setBotStatus('running');
        showToast(`${botMarket === 'crypto' ? 'Crypto' : 'Forex'} bot started on ${botSymbol}!`, 'success');
      } else if (action === 'stop') {
        await stopBot();
        setBotStatus('stopped');
        showToast('Bot stopped.', 'success');
      }
    } catch (e) {
      showToast('Bot action failed.', 'danger');
    }
    setShowBotConfirm(false);
  };
  const handleTradeClick = (trade) => {
    setSelectedTrade(trade);
    setShowTradeModal(true);
  };
  const handleBotParamChange = (e) => {
    const { name, value } = e.target;
    setBotParams((prev) => ({ ...prev, [name]: Number(value) }));
    // Add support for triple EMA params
    if (name === 'strategy' && value === 'triple-ema') {
      setBotParams((prev) => ({
        ...prev,
        tripleFast: prev.tripleFast || 5,
        tripleMid: prev.tripleMid || 15,
        tripleSlow: prev.tripleSlow || 30
      }));
    }
  };
  const handleSaveBotParams = async () => {
    setShowBotSettings(false);
    try {
      await updateBotParams({
        strategy: botParams.strategy,
        emaFast: botParams.emaFastPeriod,
        emaSlow: botParams.emaSlowPeriod,
        rsiPeriod: botParams.rsiPeriod,
        macdFast: botParams.macdFast,
        macdSlow: botParams.macdSlow,
        macdSignal: botParams.macdSignal,
        riskLevel: botParams.riskLevel,
        // Add triple EMA params if strategy is triple-ema
        ...(botParams.strategy === 'triple-ema' ? {
          tripleFast: botParams.tripleFast,
          tripleMid: botParams.tripleMid,
          tripleSlow: botParams.tripleSlow
        } : {})
      });
      showToast('Bot parameters updated!', 'success');
    } catch (e) {
      showToast('Failed to update bot parameters.', 'danger');
    }
  };

  // Prepare chart data for P&L over trades
  const pnlChartData = analytics && analytics.trades ? {
    labels: analytics.trades.map((t, i) => `#${i + 1}`),
    datasets: [
      {
        label: 'P&L',
        data: analytics.trades.map(t => t.profit ?? 0),
        borderColor: 'rgba(75,192,192,1)',
        backgroundColor: 'rgba(75,192,192,0.2)',
        tension: 0.2,
      },
    ],
  } : null;

  // Prepare chart data for volume over time
  const volumeChartData = analytics && analytics.volumeSeries ? {
    labels: analytics.volumeSeries.map((v) => v.time),
    datasets: [
      {
        label: 'Volume',
        data: analytics.volumeSeries.map(v => v.volume),
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
      },
    ],
  } : null;

  // Prepare win/loss pie chart data
  const winLossChartData = analytics ? {
    labels: ['Win', 'Loss', 'Even'],
    datasets: [
      {
        label: 'Win/Loss',
        data: [analytics.win, analytics.loss, analytics.total - analytics.win - analytics.loss],
        backgroundColor: [
          'rgba(75, 192, 192, 0.7)',
          'rgba(255, 99, 132, 0.7)',
          'rgba(201, 203, 207, 0.7)'
        ],
      },
    ],
  } : null;

  // Prepare equity curve and profit distribution data from tradeHistory
  const equityCurveData = tradeHistory && tradeHistory.length ? {
    labels: tradeHistory.map((t, i) => `#${i + 1}`),
    datasets: [
      {
        label: 'Equity',
        data: tradeHistory.reduce((acc, t, i) => {
          acc.push((acc[i - 1] || 0) + (t.profit ?? 0));
          return acc;
        }, []),
        borderColor: 'rgba(153,102,255,1)',
        backgroundColor: 'rgba(153,102,255,0.2)',
        tension: 0.2,
      },
    ],
  } : null;
  const profitDistData = tradeHistory && tradeHistory.length ? {
    labels: tradeHistory.map((t, i) => `#${i + 1}`),
    datasets: [
      {
        label: 'Trade Profit',
        data: tradeHistory.map(t => t.profit ?? 0),
        backgroundColor: 'rgba(255, 206, 86, 0.5)',
      },
    ],
  } : null;

  if (authLoading) return <Spinner animation="border" className="d-block mx-auto mt-5" />;
  if (!user) return <Navigate to="/login" />;

  return (
    <ErrorBoundary>
      <Card className="mb-4">
        <Card.Header as="h2">Trader Dashboard <BotStatus status={botStatus} market={botMarket} /></Card.Header>
        <Card.Body>
          {analytics && (
            <Row className="mb-3">
              <Col>
                <h5>Trade Analytics</h5>
                <ul style={{ marginBottom: 0 }}>
                  <li><strong>P&amp;L:</strong> {analytics.pnl}</li>
                  <li><strong>Volume:</strong> {analytics.volume}</li>
                  <li><strong>Win:</strong> {analytics.win}</li>
                  <li><strong>Loss:</strong> {analytics.loss}</li>
                  <li><strong>Total Trades:</strong> {analytics.total}</li>
                </ul>
              </Col>
            </Row>
          )}
          {advancedAnalytics && (
            <Row className="mb-3">
              <Col>
                <h5>Advanced Bot Analytics</h5>
                <ul style={{ marginBottom: 0 }}>
                  <li><strong>Max Drawdown:</strong> {advancedAnalytics.maxDrawdown}</li>
                  <li><strong>Best Trade:</strong> {advancedAnalytics.bestTrade}</li>
                  <li><strong>Worst Trade:</strong> {advancedAnalytics.worstTrade}</li>
                  <li><strong>Max Win Streak:</strong> {advancedAnalytics.maxWinStreak}</li>
                  <li><strong>Avg Hold Time:</strong> {advancedAnalytics.avgHoldTime}</li>
                  <li><strong>Sharpe Ratio:</strong> {advancedAnalytics.sharpeRatio}</li>
                </ul>
              </Col>
            </Row>
          )}
          {equityCurveData && (
            <Card className="mb-3">
              <Card.Header>Equity Curve</Card.Header>
              <Card.Body>
                <Line data={equityCurveData} options={{ responsive: true, plugins: { legend: { display: false } } }} height={120} />
              </Card.Body>
            </Card>
          )}
          {profitDistData && (
            <Card className="mb-3">
              <Card.Header>Trade Profit Distribution</Card.Header>
              <Card.Body>
                <Bar data={profitDistData} options={{ responsive: true, plugins: { legend: { display: false } } }} height={120} />
              </Card.Body>
            </Card>
          )}
          {loading ? <Spinner animation="border" /> : (
            <>
              <Row className="mb-3">
                <Col>
                  <h5>Wallet Balance</h5>
                  <p>NGN: ₦{balance.ngn.toLocaleString()}</p>
                  <p>USD: ${balance.usd.toLocaleString()}</p>
                </Col>
                <Col>
                  <Button variant="success" className="me-2" onClick={handleDeposit} disabled={depositing}>
                    {depositing ? <Spinner size="sm" animation="border" /> : 'Deposit'}
                  </Button>
                  <Button variant="danger" onClick={() => setShowWithdrawConfirm(true)} disabled={withdrawing}>
                    {withdrawing ? <Spinner size="sm" animation="border" /> : 'Withdraw'}
                  </Button>
                </Col>
              </Row>
              <Row className="mb-3">
                <Col>
                  <DropdownButton title={botStatus === 'running' ? `Bot: ${botMarket}` : 'Start Bot'}>
                    <Dropdown.Item onClick={() => handleBotAction('crypto')}>Start Crypto Trading</Dropdown.Item>
                    <Dropdown.Item onClick={() => handleBotAction('forex')}>Start Forex Trading</Dropdown.Item>
                    <Dropdown.Item onClick={() => handleBotAction('stop')}>Stop Trading</Dropdown.Item>
                  </DropdownButton>
                  <Button variant="outline-primary" className="ms-2" onClick={() => setShowBotSettings(true)}>
                    Bot Settings
                  </Button>
                </Col>
              </Row>
              <Row>
                <Col>
                  <h5>Transaction Logs</h5>
                  <Table striped bordered hover>
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Currency</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {logs.length === 0 ? (
                        <tr><td colSpan={4}>No transactions yet.</td></tr>
                      ) : logs.map((log, idx) => (
                        <tr key={idx} style={{ cursor: 'pointer' }} onClick={() => handleTradeClick(log)}>
                          <td>{log.type}</td>
                          <td>{log.amount}</td>
                          <td>{log.currency}</td>
                          <td>{log.status}</td>
                        </tr>
                      ))}
                    </tbody>
                  </Table>
                </Col>
              </Row>
              {analytics && pnlChartData && (
                <Card className="mb-3">
                  <Card.Header>P&L Over Trades</Card.Header>
                  <Card.Body>
                    <Line data={pnlChartData} options={{ responsive: true, plugins: { legend: { display: false } } }} height={120} />
                  </Card.Body>
                </Card>
              )}
              {analytics && volumeChartData && (
                <Card className="mb-3">
                  <Card.Header>Trade Volume Over Time</Card.Header>
                  <Card.Body>
                    <Bar data={volumeChartData} options={{ responsive: true, plugins: { legend: { display: false } } }} height={120} />
                  </Card.Body>
                </Card>
              )}
              {analytics && winLossChartData && (
                <Card className="mb-3">
                  <Card.Header>Win/Loss Ratio</Card.Header>
                  <Card.Body>
                    <Pie data={winLossChartData} options={{ responsive: true, plugins: { legend: { position: 'bottom' } } }} height={120} />
                  </Card.Body>
                </Card>
              )}
            </>
          )}
        </Card.Body>
      </Card>
      {/* Withdraw Confirmation Modal */}
      <Modal show={showWithdrawConfirm} onHide={() => setShowWithdrawConfirm(false)}>
        <Modal.Header closeButton><Modal.Title>Confirm Withdrawal</Modal.Title></Modal.Header>
        <Modal.Body>
          <p>Are you sure you want to withdraw ₦{balance.ngn.toLocaleString()}?</p>
          <p>A withdrawal fee of ₦100 will apply.</p>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowWithdrawConfirm(false)}>Cancel</Button>
          <Button variant="primary" onClick={handleWithdraw} disabled={withdrawing}>
            {withdrawing ? <Spinner size="sm" animation="border" /> : 'Confirm Withdrawal'}
          </Button>
        </Modal.Footer>
      </Modal>
      {/* Bot Stop Confirmation Modal */}
      <Modal show={showBotConfirm} onHide={() => setShowBotConfirm(false)}>
        <Modal.Header closeButton><Modal.Title>Stop Bot</Modal.Title></Modal.Header>
        <Modal.Body>Are you sure you want to stop the trading bot?</Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowBotConfirm(false)}>Cancel</Button>
          <Button variant="danger" onClick={() => doBotAction(pendingBotAction)}>Stop Bot</Button>
        </Modal.Footer>
      </Modal>
      {/* Trade Detail Modal */}
      <TradeDetailModal show={showTradeModal} onHide={() => setShowTradeModal(false)} trade={selectedTrade} />
      {/* Bot Settings Modal */}
      <Modal show={showBotSettings} onHide={() => setShowBotSettings(false)}>
        <Modal.Header closeButton><Modal.Title>Bot Settings</Modal.Title></Modal.Header>
        <Modal.Body>
          <form>
            <div className="mb-3">
              <label className="form-label">Strategy
                <span title="Select the algorithm your bot will use for trade signals. Click info for details." style={{cursor:'pointer',marginLeft:6}}>
                  <i className="bi bi-info-circle" data-bs-toggle="tooltip" data-bs-placement="right" title={
                    `\
EMA/RSI: Combines Exponential Moving Averages and Relative Strength Index. Buys when short EMA > long EMA and RSI < 70. Sells when short EMA < long EMA and RSI > 30.\n\nMACD: Uses the difference between two EMAs and a signal line. Buys on MACD cross above signal, sells on cross below.\n\nVolume: Looks for volume spikes with price movement. Buys on high volume and price up, sells on high volume and price down.\n\nTriple EMA: Uses three EMAs (fast, mid, slow) for trend confirmation. Buys when fast > mid > slow, sells when fast < mid < slow.\n\nChoose a strategy that matches your trading style.\n`
                  }/>
                </span>
              </label>
              <select className="form-select" name="strategy" value={botParams.strategy} onChange={handleBotParamChange} required aria-describedby="strategyHelp">
                <option value="">-- Select Strategy --</option>
                <option value="ema-rsi">EMA/RSI (Momentum + Overbought/Oversold)</option>
                <option value="macd">MACD (Trend Crossovers)</option>
                <option value="volume">Volume (Volume Spikes)</option>
                <option value="triple-ema">Triple EMA (Trend Confirmation)</option>
                <option value="hybrid">Hybrid (MACD + RSI + Volume)</option>
              </select>
              <div className="form-text text-secondary" id="strategyHelp">
                {botParams.strategy === '' && <span className="text-danger">Please select a strategy to get started.</span>}
                {botParams.strategy === 'ema-rsi' && (<><b>EMA/RSI:</b> Combines Exponential Moving Averages and RSI. Buys when fast EMA &gt; slow EMA and RSI &lt; 70. Sells when fast EMA &lt; slow EMA and RSI &gt; 30.</>)}
                {botParams.strategy === 'macd' && (<><b>MACD:</b> Uses the difference between two EMAs and a signal line. Buys on MACD cross above signal, sells on cross below.</>)}
                {botParams.strategy === 'volume' && (<><b>Volume:</b> Looks for volume spikes with price movement. Buys on high volume and price up, sells on high volume and price down.</>)}
                {botParams.strategy === 'triple-ema' && (<><b>Triple EMA:</b> Uses three EMAs (fast, mid, slow) for trend confirmation. Buys when fast &gt; mid &gt; slow, sells when fast &lt; mid &lt; slow.</>)}
                {botParams.strategy === 'hybrid' && (<><b>Hybrid:</b> Combines MACD, RSI, and Volume trends. Buys when MACD &gt; signal, RSI &lt; 60, and volume trend is buy. Sells when MACD &lt; signal, RSI &gt; 40, and volume trend is sell.</>)}
              </div>
              {botParams.strategy && (
                <div className="alert alert-info mt-2 p-2">
                  <b>Tip:</b> You can change your strategy at any time. Start with EMA/RSI for a balanced approach, or try Triple EMA for trend confirmation. Adjust parameters to match your risk tolerance and market conditions.
                </div>
              )}
            </div>
            {/* Show only relevant parameter fields for the selected strategy */}
            {botParams.strategy === 'ema-rsi' && (
              <>
                <div className="mb-3">
                  <label className="form-label">EMA Fast Period</label>
                  <input type="number" className="form-control" name="emaFastPeriod" min="2" max="50" value={botParams.emaFastPeriod} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">EMA Slow Period</label>
                  <input type="number" className="form-control" name="emaSlowPeriod" min="2" max="100" value={botParams.emaSlowPeriod} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">RSI Period</label>
                  <input type="number" className="form-control" name="rsiPeriod" min="2" max="50" value={botParams.rsiPeriod} onChange={handleBotParamChange} />
                </div>
              </>
            )}
            {botParams.strategy === 'macd' && (
              <>
                <div className="mb-3">
                  <label className="form-label">MACD Fast Period</label>
                  <input type="number" className="form-control" name="macdFast" min="2" max="50" value={botParams.macdFast} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">MACD Slow Period</label>
                  <input type="number" className="form-control" name="macdSlow" min="2" max="50" value={botParams.macdSlow} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">MACD Signal Period</label>
                  <input type="number" className="form-control" name="macdSignal" min="1" max="50" value={botParams.macdSignal} onChange={handleBotParamChange} />
                </div>
              </>
            )}
            {botParams.strategy === 'triple-ema' && (
              <>
                <div className="mb-3">
                  <label className="form-label">Triple EMA Fast Period
                    <span title="Short-term EMA. Lower values = more sensitive. Must be &lt; Mid and Slow." style={{marginLeft:4,cursor:'pointer'}}>
                      <i className="bi bi-info-circle" />
                    </span>
                  </label>
                  <input type="number" className="form-control" name="tripleFast" min="2" max={botParams.tripleMid ? botParams.tripleMid-1 : 49} value={botParams.tripleFast || 5} onChange={handleBotParamChange} required aria-describedby="tripleFastHelp" />
                  {botParams.tripleFast >= botParams.tripleMid && <div className="text-danger small">Fast period must be less than Mid period.</div>}
                  <div className="form-text" id="tripleFastHelp">Recommended: 5-10 for most markets.</div>
                </div>
                <div className="mb-3">
                  <label className="form-label">Triple EMA Mid Period
                    <span title="Medium-term EMA. Must be between Fast and Slow." style={{marginLeft:4,cursor:'pointer'}}>
                      <i className="bi bi-info-circle" />
                    </span>
                  </label>
                  <input type="number" className="form-control" name="tripleMid" min={botParams.tripleFast ? botParams.tripleFast+1 : 3} max={botParams.tripleSlow ? botParams.tripleSlow-1 : 99} value={botParams.tripleMid || 15} onChange={handleBotParamChange} required aria-describedby="tripleMidHelp" />
                  {(botParams.tripleMid <= botParams.tripleFast || botParams.tripleMid >= botParams.tripleSlow) && <div className="text-danger small">Mid period must be between Fast and Slow.</div>}
                  <div className="form-text" id="tripleMidHelp">Recommended: 10-20 for most markets.</div>
                </div>
                <div className="mb-3">
                  <label className="form-label">Triple EMA Slow Period
                    <span title="Long-term EMA. Higher values = smoother trend. Must be &gt; Fast and Mid." style={{marginLeft:4,cursor:'pointer'}}>
                      <i className="bi bi-info-circle" />
                    </span>
                  </label>
                  <input type="number" className="form-control" name="tripleSlow" min={botParams.tripleMid ? botParams.tripleMid+1 : 16} max="200" value={botParams.tripleSlow || 30} onChange={handleBotParamChange} required aria-describedby="tripleSlowHelp" />
                  {botParams.tripleSlow <= botParams.tripleMid && <div className="text-danger small">Slow period must be greater than Mid period.</div>}
                  <div className="form-text" id="tripleSlowHelp">Recommended: 20-50 for most markets.</div>
                </div>
              </>
            )}
            {botParams.strategy === 'hybrid' && (
              <>
                <div className="mb-3">
                  <label className="form-label">MACD Fast Period</label>
                  <input type="number" className="form-control" name="macdFast" min="2" max="50" value={botParams.macdFast} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">MACD Slow Period</label>
                  <input type="number" className="form-control" name="macdSlow" min="2" max="50" value={botParams.macdSlow} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">MACD Signal Period</label>
                  <input type="number" className="form-control" name="macdSignal" min="1" max="50" value={botParams.macdSignal} onChange={handleBotParamChange} />
                </div>
                <div className="mb-3">
                  <label className="form-label">RSI Period</label>
                  <input type="number" className="form-control" name="rsiPeriod" min="2" max="50" value={botParams.rsiPeriod} onChange={handleBotParamChange} />
                </div>
                <div className="alert alert-info p-2">
                  <b>Hybrid Strategy:</b> Buys when MACD &gt; signal, RSI &lt; 60, and volume trend is buy. Sells when MACD &lt; signal, RSI &gt; 40, and volume trend is sell.
                </div>
              </>
            )}
            {/* Volume strategy does not require extra params */}
            <div className="mb-3">
              <label className="form-label">Risk Level (1-5)</label>
              <input type="number" className="form-control" name="riskLevel" min="1" max="5" value={botParams.riskLevel} onChange={handleBotParamChange} />
            </div>
            <div className="mb-3">
              <label className="form-label">Market</label>
              <select className="form-select" name="market" value={botMarket} onChange={e => {
                setBotMarket(e.target.value);
                setBotSymbol(MAJOR_PAIRS[e.target.value][0]);
              }} required>
                <option value="crypto">Crypto</option>
                <option value="forex">Forex</option>
              </select>
            </div>
            <div className="mb-3">
              <label className="form-label">Pair/Symbol</label>
              <select className="form-select" name="symbol" value={botSymbol} onChange={e => setBotSymbol(e.target.value)} required>
                {MAJOR_PAIRS[botMarket].map(pair => (
                  <option key={pair} value={pair}>{pair}</option>
                ))}
              </select>
            </div>
          </form>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={() => setShowBotSettings(false)}>Cancel</Button>
          <Button variant="primary" onClick={handleSaveBotParams}>Save</Button>
        </Modal.Footer>
      </Modal>
    </ErrorBoundary>
  );
};

export default TraderDashboard;
