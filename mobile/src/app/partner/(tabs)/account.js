import React from 'react';

import { useGrabBasket } from '../../../../App';
import OperationsAuthScreen from '../../../components/operations-auth-screen';
import OperationsScreen from '../../../components/operations-screen';

export default function PartnerAccountScreen() {
  const { isAuthenticated } = useGrabBasket();

  if (!isAuthenticated) {
    return <OperationsAuthScreen variant="partner" />;
  }

  return <OperationsScreen variant="partner" screen="account" />;
}