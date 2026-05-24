<script setup lang="ts">
interface Props {
    title?: string;
    height?: string;
}

withDefaults(defineProps<Props>(), {
    title: undefined,
    height: '640px',
});
</script>

<template>
    <div class="mx-auto w-full max-w-5xl px-4">
        <div
            class="flex w-full flex-col overflow-hidden rounded-4xl border-2 bg-card px-8 py-10 shadow-sm"
            :style="{ height }"
        >
            <div v-if="title" class="mb-8 text-center">
                <h2 class="text-lg font-semibold text-foreground">
                    {{ title }}
                </h2>
            </div>

            <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
                <slot />
            </div>
        </div>
    </div>
</template>
