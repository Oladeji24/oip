import React from 'react';
import AuthForm from '../components/AuthForm';
import { Container, Row, Col } from 'react-bootstrap';

const AuthPage = () => (
  <div className="auth-page py-5">
    <Container>
      <Row className="justify-content-center">
        <Col md={10} lg={8} className="text-center mb-4">
          <h1 className="display-5 fw-bold mb-3">Join Our Trading Platform</h1>
          <p className="lead text-muted">
            Create your account today and start using our advanced trading tools and analytics
          </p>
        </Col>
      </Row>
      <AuthForm />
    </Container>
  </div>
);

export default AuthPage;

