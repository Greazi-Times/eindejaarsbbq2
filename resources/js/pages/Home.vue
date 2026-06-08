<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    PhAppleLogo,
    PhCalendarBlank,
    PhClock,
    PhGoogleLogo,
    PhMapPin,
    PhMicrosoftOutlookLogo,
    PhUsersThree,
    PhX,
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

type FlashPayment = {
    type: 'success' | 'warning' | 'error' | 'info';
    title: string;
    message: string;
    action_url?: string | null;
    action_label?: string | null;
};

const showCalendarModal = ref(false);
const dismissedFlashBanner = ref(false);
const localFlashBanner = ref<FlashPayment | null>(null);

const currentStep = ref(1);

const fullName = ref('');
const email = ref('');

const selectedTypes = ref<string[]>([]);

const selectedStudentAssociation = ref<string[]>([]);
const selectedEducation = ref<string[]>([]);
const selectedPartnerCompany = ref<string[]>([]);

const customStudentAssociation = ref('');
const customEducation = ref('');
const companyName = ref('');

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
                students_must_pay: boolean;
            }[];
            verenigingen: {
                id: number;
                name: string;
                logo: string | null;
                website: string | null;
                students_must_pay: boolean;
            }[];
        } | null;
        flash?: {
            success?: string | null;
            warning?: string | null;
            error?: string | null;
            banner?: FlashPayment | null;
            payment?: FlashPayment | null;
        };
    }>(),
    {
        activeEvent: null,
        flash: () => ({
            success: null,
            warning: null,
            error: null,
            banner: null,
            payment: null,
        }),
    },
);

const eventTitle = computed(() => props.activeEvent?.name || 'Eindejaars BBQ');

const eventLocation = computed(
    () => props.activeEvent?.location || 'Locatie volgt nog',
);

const eventPartners = computed(() => props.activeEvent?.partners || []);
const eventVerenigingen = computed(() => props.activeEvent?.verenigingen || []);

const flashBanner = computed<FlashPayment | null>(() => {
    if (localFlashBanner.value) {
        return localFlashBanner.value;
    }

    if (props.flash?.banner) {
        return props.flash.banner;
    }

    if (props.flash?.payment) {
        return props.flash.payment;
    }

    if (props.flash?.success) {
        return {
            type: 'success',
            title: 'Gelukt',
            message: props.flash.success,
        };
    }

    if (props.flash?.warning) {
        return {
            type: 'warning',
            title: 'Let op',
            message: props.flash.warning,
        };
    }

    if (props.flash?.error) {
        return {
            type: 'error',
            title: 'Er ging iets mis',
            message: props.flash.error,
        };
    }

    return null;
});

const flashBannerClasses = computed(() => {
    if (flashBanner.value?.type === 'success') {
        return 'border-emerald-300/60 bg-emerald-500 text-white';
    }

    if (flashBanner.value?.type === 'warning') {
        return 'border-amber-300/70 bg-amber-400 text-black';
    }

    if (flashBanner.value?.type === 'error') {
        return 'border-red-300/60 bg-red-500 text-white';
    }

    return 'border-sky-300/60 bg-sky-500 text-white';
});

const visibleFlashBanner = computed(() => {
    return dismissedFlashBanner.value ? null : flashBanner.value;
});

watch(
    () => [
        props.flash?.banner,
        props.flash?.payment,
        props.flash?.success,
        props.flash?.warning,
        props.flash?.error,
    ],
    () => {
        dismissedFlashBanner.value = false;
    },
);

const dismissFlashBanner = () => {
    dismissedFlashBanner.value = true;
};

const showDuplicateEmailNotification = (message: string) => {
    localFlashBanner.value = {
        type: 'warning',
        title: 'E-mail al aangemeld',
        message,
    };
    dismissedFlashBanner.value = false;
};

const isDuplicateEmailError = (message: string) => {
    return message.toLowerCase().includes('al aangemeld');
};

const handleEmailError = (message: string) => {
    if (!isDuplicateEmailError(message)) {
        return false;
    }

    currentStep.value = 1;
    showDuplicateEmailNotification(message);

    return true;
};

watch(
    () => stepErrors.value.email,
    (message) => {
        if (message) {
            handleEmailError(message);
        }
    },
);

const customOption = {
    label: 'Anders',
    value: 'anders',
};

const studentAssociationOptions = computed(() => [
    ...eventVerenigingen.value.map((vereniging) => ({
        label: vereniging.name,
        value: vereniging.name,
    })),
    customOption,
]);

const partnerCompanyOptions = computed(() => [
    ...eventPartners.value.map((partner) => ({
        label: partner.name,
        value: partner.name,
    })),
    customOption,
]);

const getImageUrl = (logo: string | null) => {
    if (!logo) {
        return null;
    }
    if (
        logo.startsWith('http://') ||
        logo.startsWith('https://') ||
        logo.startsWith('/')
    ) {
        return logo;
    }
    return `/storage/${logo}`;
};

const parseLocalEventDate = (value: string | null | undefined) => {
    if (!value) {
        return null;
    }

    const [datePart, timePart = '00:00:00'] = value
        .replace(' ', 'T')
        .split('T');
    const [year, month, day] = datePart.split('-').map(Number);
    const [hour = 0, minute = 0, second = 0] = timePart.split(':').map(Number);

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day, hour, minute, second);
};

const eventStartDate = computed(() =>
    parseLocalEventDate(props.activeEvent?.starts_at),
);
const eventEndDate = computed(() =>
    parseLocalEventDate(props.activeEvent?.ends_at),
);

const eventDateOnly = computed(() => {
    if (!eventStartDate.value) {
        return 'Datum volgt';
    }

    return new Intl.DateTimeFormat('nl-NL', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(eventStartDate.value);
});

const eventTimeOnly = computed(() => {
    if (!props.activeEvent?.starts_at) {
        return 'Tijd volgt';
    }

    return props.activeEvent.starts_at.split(' ')[1]?.slice(0, 5) || '00:00';
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

    inputErrors.value[payload.name] = payload.error || 'Ongeldige waarde.';
    stepErrors.value[payload.name] = payload.error || 'Ongeldige waarde.';
};

const validateStep = () => {
    stepErrors.value = {};

    if (currentStep.value === 1) {
        if (!fullName.value.trim()) {
            stepErrors.value.fullName = 'Volledige naam is verplicht.';
        }

        if (!email.value.trim()) {
            stepErrors.value.email = 'E-mailadres is verplicht.';
        } else if (inputErrors.value.email) {
            stepErrors.value.email = inputErrors.value.email;
        }

        if (!selectedTypes.value.length) {
            stepErrors.value.type = 'Selecteer een type.';
        }
    }

    if (currentStep.value === 2) {
        if (
            selectedTypes.value.includes('student') &&
            !selectedStudentAssociation.value.length
        ) {
            stepErrors.value.studentAssociation = 'Selecteer een vereniging.';
        }

        if (
            hasCustomStudentAssociation.value &&
            !customStudentAssociation.value.trim()
        ) {
            stepErrors.value.customStudentAssociation = 'Vul je vereniging in.';
        }

        if (
            selectedTypes.value.includes('docent') &&
            !selectedEducation.value.length
        ) {
            stepErrors.value.education = 'Selecteer een opleiding.';
        }

        if (hasCustomEducation.value && !customEducation.value.trim()) {
            stepErrors.value.customEducation = 'Vul je opleiding in.';
        }

        if (
            selectedTypes.value.includes('partner-bedrijf') &&
            !selectedPartnerCompany.value.length
        ) {
            stepErrors.value.partnerCompany =
                'Selecteer een partner of bedrijf.';
        }

        if (
            selectedTypes.value.includes('partner-bedrijf') &&
            !companyName.value.trim()
        ) {
            stepErrors.value.companyName = 'Vul je bedrijfsnaam in.';
        }
    }

    return Object.keys(stepErrors.value).length === 0;
};

const guests = computed(() => {
    const amount = Math.min(Math.max(Number(guestAmount.value) || 1, 1), 3);

    return Array.from({ length: amount }, (_, index) => ({
        label: `Persoon ${index + 1}`,
        value: `person-${index + 1}`,
    }));
});

const hasCustomStudentAssociation = computed(() => {
    return selectedStudentAssociation.value.includes('anders');
});

const hasCustomEducation = computed(() => {
    return selectedEducation.value.includes('anders');
});

const selectedStudentAssociationName = computed(() => {
    if (selectedStudentAssociation.value[0] === 'anders') {
        return customStudentAssociation.value.trim();
    }

    return selectedStudentAssociation.value[0] || '';
});

const selectedPartnerCompanyName = computed(() => {
    return selectedPartnerCompany.value[0] || '';
});

const selectedEducationName = computed(() => {
    if (selectedEducation.value[0] === 'anders') {
        return customEducation.value.trim();
    }

    return selectedEducation.value[0] || '';
});

const selectedTypeLabel = computed(() => {
    return (
        {
            student: 'Student',
            docent: 'Docent',
            'partner-bedrijf': 'Partner / bedrijf',
        }[selectedTypes.value[0]] || ''
    );
});

const fieldErrorKeys = [
    'fullName',
    'email',
    'type',
    'studentAssociation',
    'customStudentAssociation',
    'education',
    'customEducation',
    'companyName',
    'partnerCompany',
];

const formErrors = computed(() => {
    return Object.entries(stepErrors.value)
        .filter(([key]) => !fieldErrorKeys.includes(key))
        .map(([, error]) => error)
        .filter(Boolean);
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
        selectedPartnerCompany.value = [];
        companyName.value = '';
        guestAmount.value = '1';
    }
});

const dietaryOptions = [
    {
        label: 'Vegetarisch',
        value: 'vegetarian',
    },
    {
        label: 'Veganistisch',
        value: 'vegan',
    },
    {
        label: 'Halal',
        value: 'halal',
    },
];

const dietaryOptionLabel = (value: string) => {
    return (
        dietaryOptions.find((option) => option.value === value)?.label || value
    );
};

const reviewDetails = computed(() => {
    return [
        {
            label: 'Naam',
            value: fullName.value || 'Niet ingevuld',
        },
        {
            label: 'E-mail',
            value: email.value || 'Niet ingevuld',
        },
        {
            label: 'Type',
            value: selectedTypeLabel.value || 'Niet geselecteerd',
        },
        {
            label: 'Vereniging',
            value: selectedStudentAssociationName.value,
        },
        {
            label: 'Opleiding',
            value: selectedEducationName.value,
        },
        {
            label: 'Partner / bedrijf',
            value: selectedPartnerCompanyName.value,
        },
        {
            label: 'Bedrijfsnaam',
            value: companyName.value,
        },
        {
            label: 'Aantal personen',
            value: guestAmount.value,
        },
    ].filter((item) => item.value);
});

const dietaryReview = computed(() => {
    return guests.value.map((guest) => ({
        ...guest,
        preferences: (guestDietaryPreferences.value[guest.value] || []).map(
            (preference) => dietaryOptionLabel(preference),
        ),
    }));
});

const formSteps = [
    { label: 'Persoonlijke Informatie' },
    { label: 'Gegevens' },
    { label: 'Dieetwensen' },
    { label: 'Bevestigen' },
];

const resetEnrollmentForm = () => {
    currentStep.value = 1;
    fullName.value = '';
    email.value = '';
    selectedTypes.value = [];
    selectedStudentAssociation.value = [];
    selectedEducation.value = [];
    selectedPartnerCompany.value = [];
    customStudentAssociation.value = '';
    customEducation.value = '';
    companyName.value = '';
    guestAmount.value = '1';
    guestDietaryPreferences.value = {};
    stepErrors.value = {};
    inputErrors.value = {};
};

const submitEnrollment = () => {
    if (isSubmitting.value) {
        return;
    }

    isSubmitting.value = true;

    router.post(
        '/aanmelden',
        {
            full_name: fullName.value,
            email: email.value,
            type: selectedTypes.value[0],
            student_association: selectedStudentAssociation.value[0] || null,
            custom_student_association:
                selectedStudentAssociation.value[0] === 'anders'
                    ? customStudentAssociation.value
                    : null,
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

                if (errors.email && handleEmailError(String(errors.email))) {
                    return;
                }

                localFlashBanner.value = null;
            },
            onSuccess: () => {
                localFlashBanner.value = null;
                resetEnrollmentForm();
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
};

const nextStep = () => {
    if (isSubmitting.value) {
        return;
    }

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
    if (isSubmitting.value) {
        return;
    }

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
        eventEndDate.value ||
            new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000),
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

    const endDate =
        eventEndDate.value ||
        new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000);

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

    const endDate =
        eventEndDate.value ||
        new Date(eventStartDate.value.getTime() + 2 * 60 * 60 * 1000);

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

        <div
            v-if="visibleFlashBanner"
            class="pointer-events-none fixed top-24 right-0 left-0 z-50 px-4"
        >
            <div
                :class="[
                    'pointer-events-auto mx-auto flex max-w-3xl items-start gap-4 rounded-xl border px-5 py-4 shadow-2xl shadow-black/20 sm:items-center sm:justify-between',
                    flashBannerClasses,
                ]"
            >
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">
                        {{ visibleFlashBanner.title }}
                    </p>

                    <p class="mt-1 text-sm opacity-90">
                        {{ visibleFlashBanner.message }}
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        v-if="visibleFlashBanner.action_url"
                        :href="visibleFlashBanner.action_url"
                        class="inline-flex items-center justify-center rounded-md border border-current px-4 py-2 text-sm font-semibold transition hover:bg-white/20"
                    >
                        {{ visibleFlashBanner.action_label || 'Doorgaan' }}
                    </a>

                    <button
                        type="button"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-current/50 transition hover:bg-white/20"
                        aria-label="Melding sluiten"
                        @click="dismissFlashBanner"
                    >
                        <PhX :size="18" weight="bold" />
                    </button>
                </div>
            </div>
        </div>

        <section
            class="relative flex min-h-[88svh] items-center overflow-hidden pt-20 pb-16"
            :style="{
                background:
                    'linear-gradient(90deg, rgba(20, 13, 10, 0.9) 0%, rgba(20, 13, 10, 0.64) 45%, rgba(20, 13, 10, 0.42) 100%), linear-gradient(180deg, rgba(20, 13, 10, 0.1) 0%, rgba(20, 13, 10, 0.82) 100%), url(/images/hero-2.jpg) no-repeat center / cover',
            }"
        >
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-background to-transparent"
            />

            <div class="relative z-10 mx-auto w-full max-w-7xl px-4 sm:px-6">
                <div class="max-w-4xl">
                    <p
                        class="mb-5 inline-flex rounded-full border border-secondary/40 bg-secondary/15 px-4 py-2 text-sm font-bold tracking-wide text-secondary shadow-lg shadow-black/20 backdrop-blur"
                    >
                        Avans ATIx sluit het jaar buiten af
                    </p>

                    <h1
                        class="font-chewy text-6xl leading-none text-white drop-shadow-[5px_5px_0_rgba(0,0,0,0.35)] sm:text-7xl md:text-8xl lg:text-9xl"
                    >
                        {{ eventTitle }}
                    </h1>

                    <p
                        class="mt-6 max-w-2xl text-lg leading-8 font-medium text-white/80 md:text-2xl md:leading-9"
                    >
                        Een avond vol vuur, verenigingen en goede gesprekken.
                        Meld je aan en schuif aan bij de Eindejaars BBQ.
                    </p>

                    <div
                        class="mt-8 grid max-w-3xl gap-3 sm:grid-cols-2 lg:grid-cols-4"
                    >
                        <div
                            class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhCalendarBlank
                                :size="22"
                                class="shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="text-sm font-semibold">
                                {{ eventDateOnly }}
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhClock
                                :size="22"
                                class="shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="text-sm font-semibold">
                                {{ eventTimeOnly }}
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhMapPin
                                :size="22"
                                class="shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="text-sm font-semibold">
                                {{ eventLocation }}
                            </span>
                        </div>

                        <div
                            class="flex items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhUsersThree
                                :size="22"
                                class="shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="text-sm font-semibold">
                                {{ eventVerenigingen.length || 0 }}
                                verenigingen
                            </span>
                        </div>
                    </div>

                    <div
                        class="mt-10 flex flex-col items-stretch gap-4 sm:flex-row sm:items-center"
                    >
                        <Button
                            variant="primary"
                            size="lg"
                            @click="scrollToRegisterForm"
                        >
                            Aanmelden
                        </Button>

                        <Button
                            variant="outline"
                            size="lg"
                            @click="openCalendarModal"
                        >
                            Toevoegen aan agenda
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <div class="event-content-band relative overflow-hidden">
            <!-- Aanmeldformulier -->
            <section id="aanmelden" class="relative px-4 py-20 md:py-28">
                <div v-if="props.activeEvent" class="relative z-10">
                    <div class="mx-auto mb-10 max-w-3xl text-center">
                        <p
                            class="text-sm font-bold tracking-wide text-secondary uppercase"
                        >
                            Aanmelden
                        </p>
                        <h2
                            class="mt-3 text-3xl font-semibold text-white md:text-5xl"
                        >
                            Zet jezelf op de gastenlijst
                        </h2>
                        <p
                            class="mx-auto mt-4 max-w-2xl text-base leading-7 text-white/65"
                        >
                            Kies je rol, vul je gegevens in en geef dieetwensen
                            meteen door.
                        </p>
                    </div>

                    <FormCard height="680px">
                        <FormStepper
                            :current-step="currentStep"
                            :steps="formSteps"
                        />

                        <FormSection v-if="currentStep === 1">
                            <FormGrid>
                                <Input
                                    v-model="fullName"
                                    name="fullName"
                                    label="Volledige naam"
                                    placeholder="Vul je volledige naam in"
                                    :error="stepErrors.fullName"
                                    required
                                />
                                <Input
                                    v-model="email"
                                    name="email"
                                    type="email"
                                    label="E-mailadres"
                                    placeholder="Vul je e-mailadres in"
                                    :error="stepErrors.email"
                                    required
                                    @validation-change="updateInputValidation"
                                />
                            </FormGrid>

                            <CheckboxGroup
                                v-model="selectedTypes"
                                label="Type"
                                description="Selecteer het type dat bij jou past."
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
                                        label: 'Partner / bedrijf',
                                        value: 'partner-bedrijf',
                                    },
                                ]"
                            />
                        </FormSection>

                        <FormSection v-if="currentStep === 2">
                            <CheckboxGroup
                                v-if="selectedTypes.includes('student')"
                                v-model="selectedStudentAssociation"
                                label="Vereniging"
                                description="Selecteer je vereniging."
                                :error="stepErrors.studentAssociation"
                                required
                                :max="1"
                                :options="studentAssociationOptions"
                            />

                            <Input
                                v-if="hasCustomStudentAssociation"
                                v-model="customStudentAssociation"
                                name="customStudentAssociation"
                                label="Andere vereniging"
                                placeholder="Vul je vereniging in"
                                :error="stepErrors.customStudentAssociation"
                                required
                            />

                            <CheckboxGroup
                                v-if="selectedTypes.includes('docent')"
                                v-model="selectedEducation"
                                label="Opleiding"
                                description="Selecteer je opleiding."
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
                                label="Andere opleiding"
                                placeholder="Vul je opleiding in"
                                :error="stepErrors.customEducation"
                                required
                            />

                            <FormGrid
                                v-if="selectedTypes.includes('partner-bedrijf')"
                            >
                                <Input
                                    v-model="companyName"
                                    name="companyName"
                                    label="Bedrijfsnaam"
                                    placeholder="Vul je bedrijfsnaam in"
                                    :error="stepErrors.companyName"
                                    required
                                />

                                <Input
                                    v-model="guestAmount"
                                    name="guestAmount"
                                    type="number"
                                    label="Aantal personen"
                                    placeholder="1"
                                    min="1"
                                    max="3"
                                    required
                                />

                                <CheckboxGroup
                                    v-model="selectedPartnerCompany"
                                    class="md:col-span-2"
                                    label="Partner / bedrijf"
                                    description="Selecteer je partner of bedrijf."
                                    :error="stepErrors.partnerCompany"
                                    required
                                    :max="1"
                                    :options="partnerCompanyOptions"
                                />
                            </FormGrid>
                        </FormSection>

                        <FormSection v-if="currentStep === 3">
                            <div class="mt-8 space-y-8">
                                <CheckboxGroup
                                    v-for="guest in guests"
                                    :key="guest.value"
                                    v-model="
                                        guestDietaryPreferences[guest.value]
                                    "
                                    :label="`${guest.label} dieetwensen`"
                                    description="Selecteer alle dieetwensen voor deze persoon."
                                    :options="dietaryOptions"
                                />
                            </div>
                        </FormSection>

                        <FormSection
                            v-if="currentStep === 4"
                            title="Controleer je aanmelding"
                            description="Alles klopt? Dan kun je je aanmelding verzenden."
                        >
                            <div class="space-y-5">
                                <div
                                    class="rounded-xl border border-primary/30 bg-primary/10 px-5 py-4"
                                >
                                    <p
                                        class="text-lg font-semibold text-foreground"
                                    >
                                        Bijna klaar
                                    </p>

                                    <p
                                        class="mt-1 text-sm text-muted-foreground"
                                    >
                                        We gebruiken deze gegevens voor de
                                        gastenlijst en eventuele betaling.
                                    </p>
                                </div>

                                <dl class="grid gap-x-8 gap-y-4 md:grid-cols-2">
                                    <div
                                        v-for="item in reviewDetails"
                                        :key="item.label"
                                        class="border-b border-border/70 pb-3"
                                    >
                                        <dt
                                            class="text-xs font-semibold text-muted-foreground uppercase"
                                        >
                                            {{ item.label }}
                                        </dt>

                                        <dd
                                            class="mt-1 text-base font-semibold break-words text-foreground"
                                        >
                                            {{ item.value }}
                                        </dd>
                                    </div>
                                </dl>

                                <div class="border-t border-border/70 pt-5">
                                    <div
                                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <h3
                                            class="text-lg font-semibold text-foreground"
                                        >
                                            Dieetwensen
                                        </h3>

                                        <span
                                            class="text-sm font-medium text-muted-foreground"
                                        >
                                            {{ guestAmount }}
                                            {{
                                                Number(guestAmount) === 1
                                                    ? 'persoon'
                                                    : 'personen'
                                            }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid gap-3 md:grid-cols-2">
                                        <div
                                            v-for="guest in dietaryReview"
                                            :key="guest.value"
                                            class="flex min-h-12 items-start justify-between gap-3 rounded-lg border border-border/70 px-4 py-3"
                                        >
                                            <p
                                                class="shrink-0 font-medium text-foreground"
                                            >
                                                {{ guest.label }}
                                            </p>

                                            <div
                                                v-if="guest.preferences.length"
                                                class="flex flex-wrap justify-end gap-2"
                                            >
                                                <span
                                                    v-for="preference in guest.preferences"
                                                    :key="preference"
                                                    class="rounded-full border border-primary/30 bg-primary/10 px-3 py-1 text-sm font-medium text-primary"
                                                >
                                                    {{ preference }}
                                                </span>
                                            </div>

                                            <p
                                                v-else
                                                class="text-sm text-muted-foreground"
                                            >
                                                Geen
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </FormSection>

                        <div>
                            <div
                                v-if="formErrors.length"
                                class="mt-6 rounded-xl border border-red-500/30 bg-red-500/10 p-4 text-sm text-red-100"
                            >
                                <p class="font-semibold text-red-50">
                                    Er ging iets mis
                                </p>

                                <ul class="mt-2 space-y-1">
                                    <li
                                        v-for="error in formErrors"
                                        :key="error"
                                    >
                                        {{ error }}
                                    </li>
                                </ul>
                            </div>

                            <FormNavigation
                                :show-previous="currentStep > 1"
                                :next-disabled="isSubmitting"
                                :next-loading="isSubmitting"
                                :next-label="
                                    isSubmitting
                                        ? 'Verzenden...'
                                        : currentStep === formSteps.length
                                          ? 'Verzenden'
                                          : 'Verder'
                                "
                                @next="nextStep"
                                @previous="previousStep"
                            />
                        </div>
                    </FormCard>
                </div>

                <div
                    v-else
                    class="relative z-10 mx-auto max-w-3xl px-4 text-center"
                >
                    <div
                        class="rounded-3xl border border-white/10 bg-white/5 p-8 shadow-2xl shadow-black/20 backdrop-blur-md"
                    >
                        <h2 class="text-3xl font-semibold text-white">
                            Er is momenteel geen barbecue gepland.
                        </h2>

                        <p class="mt-3 text-white/60">
                            Zodra er een nieuwe barbecue gepland is, kun je je
                            hier aanmelden.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Verenigingen -->
            <section
                v-if="eventVerenigingen.length"
                id="verenigingen"
                class="relative z-10 mx-auto max-w-7xl px-4 pt-12 pb-24 sm:px-6 md:pt-16 md:pb-28"
            >
                <div class="mb-10 max-w-2xl">
                    <p
                        class="text-sm font-bold tracking-wide text-secondary uppercase"
                    >
                        Samen aan tafel
                    </p>
                    <h2
                        class="mt-3 text-3xl font-semibold text-white md:text-4xl"
                    >
                        Verenigingen
                    </h2>
                    <p class="mt-3 text-base leading-7 text-white/60">
                        Zie hier de organisatorische verenigingen van de
                        Eindejaars BBQ.
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
                            :rel="
                                vereniging.website
                                    ? 'noopener noreferrer'
                                    : undefined
                            "
                            class="group flex min-h-28 items-center gap-4 rounded-2xl border border-white/10 bg-white/5 p-5 shadow-lg shadow-black/10 backdrop-blur-md transition hover:-translate-y-0.5 hover:border-primary/45 hover:bg-white/10"
                        >
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-white/10 bg-white/15 transition group-hover:border-secondary/50"
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

                            <span class="font-semibold text-white/85">
                                {{ vereniging.name }}
                            </span>
                        </a>
                    </div>
                </div>
            </section>
        </div>

        <Footer :partners="eventPartners" :verenigingen="eventVerenigingen" />

        <Modal v-model="showCalendarModal" size="sm">
            <template #header>
                <h2 class="text-xl font-semibold">Kies agenda</h2>
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

<style scoped>
.event-content-band {
    background:
        linear-gradient(
            180deg,
            hsl(24 20% 9%) 0%,
            hsl(24 24% 10%) 46%,
            hsl(24 20% 9%) 100%
        ),
        radial-gradient(
            ellipse 90% 42% at 50% 0%,
            hsl(14 93% 58% / 0.22),
            transparent 68%
        ),
        radial-gradient(
            ellipse 78% 34% at 50% 100%,
            hsl(157 56% 42% / 0.12),
            transparent 72%
        );
}

.event-content-band::before,
.event-content-band::after {
    position: absolute;
    inset: 0;
    pointer-events: none;
    content: '';
}

.event-content-band::before {
    opacity: 0.34;
    background-image:
        linear-gradient(hsl(42 91% 58% / 0.08) 1px, transparent 1px),
        linear-gradient(90deg, hsl(42 91% 58% / 0.05) 1px, transparent 1px);
    background-size: 72px 72px;
    mask-image: linear-gradient(
        180deg,
        transparent 0%,
        black 24%,
        black 76%,
        transparent 100%
    );
}

.event-content-band::after {
    opacity: 0.72;
    background:
        linear-gradient(
            115deg,
            transparent 0%,
            hsl(42 91% 58% / 0.09) 28%,
            transparent 46%
        ),
        linear-gradient(
            245deg,
            transparent 10%,
            hsl(157 56% 42% / 0.08) 38%,
            transparent 58%
        ),
        linear-gradient(
            180deg,
            transparent 0%,
            transparent 76%,
            hsl(24 20% 9% / 0.78) 100%
        );
}
</style>
