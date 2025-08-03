import React from 'react';
import { Modal, Button } from 'react-bootstrap';

const TradeDetailModal = ({ show, onHide, trade }) => {
  if (!trade) return null;
  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton>
        <Modal.Title>Trade Details</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p><strong>ID:</strong> {trade.id}</p>
        <p><strong>Type:</strong> {trade.type}</p>
        <p><strong>Asset:</strong> {trade.asset}</p>
        <p><strong>Amount:</strong> {trade.amount}</p>
        <p><strong>Status:</strong> {trade.status}</p>
        <p><strong>Timestamp:</strong> {trade.created_at}</p>
        {/* Add more fields as needed */}
      </Modal.Body>
      <Modal.Footer>
        <Button variant="secondary" onClick={onHide}>Close</Button>
      </Modal.Footer>
    </Modal>
  );
};

export default TradeDetailModal;
