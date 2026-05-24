<script setup lang="ts">
interface Props {
    modelValue?: boolean;
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: false,
    id: undefined,
    name: undefined,
    label: undefined,
    description: undefined,
    disabled: false,
    required: false,
    error: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: boolean];
}>();

const updateValue = (event: Event) => {
    emit(
        'update:modelValue',
        (event.target as HTMLInputElement).checked,
    );
};
</script>

<template>
    <div>
        <label
            class="flex cursor-pointer items-start gap-3"
            :for="props.id"
        >
            <input
                :id="props.id"
                :name="props.name"
                type="checkbox"
                :checked="props.modelValue"
                :disabled="props.disabled"
                :required="props.required"
                :aria-disabled="props.disabled"
                :aria-invalid="Boolean(props.error)"
                class="peer sr-only"
                @change="updateValue"
            />

            <span
                class="mt-1 flex h-5 w-5 shrink-0 items-center justify-center rounded-md border border-border bg-background text-transparent transition peer-checked:border-primary peer-checked:bg-primary peer-checked:text-primary-foreground peer-focus-visible:ring-2 peer-focus-visible:ring-primary/20 peer-disabled:cursor-not-allowed peer-disabled:opacity-50"
                aria-hidden="true"
            >
                <svg
                    class="h-4 w-4"
                    viewBox="0 0 20 20"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M16.25 5.75L8.25 13.75L4.25 9.75"
                        stroke="currentColor"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
            </span>

            <div>
                <div
                    v-if="props.label"
                    class="text-sm font-medium text-foreground"
                >
                    {{ props.label }}

                    <span
                        v-if="props.required"
                        class="text-red-500"
                    >
                        *
                    </span>
                </div>

                <p
                    v-if="props.description"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ props.description }}
                </p>
            </div>
        </label>

        <p
            v-if="props.error"
            class="mt-2 text-sm text-red-500"
        >
            {{ props.error }}
        </p>
    </div>
</template>

<style scoped></style>
