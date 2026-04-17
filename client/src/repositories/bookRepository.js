import { bookService } from '@/services/bookService'

export const bookRepository = {
  async getAll(filters = {}) {
    const { data } = await bookService.getAll(filters)
    return data
  },

  async getById(id) {
    const { data } = await bookService.getById(id)
    return data
  },

  async search(q) {
    const { data } = await bookService.search(q)
    return data
  },

  async create(bookData) {
    const { data } = await bookService.create(bookData)
    return data
  },

  async update(id, bookData) {
    const { data } = await bookService.update(id, bookData)
    return data
  },

  async delete(id) {
    await bookService.delete(id)
    return true
  },
}
