<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

interface Props {
    title?: string;
    description?: string;
}

defineProps<Props>();

const section = ref<HTMLElement | null>(null);
const content = ref<HTMLElement | null>(null);
const hasMoreBelow = ref(false);
let resizeObserver: ResizeObserver | null = null;

const updateScrollHint = () => {
    const element = section.value;

    if (!element) {
        hasMoreBelow.value = false;

        return;
    }

    hasMoreBelow.value =
        element.scrollHeight - element.scrollTop - element.clientHeight > 12;
};

onMounted(() => {
    nextTick(updateScrollHint);

    section.value?.addEventListener('scroll', updateScrollHint, {
        passive: true,
    });
    window.addEventListener('resize', updateScrollHint);

    if (typeof ResizeObserver !== 'undefined') {
        resizeObserver = new ResizeObserver(updateScrollHint);

        if (section.value) {
            resizeObserver.observe(section.value);
        }

        if (content.value) {
            resizeObserver.observe(content.value);
        }
    }
});

onBeforeUnmount(() => {
    section.value?.removeEventListener('scroll', updateScrollHint);
    window.removeEventListener('resize', updateScrollHint);
    resizeObserver?.disconnect();
});
</script>

<template>
    <div
        ref="section"
        class="mx-auto min-h-0 w-full max-w-4xl flex-1 overflow-y-auto pr-1 sm:pr-2"
    >
        <div ref="content">
            <div v-if="title || description" class="mb-8 text-center">
                <h2 v-if="title" class="text-2xl font-semibold text-foreground">
                    {{ title }}
                </h2>

                <p
                    v-if="description"
                    class="mx-auto mt-2 max-w-xl text-sm leading-6 text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>

            <div class="space-y-6">
                <slot />
            </div>
        </div>

        <div
            v-if="hasMoreBelow"
            class="pointer-events-none sticky bottom-0 -mx-2 mt-2 flex justify-center bg-gradient-to-t from-card via-card/95 to-transparent pt-10 pb-1"
        >
            <div
                class="rounded-full border border-secondary/40 bg-background/90 px-4 py-2 text-xs font-semibold text-secondary shadow-lg shadow-black/20"
            >
                Meer hieronder
                <span aria-hidden="true">&darr;</span>
            </div>
        </div>
    </div>
</template>
