import React, { useEffect, useState } from 'react';
import { Card, Table, Spinner, Form, Button, Row, Col } from 'react-bootstrap';
import { getDerivSymbols, getDerivAccount, placeDerivOrder } from '../api/deriv';

const DerivMarket = () => {
  const [symbols, setSymbols] = useState([]);
  const [selectedSymbol, setSelectedSymbol] = useState('frxEURUSD');
  const [account, setAccount] = useState(null);
  const [loading, setLoading] = useState(true);
  const [order, setOrder] = useState({ side: 'buy', amount: '' });
  const [placing, setPlacing] = useState(false);
  const [orderResult, setOrderResult] = useState(null);

  useEffect(() => {
    async function fetchSymbols() {
      const res = await getDerivSymbols();
      setSymbols(res.data.data?.symbols || []);
    }
    async function fetchAccount() {
      const res = await getDerivAccount();
      setAccount(res.data);
    }
    fetchSymbols();
    fetchAccount();
    setLoading(false);
  }, []);

  const handleOrderChange = e => {
    setOrder({ ...order, [e.target.name]: e.target.value });
  };
  const handleOrderSubmit = async e => {
    e.preventDefault();
    setPlacing(true);
    try {
      const res = await placeDerivOrder({
        symbol: selectedSymbol,
        ...order,
        amount: parseFloat(order.amount)
      });
      setOrderResult(res.data);
    } catch (err) {
      setOrderResult({ error: err.message });
    }
    setPlacing(false);
  };

  return (
    <Card className="mb-4">
      <Card.Header as="h3">Deriv Market</Card.Header>
      <Card.Body>
        <Form.Group className="mb-3" controlId="symbolSelect">
          <Form.Label>Select Market</Form.Label>
          <Form.Select value={selectedSymbol} onChange={e => setSelectedSymbol(e.target.value)}>
            {symbols.map(s => (
              <option key={s.symbol} value={s.symbol}>{s.display_name || s.symbol}</option>
            ))}
          </Form.Select>
        </Form.Group>
        {loading ? <Spinner animation="border" /> : (
          <Row>
            <Col md={6}>
              <h5>Account Info</h5>
              {account && (
                <ul>
                  <li>Login ID: {account.loginid}</li>
                  <li>Balance: {account.balance}</li>
                  <li>Currency: {account.currency}</li>
                </ul>
              )}
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
              <Form.Control name="amount" type="number" step="any" placeholder="Amount" value={order.amount} onChange={handleOrderChange} required />
            </Col>
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
              <span className="text-success">Order placed! {JSON.stringify(orderResult)}</span>
            )}
          </div>
        )}
      </Card.Body>
    </Card>
  );
};

export default DerivMarket;
