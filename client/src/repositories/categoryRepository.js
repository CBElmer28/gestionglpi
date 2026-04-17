import { categoryService } from '@/services/categoryService'

export const categoryRepository = {
  async getAll() {
    const { data } = await categoryService.getAll()
    return data
  },

  async getById(id) {
    const { data } = await categoryService.getById(id)
    return data
  },

  async create(data) {
    const { data: result } = await categoryService.create(data)
    return result
  },

  async update(id, data) {
    const { data: result } = await categoryService.update(id, data)
    return result
  },

  async delete(id) {
    await categoryService.delete(id)
    return true
  },
}
