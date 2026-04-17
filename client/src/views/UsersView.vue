<template>
  <div>
    <div class="page-header">
      <div class="page-header-info">
        <h1><font-awesome-icon icon="users" /> Usuarios del Sistema</h1>
        <p>Gestiona los usuarios y sus roles de acceso.</p>
      </div>
      <div style="display:flex; gap: var(--sp-3)">
        <button class="btn btn-ghost" @click="permModalVisible = true">
          <font-awesome-icon icon="shield-alt" /> Gestionar Permisos
        </button>
        <button id="btn-new-user" class="btn btn-primary" @click="openCreate">
          <font-awesome-icon icon="plus" /> Nuevo Usuario
        </button>
      </div>
    </div>

    <div class="card">
      <div v-if="loading" class="spinner-overlay"><div class="spinner spinner-lg"></div></div>
      <div v-else-if="!users.length" class="empty-state">
        <div class="empty-state-icon">
          <font-awesome-icon icon="users" />
        </div>
        <h3>No hay usuarios registrados</h3>
      </div>
      <div v-else class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>#</th><th>Nombre</th><th>Email</th><th>Rol</th><th style="text-align:right">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td style="color:var(--c-text-muted);font-size:.8rem">{{ user.id }}</td>
              <td><strong>{{ user.name }}</strong></td>
              <td style="font-size:.875rem">{{ user.email }}</td>
              <td>
                <span class="badge" :class="roleBadge(user.role?.slug)">
                  {{ user.role?.name || 'Sin Rol' }}
                </span>
              </td>
              <td>
                <div style="display:flex;gap:4px;justify-content:flex-end">
                  <button class="btn btn-ghost btn-icon" @click="openEdit(user)" title="Editar usuario">
                    <font-awesome-icon icon="edit" />
                  </button>
                  <button class="btn btn-ghost btn-icon" @click="confirmDelete(user)" title="Eliminar usuario">
                    <font-awesome-icon icon="trash-alt" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal crear/editar -->
    <div v-if="modal.visible" class="modal-backdrop" @click.self="modal.visible = false">
      <div class="modal">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon :icon="modal.type === 'create' ? 'circle-plus' : 'edit'" />
            {{ modal.type === 'create' ? ' Nuevo Usuario' : ' Editar Usuario' }}
          </h3>
          <button class="btn btn-ghost btn-icon" @click="modal.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Nombre *</label>
              <input v-model="modal.form.name" type="text" class="form-control" placeholder="Nombre completo" />
              <span v-if="modal.errors.name" class="form-error">{{ modal.errors.name[0] }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Rol *</label>
              <BaseCombobox 
                v-model="modal.form.role_id"
                :options="roles"
                placeholder="Seleccione un rol..."
              />
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email *</label>
            <input v-model="modal.form.email" type="email" class="form-control" placeholder="usuario@ejemplo.com" />
            <span v-if="modal.errors.email" class="form-error">{{ modal.errors.email[0] }}</span>
          </div>
          <p v-if="modal.type === 'edit'" style="margin-bottom:var(--sp-4); font-size:0.85rem; color:var(--c-text-muted)">
            <strong>Opcional:</strong> deje los campos de contraseña en blanco si no desea realizar cambios.
          </p>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Contraseña {{ modal.type === 'create' ? '*' : '' }}</label>
              <input v-model="modal.form.password" type="password" class="form-control" placeholder="••••••••" />
              <span v-if="modal.errors.password" class="form-error">{{ modal.errors.password[0] }}</span>
            </div>
            <div class="form-group">
              <label class="form-label">Confirmar contraseña</label>
              <input v-model="modal.form.password_confirmation" type="password" class="form-control" placeholder="••••••••" />
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="modal.visible = false">Cancelar</button>
          <button class="btn btn-primary" @click="save" :disabled="modal.submitting">
            <span v-if="modal.submitting" class="spinner"></span>
            <span v-else>{{ modal.type === 'create' ? 'Crear Usuario' : 'Guardar' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Confirmar eliminar -->
    <div v-if="deleteConfirm.visible" class="modal-backdrop" @click.self="deleteConfirm.visible = false">
      <div class="modal" style="max-width:400px">
        <div class="modal-header">
          <h3 class="modal-title">
            <font-awesome-icon icon="trash-alt" /> Eliminar Usuario
          </h3>
          <button class="btn btn-ghost btn-icon" @click="deleteConfirm.visible = false">
            <font-awesome-icon icon="times" />
          </button>
        </div>
        <div class="modal-body">
          <p>¿Eliminar al usuario <strong>{{ deleteConfirm.userName }}</strong>? Esta acción es irreversible.</p>
        </div>
        <div class="modal-footer">
          <button class="btn btn-ghost" @click="deleteConfirm.visible = false">Cancelar</button>
          <button class="btn btn-danger" @click="deleteUser">Eliminar</button>
        </div>
      </div>
    </div>

    <!-- Modal de Permisos -->
    <PermissionModal 
      :visible="permModalVisible" 
      @close="permModalVisible = false" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useUserController } from '@/controllers/userController'
import PermissionModal from '@/components/users/PermissionModal.vue'
import BaseCombobox from '@/components/common/BaseCombobox.vue'

const {
  users, roles, loading, modal, deleteConfirm,
  fetchUsers, fetchRoles, openCreate, openEdit, save, confirmDelete, deleteUser,
} = useUserController()

const permModalVisible = ref(false)

const roleBadge = (slug) => ({
  admin: 'badge-danger',
  bibliotecario: 'badge-info',
  lector: 'badge-gray'
}[slug] || 'badge-gray')

onMounted(async () => {
  await Promise.all([
    fetchUsers(),
    fetchRoles()
  ])
})
</script>
