import { useState } from 'react';

export default function TransactionModal({ isOpen, onClose, onSubmit }) {
    if (!isOpen) return null;

    const [amount, setAmount] = useState('');
    const [description, setDescription] = useState('');
    const [type, setType] = useState('EXPENSE');
    const [categoryId, setCategoryId] = useState(1); // Por defecto Comida
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        // Enviamos los datos al Dashboard para que él los procese
        await onSubmit({
            account_id: 1, // Por ahora fijo a la cuenta principal
            amount: parseFloat(amount),
            description,
            type,
            category_id: parseInt(categoryId)
        });
        setLoading(false);
        onClose(); // Cerramos el modal
    };

    return (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
            <div className="bg-white p-6 rounded-lg shadow-xl w-96">
                <h2 className="text-xl font-bold mb-4 text-gray-800">Registrar Movimiento</h2>
                
                <form onSubmit={handleSubmit} className="space-y-4">
                    {/* Tipo: Ingreso o Gasto */}
                    <div className="flex gap-2">
                        <button type="button" onClick={() => setType('EXPENSE')}
                            className={`flex-1 py-2 rounded ${type === 'EXPENSE' ? 'bg-red-500 text-white' : 'bg-gray-200'}`}>
                            Gasto
                        </button>
                        <button type="button" onClick={() => setType('INCOME')}
                            className={`flex-1 py-2 rounded ${type === 'INCOME' ? 'bg-green-500 text-white' : 'bg-gray-200'}`}>
                            Ingreso
                        </button>
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Monto</label>
                        <input type="number" step="0.01" required value={amount} onChange={e => setAmount(e.target.value)}
                            className="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="0.00" />
                    </div>

                    <div>
                        <label className="block text-sm font-medium text-gray-700">Descripción</label>
                        <input type="text" required value={description} onChange={e => setDescription(e.target.value)}
                            className="w-full border p-2 rounded focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Ej: Supermercado" />
                    </div>

                    {/* Selector de Categoría (Simple por ahora) */}
                    <div>
                        <label className="block text-sm font-medium text-gray-700">Categoría</label>
                        <select value={categoryId} onChange={e => setCategoryId(e.target.value)}
                            className="w-full border p-2 rounded bg-white">
                            <option value="1">🍔 Comida</option>
                            <option value="2">🚌 Transporte</option>
                            <option value="3">💡 Servicios</option>
                            <option value="4">🎬 Entretenimiento</option>
                            <option value="5">💰 Salario</option>
                        </select>
                    </div>

                    <div className="flex justify-end gap-2 mt-4">
                        <button type="button" onClick={onClose} className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded">Cancelar</button>
                        <button type="submit" disabled={loading} className="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                            {loading ? 'Guardando...' : 'Guardar'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}