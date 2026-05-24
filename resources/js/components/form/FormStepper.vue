<script setup lang="ts">
interface Step {
    label: string;
}

interface Props {
    steps: Step[];
    currentStep: number;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-12 flex items-center justify-between gap-4">
        <template v-for="(step, index) in steps" :key="step.label">
            <div
                class="flex items-center gap-3"
                :class="
                    index + 1 === currentStep ? 'text-primary' : 'text-muted-foreground'
                "
            >
                <span
                    class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-semibold text-white"
                    :class="
                        index + 1 === currentStep ? 'bg-primary' : 'bg-muted-foreground'
                    "
                >
                    {{ index + 1 }}
                </span>

                <span class="text-sm font-semibold">
                    {{ step.label }}
                </span>
            </div>

            <div
                v-if="index !== steps.length - 1"
                class="h-px flex-1 bg-gray-300"
            />
        </template>
    </div>
</template>
