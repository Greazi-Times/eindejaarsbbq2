<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import { PhImageBroken } from '@phosphor-icons/vue';

const props = defineProps<{
    class?: HTMLAttributes["class"]
    image?: string
    alt?: string
}>()
</script>

<template>
    <div
        data-slot="card-image"
        :class="cn(
            'h-32 min-h-32 max-h-32 w-full overflow-hidden rounded-lg bg-white/10 flex items-center justify-center',
            props.class
        )"
    >
        <!-- Image -->
        <img
            v-if="props.image"
            :src="props.image"
            :alt="props.alt || 'card image'"
            class="h-full w-full object-cover"
        />

        <!-- Fallback (no image) -->
        <slot v-else>
            <div class="content-center"><PhImageBroken :size="64" weight="thin" color="#919191"/></div>
        </slot>
    </div>
</template>
