<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

interface Props {
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'success' | 'warning' | 'danger' | 'link' | 'glass'
    type?: 'button' | 'submit' | 'reset'
    disabled?: boolean
    loading?: boolean
    size?: 'sm' | 'md' | 'lg'
    route?: string
    link?: string
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    type: 'button',
    disabled: false,
    loading: false,
    size: 'md',
})

const isExternal = (url?: string) => !!url

const base =
    'inline-flex items-center justify-center rounded-md font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/30 disabled:cursor-not-allowed disabled:opacity-50'

const variants = {
    primary: 'bg-primary text-primary-foreground shadow-lg shadow-primary/25 hover:bg-primary/90',
    secondary: 'bg-secondary text-secondary-foreground shadow-lg shadow-secondary/20 hover:bg-secondary/90',
    outline: 'border border-white/20 bg-white/5 text-foreground hover:border-primary/60 hover:bg-primary/15',
    ghost: 'bg-transparent text-foreground hover:bg-white/10',
    success: 'bg-[var(--success)] text-primary-foreground hover:opacity-90',
    warning: 'bg-[var(--warning)] text-black hover:opacity-90',
    danger: 'bg-[var(--danger)] text-primary-foreground hover:opacity-90',
    link: 'bg-transparent text-primary underline-offset-4 hover:underline px-0 py-0',
    glass: 'border border-white/20 bg-white/10 px-3 py-1 text-xs text-white/80 backdrop-blur hover:bg-white/20 hover:text-white',
}

const sizes = {
    sm: 'px-3 py-1 text-sm',
    md: 'px-4 py-2',
    lg: 'px-6 py-3 text-base md:text-lg',
}
</script>

<template>
    <component
        :is="props.route ? Link : (props.link ? 'a' : 'button')"
        :href="props.route || props.link"
        :type="props.route || props.link ? undefined : props.type"
        :disabled="props.route || props.link ? undefined : (props.disabled || props.loading)"
        :target="props.link ? '_blank' : undefined"
        :rel="props.link ? 'noopener noreferrer' : undefined"
        :class="[base, sizes[props.size], variants[props.variant], props.variant === 'link' && 'p-0']"
    >
        <svg
            v-if="props.loading"
            class="animate-spin mr-2 h-4 w-4"
            viewBox="0 0 24 24"
            fill="none"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            />
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8H4z"
            />
        </svg>
        <span :class="props.loading && 'opacity-70'">
            <slot />
        </span>
    </component>
</template>
