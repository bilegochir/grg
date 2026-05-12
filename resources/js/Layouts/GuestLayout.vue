<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { useLocale } from '@/lib/i18n';
import { Link } from '@inertiajs/vue3';

const { t } = useLocale();
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center bg-slate-50 px-4 py-10"
    >
        <div class="mb-6 flex flex-col items-center gap-3 text-center">
            <Link href="/">
                <ApplicationLogo class="h-16 w-16 fill-current text-brand-primary" />
            </Link>
            <div class="space-y-1">
                <p class="text-lg font-semibold tracking-tight text-slate-900">{{ t('common.agency') }}</p>
                <p class="text-sm text-slate-500">{{ t('auth.guestTagline') }}</p>
            </div>
        </div>

        <div
            class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white px-6 py-6 shadow-[0_20px_60px_rgba(15,23,42,0.08)]"
        >
            <slot />
        </div>
    </div>
</template>
