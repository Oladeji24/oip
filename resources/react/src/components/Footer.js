import React from 'react';
import { Container, Row, Col } from 'react-bootstrap';
import { Link } from 'react-router-dom';
import './Footer.css';

const Footer = () => {
  const currentYear = new Date().getFullYear();

  return (
    <footer className="app-footer">
      <Container>
        <Row className="py-4">
          <Col md={4} className="mb-4 mb-md-0">
            <div className="footer-brand mb-3">
              <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" alt="logo" className="footer-logo" />
              <span className="footer-title">OIP Trading</span>
            </div>
            <p className="mb-3">Advanced algorithmic trading platform with comprehensive analytics for crypto and forex markets.</p>
            <div className="social-icons">
              <a href="#" className="me-3"><i className="bi bi-twitter"></i></a>
              <a href="#" className="me-3"><i className="bi bi-facebook"></i></a>
              <a href="#" className="me-3"><i className="bi bi-linkedin"></i></a>
              <a href="#"><i className="bi bi-instagram"></i></a>
            </div>
          </Col>
          <Col md={3} className="mb-4 mb-md-0">
            <h5 className="footer-heading">Products</h5>
            <ul className="footer-links-list">
              <li><Link to="/dashboard">Dashboard</Link></li>
              <li><Link to="/markets">Markets</Link></li>
              <li><Link to="/demo">Demo Trading</Link></li>
            </ul>
          </Col>
          <Col md={2} className="mb-4 mb-md-0">
            <h5 className="footer-heading">Resources</h5>
            <ul className="footer-links-list">
              <li><a href="#">Documentation</a></li>
              <li><a href="#">API</a></li>
              <li><a href="#">FAQ</a></li>
            </ul>
          </Col>
          <Col md={3}>
            <h5 className="footer-heading">Contact Us</h5>
            <p className="footer-contact"><i className="bi bi-envelope me-2"></i> support@oiptrading.com</p>
            <p className="footer-contact"><i className="bi bi-telephone me-2"></i> +1 (555) 123-4567</p>
            <p className="footer-contact"><i className="bi bi-geo-alt me-2"></i> 123 Trading St, Financial District</p>
          </Col>
        </Row>
        <div className="footer-copy text-center pt-3 mt-3 border-top border-secondary">
          &copy; {currentYear} OIP Trading. All rights reserved.
        </div>
      </Container>
    </footer>
  );
};

export default Footer;
