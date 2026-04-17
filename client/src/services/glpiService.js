import api from './api'

export const glpiService = {
  ping: () => api.get('/glpi/ping'),
  listBooks: () => api.get('/glpi/books'),
  listTickets: (limit = 20) => api.get('/glpi/tickets', { params: { limit } }),
  listGenres: () => api.get('/glpi/genres'),
  listPublishers: () => api.get('/glpi/publishers'),
  syncBook: (bookId) => api.post(`/glpi/sync-book/${bookId}`),
  syncAll: () => api.post('/glpi/sync-all'),
  createReport: (formData) => api.post('/glpi/create-report', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  }),
}
