import { scanService } from '@/services/scanService'

export const scanRepository = {
  async getAll() {
    const { data } = await scanService.getAll()
    return data
  },

  async lookup(isbn) {
    const { data } = await scanService.lookup(isbn)
    return data
  },

  async create(payload) {
    const { data } = await scanService.create(payload)
    return data
  },
}