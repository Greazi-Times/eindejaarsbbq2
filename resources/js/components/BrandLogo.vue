<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        size?: 'sm' | 'md';
    }>(),
    {
        label: 'EindejaarsBBQ.nl',
        size: 'md',
    },
);
</script>

<template>
    <span class="brand-logo" :class="`brand-logo--${size}`">
        <img
            class="brand-logo__mark"
            src="/favicon.svg"
            alt=""
            aria-hidden="true"
        />

        <span class="brand-logo__label">{{ label }}</span>
    </span>
</template>

<style scoped>
.brand-logo {
    display: inline-flex;
    min-width: 0;
    align-items: center;
    gap: 0.7rem;
}

.brand-logo__mark {
    width: var(--brand-logo-size);
    height: var(--brand-logo-size);
    flex-shrink: 0;
    filter: drop-shadow(0 8px 14px rgba(234, 96, 42, 0.22));
}

.brand-logo__label {
    overflow: hidden;
    color: white;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.brand-logo--sm {
    --brand-logo-size: 2rem;
}

.brand-logo--sm .brand-logo__label {
    font-size: 0.875rem;
}

.brand-logo--md {
    --brand-logo-size: 2.5rem;
}

.brand-logo--md .brand-logo__label {
    font-size: 1.125rem;
}
</style>
