import api from './api'

export const scanService = {
    getAll: () => api.get('/scans'),
    lookup: (isbn) => api.get(`/scans/lookup/${isbn}`),
    create: (data) => api.post('/scans', data, data instanceof FormData ? {
        headers: { 'Content-Type': 'multipart/form-data' }
    } : {}),
}