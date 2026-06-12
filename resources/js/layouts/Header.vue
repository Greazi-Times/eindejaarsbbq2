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
        class="fixed top-0 left-0 z-50 w-full border-b border-white/10 bg-header/95 backdrop-blur-xl"
    >
        <div
            class="mx-auto flex min-h-16 w-[88%] items-center lg:min-h-[4.5rem]"
        >
            <div
                class="flex w-full min-w-0 items-center gap-[clamp(0.75rem,1.5vw,1.25rem)]"
            >
                <!-- Left: Logo -->
                <div class="flex min-w-0 items-center gap-2.5">
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-primary to-secondary text-white shadow-lg shadow-primary/20 lg:h-10 lg:w-10"
                    >
                        <PhHamburger :size="22" weight="fill" />
                    </div>
                    <span
                        class="truncate text-sm font-bold tracking-wide text-white lg:text-base"
                        >EindejaarsBBQ.nl</span
                    >
                </div>

                <!-- Center: Navigation -->
                <nav
                    class="ml-[clamp(1.25rem,2.25vw,3rem)] hidden items-center gap-[clamp(1.25rem,2vw,2.25rem)] text-[0.8125rem] font-semibold text-white/70 lg:flex xl:text-sm"
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
                        class="hidden items-center space-x-3 text-white/60 lg:flex"
                    >
                        <a
                            class="rounded-md p-2 text-secondary transition hover:bg-white/10 hover:text-white"
                            href="https://github.com/Greazi-Times/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <PhGithubLogo :size="22" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped></style>
