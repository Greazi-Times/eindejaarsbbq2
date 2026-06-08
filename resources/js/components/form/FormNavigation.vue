<script setup lang="ts">
import Button from '@/components/ui/Button.vue';

interface Props {
    nextLabel?: string;
    previousLabel?: string;
    showPrevious?: boolean;
    showNext?: boolean;
    nextDisabled?: boolean;
    nextLoading?: boolean;
}

withDefaults(defineProps<Props>(), {
    nextLabel: 'Verder',
    previousLabel: 'Terug',
    showPrevious: false,
    showNext: true,
    nextDisabled: false,
    nextLoading: false,
});

const emit = defineEmits<{
    next: [];
    previous: [];
}>();
</script>

<template>
    <div
        class="mt-2 flex shrink-0 items-center justify-between border-t border-white/10 pt-6"
    >
        <div>
            <Button
                v-if="showPrevious"
                variant="outline"
                @click="emit('previous')"
            >
                {{ previousLabel }}
            </Button>
        </div>

        <div class="ml-auto">
            <Button
                v-if="showNext"
                variant="primary"
                :disabled="nextDisabled"
                :loading="nextLoading"
                @click="emit('next')"
            >
                {{ nextLabel }}
            </Button>
        </div>
    </div>
</template>
