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
    description?: string;
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
    description: undefined,
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
    'w-full rounded-md border bg-white/[0.07] px-4 py-3 text-sm text-foreground outline-none transition placeholder:text-white/35';

const states =
    'focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50';

const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

const getInternalError = (value: string) => {
    if (props.type === 'email' && value && !emailRegex.test(value)) {
        return 'Vul een geldig e-mailadres in.';
    }

    return undefined;
};

const internalError = computed(() => getInternalError(props.modelValue));

const displayedError = computed(() => internalError.value || props.error);

const describedBy = computed(() => {
    if (!props.id) {
        return undefined;
    }

    const ids: string[] = [];

    if (props.description) {
        ids.push(`${props.id}-description`);
    }

    if (displayedError.value) {
        ids.push(`${props.id}-error`);
    }

    return ids.length ? ids.join(' ') : undefined;
});

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
            class="mb-2 block text-sm font-semibold text-foreground"
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

        <p
            v-if="props.description"
            :id="props.id ? `${props.id}-description` : undefined"
            class="mb-2 text-sm leading-5 text-muted-foreground"
        >
            {{ props.description }}
        </p>

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
            :aria-describedby="describedBy"
            :class="[
                base,
                states,
                displayedError
                    ? 'border-red-400 focus:border-red-400 focus:ring-red-400/20'
                    : 'border-white/15 hover:border-white/25 focus:border-primary focus:ring-primary/20',
            ]"
            @input="updateValue"
        />

        <p
            v-if="displayedError"
            :id="props.id ? `${props.id}-error` : undefined"
            class="mt-2 text-sm font-medium text-red-300"
        >
            {{ displayedError }}
        </p>
    </div>
</template>

<style scoped></style>
