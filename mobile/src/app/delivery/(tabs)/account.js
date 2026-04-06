import React from 'react';

import { useGrabBasket } from '../../../../App';
import OperationsAuthScreen from '../../../components/operations-auth-screen';
import OperationsScreen from '../../../components/operations-screen';

export default function DeliveryAccountScreen() {
  const { isAuthenticated } = useGrabBasket();

  if (!isAuthenticated) {
    return <OperationsAuthScreen variant="delivery" />;
  }

  return <OperationsScreen variant="delivery" screen="account" />;
}