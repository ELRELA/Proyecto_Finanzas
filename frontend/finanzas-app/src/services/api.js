// src/services/api.js

// IMPORTANTE: Asegúrate que esta URL coincida con tu carpeta en XAMPP.
// Si tu carpeta es "Proyecto_Finanzas", la URL base es esta:
const API_URL = 'http://localhost/PROYECTO_FINANZAS'; 

export const loginUser = async (email, password) => {
    const response = await fetch(`${API_URL}/auth_login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Error en login');
    return data; 
};

export const getDashboardData = async (token) => {
    const response = await fetch(`${API_URL}/get_dashboard_data.php`, {
        method: 'GET',
        headers: {
            'Authorization': `Bearer ${token}`, 
            'Content-Type': 'application/json'
        }
    });

    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Error al cargar datos');
    return data;

    
    
};

// ... (código anterior)

export const createTransaction = async (token, transactionData) => {
    const response = await fetch(`${API_URL}/create_transaction.php`, {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(transactionData)
    });

    const data = await response.json();
    if (!response.ok) throw new Error(data.error || 'Error al crear transacción');
    return data;
};