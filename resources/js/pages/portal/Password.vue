<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    portal: {
        token: string;
        company: {
            name: string;
        };
        client: {
            full_name: string;
        };
        requiresPasswordSetup: boolean;
    };
}>();

const page = usePage<SharedData>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.put(route('portal.password.update', props.portal.token), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>

<template>
    <Head :title="props.portal.requiresPasswordSetup ? 'Create portal password' : 'Change portal password'" />

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto flex min-h-screen w-full max-w-5xl items-center px-4 py-8 md:px-6">
            <div class="grid w-full gap-5 lg:grid-cols-[minmax(0,1fr)_430px]">
                <section class="rounded-3xl border border-slate-200 bg-white p-6 lg:p-8">
                    <p class="text-sm font-medium text-sky-700">Portal security</p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                        {{ portal.requiresPasswordSetup ? 'Create your password' : 'Change your password' }}
                    </h1>
                    <p class="mt-3 max-w-2xl text-base leading-7 text-slate-600">
                        {{
                            portal.requiresPasswordSetup
                                ? 'Create a password once so the next sign-in is quick and easy.'
                                : 'You can update your password here any time.'
                        }}
                    </p>

                    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-950">{{ portal.client.full_name }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ portal.company.name }}</p>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-6 lg:p-8">
                    <div v-if="page.props.flash.success" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ page.props.flash.success }}
                    </div>

                    <div class="space-y-2" :class="page.props.flash.success ? 'mt-5' : ''">
                        <p class="text-sm font-medium text-slate-500">Password</p>
                        <h2 class="text-2xl font-semibold tracking-tight text-slate-950">
                            {{ portal.requiresPasswordSetup ? 'Finish setting up your portal' : 'Update your password' }}
                        </h2>
                    </div>

                    <form class="mt-6 space-y-5" @submit.prevent="submit">
                        <div v-if="!portal.requiresPasswordSetup" class="grid gap-2">
                            <Label for="current_password">Current password</Label>
                            <Input id="current_password" v-model="form.current_password" type="password" autocomplete="current-password" class="h-11 text-base" />
                            <InputError :message="form.errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">New password</Label>
                            <Input id="password" v-model="form.password" type="password" autocomplete="new-password" class="h-11 text-base" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirm password</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                                autocomplete="new-password"
                                class="h-11 text-base"
                            />
                            <InputError :message="form.errors.password_confirmation" />
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <Button :disabled="form.processing" class="h-11 flex-1 text-base sm:flex-none">
                                {{ portal.requiresPasswordSetup ? 'Save password and continue' : 'Save new password' }}
                            </Button>

                            <Button v-if="!portal.requiresPasswordSetup" as-child variant="ghost">
                                <Link :href="route('portal.show', portal.token)">Back to portal</Link>
                            </Button>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
</template>
