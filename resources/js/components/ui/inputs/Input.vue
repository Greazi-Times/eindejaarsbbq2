<script setup lang="ts">
import { computed, watch } from 'vue';
interface Props {
    modelValue?: string;
    type?: 'text' | 'email' | 'tel' | 'password' | 'number';
    placeholder?: string;
    disabled?: boolean;
    id?: string;
    name?: string;
    label?: string;
    error?: string;
    required?: boolean;
    min?: number | string;
    max?: number | string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    placeholder: '',
    disabled: false,
    id: undefined,
    name: undefined,
    label: undefined,
    error: undefined,
    required: false,
    min: undefined,
    max: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
    'validation-change': [payload: {
        name?: string;
        valid: boolean;
        error?: string;
    }];
}>();

const base =
    'w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground outline-none transition';

const states =
    'focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50';

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const getInternalError = (value: string) => {
    if (props.type === 'email' && value && !emailRegex.test(value)) {
        return 'Please enter a valid email address.';
    }

    return undefined;
};

const internalError = computed(() => getInternalError(props.modelValue));

const displayedError = computed(() => internalError.value || props.error);

const emitValidation = (value: string) => {
    const error = getInternalError(value);

    emit('validation-change', {
        name: props.name,
        valid: !error,
        error,
    });
};

const updateValue = (event: Event) => {
    const value = (event.target as HTMLInputElement).value;

    emit('update:modelValue', value);
    emitValidation(value);
};

watch(
    () => props.modelValue,
    (value) => {
        emitValidation(value);
    },
    { immediate: true },
);
</script>

<template>
    <div>
        <label
            v-if="props.label"
            class="mb-2 block text-sm font-medium text-foreground"
            :for="props.id"
        >
            {{ props.label }}

            <span
                v-if="props.required"
                class="text-red-500"
            >
                *
            </span>
        </label>

        <input
            :id="props.id"
            :name="props.name"
            :type="props.type"
            :value="props.modelValue"
            :placeholder="props.placeholder"
            :disabled="props.disabled"
            :required="props.required"
            :min="props.min"
            :max="props.max"
            :aria-disabled="props.disabled"
            :aria-invalid="Boolean(displayedError)"
            :aria-describedby="displayedError && props.id ? `${props.id}-error` : undefined"
            :class="[
                base,
                states,
                displayedError
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-border focus:border-primary focus:ring-primary/20',
            ]"
            @input="updateValue"
        />

        <p
            v-if="displayedError"
            :id="props.id ? `${props.id}-error` : undefined"
            class="mt-2 text-sm text-red-500"
        >
            {{ displayedError }}
        </p>
    </div>
</template>

<style scoped></style>
