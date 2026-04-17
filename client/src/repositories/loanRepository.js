import { loanService } from '@/services/loanService'

export const loanRepository = {
  async getAll(filters = {}) {
    const { data } = await loanService.getAll(filters)
    return data
  },

  async getById(id) {
    const { data } = await loanService.getById(id)
    return data
  },

  async create(data) {
    const { data: result } = await loanService.create(data)
    return result
  },

  async returnLoan(id) {
    const { data } = await loanService.returnLoan(id)
    return data
  },

  async delete(id) {
    await loanService.delete(id)
    return true
  },
}
