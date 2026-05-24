<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
    primary?: string
    secondary?: string
}>()
</script>

<template>
    <div
        data-slot="card-header"
        :class="cn('flex flex-col gap-1 text-xs', props.class)"
    >
        <div class="leading-tight font-semibold text-muted wrap-break-word">
            {{ props.primary}}
        </div>

        <span
            v-if="props.secondary"
            class="text-lg font-medium text-primary self-end whitespace-nowrap"
            >
            {{ props.secondary}}
        </span>
    </div>
</template>



