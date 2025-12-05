import { useEffect, useState } from 'react';
import { getDashboardData, createTransaction } from '../services/api'; 
import TransactionModal from '../components/TransactionModal';

// --- CONFIGURACIÓN DE CHART.JS ---
import { Chart as ChartJS, ArcElement, Tooltip, Legend } from 'chart.js';
import { Pie } from 'react-chartjs-2';
ChartJS.register(ArcElement, Tooltip, Legend);

export default function Dashboard({ token, onLogout }) {
    const [data, setData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Cargar datos del servidor
    const loadData = async () => {
        try {
            const result = await getDashboardData(token);
            setData(result.data);
        } catch (err) {
            setError(err.message);
            // Si el token falló, sacamos al usuario
            if(err.message.includes('Token') || err.message.includes('Acceso')) onLogout();
        } finally {
            setLoading(false);
        }
    };

    // Ejecutar al iniciar
    useEffect(() => { loadData(); }, [token]);

    // Manejar nuevo gasto desde el Modal
    const handleNewTransaction = async (transactionData) => {
        try {
            await createTransaction(token, transactionData);
            await loadData(); // Recargamos todo para ver los cambios al instante
            alert("¡Movimiento registrado con éxito!");
        } catch (err) {
            alert("Error: " + err.message);
        }
    };

    // CONFIGURACIÓN GRÁFICO 1: MES ACTUAL
    const monthlyChartConfig = {
        labels: data?.monthly_spending_chart?.map(item => item.name) || [],
        datasets: [
            {
                label: 'Gastos Mes',
                data: data?.monthly_spending_chart?.map(item => item.total) || [],
                backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#3b82f6'],
                borderWidth: 1,
            },
        ],
    };

    // CONFIGURACIÓN GRÁFICO 2: DÍA ACTUAL (HOY)
    const dailyChartConfig = {
        labels: data?.daily_spending_chart?.map(item => item.name) || [],
        datasets: [
            {
                label: 'Gastos Hoy',
                data: data?.daily_spending_chart?.map(item => item.total) || [],
                backgroundColor: ['#818cf8', '#f472b6', '#fbbf24', '#34d399', '#60a5fa'],
                borderWidth: 1,
            },
        ],
    };

    if (loading) return <div className="flex justify-center items-center h-screen text-indigo-600 font-bold text-xl">Cargando tus finanzas...</div>;
    if (error) return <div className="text-center mt-20 text-red-500 font-bold">Error: {error}</div>;

    return (
        <div className="min-h-screen bg-gray-50 pb-10">
            {/* Navbar Superior */}
            <nav className="bg-white shadow-sm px-6 py-4 flex justify-between items-center sticky top-0 z-20 border-b border-gray-100">
                <h1 className="text-2xl font-bold text-indigo-700 tracking-tight">Mi Finanzas</h1>
                <div className="flex gap-4">
                    <button onClick={() => setIsModalOpen(true)} 
                        className="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 shadow-md transition-all font-medium flex items-center gap-2">
                        <span>+</span> Nuevo Movimiento
                    </button>
                    <button onClick={onLogout} className="text-red-600 hover:text-red-800 font-medium px-4 py-2 border border-red-100 rounded-lg hover:bg-red-50 transition">
                        Salir
                    </button>
                </div>
            </nav>

            <div className="max-w-7xl mx-auto p-6 space-y-8">
                
                {/* 1. TARJETAS DE SALDO */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    {data.net_worth.map((balance, index) => (
                        <div key={index} className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition">
                            <p className="text-gray-500 text-xs uppercase font-bold tracking-wider">Saldo Disponible ({balance.currency})</p>
                            <p className={`text-4xl font-extrabold mt-2 ${balance.total_balance < 0 ? 'text-red-500' : 'text-slate-800'}`}>
                                {Number(balance.total_balance).toLocaleString('en-US', { style: 'currency', currency: balance.currency })}
                            </p>
                        </div>
                    ))}
                </div>

                {/* 2. SECCIÓN DE GRÁFICOS (GRID DE 2 COLUMNAS) */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    {/* Gráfico Izquierdo: Mes Actual */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center">
                        <h3 className="text-gray-700 font-bold mb-6 w-full text-center border-b border-gray-100 pb-3">📅 Gastos del Mes</h3>
                        <div className="w-64 h-64 relative">
                            {data.monthly_spending_chart.length > 0 ? (
                                <Pie data={monthlyChartConfig} />
                            ) : (
                                <div className="absolute inset-0 flex items-center justify-center text-gray-400 italic text-center">
                                    No hay gastos este mes
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Gráfico Derecho: Día Actual */}
                    <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col items-center">
                        <h3 className="text-indigo-700 font-bold mb-6 w-full text-center border-b border-indigo-50 pb-3">☀️ Gastos de Hoy</h3>
                        <div className="w-64 h-64 relative">
                            {data.daily_spending_chart.length > 0 ? (
                                <Pie data={dailyChartConfig} />
                            ) : (
                                <div className="absolute inset-0 flex items-center justify-center text-gray-400 italic text-center px-4">
                                    ¡Hoy no has gastado nada! 🎉
                                </div>
                            )}
                        </div>
                    </div>

                </div>

                {/* 3. TABLA DE ÚLTIMOS MOVIMIENTOS */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-100 bg-gray-50">
                        <h3 className="text-gray-800 font-bold">Últimos Movimientos</h3>
                    </div>
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 text-xs uppercase font-semibold text-gray-500">
                            <tr>
                                <th className="px-6 py-3 text-left">Fecha</th>
                                <th className="px-6 py-3 text-left">Descripción</th>
                                <th className="px-6 py-3 text-left">Categoría</th>
                                <th className="px-6 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {data.recent_activity.map((t) => (
                                <tr key={t.transaction_id} className="hover:bg-gray-50 transition">
                                    <td className="px-6 py-4 text-gray-500">{t.date}</td>
                                    <td className="px-6 py-4 font-medium text-gray-900">{t.description}</td>
                                    <td className="px-6 py-4">
                                        <span className="px-2 py-1 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                            {t.category_name || 'General'}
                                        </span>
                                    </td>
                                    <td className={`px-6 py-4 text-right font-bold ${t.type === 'INCOME' ? 'text-green-600' : 'text-red-600'}`}>
                                        {t.type === 'INCOME' ? '+' : '-'} {Number(t.amount).toLocaleString('en-US', { style: 'currency', currency: 'USD' })}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            {/* MODAL PARA REGISTRAR (Se muestra sobre todo lo demás) */}
            <TransactionModal 
                isOpen={isModalOpen} 
                onClose={() => setIsModalOpen(false)} 
                onSubmit={handleNewTransaction} 
            />
        </div>
    );
}