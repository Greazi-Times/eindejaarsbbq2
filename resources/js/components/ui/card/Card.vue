<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
    class?: HTMLAttributes["class"]
}>()
</script>

<template>
    <div
        data-slot="card"
        :class="
      cn(
        'flex flex-col gap-4 rounded-xl border border-border bg-card backdrop-blur-md p-4 lg:col-span-1',
        props.class,
      )
    "
    >
        <slot />
    </div>
</template>
