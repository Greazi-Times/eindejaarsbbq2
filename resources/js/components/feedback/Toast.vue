<script setup lang="ts">
import { ref } from 'vue'

type ToastType = 'success' | 'danger' | 'warning' | 'info'

interface Toast {
  id: number
  message: string
  type: ToastType
}

const toasts = ref<Toast[]>([])
let id = 0
const timers = new Map<number, number>()

const addToast = (message: string, type: ToastType = 'info') => {
  const toast: Toast = {
    id: id++,
    message,
    type,
  }

  toasts.value.push(toast)

  const timeout = window.setTimeout(() => {
    removeToast(toast.id)
  }, 3000)

  timers.set(toast.id, timeout)
}

const removeToast = (toastId: number) => {
  const timeout = timers.get(toastId)

  if (timeout) {
    clearTimeout(timeout)
  }

  timers.delete(toastId)

  toasts.value = toasts.value.filter(t => t.id !== toastId)
}

const pauseToast = (toastId: number) => {
  const timeout = timers.get(toastId)

  if (timeout) {
      clearTimeout(timeout)
  }
}

defineExpose({
  addToast,
})

const types = {
  success: 'bg-[var(--success)] text-white',
  danger: 'bg-[var(--danger)] text-white',
  warning: 'bg-[var(--warning)] text-black',
  info: 'bg-primary text-white',
}
</script>

<template>
  <div class="fixed top-6 right-4 z-50 flex flex-col gap-3">
    <TransitionGroup name="toast" tag="div">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        :class="[
          'px-4 py-4 rounded-md shadow-md flex items-start gap-3 transition relative overflow-hidden',
          types[toast.type],
        ]"
        @mouseenter="pauseToast(toast.id)"
      >
        <div v-if="$slots.icon" class="mt-0.5">
          <slot name="icon" :type="toast.type" />
        </div>
        <span class="flex-1 leading-tight">{{ toast.message }}</span>
        <div class="absolute bottom-0 left-0 h-1 w-full bg-white/20">
          <div class="h-full bg-white/60 animate-[shrink_3s_linear]"></div>
        </div>
        <button
          class="text-white/70 hover:text-white"
          @click="removeToast(toast.id)"
        >
          ✕
        </button>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.2s ease;
}
.toast-enter-from {
  opacity: 0;
  transform: translateY(-10px);
}
.toast-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

@keyframes shrink {
  from {
    width: 100%;
  }
  to {
    width: 0%;
  }
}
</style>
