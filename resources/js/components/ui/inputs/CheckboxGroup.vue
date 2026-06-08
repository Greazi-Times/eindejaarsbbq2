<script setup lang="ts">
import Checkbox from '@/components/ui/inputs/Checkbox.vue';

interface Option {
    label: string;
    value: string;
    description?: string;
}

interface Props {
    modelValue?: string[];
    label?: string;
    description?: string;
    options: Option[];
    disabled?: boolean;
    required?: boolean;
    error?: string;
    max?: number;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: () => [],
    label: undefined,
    description: undefined,
    disabled: false,
    required: false,
    error: undefined,
    max: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: string[]];
}>();

const isChecked = (value: string) => {
    return props.modelValue.includes(value);
};

const toggleValue = (value: string, checked: boolean) => {
    let values = [...props.modelValue];

    if (checked) {
        if (props.max === 1) {
            values = [value];
        } else {
            if (props.max && values.length >= props.max) {
                return;
            }

            if (!values.includes(value)) {
                values.push(value);
            }
        }
    } else {
        values = values.filter((item) => item !== value);
    }

    emit('update:modelValue', values);
};
</script>

<template>
    <div>
        <div v-if="props.label" class="mb-3">
            <h3 class="text-sm font-semibold text-foreground">
                {{ props.label }}

                <span
                    v-if="props.required"
                    class="text-red-300"
                >
                    *
                </span>
            </h3>

            <p
                v-if="props.description"
                class="mt-1 text-sm leading-5 text-muted-foreground"
            >
                {{ props.description }}
            </p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <Checkbox
                v-for="option in props.options"
                :key="option.value"
                :model-value="isChecked(option.value)"
                :label="option.label"
                :description="option.description"
                :disabled="props.disabled"
                @update:model-value="toggleValue(option.value, $event)"
            />
        </div>

        <p
            v-if="props.error"
            class="mt-2 text-sm font-medium text-red-300"
        >
            {{ props.error }}
        </p>
    </div>
</template>

<style scoped></style>
