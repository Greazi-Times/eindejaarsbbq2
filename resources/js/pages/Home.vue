<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    PhAppleLogo,
    PhGoogleLogo,
    PhMicrosoftOutlookLogo,
} from '@phosphor-icons/vue';
import { computed, ref, watch } from 'vue';
import FormCard from '@/components/form/FormCard.vue';
import FormGrid from '@/components/form/FormGrid.vue';
import FormNavigation from '@/components/form/FormNavigation.vue';
import FormSection from '@/components/form/FormSection.vue';
import FormStepper from '@/components/form/FormStepper.vue';
import Button from '@/components/ui/Button.vue';
import CheckboxGroup from '@/components/ui/inputs/CheckboxGroup.vue';
import Input from '@/components/ui/inputs/Input.vue';
import Modal from '@/components/ui/Modal.vue';
import Footer from '@/layouts/Footer.vue';
import Header from '@/layouts/Header.vue';

const showCalendarModal = ref(false);

const currentStep = ref(1);

const fullName = ref('');
const email = ref('');

const selectedTypes = ref<string[]>([]);

const selectedStudentAssociation = ref<string[]>([]);
const selectedEducation = ref<string[]>([]);
const companyName = ref('');

const customStudentAssociation = ref('');
const customEducation = ref('');

const guestAmount = ref('1');

const guestDietaryPreferences = ref<Record<string, string[]>>({});

const stepErrors = ref<Record<string, string>>({});

const inputErrors = ref<Record<string, string>>({});

const isSubmitting = ref(false);

const props = withDefaults(
    defineProps<{
        activeEvent?: {
            id: number;
            name: string;
            starts_at: string | null;
            ends_at: string | null;
            location: string | null;
            description: string | null;
            partners: {
                id: number;
                name: string;
                logo: string | null;
                website: string | null;
            }[];
            verenigingen: {
                id: number;
                name: string;
                logo: string | null;
                website: string | null;
            }[];
        } | null;
    }>(),
    {
        activeEvent: null,
    },
);

const eventTitle = computed(() => props.activeEvent?.name || 'Eindejaars BBQ');

const eventLocation = computed(() => props.activeEvent?.location || 'Locatie volgt nog');

const eventPartners = computed(() => props.activeEvent?.partners || []);
const eventVerenigingen = computed(() => props.activeEvent?.verenigingen || []);

const getImageUrl = (logo: string | null) => {
    if (!logo) {
        return null;
    }
    if (logo.startsWith('http://') || logo.startsWith('https://') || logo.startsWith('/')) {
        return logo;
    }
    return `/storage/${logo}`;
};

const parseLocalEventDate = (value: string | null | undefined) => {
    if (!value) {
        return null;
    }

    const [datePart, timePart = '00:00:00'] = value.replace(' ', 'T').split('T');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour = 0, minute = 0, second = 0] = timePart.split(':').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day, hour, minute, second);
};

const eventStartDate = computed(() => parseLocalEventDate(props.activeEvent?.starts_at));
const eventEndDate = computed(() => parseLocalEventDate(props.activeEvent?.ends_at));

const eventDateLine = computed(() => {
    if (!props.activeEvent?.starts_at || !eventStartDate.value) {
        return 'Er is momenteel geen barbecue gepland.';
    }

    const date = new Intl.DateTimeFormat('nl-NL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(eventStartDate.value);

    const time = props.activeEvent.starts_at.split(' ')[1]?.slice(0, 5) || '00:00';

    return `${date} | ${time} | ${eventLocation.value}`;
});

const formatCalendarDate = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    const second = String(date.getSeconds()).padStart(2, '0');

    return `${year}${month}${day}T${hour}${minute}${second}`;
};

const updateInputValidation = (payload: {
    name?: string;
    valid: boolean;
    error?: string;
}) => {
    if (!payload.name) {
        return;
    }

    if (payload.valid) {
        delete inputErrors.value[payload.name];
        delete stepErrors.value[payload.name];

        return;
    }

    inputErrors.value[payload.name] = payload.error || 'Invalid value.';
    stepErrors.value[payload.name] = payload.error || 'Invalid value.';
};

const validateStep = () => {
    stepErrors.value = {};

    if (currentStep.value === 1) {
        if (!fullName.value.trim()) {
            stepErrors.value.fullName = 'Full name is required.';
        }

        if (!email.value.trim()) {
            stepErrors.value.email = 'Email address is required.';
        } else if (inputErrors.value.email) {
            stepErrors.value.email = inputErrors.value.email;
        }

        if (!selectedTypes.value.length) {
            stepErrors.value.type = 'Please select a type.';
        }
    }

    if (currentStep.value === 2) {
        if (
            selectedTypes.value.includes('student') &&
            !selectedStudentAssociation.value.length
        ) {
            stepErrors.value.studentAssociation =
                'Please select a student association.';
        }

        if (
            hasCustomStudentAssociation.value &&
            !customStudentAssociation.value.trim()
        ) {
            stepErrors.value.customStudentAssociation =
                'Please enter your student association.';
        }

        if (
            selectedTypes.value.includes('docent') &&
            !selectedEducation.value.length
        ) {
            stepErrors.value.education = 'Please select an education.';
        }

        if (hasCustomEducation.value && !customEducation.value.trim()) {
            stepErrors.value.customEducation = 'Please enter your education.';
        }

        if (
            selectedTypes.value.includes('partner-bedrijf') &&
            !companyName.value.trim()
        ) {
            stepErrors.value.companyName = 'Please enter a company name.';
        }
    }

    return Object.keys(stepErrors.value).length === 0;
};

const guests = computed(() => {
    const amount = Math.min(Math.max(Number(guestAmount.value) || 1, 1), 3);

    return Array.from({ length: amount }, (_, index) => ({
        label: `Person ${index + 1}`,
        value: `person-${index + 1}`,
    }));
});

const hasCustomStudentAssociation = computed(() => {
    return selectedStudentAssociation.value.includes('anders');
});

const hasCustomEducation = computed(() => {
    return selectedEducation.value.includes('anders');
});

watch(guestAmount, (value) => {
    const amount = Number(value);

    if (Number.isNaN(amount) || amount < 1) {
        guestAmount.value = '1';

        return;
    }

    if (amount > 3) {
        guestAmount.value = '3';
    }
});

watch(selectedTypes, (types) => {
    if (!types.includes('student')) {
        selectedStudentAssociation.value = [];
        customStudentAssociation.value = '';
    }

    if (!types.includes('docent')) {
        selectedEducation.value = [];
        customEducation.value = '';
    }

    if (!types.includes('partner-bedrijf')) {
        companyName.value = '';
        guestAmount.value = '1';
    }
});

const dietaryOptions = [
    {
        label: 'Vegetarian',
        value: 'vegetarian',
    },
    {
        label: 'Vegan',
        value: 'vegan',
    },
    {
        label: 'Halal',
        value: 'halal',
    },
];

const formSteps = [
    { label: 'Persoonlijke Informatie' },
    { label: 'Details' },
    { label: 'Dieet wensen' },
    { label: 'Confirm' },
];

const submitEnrollment = () => {
    isSubmitting.value = true;

    router.post(
        '/aanmelden',
        {
            full_name: fullName.value,
            email: email.value,
            type: selectedTypes.value[0],
            student_association: selectedStudentAssociation.value[0] || null,
            custom_student_association:
                customStudentAssociation.value || null,
            education: selectedEducation.value[0] || null,
            custom_education: customEducation.value || null,
            company_name: companyName.value || null,
            guest_amount: Number(guestAmount.value),
            dietary_preferences: guestDietaryPreferences.value,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                stepErrors.value = Object.fromEntries(
                    Object.entries(errors).map(([key, value]) => [
                        key,
                        String(value),
                    ]),
                );
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const nextStep = () => {
    const isValid = validateStep();

    if (!isValid) {
        return;
    }

    if (currentStep.value === formSteps.length) {
        submitEnrollment();

        return;
    }

    currentStep.value++;
};

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--;
    }
};

const scrollToRegisterForm = () => {
    document.getElementById('aanmelden')?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const openCalendarModal = () => {
    showCalendarModal.value = true;
};

const closeCalendarModal = () => {
    showCalendarModal.value = false;
};

const addToAppleCalendar = () => {
    if (!eventStartDate.value) {
        return;
    }

    const start = formatCalendarDate(eventStartDate.value);
    const end = formatCalendarDate(
        eventEndDate.value || new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000),
    );

    const calendarContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Eindejaars BBQ//NL',
        'BEGIN:VEVENT',
        `UID:${Date.now()}@eindejaarsbbq.nl`,
        `DTSTAMP:${new Date().toISOString().replace(/[-:]/g, '').split('.')[0]}Z`,
        `DTSTART:${start}`,
        `DTEND:${end}`,
        `SUMMARY:${eventTitle.value}`,
        `DESCRIPTION:${eventTitle.value}`,
        `LOCATION:${eventLocation.value}`,
        'END:VEVENT',
        'END:VCALENDAR',
    ].join('\r\n');

    const blob = new Blob([calendarContent], {
        type: 'text/calendar;charset=utf-8',
    });

    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');

    link.href = url;
    link.download = `${eventTitle.value.toLowerCase().replace(/\s+/g, '-')}.ics`;
    link.click();

    URL.revokeObjectURL(url);

    closeCalendarModal();
};

const addToOutlookCalendar = () => {
    if (!eventStartDate.value) {
        return;
    }

    const endDate = eventEndDate.value || new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000);

    const params = new URLSearchParams({
        path: '/calendar/action/compose',
        rru: 'addevent',
        subject: eventTitle.value,
        startdt: props.activeEvent?.starts_at || '',
        enddt: props.activeEvent?.ends_at || endDate.toISOString(),
        body: eventTitle.value,
        location: eventLocation.value,
    });

    window.open(
        `https://outlook.live.com/calendar/0/deeplink/compose?${params.toString()}`,
        '_blank',
    );

    closeCalendarModal();
};

const addToGoogleCalendar = () => {
    if (!eventStartDate.value) {
        return;
    }

    const endDate = eventEndDate.value || new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000);

    const params = new URLSearchParams({
        action: 'TEMPLATE',
        text: eventTitle.value,
        dates: `${formatCalendarDate(eventStartDate.value)}/${formatCalendarDate(endDate)}`,
        details: eventTitle.value,
        location: eventLocation.value,
    });

    window.open(
        `https://calendar.google.com/calendar/render?${params.toString()}`,
        '_blank',
    );

    closeCalendarModal();
};
</script>

<template>
    <div class="relative min-h-screen overflow-hidden">
        <Header />

        <section
            class="flex min-h-screen items-center justify-center pt-20"
            :style="{
                backgroundSize: 'cover',
                backgroundPosition: 'center',
                backgroundRepeat: 'no-repeat',
                background:
                    'linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url(/images/hero-2.jpg) no-repeat center / cover',
            }"
        >
            <div class="w-full px-4 text-center">
                <h1
                    class="text-center font-chewy text-8xl leading-none tracking-wide text-white drop-shadow-[4px_4px_0_rgba(0,0,0,0.4)] md:text-8xl"
                >
                    {{ eventTitle }}
                </h1>

                <div
                    class="mx-auto mt-6 h-1 w-[40vh] rounded-full bg-white/80 drop-shadow-[4px_4px_0_rgba(0,0,0,0.4)]"
                />

                <p
                    class="mt-6 text-center text-lg font-medium tracking-wide text-white drop-shadow-[4px_4px_0_rgba(0,0,0,0.4)] md:text-2xl"
                >
                    {{ eventDateLine }}
                </p>

                <div
                    class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row"
                >
                    <Button
                        variant="primary"
                        size="lg"
                        @click="scrollToRegisterForm"
                    >
                        Aanmelden
                    </Button>

                    <Button
                        variant="secondary"
                        size="lg"
                        @click="openCalendarModal"
                    >
                        Add to Calendar
                    </Button>
                </div>
            </div>
        </section>

        <!-- Aanmeldformulier -->
        <section id="aanmelden" class="relative overflow-hidden px-4 py-24">
            <img
                src="/images/shape-1.svg"
                class="pointer-events-none absolute top-0 left-0 z-0"
                alt=""
            />
            <img
                src="/images/shape-2.svg"
                class="pointer-events-none absolute right-0 blur-2xl"
                alt=""
            />

            <div v-if="props.activeEvent" class="relative z-10">
                <FormCard height="640px">
                    <FormStepper
                        :current-step="currentStep"
                        :steps="formSteps"
                    />

                    <FormSection v-if="currentStep === 1">
                        <FormGrid>
                            <Input
                                v-model="fullName"
                                name="fullName"
                                label="Full Name"
                                placeholder="Enter your full name"
                                :error="stepErrors.fullName"
                                required
                            />
                            <Input
                                v-model="email"
                                name="email"
                                type="email"
                                label="Email Address"
                                placeholder="Enter your email address"
                                :error="stepErrors.email"
                                required
                                @validation-change="updateInputValidation"
                            />
                        </FormGrid>

                        <CheckboxGroup
                            v-model="selectedTypes"
                            label="Type"
                            description="Select the type that matches you."
                            :error="stepErrors.type"
                            required
                            :max="1"
                            :options="[
                                {
                                    label: 'Student',
                                    value: 'student',
                                },
                                {
                                    label: 'Docent',
                                    value: 'docent',
                                },
                                {
                                    label: 'Partner / Bedrijf',
                                    value: 'partner-bedrijf',
                                },
                            ]"
                        />
                    </FormSection>

                    <FormSection v-if="currentStep === 2">
                        <CheckboxGroup
                            v-if="selectedTypes.includes('student')"
                            v-model="selectedStudentAssociation"
                            label="Student Association"
                            description="Select your student association."
                            :error="stepErrors.studentAssociation"
                            required
                            :max="1"
                            :options="[
                                {
                                    label: 'SV-Motus',
                                    value: 'sv-motus',
                                },
                                {
                                    label: 'Promptus Imperii',
                                    value: 'promptus-imperii',
                                },
                                {
                                    label: 'SV-WIM',
                                    value: 'sv-wim',
                                },
                                {
                                    label: 'Silicium',
                                    value: 'silicium',
                                },
                                {
                                    label: 'Anders',
                                    value: 'anders',
                                },
                            ]"
                        />

                        <Input
                            v-if="hasCustomStudentAssociation"
                            v-model="customStudentAssociation"
                            name="customStudentAssociation"
                            label="Custom Student Association"
                            placeholder="Enter your student association"
                            :error="stepErrors.customStudentAssociation"
                            required
                        />

                        <CheckboxGroup
                            v-if="selectedTypes.includes('docent')"
                            v-model="selectedEducation"
                            label="Education"
                            description="Select your education."
                            :error="stepErrors.education"
                            required
                            :max="1"
                            :options="[
                                {
                                    label: 'Mechatronica',
                                    value: 'mechatronica',
                                },
                                {
                                    label: 'Werktuigbouwkunde',
                                    value: 'werktuigbouwkunde',
                                },
                                {
                                    label: '(Technische) Informatica',
                                    value: 'technische-informatica',
                                },
                                {
                                    label: 'Elektrotechniek',
                                    value: 'elektrotechniek',
                                },
                                {
                                    label: 'Anders',
                                    value: 'anders',
                                },
                            ]"
                        />

                        <Input
                            v-if="hasCustomEducation"
                            v-model="customEducation"
                            name="customEducation"
                            label="Custom Education"
                            placeholder="Enter your education"
                            :error="stepErrors.customEducation"
                            required
                        />

                        <FormGrid
                            v-if="selectedTypes.includes('partner-bedrijf')"
                        >
                            <Input
                                v-model="companyName"
                                name="companyName"
                                label="Company Name"
                                placeholder="Enter your company name"
                                :error="stepErrors.companyName"
                                required
                            />

                            <Input
                                v-model="guestAmount"
                                name="guestAmount"
                                type="number"
                                label="Amount Of Guests"
                                placeholder="1"
                                min="1"
                                max="3"
                                required
                            />
                        </FormGrid>
                    </FormSection>

                    <FormSection v-if="currentStep === 3">
                        <div class="mt-8 space-y-8">
                            <CheckboxGroup
                                v-for="guest in guests"
                                :key="guest.value"
                                v-model="guestDietaryPreferences[guest.value]"
                                :label="`${guest.label} Dietary Preferences`"
                                description="Select all dietary preferences for this person."
                                :options="dietaryOptions"
                            />
                        </div>
                    </FormSection>

                    <FormSection v-if="currentStep === 4">
                        <div class="space-y-6">
                            <div
                                class="rounded-2xl border border-border bg-background/60 p-6 shadow-sm"
                            >
                                <div
                                    class="mb-5 flex items-center justify-between gap-4"
                                >
                                    <div>
                                        <h3
                                            class="mt-1 text-xl font-semibold text-foreground"
                                        >
                                            Personal Information
                                        </h3>
                                    </div>
                                </div>

                                <dl class="grid gap-4 md:grid-cols-2">
                                    <div
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Full Name
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{ fullName || 'Not filled in' }}
                                        </dd>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Email Address
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{ email || 'Not filled in' }}
                                        </dd>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border bg-card p-4 md:col-span-2"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Type
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground capitalize"
                                        >
                                            {{
                                                selectedTypes[0]?.replace(
                                                    '-',
                                                    ' / ',
                                                ) || 'Not selected'
                                            }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div
                                class="rounded-2xl border border-border bg-background/60 p-6 shadow-sm"
                            >
                                <div class="mb-5">
                                    <h3
                                        class="mt-1 text-xl font-semibold text-foreground"
                                    >
                                        Details
                                    </h3>
                                </div>

                                <dl class="grid gap-4 md:grid-cols-2">
                                    <div
                                        v-if="selectedStudentAssociation.length"
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Student Association
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{
                                                selectedStudentAssociation[0] ===
                                                'anders'
                                                    ? customStudentAssociation
                                                    : selectedStudentAssociation[0]
                                            }}
                                        </dd>
                                    </div>

                                    <div
                                        v-if="selectedEducation.length"
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Education
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{
                                                selectedEducation[0] ===
                                                'anders'
                                                    ? customEducation
                                                    : selectedEducation[0]
                                            }}
                                        </dd>
                                    </div>

                                    <div
                                        v-if="companyName"
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Company Name
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{ companyName }}
                                        </dd>
                                    </div>

                                    <div
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <dt
                                            class="text-sm text-muted-foreground"
                                        >
                                            Amount Of Guests
                                        </dt>
                                        <dd
                                            class="mt-1 font-medium text-foreground"
                                        >
                                            {{ guestAmount }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div
                                class="rounded-2xl border border-border bg-background/60 p-6 shadow-sm"
                            >
                                <div class="mb-5">
                                    <h3
                                        class="mt-1 text-xl font-semibold text-foreground"
                                    >
                                        Dietary Preferences
                                    </h3>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div
                                        v-for="guest in guests"
                                        :key="guest.value"
                                        class="rounded-xl border border-border bg-card p-4"
                                    >
                                        <div
                                            class="font-medium text-foreground"
                                        >
                                            {{ guest.label }}
                                        </div>

                                        <div
                                            v-if="
                                                guestDietaryPreferences[
                                                    guest.value
                                                ]?.length
                                            "
                                            class="mt-3 flex flex-wrap gap-2"
                                        >
                                            <span
                                                v-for="preference in guestDietaryPreferences[
                                                    guest.value
                                                ]"
                                                :key="preference"
                                                class="rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-sm font-medium text-primary"
                                            >
                                                {{ preference }}
                                            </span>
                                        </div>

                                        <p
                                            v-else
                                            class="mt-2 text-sm text-muted-foreground"
                                        >
                                            No dietary preferences selected.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </FormSection>

                    <FormNavigation
                        :show-previous="currentStep > 1"
                        :next-label="
                            isSubmitting
                                ? 'Submitting...'
                                : currentStep === formSteps.length
                                  ? 'Submit'
                                  : 'Continue'
                        "
                        @next="nextStep"
                        @previous="previousStep"
                    />
                </FormCard>
            </div>

            <div v-else class="relative z-10 mx-auto max-w-3xl px-4 text-center">
                <div
                    class="rounded-2xl border border-white/10 bg-white/5 p-8 backdrop-blur-md"
                >
                    <h2 class="text-3xl font-semibold text-white">
                        Er is momenteel geen barbecue gepland.
                    </h2>

                    <p class="mt-3 text-white/60">
                        Zodra er een nieuwe barbecue gepland is, kun je je hier aanmelden.
                    </p>
                </div>
            </div>
        </section>

        <!-- Verenigingen -->
        <section
            v-if="eventVerenigingen.length"
            id="verenigingen"
            class="relative z-10 mx-auto max-w-[80%] px-6 pt-20 pb-28 md:px-12"
        >
            <div class="mb-10 text-center">
                <h2 class="text-3xl font-semibold text-white md:text-4xl">
                    Verenigingen
                </h2>
                <p class="mt-2 text-white/60">
                    Zie hier de organisatorische verenigingen van de
                    EindejaarsBBQ
                </p>
            </div>

            <div class="relative">
                <div
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <a
                        v-for="vereniging in eventVerenigingen"
                        :key="vereniging.id"
                        :href="vereniging.website || undefined"
                        :target="vereniging.website ? '_blank' : undefined"
                        :rel="vereniging.website ? 'noopener noreferrer' : undefined"
                        class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-md transition hover:bg-white/10"
                    >
                        <div
                            class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white/20"
                        >
                            <img
                                v-if="getImageUrl(vereniging.logo)"
                                :src="getImageUrl(vereniging.logo) || ''"
                                :alt="vereniging.name"
                                class="h-full w-full object-contain p-2"
                            />
                            <span
                                v-else
                                class="text-lg font-semibold text-white"
                            >
                                {{ vereniging.name.charAt(0) }}
                            </span>
                        </div>

                        <span class="text-white/80">{{ vereniging.name }}</span>
                    </a>
                </div>
            </div>
        </section>

        <Footer :partners="eventPartners" :verenigingen="eventVerenigingen" />

        <Modal v-model="showCalendarModal" size="sm">
            <template #header>
                <h2 class="text-xl font-semibold">Select Calendar</h2>
            </template>

            <div class="flex items-center justify-center gap-4">
                <Button
                    variant="ghost"
                    size="lg"
                    class="h-20 w-20 rounded-2xl"
                    @click="addToGoogleCalendar"
                >
                    <PhGoogleLogo :size="36" color="#ffffff" />
                </Button>

                <Button
                    variant="ghost"
                    size="lg"
                    class="h-20 w-20 rounded-2xl"
                    @click="addToAppleCalendar"
                >
                    <PhAppleLogo :size="36" color="#ffffff" />
                </Button>

                <Button
                    variant="ghost"
                    size="lg"
                    class="h-20 w-20 rounded-2xl"
                    @click="addToOutlookCalendar"
                >
                    <PhMicrosoftOutlookLogo :size="36" color="#ffffff" />
                </Button>
            </div>
        </Modal>
    </div>
</template>

<style scoped></style>
