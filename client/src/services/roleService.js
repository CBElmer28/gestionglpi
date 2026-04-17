import api from './api'

export const roleService = {
  /**
   * Obtiene todos los roles con sus permisos asignados.
   */
  async getAll() {
    return api.get('/roles')
  },

  /**
   * Obtiene la lista base de todos los permisos disponibles.
   */
  async getPermissions() {
    return api.get('/permissions')
  },

  /**
   * Sincroniza la lista de IDs de permisos para un rol.
   */
  async syncPermissions(roleId, permissionIds) {
    return api.post(`/roles/${roleId}/permissions`, {
      permissions: permissionIds
    })
  }
}
