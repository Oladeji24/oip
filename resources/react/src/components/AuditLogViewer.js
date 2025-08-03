import React, { useEffect, useState } from 'react';
import { Card, Table, Spinner } from 'react-bootstrap';
import { getAuditLogs } from '../api/audit';

const AuditLogViewer = () => {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchLogs() {
      setLoading(true);
      try {
        const res = await getAuditLogs();
        if (res.data && res.data.logs) setLogs(res.data.logs);
      } catch (e) {}
      setLoading(false);
    }
    fetchLogs();
  }, []);

  return (
    <Card className="mb-4 mt-4">
      <Card.Header as="h5">Admin Audit Log</Card.Header>
      <Card.Body>
        {loading ? <Spinner animation="border" /> : (
          <Table striped bordered hover size="sm">
            <thead>
              <tr>
                <th>Time</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              {logs.length === 0 ? (
                <tr><td colSpan={4} className="text-center">No audit logs found.</td></tr>
              ) : logs.map(log => (
                <tr key={log.id}>
                  <td>{new Date(log.created_at).toLocaleString()}</td>
                  <td>{log.user?.name || 'N/A'} ({log.user?.role})</td>
                  <td>{log.action}</td>
                  <td>{log.details}</td>
                </tr>
              ))}
            </tbody>
          </Table>
        )}
      </Card.Body>
    </Card>
  );
};

export default AuditLogViewer;
