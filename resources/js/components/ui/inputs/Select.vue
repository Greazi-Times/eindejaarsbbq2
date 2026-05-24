<script setup lang="ts">
interface Option {
    label: string;
    value: string;
}

interface Props {
    modelValue?: string;
    id?: string;
    name?: string;
    label?: string;
    description?: string;
    placeholder?: string;
    options: Option[];
    disabled?: boolean;
    required?: boolean;
    error?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    id: undefined,
    name: undefined,
    label: undefined,
    description: undefined,
    placeholder: 'Select an option',
    disabled: false,
    required: false,
    error: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const updateValue = (event: Event) => {
    emit(
        'update:modelValue',
        (event.target as HTMLSelectElement).value,
    );
};
</script>

<template>
    <div>
        <label
            v-if="props.label"
            class="mb-2 block text-sm font-medium text-gray-700"
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
            class="mb-2 text-sm text-muted-foreground"
        >
            {{ props.description }}
        </p>

        <select
            :id="props.id"
            :name="props.name"
            :value="props.modelValue"
            :disabled="props.disabled"
            :required="props.required"
            :aria-disabled="props.disabled"
            :aria-invalid="Boolean(props.error)"
            :class="[
                'w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground outline-none transition',
                'focus:ring-2 disabled:cursor-not-allowed disabled:opacity-50',
                props.error
                    ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                    : 'border-border focus:border-primary focus:ring-primary/20',
            ]"
            @change="updateValue"
        >
            <option
                disabled
                value=""
            >
                {{ props.placeholder }}
            </option>

            <option
                v-for="option in props.options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.label }}
            </option>
        </select>

        <p
            v-if="props.error"
            class="mt-2 text-sm text-red-500"
        >
            {{ props.error }}
        </p>
    </div>
</template>

<style scoped></style>
