import React, { useEffect, useState } from 'react';
import { Card, Spinner, Row, Col } from 'react-bootstrap';
import { getUserAnalyticsByMarket } from '../api/analytics';
import { Line, Bar } from 'react-chartjs-2';

const DerivAnalytics = () => {
  const [analytics, setAnalytics] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchAnalytics() {
      setLoading(true);
      try {
        const res = await getUserAnalyticsByMarket('deriv');
        setAnalytics(res.data);
      } catch (e) {}
      setLoading(false);
    }
    fetchAnalytics();
  }, []);

  const pnlChartData = analytics && analytics.pnlSeries ? {
    labels: analytics.pnlSeries.map((p) => p.time),
    datasets: [
      {
        label: 'P&L',
        data: analytics.pnlSeries.map(p => p.pnl),
        borderColor: 'rgba(75,192,192,1)',
        backgroundColor: 'rgba(75,192,192,0.2)',
        tension: 0.2,
      },
    ],
  } : null;

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

  return (
    <Card className="mb-4">
      <Card.Header as="h3">Deriv Analytics</Card.Header>
      <Card.Body>
        {loading ? <Spinner animation="border" /> : (
          <>
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
            {analytics && pnlChartData && (
              <Card className="mb-3">
                <Card.Header>P&L Over Time</Card.Header>
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
          </>
        )}
      </Card.Body>
    </Card>
  );
};

export default DerivAnalytics;
