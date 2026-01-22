import React, { useEffect, useState } from 'react';
import axios from 'axios';
import UserManagement from './UserManagement';
import StatsPanel from './StatsPanel';
import SignalementMap from './SignalementMap';
import SignalementTable from './SignalementTable';
import SignalementForm from './SignalementForm';
import SyncPanel from './SyncPanel';

const ROLE_OPTIONS = [
    { value: 'visitor', label: 'Visiteur' },
    { value: 'user', label: 'Utilisateur' },
    { value: 'manager', label: 'Manager' },
];

const ROLE_STORAGE_KEY = 'rc-current-role';
const resolveInitialRole = () => {
    if (typeof window === 'undefined') {
        return 'visitor';
    }

    if (typeof window.location !== 'undefined' && window.location.pathname === '/users') {
        if (typeof window.localStorage !== 'undefined') {
            window.localStorage.setItem(ROLE_STORAGE_KEY, 'manager');
        }
        return 'manager';
    }

    if (typeof window.localStorage !== 'undefined') {
        const stored = window.localStorage.getItem(ROLE_STORAGE_KEY);
        if (stored) {
            return stored;
        }
    }

    return 'visitor';
};

const initialRole = resolveInitialRole();
axios.defaults.headers.common['X-User-Role'] = initialRole;

function App() {
    const [currentRole, setCurrentRole] = useState(initialRole);
    const [refreshKey, setRefreshKey] = useState(0);

    useEffect(() => {
        axios.defaults.headers.common['X-User-Role'] = currentRole;
        if (typeof window !== 'undefined' && typeof window.localStorage !== 'undefined') {
            window.localStorage.setItem(ROLE_STORAGE_KEY, currentRole);
        }
    }, [currentRole]);

    const handleRoleChange = (event) => {
        const nextRole = event.target.value;
        if (typeof window !== 'undefined' && typeof window.localStorage !== 'undefined') {
            window.localStorage.setItem(ROLE_STORAGE_KEY, nextRole);
        }
        axios.defaults.headers.common['X-User-Role'] = nextRole;
        setCurrentRole(nextRole);
    };

    const triggerRefresh = () => setRefreshKey((prev) => prev + 1);

    return (
        <div className="container mx-auto p-4 space-y-6">
            <header className="flex flex-col md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 className="text-3xl font-bold">Suivi des travaux routiers</h1>
                    <p className="text-gray-600">Module web - Ville d'Antananarivo</p>
                </div>
                <div className="mt-4 md:mt-0">
                    <label className="text-sm font-semibold mr-2">Profil</label>
                    <select
                        value={currentRole}
                        onChange={handleRoleChange}
                        className="border px-3 py-2 rounded"
                    >
                        {ROLE_OPTIONS.map((role) => (
                            <option key={role.value} value={role.value}>
                                {role.label}
                            </option>
                        ))}
                    </select>
                </div>
            </header>

            <StatsPanel refreshKey={refreshKey} />
            <SignalementMap refreshKey={refreshKey} />

            {currentRole !== 'visitor' && (
                <SignalementForm onCreated={triggerRefresh} />
            )}

            <SignalementTable
                currentRole={currentRole}
                refreshKey={refreshKey}
                onUpdated={triggerRefresh}
            />

            {currentRole === 'manager' && (
                <>
                    <SyncPanel onSync={triggerRefresh} />
                    <UserManagement />
                </>
            )}
        </div>
    );
}

export default App;
