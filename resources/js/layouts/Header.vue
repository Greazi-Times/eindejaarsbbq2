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
        class="site-header fixed top-0 left-0 z-50 w-full border-b border-white/10 bg-header/95 backdrop-blur-xl"
    >
        <div class="site-header__inner mx-auto flex w-[88%] items-center">
            <div
                class="site-header__content flex w-full min-w-0 items-center gap-[clamp(0.75rem,1.5vw,1.25rem)]"
            >
                <!-- Left: Logo -->
                <div class="site-header__brand flex min-w-0 items-center">
                    <div
                        class="site-header__mark flex shrink-0 items-center justify-center rounded-md bg-gradient-to-br from-primary to-secondary text-white shadow-lg shadow-primary/20"
                    >
                        <PhHamburger
                            class="site-header__mark-icon"
                            weight="fill"
                        />
                    </div>
                    <span
                        class="site-header__title truncate font-bold tracking-wide text-white"
                        >EindejaarsBBQ.nl</span
                    >
                </div>

                <!-- Center: Navigation -->
                <nav
                    class="site-header__nav hidden items-center font-semibold text-white/70 lg:flex"
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
                            class="site-header__social rounded-md text-secondary transition hover:bg-white/10 hover:text-white"
                            href="https://github.com/Greazi-Times/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <PhGithubLogo class="site-header__social-icon" />
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<style scoped>
.site-header__inner {
    min-height: clamp(3.5rem, 8svh, 4.5rem);
}

.site-header__brand {
    gap: clamp(0.5rem, 1vw, 0.75rem);
}

.site-header__mark {
    width: clamp(2rem, 4.6svh, 2.5rem);
    height: clamp(2rem, 4.6svh, 2.5rem);
}

.site-header__mark-icon {
    width: clamp(1.15rem, 2.5svh, 1.375rem);
    height: clamp(1.15rem, 2.5svh, 1.375rem);
}

.site-header__title {
    font-size: clamp(0.875rem, 1.45svh, 1rem);
}

.site-header__nav {
    margin-left: clamp(1.25rem, 2.25vw, 3rem);
    gap: clamp(1.25rem, 2vw, 2.25rem);
    font-size: clamp(0.8125rem, 1.35svh, 0.875rem);
}

.site-header__social {
    padding: clamp(0.375rem, 0.9svh, 0.5rem);
}

.site-header__social-icon {
    width: clamp(1.125rem, 2.45svh, 1.375rem);
    height: clamp(1.125rem, 2.45svh, 1.375rem);
}

@media (max-height: 760px) and (min-width: 1024px) {
    .site-header__inner {
        min-height: 3.5rem;
    }

    .site-header__mark {
        width: 2rem;
        height: 2rem;
    }

    .site-header__title {
        font-size: 0.875rem;
    }

    .site-header__nav {
        margin-left: clamp(1rem, 2vw, 2rem);
        gap: clamp(1rem, 1.7vw, 1.75rem);
        font-size: 0.8125rem;
    }

    .site-header__social {
        padding: 0.375rem;
    }
}
</style>
