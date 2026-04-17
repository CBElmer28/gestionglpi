<template>
  <div class="combobox-container" ref="containerRef">
    <div class="combobox-wrapper">
      <input
        type="text"
        class="form-control"
        :class="{ 'is-invalid': hasError }"
        :placeholder="placeholder"
        v-model="searchTerm"
        @focus="handleFocus"
        @input="handleSearch"
        @keydown.esc="closeDropdown"
      />
      
      <!-- Icono de flecha o búsqueda -->
      <div class="combobox-icon">
        <font-awesome-icon :icon="isOpen ? 'chevron-up' : 'search'" />
      </div>

      <Transition name="slide-fade">
        <div v-if="isOpen" class="combobox-dropdown shadow-lg">
          <div
            v-for="option in filteredOptions"
            :key="option[valueKey]"
            class="combobox-item"
            :class="{ active: modelValue === option[valueKey] }"
            @click="selectOption(option)"
          >
            <div class="combobox-item-content">
              <span class="combobox-item-label">{{ option[labelKey] }}</span>
              <span v-if="subLabelKey && option[subLabelKey]" class="combobox-item-sub">
                {{ option[subLabelKey] }}
              </span>
            </div>
            <font-awesome-icon v-if="modelValue === option[valueKey]" icon="check" class="check-icon" />
          </div>
          
          <div v-if="filteredOptions.length === 0" class="combobox-empty">
            {{ emptyText }}
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  options: {
    type: Array,
    required: true
  },
  labelKey: {
    type: String,
    default: 'name'
  },
  valueKey: {
    type: String,
    default: 'id'
  },
  subLabelKey: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Seleccionar...'
  },
  emptyText: {
    type: String,
    default: 'No se encontraron resultados'
  },
  hasError: {
    type: Boolean,
    default: false
  }
})

const emit = defineEmits(['update:modelValue', 'change'])

const isOpen = ref(false)
const searchTerm = ref('')
const containerRef = ref(null)

// Sincronizar el searchTerm con el valor seleccionado inicialmente
const syncSearchTerm = () => {
  if (props.modelValue) {
    const selected = props.options.find(opt => opt[props.valueKey] === props.modelValue)
    if (selected) {
      searchTerm.value = selected[props.labelKey]
      return
    }
  }
  // Si no hay valor o no se encuentra, perosearchTerm tiene algo y el dropdown está cerrado, 
  // tal vez queramos limpiarlo si no coincide con nada.
}

watch(() => props.modelValue, syncSearchTerm, { immediate: true })
watch(() => props.options, syncSearchTerm)

const filteredOptions = computed(() => {
  if (!searchTerm.value || (props.modelValue && searchTerm.value === getLabelByValue(props.modelValue))) {
    return props.options
  }
  const s = searchTerm.value.toLowerCase()
  return props.options.filter(opt => {
    const label = String(opt[props.labelKey] || '').toLowerCase()
    const subLabel = props.subLabelKey ? String(opt[props.subLabelKey] || '').toLowerCase() : ''
    return label.includes(s) || subLabel.includes(s)
  })
})

function getLabelByValue(val) {
  const opt = props.options.find(o => o[props.valueKey] === val)
  return opt ? opt[props.labelKey] : ''
}

function handleFocus() {
  isOpen.value = true
  // Al ganar foco, si ya hay una selección, permitimos ver todas las opciones
}

function handleSearch() {
  isOpen.value = true
}

function selectOption(option) {
  searchTerm.value = option[props.labelKey]
  emit('update:modelValue', option[props.valueKey])
  emit('change', option)
  isOpen.value = false
}

function closeDropdown() {
  isOpen.value = false
  // Si cerramos sin seleccionar y lo que hay escrito no coincide con la selección actual, reseteamos
  syncSearchTerm()
}

// Click outside logic
const handleClickOutside = (event) => {
  if (containerRef.value && !containerRef.value.contains(event.target)) {
    closeDropdown()
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onUnmounted(() => document.removeEventListener('click', handleClickOutside))
</script>

<style scoped>
.combobox-container {
  position: relative;
  width: 100%;
}

.combobox-wrapper {
  position: relative;
}

.combobox-wrapper input {
  padding-right: 35px;
}

.combobox-icon {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--c-text-muted);
  font-size: 0.85rem;
  pointer-events: none;
  transition: transform 0.2s;
}

.combobox-dropdown {
  position: absolute;
  top: calc(100% + 5px);
  left: 0;
  right: 0;
  background: #fff;
  border: 1px solid var(--c-border);
  border-radius: var(--radius-md);
  z-index: 1000;
  max-height: 250px;
  overflow-y: auto;
  padding: 4px;
}

.combobox-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 12px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: all 0.2s;
  margin-bottom: 2px;
}

.combobox-item:hover {
  background-color: var(--c-primary-50);
}

.combobox-item.active {
  background-color: var(--c-primary-100);
  color: var(--c-primary-700);
}

.combobox-item-content {
  display: flex;
  flex-direction: column;
}

.combobox-item-label {
  font-size: 0.875rem;
  font-weight: 500;
}

.combobox-item-sub {
  font-size: 0.75rem;
  color: var(--c-text-muted);
}

.check-icon {
  font-size: 0.8rem;
  color: var(--c-primary-600);
}

.combobox-empty {
  padding: 15px;
  text-align: center;
  font-size: 0.85rem;
  color: var(--c-text-muted);
}

/* Transitions */
.slide-fade-enter-active {
  transition: all 0.2s ease-out;
}
.slide-fade-leave-active {
  transition: all 0.1s cubic-bezier(1, 0.5, 0.8, 1);
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  transform: translateY(-10px);
  opacity: 0;
}

/* Integration with form-control */
.is-invalid {
  border-color: #ef4444 !important;
}
</style>
