<script setup lang="ts">
import { onMounted, onBeforeUnmount, watch } from 'vue'
import { PhXCircle } from '@phosphor-icons/vue';

interface Props {
  modelValue: boolean
  closeOnBackdrop?: boolean
  size?: 'sm' | 'md' | 'lg'
}

const props = withDefaults(defineProps<Props>(), {
  closeOnBackdrop: true,
  size: 'md',
})

const emit = defineEmits(['update:modelValue'])

const close = () => {
  emit('update:modelValue', false)
}

const sizes = {
  sm: 'max-w-sm',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
}

const onKey = (e: KeyboardEvent) => {
  if (e.key === 'Escape') close()
}

onMounted(() => {
  document.addEventListener('keydown', onKey)
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', onKey)
})

watch(
  () => props.modelValue,
  (value) => {
    if (value) {
      document.body.style.overflow = 'hidden'
    } else {
      document.body.style.overflow = ''
    }
  }
)
</script>

<template>
  <Transition name="fade">
    <div v-if="props.modelValue" class="fixed inset-0 z-50 flex items-center justify-center">
      <!-- backdrop -->
      <div
        class="absolute inset-0 bg-black/50"
        @click="props.closeOnBackdrop && close()"
      />

      <!-- modal -->
      <div :class="['relative z-10 w-full rounded-lg bg-modal-background text-modal-foreground shadow-lg', sizes[props.size]]">
        <!-- header -->
        <div v-if="$slots.header" class="p-4 border-b border-border flex justify-between items-center">
          <slot name="header" />
          <button
            class="text-modal-foreground/60 hover:text-modal-foreground"
            @click="close"
          >
              <PhXCircle :size="24" color="#ffffff" />
          </button>
        </div>

        <!-- content -->
        <div class="p-4">
          <slot />
        </div>

        <!-- footer -->
        <div v-if="$slots.footer" class="p-4 border-t border-border flex justify-end gap-2">
          <slot name="footer" />
        </div>
      </div>
    </div>
  </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
