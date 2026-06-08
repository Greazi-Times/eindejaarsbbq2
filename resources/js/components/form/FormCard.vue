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
            class="flex w-full flex-col overflow-hidden rounded-3xl border border-white/15 bg-card/95 px-5 py-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:px-8 sm:py-8"
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
