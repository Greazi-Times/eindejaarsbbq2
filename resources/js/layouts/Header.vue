<script setup lang="ts">
import {
    PhDiscordLogo,
    PhGithubLogo,
    PhHamburger,
    PhPatreonLogo,
} from '@phosphor-icons/vue';
import Button from '@/components/ui/Button.vue';

const scrollToSection = (sectionId: string) => {
    document.getElementById(sectionId)?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const scrollToTop = () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth',
    });
};
</script>

<template>
    <header class="fixed top-0 left-0 z-50 w-full bg-header/8 backdrop-blur-sm">
        <div
            class="mx-auto max-h-20 max-w-[80%] content-center px-6 md:min-h-16 lg:min-h-20"
        >
            <div class="flex items-center gap-6">
                <!-- Left: Logo -->
                <div class="flex items-center space-x-3">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-md bg-gradient-to-br from-primary to-secondary"
                    >
                        <PhHamburger :size="24" weight="fill" />
                    </div>
                    <span class="font-semibold text-white"
                        >EindejaarsBBQ.nl</span
                    >
                </div>

                <!-- Center: Navigation -->
                <nav
                    class="text-md mr-20 ml-20 hidden items-center space-x-12 text-foreground/400 md:flex"
                >
                    <a
                        href="#"
                        class="hover:text-white"
                        @click.prevent="scrollToTop"
                    >
                        Home
                    </a>

                    <a
                        href="#aanmelden"
                        class="hover:text-white"
                        @click.prevent="scrollToSection('aanmelden')"
                    >
                        Aanmelden
                    </a>

                    <a
                        href="#verenigingen"
                        class="hover:text-white"
                        @click.prevent="scrollToSection('verenigingen')"
                    >
                        Verenigingen
                    </a>
                </nav>

                <!-- Right: Actions -->
                <div class="ml-auto flex items-center space-x-6">
                    <!-- Icons -->
                    <div
                        class="hidden items-center space-x-4 text-white/60 md:flex"
                    >
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
            </div>
        </div>
    </header>
</template>

<style scoped></style>
