import { computed } from 'vue';

export const messages = {
    en: {
        settings: {
            kicker: 'Settings',
            title: 'Shape the agency workspace around how your team really operates.',
            description: 'Manage countries, visa types, checklist templates, brand details, and reusable client messaging from one calm admin space.',
            business: {
                title: 'Business profile',
                subtitle: 'Keep your name, logo, contact details, locale, and branch-readiness aligned across the CRM.',
            },
        },
    },
    mn: {
        settings: {
            kicker: 'Тохиргоо',
            title: 'Багийнхаа ажлын урсгалд таарсан агентлагийн орчныг бүрдүүлээрэй.',
            description: 'Улс, визийн төрөл, баримт бичгийн чеклист, брэндийн мэдээлэл, харилцааны загваруудаа нэг дороос удирдаарай.',
            business: {
                title: 'Бизнесийн мэдээлэл',
                subtitle: 'CRM даяар харагдах нэр, лого, холбоо барих мэдээлэл, хэлний тохиргоо, салбарын бэлэн байдлыг нэг мөр болгоно.',
            },
        },
    },
};

export function useLocale(locale = 'en') {
    const resolvedLocale = computed(() => (messages[locale] ? locale : 'en'));

    const t = (key, fallback = key) => {
        const value = key
            .split('.')
            .reduce((carry, part) => carry?.[part], messages[resolvedLocale.value]);

        return value ?? fallback;
    };

    return {
        t,
        locale: resolvedLocale,
    };
}
