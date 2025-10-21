<template>
  <teleport to="body">
    <transition name="fade">
      <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" @click="$emit('update:modelValue', false)"></div>
        <div class="relative z-10 w-full max-w-sm rounded-2xl bg-white shadow-2xl ring-1 ring-black/5">
          <div class="p-5">
            <h3 class="text-base font-semibold text-gray-900 mb-2">{{ title }}</h3>
            <p class="text-sm text-gray-600">{{ message }}</p>
          </div>
          <div class="px-5 pb-5 pt-2 flex justify-end gap-2">
            <button type="button" class="px-4 h-9 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50" @click="$emit('cancel')">
              {{ cancelText }}
            </button>
            <button type="button" class="px-4 h-9 rounded-md bg-rose-600 text-white hover:bg-rose-700" @click="$emit('confirm')">
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup>
import { defineProps, defineEmits } from 'vue'

defineProps({
  modelValue: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar' },
  message: { type: String, default: 'Tem certeza?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
})

defineEmits(['update:modelValue', 'confirm', 'cancel'])
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
