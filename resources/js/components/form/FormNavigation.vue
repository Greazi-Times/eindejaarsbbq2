<script setup lang="ts">
import Button from '@/components/ui/Button.vue';

interface Props {
    nextLabel?: string;
    previousLabel?: string;
    showPrevious?: boolean;
    showNext?: boolean;
}

withDefaults(defineProps<Props>(), {
    nextLabel: 'Continue',
    previousLabel: 'Back',
    showPrevious: false,
    showNext: true,
});

const emit = defineEmits<{
    next: [];
    previous: [];
}>();
</script>

<template>
    <div class="flex shrink-0 items-center justify-between pt-8">
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
                @click="emit('next')"
            >
                {{ nextLabel }}
            </Button>
        </div>
    </div>
</template>
