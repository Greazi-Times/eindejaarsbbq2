<script setup lang="ts">
interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mx-auto min-h-0 w-full max-w-4xl flex-1 overflow-y-auto pr-2">
        <div v-if="title || description" class="mb-8 text-center">
            <h2 v-if="title" class="text-lg font-semibold text-foreground">
                {{ title }}
            </h2>

            <p v-if="description" class="mt-2 text-sm text-muted-foreground">
                {{ description }}
            </p>
        </div>

        <div class="space-y-6">
            <slot />
        </div>
    </div>
</template>
