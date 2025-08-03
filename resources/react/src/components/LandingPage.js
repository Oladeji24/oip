import React from 'react';
import { Container, Row, Col, Card, Button } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import { useAuth } from '../context/AuthContext';

const LandingPage = () => {
  const { user } = useAuth();

  return (
    <div className="landing-page">
      <div className="hero-section text-center">
        <Container>
          <Row className="justify-content-center">
            <Col md={10} lg={8}>
              <h1 className="display-4 fw-bold text-white mb-4">Advanced Trading Solutions</h1>
              <p className="lead text-white mb-5">
                Powerful algorithmic trading platform with comprehensive analytics and market insights for both crypto and forex markets.
              </p>
              {!user ? (
                <div className="d-grid gap-2 d-sm-flex justify-content-sm-center">
                  <Button as={Link} to="/login" variant="primary" size="lg" className="px-4 me-sm-3">Get Started</Button>
                  <Button as={Link} to="/demo" variant="outline-light" size="lg" className="px-4">Try Demo</Button>
                </div>
              ) : (
                <Button as={Link} to="/" variant="primary" size="lg" className="px-4">Dashboard</Button>
              )}
            </Col>
          </Row>
        </Container>
      </div>

      <Container className="py-5">
        <h2 className="text-center mb-5">Our Trading Platform Features</h2>
        <Row>
          <Col md={4} className="mb-4">
            <Card className="h-100 shadow-sm">
              <Card.Body>
                <div className="feature-icon mb-3">
                  <i className="bi bi-graph-up-arrow fs-2"></i>
                </div>
                <Card.Title>Advanced Analytics</Card.Title>
                <Card.Text>
                  Comprehensive trade analytics with performance metrics, equity curves, and profit distribution analysis.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
          <Col md={4} className="mb-4">
            <Card className="h-100 shadow-sm">
              <Card.Body>
                <div className="feature-icon mb-3">
                  <i className="bi bi-robot fs-2"></i>
                </div>
                <Card.Title>Automated Trading</Card.Title>
                <Card.Text>
                  Deploy sophisticated trading bots with customizable strategies for both crypto and forex markets.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
          <Col md={4} className="mb-4">
            <Card className="h-100 shadow-sm">
              <Card.Body>
                <div className="feature-icon mb-3">
                  <i className="bi bi-shield-lock fs-2"></i>
                </div>
                <Card.Title>Secure Transactions</Card.Title>
                <Card.Text>
                  Industry-standard security protocols ensuring safe deposits, withdrawals, and trading operations.
                </Card.Text>
              </Card.Body>
            </Card>
          </Col>
        </Row>
      </Container>

      <div className="bg-light py-5">
        <Container>
          <Row className="justify-content-center">
            <Col md={8} className="text-center">
              <h2 className="mb-4">Ready to elevate your trading?</h2>
              <p className="lead mb-4">
                Join thousands of traders using our platform to optimize their trading strategies and maximize returns.
              </p>
              {!user ? (
                <Button as={Link} to="/login" variant="primary" size="lg">Create Account</Button>
              ) : (
                <Button as={Link} to="/" variant="primary" size="lg">Go to Dashboard</Button>
              )}
            </Col>
          </Row>
        </Container>
      </div>
    </div>
  );
};

export default LandingPage;
