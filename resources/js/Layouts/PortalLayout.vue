<script setup>
import FlashBanner from '@/Components/FlashBanner.vue';
import { useLocale } from '@/lib/i18n';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({
    title: {
        type: String,
        default: 'Portal',
    },
    business: {
        type: Object,
        required: true,
    },
    applicant: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const { t } = useLocale();
</script>

<template>
    <Head :title="title" />

    <div class="portal-shell min-h-screen">
        <header class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 sm:pt-5">
            <div class="portal-frame flex flex-col gap-3 px-4 py-3 sm:px-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                        <img
                            v-if="business.logo_url"
                            :src="business.logo_url"
                            :alt="`${business.name} logo`"
                            class="h-full w-full object-contain"
                        />
                        <span v-else class="text-base font-semibold tracking-tight text-brand-text">
                            {{ business.name?.slice(0, 1) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-base font-semibold tracking-tight text-brand-text">{{ business.name }}</p>
                        <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[13px] text-brand-muted">
                            <span>{{ t('pages.portal.portalLabel') }}</span>
                            <span v-if="business.email">{{ business.email }}</span>
                            <span v-if="business.phone">{{ business.phone }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="applicant" class="flex flex-wrap items-center gap-2">
                    <div class="portal-chip-muted">
                        {{ applicant.name }}
                    </div>
                    <Link :href="route('portal.dashboard')" class="ui-button-ghost">{{ t('common.overview') }}</Link>
                    <Link :href="route('portal.logout')" method="post" as="button" class="ui-button-secondary">{{ t('common.signOut') }}</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-6">
            <FlashBanner :success="page.props.flash.success" :error="page.props.flash.error" />
            <slot />
        </main>
    </div>
</template>
