import React, { useState } from 'react';
import { Form, Button, Spinner, Card } from 'react-bootstrap';
import { useAuth } from '../context/AuthContext';
import { useToast } from '../utils/ToastContext';
import { updateProfile } from '../api/user';

const ProfileForm = () => {
  const { user, setUser } = useAuth();
  const { showToast } = useToast();
  const [email, setEmail] = useState(user?.email || '');
  const [password, setPassword] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    try {
      const updated = await updateProfile({ email, password });
      setUser({ ...user, email: updated.email });
      showToast('Profile updated successfully', 'success');
      setPassword('');
    } catch (err) {
      showToast(err.message || 'Failed to update profile', 'danger');
    } finally {
      setLoading(false);
    }
  };

  return (
    <Card className="mx-auto" style={{ maxWidth: 400 }}>
      <Card.Body>
        <Card.Title>Profile Settings</Card.Title>
        <Form onSubmit={handleSubmit}>
          <Form.Group className="mb-3" controlId="formEmail">
            <Form.Label>Email address</Form.Label>
            <Form.Control
              type="email"
              value={email}
              onChange={e => setEmail(e.target.value)}
              required
              autoComplete="email"
            />
          </Form.Group>
          <Form.Group className="mb-3" controlId="formPassword">
            <Form.Label>New Password</Form.Label>
            <Form.Control
              type="password"
              value={password}
              onChange={e => setPassword(e.target.value)}
              placeholder="Leave blank to keep current password"
              autoComplete="new-password"
            />
          </Form.Group>
          <Button variant="primary" type="submit" disabled={loading} className="w-100">
            {loading ? <Spinner size="sm" animation="border" /> : 'Update Profile'}
          </Button>
        </Form>
      </Card.Body>
    </Card>
  );
};

export default ProfileForm;
