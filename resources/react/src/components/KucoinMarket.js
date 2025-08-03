import React, { useEffect, useState } from 'react';
import { Card, Table, Spinner, Form, Button, Row, Col } from 'react-bootstrap';
import { getKucoinSymbols, getKucoinTicker, getKucoinOrderBook, getKucoinTrades, placeKucoinOrder } from '../api/kucoin';

const KucoinMarket = () => {
  const [symbols, setSymbols] = useState([]);
  const [selectedSymbol, setSelectedSymbol] = useState('BTC-USDT');
  const [ticker, setTicker] = useState(null);
  const [orderBook, setOrderBook] = useState(null);
  const [trades, setTrades] = useState([]);
  const [loading, setLoading] = useState(true);
  const [order, setOrder] = useState({ side: 'buy', type: 'market', size: '', price: '' });
  const [placing, setPlacing] = useState(false);
  const [orderResult, setOrderResult] = useState(null);

  useEffect(() => {
    async function fetchSymbols() {
      const res = await getKucoinSymbols();
      setSymbols(res.data.data || []);
    }
    fetchSymbols();
  }, []);

  useEffect(() => {
    async function fetchMarket() {
      setLoading(true);
      const [tick, book, trd] = await Promise.all([
        getKucoinTicker(selectedSymbol),
        getKucoinOrderBook(selectedSymbol),
        getKucoinTrades(selectedSymbol)
      ]);
      setTicker(tick.data.data);
      setOrderBook(book.data.data);
      setTrades(trd.data.data || []);
      setLoading(false);
    }
    if (selectedSymbol) fetchMarket();
  }, [selectedSymbol]);

  const handleOrderChange = e => {
    setOrder({ ...order, [e.target.name]: e.target.value });
  };
  const handleOrderSubmit = async e => {
    e.preventDefault();
    setPlacing(true);
    try {
      const res = await placeKucoinOrder({
        symbol: selectedSymbol,
        ...order,
        size: parseFloat(order.size),
        price: order.type === 'limit' ? parseFloat(order.price) : undefined
      });
      setOrderResult(res.data);
    } catch (err) {
      setOrderResult({ error: err.message });
    }
    setPlacing(false);
  };

  return (
    <Card className="mb-4">
      <Card.Header as="h3">KuCoin Market</Card.Header>
      <Card.Body>
        <Form.Group className="mb-3" controlId="symbolSelect">
          <Form.Label>Select Market</Form.Label>
          <Form.Select value={selectedSymbol} onChange={e => setSelectedSymbol(e.target.value)}>
            {symbols.map(s => (
              <option key={s.symbol} value={s.symbol}>{s.symbol}</option>
            ))}
          </Form.Select>
        </Form.Group>
        {loading ? <Spinner animation="border" /> : (
          <Row>
            <Col md={4}>
              <h5>Ticker</h5>
              {ticker && (
                <ul>
                  <li>Price: {ticker.price}</li>
                  <li>Best Ask: {ticker.bestAsk}</li>
                  <li>Best Bid: {ticker.bestBid}</li>
                  <li>Change: {ticker.changeRate}</li>
                </ul>
              )}
            </Col>
            <Col md={4}>
              <h5>Order Book</h5>
              {orderBook && (
                <>
                  <div><strong>Bids</strong></div>
                  <Table size="sm"><tbody>{orderBook.bids.slice(0,5).map((b,i) => <tr key={i}><td>{b[0]}</td><td>{b[1]}</td></tr>)}</tbody></Table>
                  <div><strong>Asks</strong></div>
                  <Table size="sm"><tbody>{orderBook.asks.slice(0,5).map((a,i) => <tr key={i}><td>{a[0]}</td><td>{a[1]}</td></tr>)}</tbody></Table>
                </>
              )}
            </Col>
            <Col md={4}>
              <h5>Recent Trades</h5>
              <Table size="sm"><tbody>{trades.slice(0,5).map((t,i) => <tr key={i}><td>{t.price}</td><td>{t.size}</td><td>{t.side}</td></tr>)}</tbody></Table>
            </Col>
          </Row>
        )}
        <hr />
        <h5>Place Order</h5>
        <Form onSubmit={handleOrderSubmit} className="mb-3">
          <Row>
            <Col md={2}>
              <Form.Select name="side" value={order.side} onChange={handleOrderChange}>
                <option value="buy">Buy</option>
                <option value="sell">Sell</option>
              </Form.Select>
            </Col>
            <Col md={2}>
              <Form.Select name="type" value={order.type} onChange={handleOrderChange}>
                <option value="market">Market</option>
                <option value="limit">Limit</option>
              </Form.Select>
            </Col>
            <Col md={2}>
              <Form.Control name="size" type="number" step="any" placeholder="Size" value={order.size} onChange={handleOrderChange} required />
            </Col>
            {order.type === 'limit' && (
              <Col md={2}>
                <Form.Control name="price" type="number" step="any" placeholder="Price" value={order.price} onChange={handleOrderChange} required />
              </Col>
            )}
            <Col md={2}>
              <Button type="submit" disabled={placing}>{placing ? 'Placing...' : 'Submit'}</Button>
            </Col>
          </Row>
        </Form>
        {orderResult && (
          <div className="mt-2">
            {orderResult.error ? (
              <span className="text-danger">Error: {orderResult.error}</span>
            ) : (
              <span className="text-success">Order placed! ID: {orderResult.data?.orderId || JSON.stringify(orderResult)}</span>
            )}
          </div>
        )}
      </Card.Body>
    </Card>
  );
};

export default KucoinMarket;
