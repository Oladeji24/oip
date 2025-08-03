import React, { useEffect, useState } from 'react';
import { Card, Table, Spinner, Form, Button, Row, Col, Badge, OverlayTrigger, Tooltip, Alert, Toast } from 'react-bootstrap';
import { getKucoinSymbols, getKucoinTicker, getKucoinOrderBook, getKucoinTrades, placeKucoinOrder, getKucoinSymbol } from '../api/kucoin';
import { getDerivSymbols, getDerivAccount, placeDerivOrder } from '../api/deriv';
import KucoinSymbolCacheAdmin from '../components/KucoinSymbolCacheAdmin';

const MarketDashboard = () => {
  // KuCoin State
  const [kucoinSymbols, setKucoinSymbols] = useState([]);
  const [kucoinSymbol, setKucoinSymbol] = useState('BTC-USDT');
  const [kucoinTicker, setKucoinTicker] = useState(null);
  const [kucoinOrderBook, setKucoinOrderBook] = useState(null);
  const [kucoinTrades, setKucoinTrades] = useState([]);
  const [kucoinOrder, setKucoinOrder] = useState({ side: 'buy', type: 'market', size: '', price: '' });
  const [kucoinPlacing, setKucoinPlacing] = useState(false);
  const [kucoinOrderResult, setKucoinOrderResult] = useState(null);
  const [kucoinLoading, setKucoinLoading] = useState(true);
  const [kucoinToastShow, setKucoinToastShow] = useState(false);
  const [symbolInfo, setSymbolInfo] = useState(null);
  const [symbolInfoLoading, setSymbolInfoLoading] = useState(false);
  const [symbolInfoError, setSymbolInfoError] = useState(null);

  // Deriv State
  const [derivSymbols, setDerivSymbols] = useState([]);
  const [derivSymbol, setDerivSymbol] = useState('frxEURUSD');
  const [derivAccount, setDerivAccount] = useState(null);
  const [derivOrder, setDerivOrder] = useState({ side: 'buy', amount: '' });
  const [derivPlacing, setDerivPlacing] = useState(false);
  const [derivOrderResult, setDerivOrderResult] = useState(null);
  const [derivLoading, setDerivLoading] = useState(true);
  const [derivToastShow, setDerivToastShow] = useState(false);

  // KuCoin Effects
  useEffect(() => {
    async function fetchSymbols() {
      const res = await getKucoinSymbols();
      setKucoinSymbols(res.data.data || []);
    }
    fetchSymbols();
  }, []);
  useEffect(() => {
    async function fetchMarket() {
      setKucoinLoading(true);
      const [tick, book, trd] = await Promise.all([
        getKucoinTicker(kucoinSymbol),
        getKucoinOrderBook(kucoinSymbol),
        getKucoinTrades(kucoinSymbol)
      ]);
      setKucoinTicker(tick.data.data);
      setKucoinOrderBook(book.data.data);
      setKucoinTrades(trd.data.data || []);
      setKucoinLoading(false);
    }
    if (kucoinSymbol) fetchMarket();
  }, [kucoinSymbol]);

  // Fetch symbol info when kucoinSymbol changes
  useEffect(() => {
    async function fetchSymbolInfo() {
      setSymbolInfoLoading(true);
      setSymbolInfoError(null);
      try {
        const res = await getKucoinSymbol(kucoinSymbol);
        setSymbolInfo(res.data.data || null);
      } catch (err) {
        setSymbolInfoError(err.response?.data?.error || err.message);
        setSymbolInfo(null);
      }
      setSymbolInfoLoading(false);
    }
    if (kucoinSymbol) fetchSymbolInfo();
  }, [kucoinSymbol]);

  // Deriv Effects
  useEffect(() => {
    async function fetchSymbols() {
      const res = await getDerivSymbols();
      setDerivSymbols(res.data.data?.symbols || []);
    }
    async function fetchAccount() {
      const res = await getDerivAccount();
      setDerivAccount(res.data);
    }
    fetchSymbols();
    fetchAccount();
    setDerivLoading(false);
  }, []);

  // KuCoin Order Handlers
  const handleKucoinOrderChange = e => {
    setKucoinOrder({ ...kucoinOrder, [e.target.name]: e.target.value });
  };
  // KuCoin Order Validation
  const validateKucoinOrder = () => {
    if (!symbolInfo) return 'Symbol info not loaded.';
    const size = parseFloat(kucoinOrder.size);
    if (isNaN(size) || size < parseFloat(symbolInfo.baseMinSize)) {
      return `Order size must be at least ${symbolInfo.baseMinSize}`;
    }
    if (size > parseFloat(symbolInfo.baseMaxSize)) {
      return `Order size must not exceed ${symbolInfo.baseMaxSize}`;
    }
    if ((size % parseFloat(symbolInfo.baseIncrement)) !== 0) {
      return `Order size must be a multiple of ${symbolInfo.baseIncrement}`;
    }
    if (kucoinOrder.type === 'limit' || kucoinOrder.type === 'stop_limit') {
      const price = parseFloat(kucoinOrder.price);
      if (isNaN(price)) return 'Price is required for limit/stop-limit orders.';
      if ((price % parseFloat(symbolInfo.priceIncrement)) !== 0) {
        return `Price must be a multiple of ${symbolInfo.priceIncrement}`;
      }
    }
    if (kucoinOrder.type === 'stop_limit') {
      const stopPrice = parseFloat(kucoinOrder.stopPrice);
      if (isNaN(stopPrice)) return 'Stop price is required for stop-limit orders.';
      if ((stopPrice % parseFloat(symbolInfo.priceIncrement)) !== 0) {
        return `Stop price must be a multiple of ${symbolInfo.priceIncrement}`;
      }
      if (!kucoinOrder.stop) return 'Stop condition (entry/loss) is required.';
    }
    return null;
  };

  const handleKucoinOrderSubmit = async e => {
    e.preventDefault();
    const validationError = validateKucoinOrder();
    if (validationError) {
      setKucoinOrderResult({ error: validationError });
      setKucoinToastShow(true);
      return;
    }
    setKucoinPlacing(true);
    try {
      const res = await placeKucoinOrder({
        symbol: kucoinSymbol,
        ...kucoinOrder,
        size: parseFloat(kucoinOrder.size),
        price: kucoinOrder.type === 'limit' ? parseFloat(kucoinOrder.price) : undefined
      });
      setKucoinOrderResult(res.data);
      setKucoinToastShow(true);
    } catch (err) {
      setKucoinOrderResult({ error: err.message });
      setKucoinToastShow(true);
    }
    setKucoinPlacing(false);
  };

  // Deriv Order Handlers
  const handleDerivOrderChange = e => {
    setDerivOrder({ ...derivOrder, [e.target.name]: e.target.value });
  };
  const handleDerivOrderSubmit = async e => {
    e.preventDefault();
    setDerivPlacing(true);
    try {
      const res = await placeDerivOrder({
        symbol: derivSymbol,
        ...derivOrder,
        amount: parseFloat(derivOrder.amount)
      });
      setDerivOrderResult(res.data);
      setDerivToastShow(true);
    } catch (err) {
      setDerivOrderResult({ error: err.message });
      setDerivToastShow(true);
    }
    setDerivPlacing(false);
  };

  return (
    <div>
      {/* KuCoin Section */}
      <Card className="mb-4">
        <Card.Header as="h3">KuCoin Market</Card.Header>
        <Card.Body>
          <Form.Group className="mb-3" controlId="kucoinSymbolSelect">
            <Form.Label>Select Market</Form.Label>
            <Form.Select value={kucoinSymbol} onChange={e => setKucoinSymbol(e.target.value)}>
              {kucoinSymbols.map(s => (
                <option key={s.symbol} value={s.symbol}>{s.symbol}</option>
              ))}
            </Form.Select>
          </Form.Group>
          {symbolInfoLoading && <Spinner animation="border" size="sm" className="me-2" />}
          {symbolInfoError && <div className="text-danger">{symbolInfoError}</div>}
          {symbolInfo && (
            <div className="mb-2">
              <strong>Symbol Info:</strong>
              <ul style={{ marginBottom: 0 }}>
                <li>Base: {symbolInfo.baseCurrency}</li>
                <li>Quote: {symbolInfo.quoteCurrency}</li>
                <li>Min Size: {symbolInfo.baseMinSize}</li>
                <li>Max Size: {symbolInfo.baseMaxSize}</li>
                <li>Price Increment: {symbolInfo.priceIncrement}</li>
                <li>Size Increment: {symbolInfo.baseIncrement}</li>
                <li>Fee Rate: {symbolInfo.feeRate}</li>
              </ul>
            </div>
          )}
          {kucoinLoading ? <Spinner animation="border" /> : (
            <Row>
              <Col md={4}>
                <h5>Ticker</h5>
                {kucoinTicker && (
                  <ul>
                    <li>Price: <Badge bg="success">{kucoinTicker.price}</Badge></li>
                    <li>Best Ask: {kucoinTicker.bestAsk}</li>
                    <li>Best Bid: {kucoinTicker.bestBid}</li>
                    <li>Change: {kucoinTicker.changeRate}</li>
                  </ul>
                )}
              </Col>
              <Col md={4}>
                <h5>Order Book</h5>
                {kucoinOrderBook ? (
                  <>
                    <div><strong>Bids</strong></div>
                    <Table size="sm">
                      <thead>
                        <tr>
                          <th>
                            Price
                            <OverlayTrigger
                              placement="top"
                              overlay={<Tooltip id="tooltip-top">The price at which buyers are willing to purchase.</Tooltip>}
                            >
                              <span className="d-inline-block">
                                <i className="bi bi-info-circle"></i>
                              </span>
                            </OverlayTrigger>
                          </th>
                          <th>
                            Size
                            <OverlayTrigger
                              placement="top"
                              overlay={<Tooltip id="tooltip-top">The amount of the asset buyers wish to purchase.</Tooltip>}
                            >
                              <span className="d-inline-block">
                                <i className="bi bi-info-circle"></i>
                              </span>
                            </OverlayTrigger>
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        {kucoinOrderBook.bids.slice(0,5).map((b,i) => (
                          <tr key={i}>
                            <td>{b[0]}</td>
                            <td>{b[1]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                    <div><strong>Asks</strong></div>
                    <Table size="sm">
                      <thead>
                        <tr>
                          <th>
                            Price
                            <OverlayTrigger
                              placement="top"
                              overlay={<Tooltip id="tooltip-top">The price at which sellers are willing to sell.</Tooltip>}
                            >
                              <span className="d-inline-block">
                                <i className="bi bi-info-circle"></i>
                              </span>
                            </OverlayTrigger>
                          </th>
                          <th>
                            Size
                            <OverlayTrigger
                              placement="top"
                              overlay={<Tooltip id="tooltip-top">The amount of the asset sellers wish to sell.</Tooltip>}
                            >
                              <span className="d-inline-block">
                                <i className="bi bi-info-circle"></i>
                              </span>
                            </OverlayTrigger>
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        {kucoinOrderBook.asks.slice(0,5).map((a,i) => (
                          <tr key={i}>
                            <td>{a[0]}</td>
                            <td>{a[1]}</td>
                          </tr>
                        ))}
                      </tbody>
                    </Table>
                  </>
                ) : (
                  <Alert variant="info">No order book data available.</Alert>
                )}
              </Col>
              <Col md={4}>
                <h5>Recent Trades</h5>
                <Table size="sm">
                  <thead>
                    <tr>
                      <th>Price</th>
                      <th>Size</th>
                      <th>Side</th>
                    </tr>
                  </thead>
                  <tbody>
                    {kucoinTrades.length > 0 ? (
                      kucoinTrades.slice(0,5).map((t,i) => (
                        <tr key={i}>
                          <td>{t.price}</td>
                          <td>{t.size}</td>
                          <td>
                            <Badge bg={t.side === 'buy' ? 'success' : 'danger'}>{t.side}</Badge>
                          </td>
                        </tr>
                      ))
                    ) : (
                      <tr>
                        <td colSpan="3" className="text-center">
                          <Alert variant="info" className="mb-0">No recent trades.</Alert>
                        </td>
                      </tr>
                    )}
                  </tbody>
                </Table>
              </Col>
            </Row>
          )}
          <hr />
          <h5>Place Order</h5>
          <Form onSubmit={handleKucoinOrderSubmit} className="mb-3">
            <Form.Group className="mb-2">
              <Form.Label>Order Type</Form.Label>
              <Form.Select name="type" value={kucoinOrder.type} onChange={handleKucoinOrderChange}>
                <option value="market">Market</option>
                <option value="limit">Limit</option>
                <option value="stop_limit">Stop-Limit</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Side</Form.Label>
              <Form.Select name="side" value={kucoinOrder.side} onChange={handleKucoinOrderChange}>
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
              </Form.Select>
            </Form.Group>
            <Form.Group className="mb-2">
              <Form.Label>Size</Form.Label>
              <Form.Control name="size" value={kucoinOrder.size} onChange={handleKucoinOrderChange} type="number" min="0" step="any" required />
            </Form.Group>
            {(kucoinOrder.type === 'limit' || kucoinOrder.type === 'stop_limit') && (
              <Form.Group className="mb-2">
                <Form.Label>Price</Form.Label>
                <Form.Control name="price" value={kucoinOrder.price} onChange={handleKucoinOrderChange} type="number" min="0" step="any" required />
              </Form.Group>
            )}
            {kucoinOrder.type === 'stop_limit' && (
              <>
                <Form.Group className="mb-2">
                  <Form.Label>Stop Condition</Form.Label>
                  <Form.Select name="stop" value={kucoinOrder.stop || ''} onChange={handleKucoinOrderChange} required>
                    <option value="">Select...</option>
                    <option value="entry">Entry</option>
                    <option value="loss">Loss</option>
                  </Form.Select>
                </Form.Group>
                <Form.Group className="mb-2">
                  <Form.Label>Stop Price</Form.Label>
                  <Form.Control name="stopPrice" value={kucoinOrder.stopPrice || ''} onChange={handleKucoinOrderChange} type="number" min="0" step="any" required />
                </Form.Group>
              </>
            )}
            <Button type="submit" disabled={kucoinPlacing || !!validateKucoinOrder()}>
              {kucoinPlacing ? <Spinner size="sm" animation="border" /> : 'Place Order'}
            </Button>
          </Form>
          {/* KuCoin Order Validation Error */}
          {validateKucoinOrder() && (
            <div className="text-danger mb-2">{validateKucoinOrder()}</div>
          )}
          {kucoinOrderResult && (
            <div className="mt-2">
              {kucoinOrderResult.error ? (
                <span className="text-danger">Error: {kucoinOrderResult.error}</span>
              ) : (
                <span className="text-success">Order placed! ID: {kucoinOrderResult.data?.orderId || JSON.stringify(kucoinOrderResult)}</span>
              )}
            </div>
          )}
          <Toast onClose={() => setKucoinToastShow(false)} show={kucoinToastShow} delay={3000} autohide>
            <Toast.Body>
              {kucoinOrderResult?.error ? (
                <span className="text-danger">Error: {kucoinOrderResult.error}</span>
              ) : (
                <span className="text-success">Order placed successfully!</span>
              )}
            </Toast.Body>
          </Toast>
        </Card.Body>
      </Card>

      {/* Deriv Section */}
      <Card className="mb-4">
        <Card.Header as="h3">Deriv Market</Card.Header>
        <Card.Body>
          <Form.Group className="mb-3" controlId="derivSymbolSelect">
            <Form.Label>Select Market</Form.Label>
            <Form.Select value={derivSymbol} onChange={e => setDerivSymbol(e.target.value)}>
              {derivSymbols.map(s => (
                <option key={s.symbol} value={s.symbol}>{s.display_name || s.symbol}</option>
              ))}
            </Form.Select>
          </Form.Group>
          {derivLoading ? <Spinner animation="border" /> : (
            <Row>
              <Col md={6}>
                <h5>Account Info</h5>
                {derivAccount && (
                  <ul>
                    <li>Login ID: {derivAccount.loginid}</li>
                    <li>Balance: {derivAccount.balance}</li>
                    <li>Currency: {derivAccount.currency}</li>
                  </ul>
                )}
              </Col>
            </Row>
          )}
          <hr />
          <h5>Place Order</h5>
          <Form onSubmit={handleDerivOrderSubmit} className="mb-3">
            <Row>
              <Col md={2}>
                <Form.Select name="side" value={derivOrder.side} onChange={handleDerivOrderChange}>
                  <option value="buy">Buy</option>
                  <option value="sell">Sell</option>
                </Form.Select>
              </Col>
              <Col md={2}>
                <Form.Control name="amount" type="number" step="any" placeholder="Amount" value={derivOrder.amount} onChange={handleDerivOrderChange} required />
              </Col>
              <Col md={2}>
                <Button type="submit" disabled={derivPlacing}>{derivPlacing ? 'Placing...' : 'Submit'}</Button>
              </Col>
            </Row>
          </Form>
          {derivOrderResult && (
            <div className="mt-2">
              {derivOrderResult.error ? (
                <span className="text-danger">Error: {derivOrderResult.error}</span>
              ) : (
                <span className="text-success">Order placed! {JSON.stringify(derivOrderResult)}</span>
              )}
            </div>
          )}
          <Toast onClose={() => setDerivToastShow(false)} show={derivToastShow} delay={3000} autohide>
            <Toast.Body>
              {derivOrderResult?.error ? (
                <span className="text-danger">Error: {derivOrderResult.error}</span>
              ) : (
                <span className="text-success">Order placed successfully!</span>
              )}
            </Toast.Body>
          </Toast>
        </Card.Body>
      </Card>

      {/* Admin Section */}
      <Row>
        <Col md={12}>
          <KucoinSymbolCacheAdmin />
        </Col>
      </Row>
    </div>
  );
};

export default MarketDashboard;
