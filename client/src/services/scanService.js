import api from './api'

export const scanService = {
    getAll: () => api.get('/scans'),
    create: (data) => api.post('/scans', data),
}