import React, { useState } from 'react';
import { Form, Button, Alert, Spinner } from 'react-bootstrap';
import { clearKucoinSymbolCache, getKucoinSymbol } from '../api/kucoin';

const KucoinSymbolCacheAdmin = () => {
  const [symbol, setSymbol] = useState('');
  const [loading, setLoading] = useState(false);
  const [infoLoading, setInfoLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [symbolInfo, setSymbolInfo] = useState(null);

  const handleClearCache = async (e) => {
    e.preventDefault();
    setLoading(true);
    setResult(null);
    try {
      const res = await clearKucoinSymbolCache(symbol);
      setResult({ success: true, message: res.data.message });
    } catch (err) {
      setResult({ success: false, message: err.response?.data?.error || err.message });
    }
    setLoading(false);
  };

  const handleFetchInfo = async () => {
    setInfoLoading(true);
    setSymbolInfo(null);
    try {
      const res = await getKucoinSymbol(symbol);
      setSymbolInfo(res.data);
    } catch (err) {
      setSymbolInfo({ error: err.response?.data?.error || err.message });
    }
    setInfoLoading(false);
  };

  return (
    <div className="my-4 p-3 border rounded bg-light shadow-sm">
      <h5 className="mb-3">KuCoin Symbol Cache & Info (Admin)</h5>
      <Form onSubmit={handleClearCache} className="d-flex align-items-center flex-wrap gap-2">
        <Form.Control
          type="text"
          placeholder="Enter symbol (e.g. BTC-USDT)"
          value={symbol}
          onChange={e => setSymbol(e.target.value)}
          style={{ width: 220, maxWidth: '100%' }}
          required
        />
        <Button type="submit" disabled={loading || !symbol} variant="danger">
          {loading ? <Spinner size="sm" animation="border" /> : 'Clear Cache'}
        </Button>
        <Button variant="primary" disabled={infoLoading || !symbol} onClick={handleFetchInfo}>
          {infoLoading ? <Spinner size="sm" animation="border" /> : 'Get Symbol Info'}
        </Button>
      </Form>
      {result && (
        <Alert variant={result.success ? 'success' : 'danger'} className="mt-3 mb-0">
          {result.message}
        </Alert>
      )}
      {symbolInfo && (
        <Alert variant={symbolInfo.error ? 'danger' : 'info'} className="mt-3 mb-0" style={{ whiteSpace: 'pre-wrap', wordBreak: 'break-all' }}>
          {symbolInfo.error ? symbolInfo.error : <pre style={{ margin: 0, fontSize: 13 }}>{JSON.stringify(symbolInfo, null, 2)}</pre>}
        </Alert>
      )}
    </div>
  );
};

export default KucoinSymbolCacheAdmin;
