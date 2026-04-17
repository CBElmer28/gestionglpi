import { userService } from '@/services/userService'

export const userRepository = {
  async getAll() {
    const { data } = await userService.getAll()
    return data
  },

  async getById(id) {
    const { data } = await userService.getById(id)
    return data
  },

  async create(data) {
    const { data: result } = await userService.create(data)
    return result
  },

  async update(id, data) {
    const { data: result } = await userService.update(id, data)
    return result
  },

  async delete(id) {
    await userService.delete(id)
    return true
  },
}
