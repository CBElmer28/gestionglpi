import api from './api'

export const loanService = {
  getAll: (params = {}) => api.get('/loans', { params }),
  getById: (id) => api.get(`/loans/${id}`),
  create: (data) => api.post('/loans', data),
  returnLoan: (id) => api.put(`/loans/${id}/return`),
  delete: (id) => api.delete(`/loans/${id}`),
}
