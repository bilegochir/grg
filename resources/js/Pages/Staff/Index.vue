<script setup>
import AppIcon from '@/Components/AppIcon.vue';
import EmptyState from '@/Components/EmptyState.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SlideOver from '@/Components/SlideOver.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    staff: Array,
    workload: Array,
    roles: Array,
    permissions: Array,
    branches: Array,
    invitations: Array,
});

const activeTab = ref('overview');
const showStaffEditor = ref(false);
const showRoleEditor = ref(false);
const showBranchEditor = ref(false);
const showInviteEditor = ref(false);
const editingStaffId = ref(null);
const editingRoleId = ref(null);
const editingBranchId = ref(null);

const staffForm = useForm({
    name: '',
    email: '',
    job_title: '',
    branch_id: '',
    is_active: true,
    role_ids: [],
    permission_ids: [],
});

const roleForm = useForm({
    description: '',
    permission_ids: [],
});

const branchForm = useForm({
    name: '',
    slug: '',
    code: '',
    is_active: true,
});

const inviteForm = useForm({
    email: '',
    name: '',
    job_title: '',
    branch_id: '',
    role_id: props.roles[0]?.id ?? '',
});

const settingGroups = computed(() => [
    {
        label: 'Team',
        items: [
            { key: 'overview', label: 'Overview', icon: 'dashboard' },
            { key: 'people', label: 'People', icon: 'users' },
            { key: 'invites', label: 'Invites', icon: 'mail' },
        ],
    },
    {
        label: 'Organization',
        items: [
            { key: 'roles', label: 'Roles', icon: 'shield' },
            { key: 'branches', label: 'Branches', icon: 'map' },
        ],
    },
]);

const topMetrics = computed(() => ({
    staffCount: props.staff.length,
    activeStaffCount: props.staff.filter((member) => member.is_active).length,
    openCases: props.workload.reduce((sum, member) => sum + member.total_open_cases, 0),
    averageConversionRate: props.staff.length
        ? Math.round(props.staff.reduce((sum, member) => sum + member.metrics.conversion_rate, 0) / props.staff.length)
        : 0,
}));

const resetStaffForm = () => {
    editingStaffId.value = null;
    staffForm.reset();
    staffForm.branch_id = '';
    staffForm.is_active = true;
    staffForm.role_ids = [];
    staffForm.permission_ids = [];
};

const resetRoleForm = () => {
    editingRoleId.value = null;
    roleForm.reset();
    roleForm.permission_ids = [];
};

const resetBranchForm = () => {
    editingBranchId.value = null;
    branchForm.reset();
    branchForm.is_active = true;
};

const resetInviteForm = () => {
    inviteForm.reset();
    inviteForm.branch_id = '';
    inviteForm.role_id = props.roles[0]?.id ?? '';
};

const openStaffEditor = (member) => {
    editingStaffId.value = member.id;
    staffForm.name = member.name;
    staffForm.email = member.email;
    staffForm.job_title = member.job_title ?? '';
    staffForm.branch_id = member.branch?.id ?? '';
    staffForm.is_active = member.is_active;
    staffForm.role_ids = member.roles.map((role) => role.id);
    staffForm.permission_ids = member.direct_permissions.map((permission) => permission.id);
    showStaffEditor.value = true;
};

const openRoleEditor = (role) => {
    editingRoleId.value = role.id;
    roleForm.description = role.description ?? '';
    roleForm.permission_ids = role.permissions.map((permission) => permission.id);
    showRoleEditor.value = true;
};

const openBranchCreate = () => {
    resetBranchForm();
    showBranchEditor.value = true;
};

const openInviteEditor = () => {
    resetInviteForm();
    showInviteEditor.value = true;
};

const openBranchEditor = (branch) => {
    editingBranchId.value = branch.id;
    branchForm.name = branch.name;
    branchForm.slug = branch.slug;
    branchForm.code = branch.code ?? '';
    branchForm.is_active = branch.is_active;
    showBranchEditor.value = true;
};

const saveStaff = () => {
    staffForm.patch(route('staff.users.update', editingStaffId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showStaffEditor.value = false;
            resetStaffForm();
        },
    });
};

const saveRole = () => {
    roleForm.patch(route('staff.roles.update', editingRoleId.value), {
        preserveScroll: true,
        onSuccess: () => {
            showRoleEditor.value = false;
            resetRoleForm();
        },
    });
};

const saveBranch = () => {
    if (editingBranchId.value) {
        branchForm.patch(route('staff.branches.update', editingBranchId.value), {
            preserveScroll: true,
            onSuccess: () => {
                showBranchEditor.value = false;
                resetBranchForm();
            },
        });
        return;
    }

    branchForm.post(route('staff.branches.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showBranchEditor.value = false;
            resetBranchForm();
        },
    });
};

const saveInvite = () => {
    inviteForm.post(route('staff.invitations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showInviteEditor.value = false;
            resetInviteForm();
        },
    });
};
</script>

<template>
    <Head title="Staff" />

    <AuthenticatedLayout>
        <template #header>
            <div class="mb-6">
                <h1 class="text-[24px] font-semibold text-slate-900 tracking-tight">Staff & Access</h1>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row lg:items-start lg:gap-12">
            <aside class="mb-8 w-full lg:mb-0 lg:w-48 lg:shrink-0 lg:sticky lg:top-6">
                <nav v-for="group in settingGroups" :key="group.label" class="mb-8 last:mb-0">
                    <p class="mb-2 px-3 text-[11px] font-bold uppercase tracking-wider text-slate-400">{{ group.label }}</p>
                    <div class="space-y-0.5">
                        <button
                            v-for="item in group.items"
                            :key="item.key"
                            class="flex w-full items-center gap-2.5 rounded-md px-3 py-1.5 text-left text-[13px] font-medium transition-colors"
                            :class="activeTab === item.key ? 'bg-slate-200/60 text-slate-900 font-bold' : 'text-slate-500 hover:bg-slate-200/40 hover:text-slate-900'"
                            @click="activeTab = item.key"
                        >
                            <AppIcon :name="item.icon" :size="14" :class="activeTab === item.key ? 'text-slate-900' : 'text-slate-400'" />
                            {{ item.label }}
                        </button>
                    </div>
                </nav>
            </aside>

            <div class="flex-1 min-w-0">
                <!-- Overview Section -->
                <div v-if="activeTab === 'overview'" class="max-w-4xl space-y-10">
                    <div>
                        <div class="mb-6">
                            <h2 class="text-[20px] font-semibold text-slate-900">Team Statistics</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Real-time performance and workload visibility across the organization.</p>
                        </div>
                        <div class="flex flex-wrap divide-x divide-slate-100 rounded-lg border border-slate-200 bg-white shadow-sm overflow-hidden">
                            <div class="flex-1 min-w-[150px] p-4 hover:bg-slate-50/50 transition-colors">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Active Staff</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900 leading-none">{{ topMetrics.activeStaffCount }}</p>
                                <p class="mt-2 text-[11px] text-slate-500 font-medium">{{ topMetrics.staffCount }} total</p>
                            </div>
                            <div class="flex-1 min-w-[150px] p-4 hover:bg-slate-50/50 transition-colors">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Open Cases</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900 leading-none">{{ topMetrics.openCases }}</p>
                                <p class="mt-2 text-[11px] text-slate-500 font-medium">All queues</p>
                            </div>
                            <div class="flex-1 min-w-[150px] p-4 hover:bg-slate-50/50 transition-colors">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Avg Conv.</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900 leading-none">{{ topMetrics.averageConversionRate }}%</p>
                                <p class="mt-2 text-[11px] text-slate-500 font-medium">Lead to app</p>
                            </div>
                            <div class="flex-1 min-w-[150px] p-4 hover:bg-slate-50/50 transition-colors">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Branches</p>
                                <p class="mt-2 text-2xl font-bold text-slate-900 leading-none">{{ branches.length }}</p>
                                <p class="mt-2 text-[11px] text-slate-500 font-medium">{{ branches.filter(b => b.is_active).length }} active</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-6">
                            <h3 class="text-[16px] font-semibold text-slate-900">Performance Dashboard</h3>
                            <p class="mt-1 text-[13px] text-slate-500">Individual metrics for team members available for work.</p>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                            <table class="w-full text-left text-[13px]">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50/50">
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Member</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Status</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Cases</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Conv.</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px] text-right">Avg Days</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="member in staff" :key="member.id" class="hover:bg-slate-50/30 transition-colors">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900">{{ member.name }}</p>
                                            <p class="text-[11px] text-slate-500">{{ member.job_title || 'Team member' }}<span v-if="member.branch"> • {{ member.branch.name }}</span></p>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="member.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                                {{ member.is_active ? 'Active' : 'Paused' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">{{ member.metrics.assigned_cases_count }}</td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">{{ member.metrics.conversion_rate }}%</td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-900">{{ member.metrics.avg_processing_days ?? '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Workload Section -->
                    <div class="pt-10 border-t border-slate-100">
                        <div class="mb-6">
                            <h3 class="text-[16px] font-semibold text-slate-900">Workload by Stage</h3>
                            <p class="mt-1 text-[13px] text-slate-500">Breakdown of case distribution per team member.</p>
                        </div>
                        <div v-if="workload.length" class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                            <table class="w-full text-left text-[13px]">
                                <thead>
                                    <tr class="border-b border-slate-100 bg-slate-50/50">
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Member</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Branch</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Total Open</th>
                                        <th class="px-4 py-2.5 font-bold text-slate-400 uppercase tracking-wider text-[11px]">Distribution</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    <tr v-for="member in workload" :key="member.user_id" class="hover:bg-slate-50/30 transition-colors">
                                        <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">{{ member.name }}</td>
                                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ member.branch_name || '—' }}</td>
                                        <td class="px-4 py-3 font-bold text-slate-900">{{ member.total_open_cases }}</td>
                                        <td class="px-4 py-3 min-w-[200px]">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <template v-for="stage in member.stages" :key="`${member.user_id}-${stage.slug}`">
                                                    <div v-if="stage.count > 0" class="flex items-center gap-1 bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                                        <span class="text-slate-500 font-medium">{{ stage.stage }}:</span>
                                                        <span class="text-slate-900 font-bold">{{ stage.count }}</span>
                                                    </div>
                                                </template>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- People Section -->
                <div v-if="activeTab === 'people'" class="max-w-4xl">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">People</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Manage access levels and branch assignments for individual members.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openInviteEditor">Invite</PrimaryButton>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white shadow-card overflow-hidden divide-y divide-slate-100">
                        <div 
                            v-for="member in staff" 
                            :key="member.id" 
                            class="flex items-start justify-between gap-4 px-4 py-4 hover:bg-slate-50/50 transition-colors group cursor-pointer"
                            @click="openStaffEditor(member)"
                        >
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <p class="text-[14px] font-bold text-slate-900 group-hover:text-black transition-colors">{{ member.name }}</p>
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ member.job_title || 'No title' }}</span>
                                    <span v-if="member.branch" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ member.branch.name }}</span>
                                </div>
                                <p class="mt-1 text-[13px] text-slate-500">{{ member.email }}</p>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span v-for="role in member.roles" :key="role.id" class="rounded bg-blue-50 px-1.5 py-0.5 text-[10px] font-bold text-blue-600 uppercase tracking-wider">
                                        {{ role.name }}
                                    </span>
                                    <span v-for="permission in member.direct_permissions" :key="permission.id" class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-700 uppercase tracking-wider">
                                        {{ permission.label }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" class="ui-button-ghost h-8 opacity-0 group-hover:opacity-100 transition-opacity">Edit access</button>
                                <AppIcon name="chevronRight" :size="14" class="text-slate-300 group-hover:text-slate-450 transition-colors" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Invites Section -->
                <div v-if="activeTab === 'invites'" class="max-w-4xl">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Pending Invites</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Track and manage invitations sent to prospective team members.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openInviteEditor">Invite team member</PrimaryButton>
                    </div>

                    <div class="grid gap-4">
                        <div v-for="invitation in invitations" :key="invitation.id" class="rounded-lg border border-slate-200 bg-white p-4 shadow-card hover:border-slate-300 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-[14px] font-bold text-slate-900">{{ invitation.name || invitation.email }}</p>
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ invitation.role_name }}</span>
                                        <span v-if="invitation.branch_name" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ invitation.branch_name }}</span>
                                        <span
                                            class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                            :class="invitation.status === 'pending' ? 'bg-amber-50 text-amber-700' : invitation.status === 'accepted' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                        >
                                            {{ invitation.status }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-[13px] text-slate-500">{{ invitation.email }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400 font-medium uppercase tracking-wider">
                                        By {{ invitation.invited_by || 'System' }} • Expires {{ invitation.expires_at }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <a :href="invitation.accept_url" class="ui-button-secondary h-8 text-[12px]">Copy link</a>
                                </div>
                            </div>
                        </div>

                        <div v-if="!invitations.length" class="rounded-lg border border-dashed border-slate-200 p-12 text-center">
                            <p class="text-[14px] font-medium text-slate-600">No pending invitations.</p>
                            <p class="mt-1 text-[13px] text-slate-500">Invite teammates to collaborate in your workspaces.</p>
                        </div>
                    </div>
                </div>

                <!-- Roles Section -->
                <div v-if="activeTab === 'roles'" class="max-w-4xl">
                    <div class="mb-6">
                        <h2 class="text-[20px] font-semibold text-slate-900">Roles & Permissions</h2>
                        <p class="mt-1 text-[13px] text-slate-500">Define reusable permission sets for different job functions.</p>
                    </div>

                    <div class="grid gap-4">
                        <div v-for="role in roles" :key="role.id" class="rounded-lg border border-slate-200 bg-white p-4 shadow-card hover:border-slate-300 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-[14px] font-bold text-slate-900">{{ role.name }}</p>
                                    <p class="mt-1 text-[13px] text-slate-500">{{ role.description }}</p>
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        <span v-for="permission in role.permissions" :key="permission.id" class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                                            {{ permission.label }}
                                        </span>
                                    </div>
                                </div>
                                <button type="button" class="ui-button-ghost h-8" @click="openRoleEditor(role)">Edit</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branches Section -->
                <div v-if="activeTab === 'branches'" class="max-w-4xl">
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-[20px] font-semibold text-slate-900">Branches</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Manage office locations and team assignments.</p>
                        </div>
                        <PrimaryButton icon="plus" @click="openBranchCreate">Add branch</PrimaryButton>
                    </div>

                    <div class="grid gap-4">
                                <div 
                                    v-for="branch in branches" 
                                    :key="branch.id" 
                                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-card hover:border-slate-300 transition-colors group cursor-pointer"
                                    @click="openBranchEditor(branch)"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <p class="text-[14px] font-bold text-slate-900 group-hover:text-black transition-colors">{{ branch.name }}</p>
                                                <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ branch.code || 'No code' }}</span>
                                                <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider" :class="branch.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                                                    {{ branch.is_active ? 'Active' : 'Paused' }}
                                                </span>
                                            </div>
                                            <p class="mt-1.5 text-[13px] text-slate-500">
                                                {{ branch.staff_count }} staff members • {{ branch.cases_count }} cases managed
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            <button type="button" class="ui-button-ghost h-8 opacity-0 group-hover:opacity-100 transition-opacity">Edit</button>
                                            <AppIcon name="chevronRight" :size="14" class="text-slate-300 group-hover:text-slate-450 transition-colors" />
                                        </div>
                                    </div>
                                </div>
                    </div>
                </div>
            </div>
        </div>

        <SlideOver :show="showStaffEditor" width="wide" @close="showStaffEditor = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-slate-100 bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[18px] font-semibold text-slate-900">Edit Staff Access</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Modify permissions and branch assignments.</p>
                        </div>
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 transition-colors" @click="showStaffEditor = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveStaff">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="staff_name" value="Name" />
                            <input id="staff_name" v-model="staffForm.name" class="ui-input" />
                            <InputError :message="staffForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="staff_email" value="Email" />
                            <input id="staff_email" v-model="staffForm.email" class="ui-input" type="email" />
                            <InputError :message="staffForm.errors.email" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="staff_job_title" value="Job title" />
                            <input id="staff_job_title" v-model="staffForm.job_title" class="ui-input" />
                            <InputError :message="staffForm.errors.job_title" />
                        </div>
                        <div>
                            <InputLabel for="staff_branch" value="Branch" />
                            <select id="staff_branch" v-model="staffForm.branch_id" class="ui-select">
                                <option value="">No branch yet</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                            <InputError :message="staffForm.errors.branch_id" />
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Roles" />
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label v-for="role in roles" :key="role.id" class="flex items-start gap-3 rounded-lg border border-slate-200 px-4 py-4 hover:bg-slate-50 transition-colors cursor-pointer group">
                                <input v-model="staffForm.role_ids" :value="role.id" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10" />
                                <div>
                                    <p class="text-[13px] font-bold text-slate-900">{{ role.name }}</p>
                                    <p class="mt-0.5 text-[12px] text-slate-500 leading-relaxed">{{ role.description }}</p>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div>
                        <InputLabel value="Direct permissions" />
                        <div class="mt-3 space-y-4">
                            <div v-for="group in permissions" :key="group.group" class="rounded-lg border border-slate-200 px-4 py-4 bg-slate-50/30">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ group.group }}</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label v-for="permission in group.items" :key="permission.id" class="flex items-center gap-2.5 cursor-pointer">
                                        <input v-model="staffForm.permission_ids" :value="permission.id" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10" />
                                        <span class="text-[13px] font-medium text-slate-700">{{ permission.label }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-4 hover:bg-slate-50 transition-colors cursor-pointer">
                        <input v-model="staffForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10" />
                        <span class="text-[13px] font-bold text-slate-900">Staff member is active for assignments</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showStaffEditor = false">Never mind</button>
                        <PrimaryButton :loading="staffForm.processing">Save staff member</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showRoleEditor" width="wide" @close="showRoleEditor = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-slate-100 bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[18px] font-semibold text-slate-900">Edit Role</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Define the permissions for this user group.</p>
                        </div>
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 transition-colors" @click="showRoleEditor = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveRole">
                    <div>
                        <InputLabel for="role_description" value="Description" />
                        <textarea id="role_description" v-model="roleForm.description" rows="3" class="ui-textarea"></textarea>
                        <InputError :message="roleForm.errors.description" />
                    </div>
                    <div class="space-y-4">
                        <div v-for="group in permissions" :key="group.group" class="rounded-lg border border-slate-200 px-4 py-4 bg-slate-50/30">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">{{ group.group }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                <label v-for="permission in group.items" :key="permission.id" class="flex items-center gap-2.5 cursor-pointer">
                                    <input v-model="roleForm.permission_ids" :value="permission.id" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10" />
                                    <span class="text-[13px] font-medium text-slate-700">{{ permission.label }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showRoleEditor = false">Never mind</button>
                        <PrimaryButton :loading="roleForm.processing">Save role</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showBranchEditor" width="wide" @close="showBranchEditor = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-slate-100 bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[18px] font-semibold text-slate-900">{{ editingBranchId ? 'Edit Branch' : 'Add Branch' }}</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Configure regional or office-specific settings.</p>
                        </div>
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 transition-colors" @click="showBranchEditor = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveBranch">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="branch_name" value="Branch name" />
                            <input id="branch_name" v-model="branchForm.name" class="ui-input" />
                            <InputError :message="branchForm.errors.name" />
                        </div>
                        <div>
                            <InputLabel for="branch_code" value="Code" />
                            <input id="branch_code" v-model="branchForm.code" class="ui-input" />
                            <InputError :message="branchForm.errors.code" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="branch_slug" value="Slug" />
                        <input id="branch_slug" v-model="branchForm.slug" class="ui-input" placeholder="auto-generated if left blank" />
                        <InputError :message="branchForm.errors.slug" />
                    </div>
                    <label class="flex items-center gap-3 rounded-lg border border-slate-200 px-4 py-4 hover:bg-slate-50 transition-colors cursor-pointer">
                        <input v-model="branchForm.is_active" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900/10" />
                        <span class="text-[13px] font-bold text-slate-900">Branch is active for staff and case assignment</span>
                    </label>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showBranchEditor = false">Never mind</button>
                        <PrimaryButton :loading="branchForm.processing">{{ editingBranchId ? 'Save branch' : 'Add branch' }}</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>

        <SlideOver :show="showInviteEditor" width="wide" @close="showInviteEditor = false">
            <div class="flex h-full flex-col">
                <div class="sticky top-0 border-b border-slate-100 bg-white px-6 py-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="text-[18px] font-semibold text-slate-900">Invite Teammate</h2>
                            <p class="mt-1 text-[13px] text-slate-500">Send an email invitation with predefined access.</p>
                        </div>
                        <button class="rounded-md p-1.5 text-slate-400 hover:bg-slate-100 transition-colors" @click="showInviteEditor = false">
                            <AppIcon name="close" :size="18" />
                        </button>
                    </div>
                </div>
                <form class="flex-1 space-y-5 overflow-y-auto px-6 py-6" @submit.prevent="saveInvite">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="invite_email" value="Email" />
                            <input id="invite_email" v-model="inviteForm.email" type="email" class="ui-input" />
                            <InputError :message="inviteForm.errors.email" />
                        </div>
                        <div>
                            <InputLabel for="invite_name" value="Name" />
                            <input id="invite_name" v-model="inviteForm.name" class="ui-input" placeholder="Optional" />
                            <InputError :message="inviteForm.errors.name" />
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <InputLabel for="invite_job_title" value="Job title" />
                            <input id="invite_job_title" v-model="inviteForm.job_title" class="ui-input" placeholder="Agent, Manager, Support" />
                            <InputError :message="inviteForm.errors.job_title" />
                        </div>
                        <div>
                            <InputLabel for="invite_role" value="Role" />
                            <select id="invite_role" v-model="inviteForm.role_id" class="ui-select">
                                <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                            </select>
                            <InputError :message="inviteForm.errors.role_id" />
                        </div>
                        <div>
                            <InputLabel for="invite_branch" value="Branch" />
                            <select id="invite_branch" v-model="inviteForm.branch_id" class="ui-select">
                                <option value="">Shared workspace</option>
                                <option v-for="branch in branches" :key="branch.id" :value="branch.id">{{ branch.name }}</option>
                            </select>
                            <InputError :message="inviteForm.errors.branch_id" />
                        </div>
                    </div>
                    <div class="sticky bottom-0 flex justify-end gap-3 border-t border-slate-100 bg-white py-5">
                        <button type="button" class="ui-button-ghost" @click="showInviteEditor = false">Never mind</button>
                        <PrimaryButton :loading="inviteForm.processing">Send invitation</PrimaryButton>
                    </div>
                </form>
            </div>
        </SlideOver>
    </AuthenticatedLayout>
</template>
