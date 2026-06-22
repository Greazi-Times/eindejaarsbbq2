<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { PhGithubLogo } from '@phosphor-icons/vue';
import BrandLogo from '@/components/BrandLogo.vue';

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

const page = usePage();
</script>

<template>
    <div class="border-t border-white/10 bg-black/20">
        <footer
            class="relative mx-auto max-w-7xl px-4 py-14 text-white sm:px-6 md:py-16"
        >
            <!-- Top -->
            <div class="grid grid-cols-1 gap-10 md:grid-cols-6">
                <!-- Brand -->
                <div class="flex flex-col gap-6 md:col-span-2">
                    <BrandLogo label="Eindejaars BBQ" />

                    <p class="max-w-sm text-white/70">
                        De beste barbecue van het jaar, georganiseerd door de
                        studieverenigingen van Avans ATIx.
                    </p>

                    <div class="flex items-center gap-4 text-white/70">
                        <a
                            class="rounded-md p-2 text-secondary transition hover:bg-white/10 hover:text-white"
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
                        <span class="text-sm font-semibold text-secondary"
                            >Partners</span
                        >

                        <template v-if="partners?.length">
                            <a
                                v-for="partner in partners"
                                :key="partner.id"
                                :href="partner.website || undefined"
                                :target="partner.website ? '_blank' : undefined"
                                :rel="
                                    partner.website
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                class="text-white/70 transition hover:text-white"
                            >
                                {{ partner.name }}
                            </a>
                        </template>

                        <span v-else class="text-white/50">
                            Geen partners gekoppeld
                        </span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <span class="text-sm font-semibold text-secondary"
                            >Verenigingen</span
                        >

                        <template v-if="verenigingen?.length">
                            <a
                                v-for="vereniging in verenigingen"
                                :key="vereniging.id"
                                :href="vereniging.website || undefined"
                                :target="
                                    vereniging.website ? '_blank' : undefined
                                "
                                :rel="
                                    vereniging.website
                                        ? 'noopener noreferrer'
                                        : undefined
                                "
                                class="text-white/70 transition hover:text-white"
                            >
                                {{ vereniging.name }}
                            </a>
                        </template>

                        <span v-else class="text-white/50">
                            Geen verenigingen gekoppeld
                        </span>
                    </div>

                    <div class="flex flex-col gap-3">
                        <span class="text-sm font-semibold text-secondary"
                            >Legal</span
                        >
                        <a
                            href="/legal#terms"
                            class="text-white/70 transition hover:text-white"
                            >Terms of Service</a
                        >
                        <a
                            href="/legal#privacy"
                            class="text-white/70 transition hover:text-white"
                            >Privacy Policy</a
                        >
                        <a
                            href="/legal#cookies"
                            class="text-white/70 transition hover:text-white"
                            >Cookie Policy</a
                        >
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
                <span class="flex flex-col gap-1 md:items-end">
                    <span>Een beter feest bestaat niet!</span>
                    <span v-if="page.props.version" class="text-xs">
                        Versie {{ page.props.version }}
                    </span>
                </span>
            </div>
        </footer>
    </div>
</template>

<style scoped></style>
