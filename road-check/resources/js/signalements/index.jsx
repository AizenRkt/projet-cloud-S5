import React from 'react';
import ReactDOM from 'react-dom/client';
import SignalementValidationApp from './SignalementValidationApp';

const mount = document.getElementById('signalements-validation-app');
if (mount) {
    ReactDOM.createRoot(mount).render(<SignalementValidationApp />);
}
