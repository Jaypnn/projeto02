<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="emitClose"></div>
        <div class="relative z-10 w-full max-w-2xl rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
            <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
          </div>

          <form @submit.prevent="onConfirm" class="p-6 space-y-5">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nome</label>
              <input v-model.trim="form.name" type="text" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" required />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Ícone</label>
              <IconPicker v-model="form.icon" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
              <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="emitClose">{{ cancelText }}</button>
              <button type="submit" class="px-4 h-9 rounded-md bg-blue-600 text-white hover:bg-blue-700">{{ confirmText }}</button>
            </div>
          </form>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { reactive, watch } from 'vue'
import IconPicker from './IconPicker.vue'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Nova Categoria' },
  subtitle: { type: String, default: '' },
  confirmText: { type: String, default: 'Salvar' },
  cancelText: { type: String, default: 'Cancelar' },
  category: { type: Object, default: null }
})

const emit = defineEmits(['update:modelValue', 'confirm'])

const form = reactive({
  name: '',
  icon: 'tag',
})

watch(() => props.modelValue, (open) => {
  if (open) {
    if (props.category) {
      form.name = props.category.name || ''
      form.icon = props.category.icon || 'tag'
    } else {
      form.name = ''
      form.icon = 'tag'
    }
  }
})

function emitClose() {
  emit('update:modelValue', false)
}

function onConfirm() {
  emit('confirm', { ...form })
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
