<script setup lang="ts">
import { PhGithubLogo, PhHamburger } from '@phosphor-icons/vue';

const scrollToSection = (sectionId: string) => {
    const section = document.getElementById(sectionId);

    if (!section) {
        window.location.href = `/#${sectionId}`;

        return;
    }

    section.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const scrollToTop = () => {
    if (window.location.pathname !== '/') {
        window.location.href = '/';

        return;
    }

    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};
</script>

<template>
    <header
        class="fixed top-0 left-0 z-50 w-full border-b border-white/10 bg-header/90 backdrop-blur-xl"
    >
        <div
            class="flex min-h-16 w-full items-center px-4 sm:px-6 lg:min-h-20 lg:px-8"
        >
            <div class="flex w-full min-w-0 items-center gap-4 lg:gap-6">
                <!-- Left: Logo -->
                <div class="flex min-w-0 items-center gap-3">
                    <div
                        class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-primary to-secondary text-white shadow-lg shadow-primary/20"
                    >
                        <PhHamburger :size="24" weight="fill" />
                    </div>
                    <span
                        class="truncate text-sm font-bold tracking-wide text-white sm:text-base"
                        >EindejaarsBBQ.nl</span
                    >
                </div>

                <!-- Center: Navigation -->
                <nav
                    class="ml-4 hidden items-center gap-5 text-sm font-semibold text-white/70 lg:flex xl:ml-8 xl:gap-8"
                >
                    <a
                        href="#"
                        class="transition hover:text-white"
                        @click.prevent="scrollToTop"
                    >
                        Home
                    </a>

                    <a
                        href="#aanmelden"
                        class="transition hover:text-white"
                        @click.prevent="scrollToSection('aanmelden')"
                    >
                        Aanmelden
                    </a>

                    <a
                        href="#verenigingen"
                        class="transition hover:text-white"
                        @click.prevent="scrollToSection('verenigingen')"
                    >
                        Verenigingen
                    </a>
                </nav>

                <!-- Right: Actions -->
                <div class="ml-auto flex items-center space-x-6">
                    <!-- Icons -->
                    <div
                        class="hidden items-center space-x-4 text-white/60 lg:flex"
                    >
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
            </div>
        </div>
    </header>
</template>

<style scoped></style>
