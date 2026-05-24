<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <div
        data-slot="card-title"
        :class="cn('gap-1 text-foreground font-semibold', props.class)"
    >
        <slot />
    </div>
</template>
