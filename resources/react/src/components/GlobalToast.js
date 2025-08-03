// src/components/GlobalToast.js
import React from 'react';
import { Toast, ToastContainer } from 'react-bootstrap';

const GlobalToast = ({ show, onClose, message, variant = 'info' }) => (
  <ToastContainer position="top-end" className="p-3" style={{ zIndex: 9999 }}>
    <Toast bg={variant} show={show} onClose={onClose} delay={4000} autohide>
      <Toast.Body className={variant === 'danger' ? 'text-white' : ''}>{message}</Toast.Body>
    </Toast>
  </ToastContainer>
);

export default GlobalToast;
