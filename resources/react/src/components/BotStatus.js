// src/components/BotStatus.js
import React from 'react';
import { Badge } from 'react-bootstrap';

const BotStatus = ({ status, market }) => {
  if (status === 'running') {
    return <Badge bg="success">Bot Running ({market})</Badge>;
  }
  if (status === 'stopped') {
    return <Badge bg="secondary">Bot Stopped</Badge>;
  }
  return <Badge bg="warning">Bot Status Unknown</Badge>;
};

export default BotStatus;
