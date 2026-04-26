<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

interface TeamMember {
    id: number;
    name: string;
    email: string;
    role: string;
    role_label: string;
    joined_at: null | string;
    is_current_user: boolean;
}

interface PendingInvite {
    id: number;
    name: null | string;
    email: string;
    role: string;
    role_label: string;
    invited_by_name: null | string;
    created_at: null | string;
    expires_at: null | string;
}

interface RoleOption {
    value: string;
    label: string;
}

const props = defineProps<{
    pendingInvites: PendingInvite[];
    roleOptions: RoleOption[];
    teamMembers: TeamMember[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Team',
        href: '/settings/team',
    },
];

const form = useForm({
    name: '',
    email: '',
    role: 'staff',
});

const formatDate = (value: null | string) =>
    value ? new Intl.DateTimeFormat('en', { dateStyle: 'medium' }).format(new Date(value)) : 'Not set';

const roleBadgeClass = (role: string) =>
    ({
        admin: 'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950',
        case_manager: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
        staff: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200',
        viewer: 'bg-muted text-muted-foreground',
    })[role] ?? 'bg-muted text-muted-foreground';

const submit = () => {
    form.post(route('settings.team.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const updateRole = (member: TeamMember, event: Event) => {
    const target = event.target as HTMLSelectElement;

    router.patch(
        route('settings.team.role.update', member.id),
        {
            role: target.value,
        },
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Team" />

        <SettingsLayout>
            <div class="space-y-8">
                <section class="grid gap-6 xl:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
                    <div class="app-panel space-y-6 p-6">
                        <HeadingSmall title="Invite teammate" />

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="invite_name">Name</Label>
                                    <Input id="invite_name" v-model="form.name" placeholder="Ariunaa Bat" />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="grid gap-2">
                                    <Label for="invite_email">Email address</Label>
                                    <Input id="invite_email" v-model="form.email" type="email" required placeholder="team@company.com" />
                                    <InputError :message="form.errors.email" />
                                </div>
                            </div>

                            <div class="grid gap-2 md:max-w-xs">
                                <Label for="invite_role">Role</Label>
                                <select
                                    id="invite_role"
                                    v-model="form.role"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15"
                                >
                                    <option v-for="option in props.roleOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError :message="form.errors.role" />
                            </div>

                            <div class="flex items-center justify-between gap-4 border-t border-border/70 pt-4">
                                <p class="text-sm text-muted-foreground">
                                    We’ll email a secure link so they can set their password and join the company workspace.
                                </p>
                                <Button :disabled="form.processing">Send invite</Button>
                            </div>
                        </form>
                    </div>

                    <div class="app-panel space-y-4 p-6">
                        <HeadingSmall title="Pending invites" />

                        <div v-if="props.pendingInvites.length" class="divide-y divide-border/70">
                            <div
                                v-for="invite in props.pendingInvites"
                                :key="invite.id"
                                class="px-1 py-4 first:pt-0 last:pb-0"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ invite.name || invite.email }}</p>
                                        <p class="truncate text-sm text-muted-foreground">{{ invite.email }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span :class="['rounded-full px-2.5 py-1 text-[11px] font-medium', roleBadgeClass(invite.role)]">
                                            {{ invite.role_label }}
                                        </span>
                                        <span class="rounded-full bg-muted px-2.5 py-1 text-[11px] font-medium text-muted-foreground">
                                            Pending
                                        </span>
                                    </div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
                                    <span>Sent {{ formatDate(invite.created_at) }}</span>
                                    <span>Expires {{ formatDate(invite.expires_at) }}</span>
                                    <span v-if="invite.invited_by_name">Invited by {{ invite.invited_by_name }}</span>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-2xl border border-dashed border-border/80 bg-muted/20 px-4 py-8 text-center">
                            <p class="text-sm font-medium text-foreground">No pending invites</p>
                            <p class="mt-1 text-sm text-muted-foreground">Invites you send will appear here until they’re accepted.</p>
                        </div>
                    </div>
                </section>

                <section class="app-panel space-y-5 p-6">
                    <HeadingSmall title="Current team" />

                    <div class="divide-y divide-border/70">
                        <div
                            v-for="member in props.teamMembers"
                            :key="member.id"
                            class="flex flex-col gap-3 py-4 first:pt-0 last:pb-0 md:flex-row md:items-center md:justify-between"
                        >
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-medium text-foreground">{{ member.name }}</p>
                                    <span :class="['rounded-full px-2.5 py-1 text-[11px] font-medium', roleBadgeClass(member.role)]">
                                        {{ member.role_label }}
                                    </span>
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[11px] font-medium"
                                        :class="member.is_current_user ? 'bg-slate-950 text-white dark:bg-slate-100 dark:text-slate-950' : 'bg-muted text-muted-foreground'"
                                    >
                                        {{ member.is_current_user ? 'You' : 'Member' }}
                                    </span>
                                </div>
                                <p class="mt-1 truncate text-sm text-muted-foreground">{{ member.email }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">Joined {{ formatDate(member.joined_at) }}</p>
                            </div>
                            <div class="flex items-center justify-between gap-4 md:justify-end">
                                <span class="text-xs text-muted-foreground md:hidden">Role</span>
                                <select
                                    :value="member.role"
                                    :disabled="member.is_current_user"
                                    class="flex h-10 rounded-2xl border border-input bg-background px-3 py-2 text-sm focus-visible:border-foreground/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring/15 disabled:cursor-not-allowed disabled:opacity-60"
                                    @change="updateRole(member, $event)"
                                >
                                    <option v-for="option in props.roleOptions" :key="option.value" :value="option.value">
                                        {{ option.label }}
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
