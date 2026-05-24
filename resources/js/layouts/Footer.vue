<script setup lang="ts">
import { PhGithubLogo } from '@phosphor-icons/vue';

defineProps<{
    partners?: {
        id: number;
        name: string;
        website: string | null;
    }[];
    verenigingen?: {
        id: number;
        name: string;
        website: string | null;
    }[];
}>();
</script>

<template>
    <div class="bg-gray-500/8">
        <footer
            class="relative mx-auto max-w-[80%] px-6 py-16 text-white md:px-12"
        >
            <!-- Top -->
            <div class="grid grid-cols-1 gap-10 md:grid-cols-6">
                <!-- Brand -->
                <div class="flex flex-col gap-6 md:col-span-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-8 w-8 rounded-md bg-gradient-to-br from-primary to-secondary"
                        ></div>
                        <span class="text-lg font-semibold"
                            >Eindejaars BBQ</span
                        >
                    </div>

                    <p class="max-w-sm text-white/70">
                        De beste barbeceu van het jaar, georganiseerd door de
                        studievereningingen van Avans ATIx
                    </p>

                    <div class="flex items-center gap-4 text-white/70">
                        <a
                            class="text-secondary hover:text-primary"
                            href="https://github.com/Greazi-Times/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <PhGithubLogo :size="24" />
                        </a>
                    </div>
                </div>

                <!-- Columns -->
                <div
                    class="grid grid-cols-2 gap-8 sm:grid-cols-2 md:col-span-3 md:grid-cols-3 lg:col-span-4 lg:grid-cols-3"
                >
                    <div class="flex flex-col gap-3">
                        <span class="text-sm text-white/60">Partners</span>

                        <template v-if="partners?.length">
                            <a
                                v-for="partner in partners"
                                :key="partner.id"
                                :href="partner.website || undefined"
                                :target="partner.website ? '_blank' : undefined"
                                :rel="partner.website ? 'noopener noreferrer' : undefined"
                                class="hover:text-white"
                            >
                                {{ partner.name }}
                            </a>
                        </template>

                        <span v-else class="text-white/50">
                            Geen partners gekoppeld
                        </span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <span class="text-sm text-white/60">Verenigingen</span>

                        <template v-if="verenigingen?.length">
                            <a
                                v-for="vereniging in verenigingen"
                                :key="vereniging.id"
                                :href="vereniging.website || undefined"
                                :target="vereniging.website ? '_blank' : undefined"
                                :rel="vereniging.website ? 'noopener noreferrer' : undefined"
                                class="hover:text-white"
                            >
                                {{ vereniging.name }}
                            </a>
                        </template>

                        <span v-else class="text-white/50">
                            Geen verenigingen gekoppeld
                        </span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <span class="text-sm text-white/60">Legal</span>
                        <a class="hover:text-white">Terms</a>
                        <a class="hover:text-white">Privacy</a>
                        <a class="hover:text-white">Cookies</a>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="mt-12 h-px w-full bg-white/10"></div>

            <!-- Bottom -->
            <div
                class="mt-6 flex flex-col gap-4 text-white/60 md:flex-row md:items-center md:justify-between"
            >
                <span>© 2026 Greazi. All rights reserved.</span>
                <span>Een beter feest bestaat niet!</span>
            </div>
        </footer>
    </div>
</template>

<style scoped></style>
