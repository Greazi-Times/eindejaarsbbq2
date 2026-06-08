<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    PhArrowLeft,
    PhCookie,
    PhFileText,
    PhShieldCheck,
} from '@phosphor-icons/vue';
import Footer from '@/layouts/Footer.vue';
import Header from '@/layouts/Header.vue';

type PolicySection = {
    title: string;
    body: string[];
};

type Policy = {
    slug: 'terms' | 'privacy' | 'cookies';
    eyebrow: string;
    title: string;
    summary: string;
    sections: PolicySection[];
};

defineProps<{
    updatedAt: string;
    contactEmail: string;
    policies: Policy[];
}>();

const policyIcons = {
    terms: PhFileText,
    privacy: PhShieldCheck,
    cookies: PhCookie,
};
</script>

<template>
    <Head title="Legal" />

    <div class="min-h-screen overflow-hidden bg-background text-white">
        <Header />

        <main>
            <section
                class="relative overflow-hidden border-b border-white/10 pt-28 pb-12 md:pt-36 md:pb-16"
            >
                <div
                    class="absolute inset-0 bg-[url('/images/hero-3.jpg')] bg-cover bg-center opacity-20"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-b from-background/80 via-background/92 to-background"
                />

                <div class="relative mx-auto max-w-5xl px-4 sm:px-6">
                    <a
                        href="/"
                        class="inline-flex items-center gap-2 text-sm font-semibold text-secondary transition hover:text-white"
                    >
                        <PhArrowLeft :size="18" weight="bold" />
                        Terug naar home
                    </a>

                    <p
                        class="mt-8 text-sm font-bold tracking-wide text-secondary uppercase"
                    >
                        Laatst bijgewerkt: {{ updatedAt }}
                    </p>

                    <h1
                        class="mt-4 max-w-3xl text-4xl font-semibold text-white md:text-6xl"
                    >
                        Legal
                    </h1>

                    <p class="mt-5 max-w-3xl text-lg leading-8 text-white/70">
                        Here you can find the Terms of Service, Privacy Policy,
                        and Cookie Policy for Eindejaars BBQ.
                    </p>

                    <nav
                        class="mt-8 grid gap-3 text-sm font-semibold sm:grid-cols-3"
                        aria-label="Legal navigatie"
                    >
                        <a
                            v-for="policy in policies"
                            :key="policy.slug"
                            :href="`#${policy.slug}`"
                            class="flex min-h-14 items-center gap-3 rounded-lg border border-white/10 bg-white/8 px-4 py-3 text-white/75 transition hover:border-secondary/45 hover:bg-white/12 hover:text-white"
                        >
                            <component
                                :is="policyIcons[policy.slug]"
                                :size="22"
                                weight="bold"
                                class="shrink-0 text-secondary"
                            />
                            {{ policy.title }}
                        </a>
                    </nav>
                </div>
            </section>

            <section class="mx-auto max-w-5xl px-4 py-12 sm:px-6 md:py-16">
                <div class="space-y-10">
                    <article
                        v-for="policy in policies"
                        :id="policy.slug"
                        :key="policy.slug"
                        class="scroll-mt-28 rounded-lg border border-white/10 bg-white/5 p-5 shadow-2xl shadow-black/10 sm:p-8"
                    >
                        <div
                            class="flex flex-col gap-5 border-b border-white/10 pb-6 md:flex-row md:items-start"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-secondary text-secondary-foreground"
                            >
                                <component
                                    :is="policyIcons[policy.slug]"
                                    :size="26"
                                    weight="bold"
                                />
                            </div>

                            <div>
                                <p
                                    class="text-sm font-bold tracking-wide text-secondary uppercase"
                                >
                                    {{ policy.eyebrow }}
                                </p>
                                <h2
                                    class="mt-2 text-2xl font-semibold text-white md:text-3xl"
                                >
                                    {{ policy.title }}
                                </h2>
                                <p
                                    class="mt-3 max-w-3xl leading-7 text-white/65"
                                >
                                    {{ policy.summary }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-7 grid gap-7">
                            <section
                                v-for="section in policy.sections"
                                :key="section.title"
                            >
                                <h3 class="text-lg font-semibold text-white">
                                    {{ section.title }}
                                </h3>
                                <div class="mt-3 space-y-3">
                                    <p
                                        v-for="paragraph in section.body"
                                        :key="paragraph"
                                        class="leading-7 text-white/68"
                                    >
                                        {{ paragraph }}
                                    </p>
                                </div>
                            </section>
                        </div>
                    </article>
                </div>

                <div
                    class="mt-10 rounded-lg border border-secondary/25 bg-secondary/10 p-5 text-sm leading-6 text-white/72"
                >
                    This text follows the same legal information structure as
                    ATIx Bedrijvendag, adapted for Eindejaars BBQ. Please review
                    it if the organizational, hosting, or contact details
                    change.
                    <a
                        :href="`mailto:${contactEmail}`"
                        class="font-semibold text-secondary transition hover:text-white"
                    >
                        {{ contactEmail }}
                    </a>
                </div>
            </section>
        </main>

        <Footer />
    </div>
</template>
