<script setup lang="ts">
import { ref } from 'vue'

type AlertType = 'success' | 'error' | 'warning' | 'info'

interface Props {
  type?: AlertType
  title?: string
  dismissible?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  type: 'info',
  title: '',
  dismissible: false,
})

const visible = ref(true)

const close = () => {
  visible.value = false
}

const types = {
  success: 'bg-[var(--success)] text-white border-[var(--success)]',
  error: 'bg-[var(--danger)] text-white border-[var(--danger)]',
  warning: 'bg-[var(--warning)] text-black border-[var(--warning)]',
  info: 'bg-primary text-white border-primary',
}
</script>

<template>
  <div v-if="visible" :class="['w-full rounded-md border px-4 py-3 flex gap-3 items-start', types[props.type]]">
    <div class="flex-1">
      <div v-if="props.title" class="font-semibold mb-1">
        {{ props.title }}
      </div>

      <div class="text-sm">
        <slot />
      </div>
    </div>

    <button
      v-if="props.dismissible"
      class="ml-2 text-white/70 hover:text-white"
      @click="close"
    >
      ✕
    </button>
  </div>
</template>

<style scoped></style>
