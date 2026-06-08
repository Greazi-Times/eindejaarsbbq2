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
    <div
        class="mb-8 grid grid-cols-4 gap-2 sm:flex sm:items-center sm:justify-between sm:gap-4"
    >
        <template v-for="(step, index) in steps" :key="step.label">
            <div
                class="flex min-w-0 flex-col items-center gap-2 text-center sm:flex-row sm:text-left"
                :class="
                    index + 1 === currentStep
                        ? 'text-primary'
                        : index + 1 < currentStep
                          ? 'text-secondary'
                          : 'text-muted-foreground'
                "
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-semibold transition"
                    :class="
                        index + 1 === currentStep
                            ? 'border-primary bg-primary text-white shadow-lg shadow-primary/25'
                            : index + 1 < currentStep
                              ? 'border-secondary bg-secondary text-secondary-foreground'
                              : 'border-white/15 bg-white/5 text-muted-foreground'
                    "
                >
                    {{ index + 1 }}
                </span>

                <span
                    class="hidden truncate text-xs font-semibold sm:inline sm:text-sm"
                >
                    {{ step.label }}
                </span>
            </div>

            <div
                v-if="index !== steps.length - 1"
                class="hidden h-px flex-1 bg-white/15 sm:block"
            />
        </template>
    </div>
</template>
