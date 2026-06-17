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

type BasicOption = {
    label: string;
    value: string;
    description?: string;
};

type EventVereniging = {
    id: number;
    name: string;
    education: string | null;
    logo: string | null;
    website: string | null;
    show_for_students_docents: boolean;
    show_for_partner_companies: boolean;
    current_guest_amount: number;
    free_guest_limit: number | null;
    over_limit_payment_amount: string | number | null;
    student_payment_amount: string | number | null;
    docent_payment_amount: string | number | null;
    members_must_pay: boolean;
};

type EventPartner = {
    id: number;
    name: string;
    logo: string | null;
    website: string | null;
    show_for_students_docents: boolean;
    show_for_partner_companies: boolean;
    current_guest_amount: number;
    free_guest_limit: number | null;
    over_limit_payment_amount: string | number | null;
    student_payment_amount: string | number | null;
    docent_payment_amount: string | number | null;
};

const showCalendarModal = ref(false);
const dismissedFlashBanner = ref(false);
const localFlashBanner = ref<FlashPayment | null>(null);

const currentStep = ref(1);

const fullName = ref('');
const email = ref('');

const selectedTypes = ref<string[]>([]);

const selectedEducation = ref<string[]>([]);
const selectedRegistrationOrganization = ref<string[]>([]);
const selectedOrganizationMembership = ref<string[]>([]);

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
            default_payment_amount: string | number | null;
            partners: EventPartner[];
            verenigingen: EventVereniging[];
        } | null;
        educationOptions: BasicOption[];
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
        educationOptions: () => [],
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

const educationOptions = computed(() =>
    props.educationOptions.length
        ? props.educationOptions
        : [
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
              customOption,
          ],
);

const guestAmountOptions = [
    {
        label: '1 persoon',
        value: '1',
    },
    {
        label: '2 personen',
        value: '2',
    },
    {
        label: '3 personen',
        value: '3',
    },
];

const membershipOptions = [
    {
        label: 'Ja, ik ben lid',
        value: 'yes',
    },
    {
        label: 'Nee, ik ben geen lid',
        value: 'no',
    },
];

const registrationOrganizationOptions = computed(() => [
    ...eventPartners.value
        .filter((partner) =>
            selectedTypes.value.includes('partner-bedrijf')
                ? partner.show_for_partner_companies
                : partner.show_for_students_docents,
        )
        .map((partner) => ({
            label: partner.name,
            value: `partner:${partner.name}`,
            description: 'Partner',
        })),
    ...eventVerenigingen.value
        .filter(
            (vereniging) =>
                (selectedTypes.value.includes('partner-bedrijf')
                    ? vereniging.show_for_partner_companies
                    : vereniging.show_for_students_docents) &&
                (selectedTypes.value.includes('partner-bedrijf') ||
                    !vereniging.education),
        )
        .map((vereniging) => ({
            label: vereniging.name,
            value: `vereniging:${vereniging.name}`,
            description: 'Vereniging',
        })),
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
        if (shouldAskEducation.value && !selectedEducation.value.length) {
            stepErrors.value.education = 'Selecteer een opleiding.';
        }

        if (
            shouldAskEducation.value &&
            hasCustomEducation.value &&
            !customEducation.value.trim()
        ) {
            stepErrors.value.customEducation = 'Vul je opleiding in.';
        }

        if (
            shouldAskOrganizationMembership.value &&
            !selectedOrganizationMembership.value.length
        ) {
            stepErrors.value.organizationMembership =
                'Selecteer of je lid bent van deze vereniging.';
        }

        if (
            selectedTypes.value.includes('partner-bedrijf') &&
            !selectedRegistrationOrganization.value.length
        ) {
            stepErrors.value.partnerOrganization =
                'Selecteer een partner of vereniging.';
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

const hasCustomEducation = computed(() => {
    return selectedEducation.value.includes('anders');
});

const selectedGuestAmount = computed({
    get: () => [guestAmount.value],
    set: (value: string[]) => {
        guestAmount.value = value[0] || '1';
    },
});

const selectedEducationValue = computed(() => selectedEducation.value[0] || '');

const selectedEducationOption = computed(() => {
    return (
        educationOptions.value.find(
            (option) => option.value === selectedEducationValue.value,
        ) || null
    );
});

const selectedEducationName = computed(() => {
    if (selectedEducationValue.value === 'anders') {
        return customEducation.value.trim();
    }

    return selectedEducationOption.value?.label || '';
});

const educationVereniging = computed(() => {
    if (
        !selectedEducationValue.value ||
        selectedEducationValue.value === 'anders'
    ) {
        return null;
    }

    return (
        eventVerenigingen.value.find(
            (vereniging) =>
                vereniging.education === selectedEducationValue.value,
        ) || null
    );
});

const selectedRegistrationOrganizationOption = computed(() => {
    const value = selectedRegistrationOrganization.value[0];

    if (!value) {
        return null;
    }

    const [type, ...nameParts] = value.split(':');
    const name = nameParts.join(':');

    if (!['partner', 'vereniging'].includes(type) || !name) {
        return null;
    }

    return { type, name };
});

const selectedRegistrationOrganizationModel = computed(() => {
    const organization = selectedRegistrationOrganizationOption.value;

    if (!organization) {
        return null;
    }

    if (organization.type === 'partner') {
        return (
            eventPartners.value.find(
                (partner) => partner.name === organization.name,
            ) || null
        );
    }

    return (
        eventVerenigingen.value.find(
            (vereniging) => vereniging.name === organization.name,
        ) || null
    );
});

const selectedEducationVereniging = computed(() => {
    if (!selectedTypes.value.includes('student')) {
        return null;
    }

    return educationVereniging.value;
});

const selectedMembershipVereniging = computed(() => {
    if (!selectedTypes.value.includes('student')) {
        return null;
    }

    if (selectedRegistrationOrganizationOption.value) {
        return null;
    }

    return selectedEducationVereniging.value;
});

const selectedPaymentOrganization = computed(() => {
    if (selectedRegistrationOrganizationModel.value) {
        return selectedRegistrationOrganizationModel.value;
    }

    if (selectedTypes.value.includes('student')) {
        return selectedEducationVereniging.value;
    }

    if (selectedTypes.value.includes('docent')) {
        return educationVereniging.value;
    }

    return null;
});

const shouldAskOrganizationMembership = computed(() => {
    return Boolean(selectedMembershipVereniging.value);
});

const canSelectGuestAmount = computed(() => {
    return selectedTypes.value.includes('partner-bedrijf');
});

const shouldAskEducation = computed(() => {
    return selectedTypes.value.some((type) =>
        ['student', 'docent'].includes(type),
    );
});

const canSelectRegistrationOrganization = computed(() => {
    return (
        selectedTypes.value.some((type) =>
            ['student', 'docent', 'partner-bedrijf'].includes(type),
        ) && registrationOrganizationOptions.value.length > 0
    );
});

const isOrganizationMember = computed(() => {
    if (selectedOrganizationMembership.value[0] === 'yes') {
        return true;
    }

    if (selectedOrganizationMembership.value[0] === 'no') {
        return false;
    }

    return null;
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
    'education',
    'custom_education',
    'customEducation',
    'partnerOrganizationType',
    'partner_organization_type',
    'partnerOrganization',
    'partner_organization_name',
    'organizationMembership',
    'is_organization_member',
    'guestAmount',
    'guest_amount',
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
    if (!types.includes('partner-bedrijf')) {
        guestAmount.value = '1';
    }

    if (!types.some((type) => ['student', 'docent'].includes(type))) {
        selectedEducation.value = [];
        customEducation.value = '';
        selectedRegistrationOrganization.value = [];
    }

    if (!types.includes('student')) {
        selectedOrganizationMembership.value = [];
    }
});

watch(selectedRegistrationOrganization, () => {
    selectedOrganizationMembership.value = [];
});

watch(
    () => selectedEducationValue.value,
    () => {
        selectedRegistrationOrganization.value = [];
        selectedOrganizationMembership.value = [];

        if (!hasCustomEducation.value) {
            customEducation.value = '';
        }
    },
);

const amountAsNumber = (amount: string | number | null | undefined) => {
    if (amount === null || amount === undefined || amount === '') {
        return null;
    }

    const numericAmount = Number(amount);

    return Number.isFinite(numericAmount) && numericAmount > 0
        ? numericAmount
        : null;
};

const formatEuro = (amount: number) => {
    return new Intl.NumberFormat('nl-NL', {
        style: 'currency',
        currency: 'EUR',
    }).format(amount);
};

const rolePaymentAmount = computed(() => {
    const role = selectedTypes.value[0];
    const organization = selectedPaymentOrganization.value;

    if (!organization || !['student', 'docent'].includes(role)) {
        return null;
    }

    const configuredRoleAmount = amountAsNumber(
        role === 'student'
            ? organization.student_payment_amount
            : organization.docent_payment_amount,
    );

    const mustPay =
        role === 'student' && isOrganizationMember.value
            ? Boolean(
                  'members_must_pay' in organization &&
                  organization.members_must_pay,
              )
            : configuredRoleAmount !== null;

    if (!mustPay) {
        return null;
    }

    if (
        isOrganizationMember.value &&
        'members_must_pay' in organization &&
        organization.members_must_pay
    ) {
        return (
            configuredRoleAmount ??
            amountAsNumber(organization.over_limit_payment_amount)
        );
    }

    return configuredRoleAmount;
});

const extraPersonsPaymentAmount = computed(() => {
    const organization = selectedPaymentOrganization.value;
    const role = selectedTypes.value[0];

    if (
        !organization ||
        !['student', 'docent', 'partner-bedrijf'].includes(role)
    ) {
        return null;
    }

    if (
        rolePaymentAmount.value !== null ||
        (isOrganizationMember.value &&
            'members_must_pay' in organization &&
            !organization.members_must_pay) ||
        organization.free_guest_limit === null
    ) {
        return null;
    }

    const amount = Number(guestAmount.value) || 1;
    const overLimitGuests = Math.min(
        amount,
        Math.max(
            0,
            organization.current_guest_amount +
                amount -
                organization.free_guest_limit,
        ),
    );

    if (overLimitGuests <= 0) {
        return null;
    }

    return amountAsNumber(organization.over_limit_payment_amount);
});

const defaultPaymentAmount = computed(() => {
    if (!shouldAskEducation.value || !selectedEducationValue.value) {
        return null;
    }

    if (selectedRegistrationOrganizationOption.value) {
        return null;
    }

    if (
        selectedEducationValue.value !== 'anders' &&
        educationVereniging.value
    ) {
        return null;
    }

    return amountAsNumber(props.activeEvent?.default_payment_amount);
});

const paymentPreview = computed(() => {
    const amount =
        rolePaymentAmount.value ??
        extraPersonsPaymentAmount.value ??
        defaultPaymentAmount.value;

    if (
        (shouldAskEducation.value && !selectedEducationValue.value) ||
        !selectedTypes.value.length
    ) {
        return {
            label: 'Nog niet bekend',
            title: 'Betaling',
            description: 'Selecteer je gegevens om te zien of je moet betalen.',
            amount,
            requiresPayment: false,
        };
    }

    if (amount === null) {
        return {
            label: 'Geen betaling nodig',
            title: 'Geen betaling nodig',
            description: 'Je kunt je aanmelding direct verzenden.',
            amount,
            requiresPayment: false,
        };
    }

    return {
        label: `${formatEuro(amount)} betalen`,
        title: 'Betaling vereist',
        description:
            'Na verzenden word je doorgestuurd om de betaling af te ronden.',
        amount,
        requiresPayment: true,
    };
});

const paymentPreviewClasses = computed(() => {
    return paymentPreview.value.requiresPayment
        ? 'border-amber-300/70 bg-amber-300/15 text-amber-50'
        : 'border-emerald-300/60 bg-emerald-300/10 text-emerald-50';
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
            label: 'Opleiding',
            value: selectedEducationName.value,
        },
        {
            label: 'Vereniging',
            value: selectedMembershipVereniging.value?.name || '',
        },
        {
            label: 'Partner / vereniging',
            value: selectedRegistrationOrganizationOption.value?.name || '',
        },
        {
            label: 'Lid van vereniging',
            value:
                isOrganizationMember.value === null
                    ? ''
                    : isOrganizationMember.value
                      ? 'Ja'
                      : 'Nee',
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
    selectedEducation.value = [];
    selectedRegistrationOrganization.value = [];
    selectedOrganizationMembership.value = [];
    customEducation.value = '';
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
            student_association: selectedTypes.value.includes('student')
                ? selectedMembershipVereniging.value?.name || null
                : null,
            custom_student_association: null,
            education: shouldAskEducation.value
                ? selectedEducation.value[0] || null
                : null,
            custom_education:
                shouldAskEducation.value &&
                selectedEducationValue.value === 'anders'
                    ? customEducation.value
                    : null,
            partner_organization_type:
                selectedRegistrationOrganizationOption.value?.type || null,
            partner_organization_name:
                selectedRegistrationOrganizationOption.value?.name || null,
            is_organization_member: shouldAskOrganizationMembership.value
                ? isOrganizationMember.value
                : null,
            company_name: null,
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
            class="home-hero relative flex min-h-[88svh] items-center overflow-hidden pt-20 pb-16"
            :style="{
                background:
                    'linear-gradient(90deg, rgba(20, 13, 10, 0.9) 0%, rgba(20, 13, 10, 0.64) 45%, rgba(20, 13, 10, 0.42) 100%), linear-gradient(180deg, rgba(20, 13, 10, 0.1) 0%, rgba(20, 13, 10, 0.82) 100%), url(/images/hero-2.jpg) no-repeat center / cover',
            }"
        >
            <div
                class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-background to-transparent"
            />

            <div class="home-hero__inner relative z-10 mx-auto w-full">
                <div class="home-hero__content">
                    <p
                        class="home-hero__badge inline-flex rounded-full border border-secondary/40 bg-secondary/15 font-bold tracking-wide text-secondary shadow-lg shadow-black/20 backdrop-blur"
                    >
                        Avans ATIx sluit het jaar buiten af
                    </p>

                    <h1
                        class="home-hero__title font-chewy leading-none text-white drop-shadow-[5px_5px_0_rgba(0,0,0,0.35)]"
                    >
                        {{ eventTitle }}
                    </h1>

                    <p class="home-hero__lead font-medium text-white/80">
                        Een avond vol vuur, verenigingen en goede gesprekken.
                        Meld je aan en schuif aan bij de Eindejaars BBQ.
                    </p>

                    <div class="home-hero__facts grid">
                        <div
                            class="home-hero__fact flex items-center rounded-xl border border-white/15 bg-white/10 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhCalendarBlank
                                class="home-hero__fact-icon shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="font-semibold">
                                {{ eventDateOnly }}
                            </span>
                        </div>

                        <div
                            class="home-hero__fact flex items-center rounded-xl border border-white/15 bg-white/10 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhClock
                                class="home-hero__fact-icon shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="font-semibold">
                                {{ eventTimeOnly }}
                            </span>
                        </div>

                        <div
                            class="home-hero__fact flex items-center rounded-xl border border-white/15 bg-white/10 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhMapPin
                                class="home-hero__fact-icon shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="font-semibold">
                                {{ eventLocation }}
                            </span>
                        </div>

                        <div
                            class="home-hero__fact flex items-center rounded-xl border border-white/15 bg-white/10 text-white shadow-lg shadow-black/20 backdrop-blur"
                        >
                            <PhUsersThree
                                class="home-hero__fact-icon shrink-0 text-secondary"
                                weight="bold"
                            />
                            <span class="font-semibold">
                                {{ eventVerenigingen.length || 0 }}
                                verenigingen
                            </span>
                        </div>
                    </div>

                    <div
                        class="home-hero__actions flex flex-col items-stretch sm:flex-row sm:items-center"
                    >
                        <Button
                            variant="primary"
                            size="lg"
                            class="home-hero__action"
                            @click="scrollToRegisterForm"
                        >
                            Aanmelden
                        </Button>

                        <Button
                            variant="outline"
                            size="lg"
                            class="home-hero__action"
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
                                v-if="shouldAskEducation"
                                v-model="selectedEducation"
                                label="Opleiding"
                                description="Selecteer je opleiding."
                                :error="stepErrors.education"
                                required
                                :max="1"
                                :options="educationOptions"
                            />

                            <Input
                                v-if="shouldAskEducation && hasCustomEducation"
                                v-model="customEducation"
                                name="customEducation"
                                label="Andere opleiding"
                                placeholder="Vul je opleiding in"
                                :error="
                                    stepErrors.customEducation ||
                                    stepErrors.custom_education
                                "
                                required
                            />

                            <CheckboxGroup
                                v-if="canSelectRegistrationOrganization"
                                v-model="selectedRegistrationOrganization"
                                label="Partner / vereniging"
                                description="Selecteer dit alleen als je aanmelding bij een zichtbare partner of losse vereniging hoort."
                                :max="1"
                                :options="registrationOrganizationOptions"
                            />

                            <CheckboxGroup
                                v-if="canSelectGuestAmount"
                                v-model="selectedGuestAmount"
                                label="Aantal personen"
                                description="Maximaal 3 personen."
                                :error="
                                    stepErrors.guestAmount ||
                                    stepErrors.guest_amount
                                "
                                required
                                :max="1"
                                :options="guestAmountOptions"
                            />

                            <CheckboxGroup
                                v-if="shouldAskOrganizationMembership"
                                v-model="selectedOrganizationMembership"
                                label="Lidmaatschap"
                                :description="`Ben je lid van de studievereniging ${selectedMembershipVereniging?.name}?`"
                                :error="
                                    stepErrors.organizationMembership ||
                                    stepErrors.is_organization_member
                                "
                                required
                                :max="1"
                                :options="membershipOptions"
                            />
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

                                <div
                                    class="rounded-xl border px-5 py-5"
                                    :class="paymentPreviewClasses"
                                >
                                    <div
                                        class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                                    >
                                        <div>
                                            <p
                                                class="text-sm font-semibold uppercase"
                                            >
                                                {{ paymentPreview.title }}
                                            </p>
                                            <p class="mt-1 text-sm opacity-80">
                                                {{ paymentPreview.description }}
                                            </p>
                                        </div>

                                        <div
                                            class="text-2xl font-bold tracking-normal sm:text-right"
                                        >
                                            {{
                                                paymentPreview.amount === null
                                                    ? '€ 0,00'
                                                    : formatEuro(
                                                          paymentPreview.amount,
                                                      )
                                            }}
                                        </div>
                                    </div>
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
.home-hero {
    padding-top: clamp(4rem, 9svh, 5rem);
}

.home-hero__inner {
    width: min(88%, 80rem);
}

.home-hero__content {
    max-width: min(62rem, 74vw);
}

.home-hero__badge {
    margin-bottom: clamp(0.875rem, 2svh, 1.25rem);
    padding: clamp(0.45rem, 0.9svh, 0.5rem) clamp(0.9rem, 1.8vw, 1rem);
    font-size: clamp(0.75rem, 1.25svh, 0.875rem);
}

.home-hero__title {
    max-width: 11ch;
    font-size: clamp(4.2rem, min(10vw, 15svh), 8rem);
}

.home-hero__lead {
    max-width: min(42rem, 58vw);
    margin-top: clamp(1rem, 2.6svh, 1.5rem);
    font-size: clamp(1.05rem, min(1.75vw, 2.75svh), 1.5rem);
    line-height: 1.35;
}

.home-hero__facts {
    max-width: min(54rem, 68vw);
    margin-top: clamp(1.25rem, 3.2svh, 2rem);
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: clamp(0.55rem, 1.2vw, 0.75rem);
}

.home-hero__fact {
    min-height: clamp(3.05rem, 7.5svh, 4.25rem);
    gap: clamp(0.6rem, 1.2vw, 0.75rem);
    padding: clamp(0.65rem, 1.45svh, 0.85rem) clamp(0.8rem, 1.65vw, 1rem);
    font-size: clamp(0.8125rem, 1.35svh, 0.875rem);
}

.home-hero__fact-icon {
    width: clamp(1.1rem, 2.35svh, 1.375rem);
    height: clamp(1.1rem, 2.35svh, 1.375rem);
}

.home-hero__actions {
    margin-top: clamp(1.5rem, 3.6svh, 2.5rem);
    gap: clamp(0.75rem, 1.5vw, 1rem);
}

.home-hero__action {
    padding: clamp(0.75rem, 1.55svh, 0.9rem) clamp(1.15rem, 2.4vw, 1.5rem);
    font-size: clamp(0.95rem, 1.55svh, 1.125rem);
}

@media (max-height: 760px) and (min-width: 1024px) {
    .home-hero {
        padding-top: 3.5rem;
        padding-bottom: 2.5rem;
    }

    .home-hero__title {
        font-size: clamp(3.75rem, min(8.2vw, 13.5svh), 6.25rem);
    }

    .home-hero__lead {
        max-width: min(38rem, 56vw);
        font-size: clamp(1rem, 2.5svh, 1.25rem);
    }

    .home-hero__facts {
        max-width: min(48rem, 66vw);
    }
}

@media (max-width: 900px) {
    .home-hero__content,
    .home-hero__lead,
    .home-hero__facts {
        max-width: none;
    }

    .home-hero__facts {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 560px) {
    .home-hero__inner {
        width: calc(100% - 2rem);
    }

    .home-hero__title {
        font-size: clamp(3.25rem, 15vw, 4.75rem);
    }

    .home-hero__facts {
        grid-template-columns: 1fr;
    }
}

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
            hsla(14, 93%, 58%, 0.22),
            transparent 68%
        ),
        radial-gradient(
            ellipse 78% 34% at 50% 100%,
            hsla(157, 56%, 42%, 0.12),
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
        linear-gradient(hsla(42, 91%, 58%, 0.08) 1px, transparent 1px),
        linear-gradient(90deg, hsla(42, 91%, 58%, 0.05) 1px, transparent 1px);
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
            hsla(42, 91%, 58%, 0.09) 28%,
            transparent 46%
        ),
        linear-gradient(
            245deg,
            transparent 10%,
            hsla(157, 56%, 42%, 0.08) 38%,
            transparent 58%
        ),
        linear-gradient(
            180deg,
            transparent 0%,
            transparent 76%,
            hsla(24, 20%, 9%, 0.78) 100%
        );
}
</style>
