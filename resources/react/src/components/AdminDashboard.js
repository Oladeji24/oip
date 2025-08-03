import React, { useEffect, useState } from 'react';
import { Card, Table, Button, Spinner, Form, InputGroup, Pagination } from 'react-bootstrap';
import { getUsers, flagUser, updateUser, promoteUser, demoteUser, impersonateUser } from '../api/user';
import { approveWithdrawal } from '../api/withdrawal';
import { useToast } from '../utils/ToastContext';
import UserDetailModal from './UserDetailModal';
import KucoinSymbolCacheAdmin from './KucoinSymbolCacheAdmin';
import DerivSymbolCacheAdmin from './DerivSymbolCacheAdmin';
import DerivAnalytics from './DerivAnalytics';
import AuditLogViewer from './AuditLogViewer';
import AdminUserEditModal from './AdminUserEditModal';
import { CSVLink } from 'react-csv';

const PAGE_SIZE = 10;

const AdminDashboard = () => {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [approving, setApproving] = useState(false);
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [selectedUser, setSelectedUser] = useState(null);
  const [showUserModal, setShowUserModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [editUser, setEditUser] = useState(null);
  const [roleFilter, setRoleFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const { showToast } = useToast();

  useEffect(() => {
    async function fetchUsers() {
      setLoading(true);
      try {
        const res = await getUsers();
        if (res.data && res.data.users) setUsers(res.data.users);
      } catch (e) {}
      setLoading(false);
    }
    fetchUsers();
  }, []);

  const handleFlag = async (id) => {
    try {
      await flagUser(id, 'flagged');
      showToast('User flagged!', 'warning');
    } catch (e) {
      showToast('Failed to flag user.', 'danger');
    }
  };

  const handleApproveWithdrawal = async (userId) => {
    setApproving(true);
    try {
      await approveWithdrawal(userId, 5000, 'NGN');
      showToast('Withdrawal approved!', 'success');
    } catch (e) {
      showToast('Failed to approve withdrawal.', 'danger');
    }
    setApproving(false);
  };

  const handleUserClick = (user) => {
    setSelectedUser(user);
    setShowUserModal(true);
  };

  const handlePromote = async (id) => {
    try {
      await promoteUser(id);
      showToast('User promoted to admin!', 'success');
      setUsers(users.map(u => u.id === id ? { ...u, role: 'admin' } : u));
    } catch (e) {
      showToast('Failed to promote user.', 'danger');
    }
  };
  const handleDemote = async (id) => {
    try {
      await demoteUser(id);
      showToast('User demoted to user!', 'warning');
      setUsers(users.map(u => u.id === id ? { ...u, role: 'user' } : u));
    } catch (e) {
      showToast('Failed to demote user.', 'danger');
    }
  };

  const handleEdit = (user) => {
    setEditUser(user);
    setShowEditModal(true);
  };
  const handleSaveEdit = async (form) => {
    try {
      await updateUser(editUser.id, form);
      showToast('User updated!', 'success');
      setUsers(users.map(u => u.id === editUser.id ? { ...u, ...form } : u));
    } catch (e) {
      showToast('Failed to update user.', 'danger');
    }
    setShowEditModal(false);
  };

  const handleImpersonate = async (user) => {
    try {
      const res = await impersonateUser(user.id);
      if (res.data && res.data.access_token) {
        localStorage.setItem('token', res.data.access_token);
        window.location.reload();
      } else {
        showToast('Failed to impersonate user.', 'danger');
      }
    } catch (e) {
      showToast('Failed to impersonate user.', 'danger');
    }
  };

  // User analytics widgets
  const totalUsers = users.length;
  const totalAdmins = users.filter(u => u.role === 'admin').length;
  const totalSuperadmins = users.filter(u => u.role === 'superadmin').length;
  const totalFlagged = users.filter(u => (u.status || 'active') === 'flagged').length;
  const totalBanned = users.filter(u => (u.status || 'active') === 'banned').length;
  const totalActive = users.filter(u => (u.status || 'active') === 'active').length;

  // Filter and paginate users
  const filteredUsers = users.filter(u =>
    u.email.toLowerCase().includes(search.toLowerCase()) &&
    (roleFilter ? u.role === roleFilter : true) &&
    (statusFilter ? (u.status || 'active') === statusFilter : true)
  );
  const totalPages = Math.ceil(filteredUsers.length / PAGE_SIZE) || 1;
  const pagedUsers = filteredUsers.slice((page - 1) * PAGE_SIZE, page * PAGE_SIZE);

  const handleSearchChange = (e) => {
    setSearch(e.target.value);
    setPage(1);
  };

  const handlePageChange = (newPage) => {
    setPage(newPage);
  };

  // Prepare CSV data for export
  const csvHeaders = [
    { label: 'ID', key: 'id' },
    { label: 'Name', key: 'name' },
    { label: 'Email', key: 'email' },
    { label: 'Role', key: 'role' },
    { label: 'Status', key: 'status' },
    { label: 'Created At', key: 'created_at' },
  ];
  const csvData = users.map(u => ({
    id: u.id,
    name: u.name,
    email: u.email,
    role: u.role,
    status: u.status || 'active',
    created_at: u.created_at,
  }));

  // Avatar and badge helpers
  const getAvatar = (user) => `https://api.dicebear.com/7.x/identicon/svg?seed=${encodeURIComponent(user.email)}`;
  const getStatusBadge = (status) => {
    switch ((status || 'active').toLowerCase()) {
      case 'active': return <span className="badge bg-success">Active</span>;
      case 'flagged': return <span className="badge bg-warning text-dark">Flagged</span>;
      case 'banned': return <span className="badge bg-danger">Banned</span>;
      default: return <span className="badge bg-secondary">{status}</span>;
    }
  };
  const getRoleBadge = (role) => {
    switch ((role || 'user').toLowerCase()) {
      case 'superadmin': return <span className="badge bg-gradient" style={{ background: 'linear-gradient(90deg,#facc15,#a78bfa)', color: '#232946' }}>Superadmin</span>;
      case 'admin': return <span className="badge bg-info text-dark">Admin</span>;
      default: return <span className="badge bg-secondary">User</span>;
    }
  };

  return (
    <Card className="mb-4">
      <Card.Header as="h2">Admin Dashboard</Card.Header>
      <Card.Body>
        <h5>Monitor Users</h5>
        <InputGroup className="mb-3" style={{ maxWidth: 300 }}>
          <Form.Control
            placeholder="Search by email"
            value={search}
            onChange={handleSearchChange}
          />
        </InputGroup>
        <div className="d-flex mb-3" style={{ gap: 16 }}>
          <Form.Select style={{ maxWidth: 180 }} value={roleFilter} onChange={e => setRoleFilter(e.target.value)}>
            <option value="">All Roles</option>
            <option value="user">User</option>
            <option value="admin">Admin</option>
            <option value="superadmin">Superadmin</option>
          </Form.Select>
          <Form.Select style={{ maxWidth: 180 }} value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="flagged">Flagged</option>
            <option value="banned">Banned</option>
          </Form.Select>
        </div>
        {loading ? <Spinner animation="border" /> : (
          <>
            <div className="d-flex flex-wrap gap-3 mb-4 align-items-center">
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#a78bfa,#38bdf8)', color: '#fff' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalUsers}</div>
                  <div>Total Users</div>
                </Card.Body>
              </Card>
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#22c55e,#facc15)', color: '#232946' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalAdmins}</div>
                  <div>Admins</div>
                </Card.Body>
              </Card>
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#facc15,#a78bfa)', color: '#232946' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalSuperadmins}</div>
                  <div>Superadmins</div>
                </Card.Body>
              </Card>
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#f43f5e,#a78bfa)', color: '#fff' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalFlagged}</div>
                  <div>Flagged</div>
                </Card.Body>
              </Card>
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#232946,#38bdf8)', color: '#fff' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalBanned}</div>
                  <div>Banned</div>
                </Card.Body>
              </Card>
              <Card style={{ minWidth: 180, background: 'linear-gradient(90deg,#22c55e,#38bdf8)', color: '#fff' }}>
                <Card.Body>
                  <div style={{ fontSize: 22, fontWeight: 700 }}>{totalActive}</div>
                  <div>Active</div>
                </Card.Body>
              </Card>
              <div style={{ marginLeft: 'auto' }}>
                <CSVLink data={csvData} headers={csvHeaders} filename="users.csv" className="btn btn-outline-primary">
                  Export Users CSV
                </CSVLink>
              </div>
            </div>
            <Table striped bordered hover>
              <thead>
                <tr>
                  <th>User</th>
                  <th>Status</th>
                  <th>Role</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {pagedUsers.length === 0 ? (
                  <tr><td colSpan={4} className="text-center">No users found.</td></tr>
                ) : pagedUsers.map((user) => (
                  <tr key={user.id}>
                    <td style={{ cursor: 'pointer', textDecoration: 'underline', display: 'flex', alignItems: 'center', gap: 8 }} onClick={() => handleUserClick(user)}>
                      <img src={getAvatar(user)} alt="avatar" style={{ width: 32, height: 32, borderRadius: '50%', marginRight: 8, border: '2px solid #a78bfa' }} />
                      {user.email}
                    </td>
                    <td>{getStatusBadge(user.status)}</td>
                    <td>{getRoleBadge(user.role)}</td>
                    <td>
                      <Button size="sm" variant="warning" className="me-2" onClick={() => handleFlag(user.id)}>Flag</Button>
                      <Button size="sm" variant="success" disabled={approving} onClick={() => handleApproveWithdrawal(user.id)}>
                        {approving ? 'Approving...' : 'Approve Withdrawal'}
                      </Button>
                      {user.role !== 'superadmin' && (
                        <>
                          <Button size="sm" variant="info" className="ms-2" onClick={() => handlePromote(user.id)}>Promote</Button>
                          <Button size="sm" variant="secondary" className="ms-2" onClick={() => handleDemote(user.id)}>Demote</Button>
                          <Button size="sm" variant="primary" className="ms-2" onClick={() => handleEdit(user)}>Edit</Button>
                          <Button size="sm" variant="dark" className="ms-2" onClick={() => handleImpersonate(user)}>Impersonate</Button>
                        </>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </Table>
            <Pagination className="justify-content-center">
              <Pagination.First onClick={() => handlePageChange(1)} disabled={page === 1} />
              <Pagination.Prev onClick={() => handlePageChange(page - 1)} disabled={page === 1} />
              {[...Array(totalPages)].map((_, idx) => (
                <Pagination.Item
                  key={idx + 1}
                  active={page === idx + 1}
                  onClick={() => handlePageChange(idx + 1)}
                >
                  {idx + 1}
                </Pagination.Item>
              ))}
              <Pagination.Next onClick={() => handlePageChange(page + 1)} disabled={page === totalPages} />
              <Pagination.Last onClick={() => handlePageChange(totalPages)} disabled={page === totalPages} />
            </Pagination>
          </>
        )}
        <h5 className="mt-4">Platform Health</h5>
        <p>All systems operational.</p>
        <KucoinSymbolCacheAdmin />
        <DerivSymbolCacheAdmin />
        <DerivAnalytics />
      </Card.Body>
      <UserDetailModal show={showUserModal} onHide={() => setShowUserModal(false)} user={selectedUser} />
      <AdminUserEditModal show={showEditModal} onHide={() => setShowEditModal(false)} user={editUser} onSave={handleSaveEdit} />
      <AuditLogViewer />
    </Card>
  );
};

export default AdminDashboard;
