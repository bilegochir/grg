<script setup>
import FlashBanner from '@/Components/FlashBanner.vue';
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
</script>

<template>
    <Head :title="title" />

    <div class="min-h-screen bg-[linear-gradient(180deg,#fff7ed_0%,#f8fafc_18%,#f8fafc_100%)]">
        <header class="border-b border-brand-border bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:px-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="min-w-0">
                    <p class="text-lg font-semibold tracking-tight text-brand-text">{{ business.name }}</p>
                    <div class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-brand-muted">
                        <span>Applicant portal</span>
                        <span v-if="business.email">{{ business.email }}</span>
                        <span v-if="business.phone">{{ business.phone }}</span>
                    </div>
                </div>

                <div v-if="applicant" class="flex flex-wrap items-center gap-2">
                    <div class="rounded-full bg-orange-50 px-3 py-1 text-sm font-medium text-orange-700">
                        {{ applicant.name }}
                    </div>
                    <Link :href="route('portal.dashboard')" class="ui-button-ghost">Overview</Link>
                    <Link :href="route('portal.logout')" method="post" as="button" class="ui-button-secondary">Sign out</Link>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
            <FlashBanner :success="page.props.flash.success" :error="page.props.flash.error" />
            <slot />
        </main>
    </div>
</template>
