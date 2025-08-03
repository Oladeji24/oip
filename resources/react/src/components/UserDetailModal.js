import React from 'react';
import { Modal, Button } from 'react-bootstrap';

const UserDetailModal = ({ show, onHide, user }) => {
  if (!user) return null;
  return (
    <Modal show={show} onHide={onHide} centered>
      <Modal.Header closeButton>
        <Modal.Title>User Details</Modal.Title>
      </Modal.Header>
      <Modal.Body>
        <p><strong>ID:</strong> {user.id}</p>
        <p><strong>Email:</strong> {user.email}</p>
        <p><strong>Status:</strong> {user.status || 'Active'}</p>
        <p><strong>Created:</strong> {user.created_at}</p>
        <p><strong>Role:</strong> {user.role}</p>
        {/* Add more fields as needed */}
      </Modal.Body>
      <Modal.Footer>
        <Button variant="secondary" onClick={onHide}>Close</Button>
      </Modal.Footer>
    </Modal>
  );
};

export default UserDetailModal;
