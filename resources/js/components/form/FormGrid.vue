<script setup lang="ts">
interface Props {
    columns?: 1 | 2 | 3;
}

withDefaults(defineProps<Props>(), {
    columns: 2,
});
</script>

<template>
    <div
        class="grid gap-6"
        :class="{
            'md:grid-cols-1': columns === 1,
            'md:grid-cols-2': columns === 2,
            'md:grid-cols-3': columns === 3,
        }"
    >
        <slot />
    </div>
</template>
