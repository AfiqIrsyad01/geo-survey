<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    users: Array,
});

const showForm = ref(false);
const editingUser = ref(null);

const form = useForm({
    id: null,
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'staff',
});

const submit = () => {
    if (editingUser.value) {
        form.put(route('users.update', editingUser.value.id), {
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'IDENTITY UPDATED',
                    text: `Personnel record for ${editingUser.value.name} has been synchronized.`,
                    confirmButtonColor: '#0a192f',
                    timer: 2000
                });
                closeForm();
            },
        });
    } else {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
            onSuccess: () => {
                Swal.fire({
                    icon: 'success',
                    title: 'IDENTITY INITIALIZED',
                    text: `A new personnel profile for ${form.name} has been registered in the core.`,
                    confirmButtonColor: '#0a192f',
                    timer: 3000
                });
                closeForm();
            },
        });
    }
};

const openAddForm = () => {
    editingUser.value = null;
    form.reset();
    form.clearErrors();
    showForm.value = true;
};

const openEditForm = (user) => {
    if (user.role === 'admin') return;
    editingUser.value = user;
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.clearErrors();
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingUser.value = null;
    form.reset();
};

const deleteUser = (user) => {
    if (user.role === 'admin') return;
    Swal.fire({
        title: 'PURGE IDENTITY?',
        text: `Records for ${user.name} will be removed from the core. This is irreversible.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'CONFIRM',
        background: '#ffffff',
        customClass: {
            title: 'font-black text-geo-navy',
            content: 'text-xs italic'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('users.destroy', user.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'PURGE COMPLETE',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};

const toggleStatus = (user) => {
    const action = user.is_active ? 'SUSPEND' : 'ACTIVATE';
    Swal.fire({
        title: `${action} CLEARANCE?`,
        text: `Operation access for ${user.name} will be limited.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: user.is_active ? '#ef4444' : '#10b981',
        confirmButtonText: `YES, ${action}`
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('users.toggle-status', user.id), {
                onSuccess: () => {
                    Swal.fire({
                        title: 'STATUS UPDATED',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }
    });
};

const emailExists = computed(() => {
    if (!form.email) return false;
    return props.users.some(u => 
        u.email.toLowerCase() === form.email.toLowerCase() && 
        u.id !== form.id
    );
});

const stats = computed(() => {
    return {
        total: props.users.length,
        active: props.users.filter(u => u.is_active).length,
        staff: props.users.filter(u => u.role === 'staff').length,
    };
});

const sortedUsers = computed(() => {
    return [...props.users].sort((a, b) => {
        const roles = { 'admin': 0, 'hod': 1, 'staff': 2 };
        if (roles[a.role] !== roles[b.role]) {
            return roles[a.role] - roles[b.role];
        }
        return a.name.localeCompare(b.name);
    });
});

const getRoleIcon = (role) => {
    switch (role) {
        case 'admin': return 'fa-solid fa-user-shield';
        case 'hod': return 'fa-solid fa-user-tie';
        case 'staff': return 'fa-solid fa-user-gear';
        default: return 'fa-solid fa-user';
    }
};
</script>

<template>
    <Head title="Personnel Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 transition-colors">
                <div>
                    <h2 class="font-black text-2xl text-geo-navy dark:text-white leading-tight transition-colors">Identity Registry</h2>
                    <p class="text-sm text-geo-slate dark:text-gray-400 mt-1 italic transition-colors">Managing personnel clearance and system access levels.</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <Link :href="route('dashboard')" class="bg-white dark:bg-white/5 text-geo-navy dark:text-white border border-gray-200 dark:border-white/5 px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 dark:hover:bg-white/10 transition-all active:scale-95 flex items-center space-x-2">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </Link>
                    <button @click="openAddForm" class="bg-geo-teal text-geo-navy px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg hover:brightness-110 active:scale-95 transition-all">
                        + Initialize Personnel
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-[var(--geo-bg)] min-h-screen transition-colors duration-500">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 transition-colors duration-500">
                    <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex items-center space-x-4">
                        <div class="p-3 bg-geo-navy/5 dark:bg-white/5 text-geo-navy dark:text-geo-teal rounded-2xl transition-colors">
                            <i class="fa-solid fa-users fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest transition-colors">Total Identities</p>
                            <p class="text-2xl font-black text-geo-navy dark:text-white transition-colors">{{ stats.total }}</p>
                        </div>
                    </div>
                    <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex items-center space-x-4">
                        <div class="p-3 bg-green-50 dark:bg-green-900/10 text-green-600 dark:text-green-400 rounded-2xl transition-colors">
                            <i class="fa-solid fa-user-check fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest transition-colors">Active Clearance</p>
                            <p class="text-2xl font-black text-geo-navy dark:text-white transition-colors">{{ stats.active }}</p>
                        </div>
                    </div>
                    <div class="bg-[var(--geo-surface)] p-6 rounded-3xl shadow-sm border border-[var(--geo-border)] flex items-center space-x-4">
                        <div class="p-3 bg-geo-teal/10 dark:bg-geo-teal/10 text-geo-teal rounded-2xl transition-colors">
                            <i class="fa-solid fa-hard-hat fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest transition-colors">Field Operators</p>
                            <p class="text-2xl font-black text-geo-navy dark:text-white transition-colors">{{ stats.staff }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modals / Forms -->
                <div v-if="showForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-geo-navy/40 backdrop-blur-sm" @click="closeForm"></div>
                    <div class="bg-[var(--geo-surface)] w-full max-w-md rounded-3xl shadow-2xl relative z-[101] overflow-hidden border border-[var(--geo-border)] animate-fade-in transition-colors duration-500">
                        <div class="h-1.5 bg-gradient-to-r from-geo-teal to-geo-blue"></div>
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-geo-navy dark:text-white mb-2 transition-colors">{{ editingUser ? 'Modify Identity' : 'Register New Personnel' }}</h3>
                            <p class="text-xs text-geo-slate dark:text-gray-400 mb-6 italic transition-colors">{{ editingUser ? 'Updating records for ' + editingUser.name : 'Initializing a new identity in the Geosurvey Core.' }}</p>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <InputLabel for="name" value="Full Name" class="text-[10px] font-black uppercase text-geo-slate dark:text-gray-400 tracking-[0.2em] transition-colors" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy placeholder:text-gray-300 transition-colors" v-model="form.name" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Official Email" class="text-[10px] font-black uppercase text-geo-slate dark:text-gray-400 tracking-[0.2em] transition-colors" />
                                    <TextInput id="email" type="email" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Please enter a valid email address (e.g. user@example.com)" class="mt-1 block w-full border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy placeholder:text-gray-300 transition-colors" v-model="form.email" required />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                    <p v-if="emailExists" class="text-[10px] text-red-500 font-bold mt-1 uppercase tracking-wider animate-bounce">
                                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Identity with this email is already registered in the core!
                                    </p>
                                </div>

                                <div>
                                    <InputLabel for="role" value="Access Clearance" class="text-[10px] font-black uppercase text-geo-slate dark:text-gray-400 tracking-[0.2em] transition-colors" />
                                    <select id="role" v-model="form.role" class="mt-1 block w-full border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-geo-navy dark:text-white focus:ring-geo-teal focus:border-geo-teal rounded-xl text-sm transition-colors" required>
                                        <option value="staff" class="dark:bg-geo-navy">Field Staff</option>
                                        <option value="hod" class="dark:bg-geo-navy">HOD (Approval Authority)</option>
                                        <option value="admin" class="dark:bg-geo-navy">System Administrator</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.role" />
                                </div>

                                <div v-if="!editingUser">
                                    <InputLabel for="password" value="Initial Password" class="text-[10px] font-black uppercase text-geo-slate dark:text-gray-400 tracking-[0.2em] transition-colors" />
                                    <TextInput id="password" type="password" class="mt-1 block w-full border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy placeholder:text-gray-300 transition-colors" v-model="form.password" required />
                                    
                                    <!-- Password Strength Indicator -->
                                    <div class="mt-3 p-3 bg-slate-50 dark:bg-white/5 rounded-xl border border-gray-100 dark:border-white/5 space-y-2 transition-colors">
                                        <p class="text-[9px] font-bold text-geo-navy dark:text-white uppercase tracking-widest mb-1 transition-colors">Security Compliance:</p>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="form.password.length >= 8 ? 'bg-green-500' : 'bg-gray-300 dark:bg-white/10'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="form.password.length >= 8 ? 'text-green-600 dark:text-green-400 font-bold' : 'text-gray-400'">Minimum 8 Characters</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="/[0-9]/.test(form.password) ? 'bg-green-500' : 'bg-gray-300 dark:bg-white/10'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="/[0-9]/.test(form.password) ? 'text-green-600 dark:text-green-400 font-bold' : 'text-gray-400'">At least one number</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) ? 'bg-green-500' : 'bg-gray-300 dark:bg-white/10'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) ? 'text-green-600 dark:text-green-400 font-bold' : 'text-gray-400'">At least one symbol</span>
                                        </div>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div v-if="!editingUser">
                                    <InputLabel for="password_confirmation" value="Verify Password" class="text-[10px] font-black uppercase text-geo-slate dark:text-gray-400 tracking-[0.2em] transition-colors" />
                                    <TextInput id="password_confirmation" type="password" class="mt-1 block w-full border-gray-100 dark:border-white/5 bg-gray-50 dark:bg-white/5 text-geo-navy dark:text-white focus:bg-white dark:focus:bg-geo-navy placeholder:text-gray-300 transition-colors" v-model="form.password_confirmation" required />
                                </div>

                                <div class="flex gap-3 pt-6">
                                    <button type="button" @click="closeForm" class="flex-1 py-3 bg-gray-100 dark:bg-white/5 text-geo-slate dark:text-gray-400 font-bold rounded-xl hover:bg-gray-200 dark:hover:bg-white/10 transition-all uppercase text-xs tracking-widest">Cancel</button>
                                    <PrimaryButton 
                                        class="flex-[2] justify-center py-3 bg-geo-navy dark:bg-geo-teal text-white dark:text-geo-navy font-bold rounded-xl shadow-lg border-none hover:bg-geo-light-navy dark:hover:bg-geo-teal/80 transition-all uppercase text-xs tracking-widest" 
                                        :class="{ 'opacity-25': form.processing || emailExists || (!editingUser && (form.password.length < 8 || !/[0-9]/.test(form.password) || !/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) || form.password !== form.password_confirmation)) }" 
                                        :disabled="form.processing || emailExists || (!editingUser && (form.password.length < 8 || !/[0-9]/.test(form.password) || !/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) || form.password !== form.password_confirmation))"
                                    >
                                        {{ editingUser ? 'Update Records' : 'Initialize Identity' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- User Table -->
                <div class="bg-[var(--geo-surface)] rounded-3xl shadow-sm border border-[var(--geo-border)] overflow-hidden transition-colors duration-500">
                    <div class="p-6 border-b border-gray-50 dark:border-white/5 flex justify-between items-center transition-colors">
                        <h3 class="font-black text-geo-navy dark:text-white transition-colors">Operational User Registry</h3>
                        <div class="flex gap-2">
                             <div class="px-3 py-1 bg-green-50 dark:bg-green-900/10 text-green-700 dark:text-green-400 text-[10px] font-black uppercase rounded-full border border-green-100 dark:border-green-900/20 transition-colors">Live Connection</div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 dark:bg-white/5 text-[10px] font-black text-geo-slate dark:text-gray-400 uppercase tracking-widest transition-colors">
                                <tr>
                                    <th class="px-6 py-4">Identity</th>
                                    <th class="px-6 py-4">Clearance</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Operations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 dark:divide-white/5 transition-colors">
                                <tr v-for="user in sortedUsers" :key="user.id" class="hover:bg-blue-50/20 dark:hover:bg-geo-teal/5 transition-all group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center space-x-3">
                                            <div :class="{
                                                'bg-amber-500 text-geo-navy': user.role === 'admin',
                                                'bg-geo-teal text-geo-navy': user.role === 'hod',
                                                'bg-geo-navy text-white': user.role === 'staff'
                                            }" class="w-10 h-10 rounded-2xl flex items-center justify-center text-xs font-bold uppercase shadow-inner transition-colors">
                                                <i :class="getRoleIcon(user.role)" class="fa-lg"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-geo-navy dark:text-white transition-colors leading-none">{{ user.name }}</p>
                                                <p class="text-[10px] text-geo-slate dark:text-gray-400 mt-1 italic transition-colors">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span :class="{
                                            'text-blue-600 bg-blue-50': user.role === 'admin',
                                            'text-teal-900 bg-teal-50': user.role === 'hod',
                                            'text-amber-600 bg-amber-50': user.role === 'staff'
                                        }" class="px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-tighter shadow-sm border border-black/5">
                                            {{ user.role === 'hod' ? 'Authority (HOD)' : (user.role === 'admin' ? 'Strategic (Admin)' : 'Operational (Staff)') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-5">
                                        <button @click="toggleStatus(user)" :disabled="user.id === $page.props.auth.user.id"
                                            :class="user.is_active ? 'text-green-600 bg-green-50' : 'text-red-500 bg-red-50'"
                                            class="flex items-center space-x-2 px-3 py-1.5 rounded-xl border border-black/5 hover:scale-105 transition-all active:scale-95 disabled:opacity-50 disabled:hover:scale-100">
                                            <div :class="user.is_active ? 'bg-green-500 shadow-[0_0_8px_#22c55e]' : 'bg-red-500'" class="w-1.5 h-1.5 rounded-full"></div>
                                            <span class="text-[10px] font-black uppercase tracking-widest">{{ user.is_active ? 'Active' : 'Suspended' }}</span>
                                        </button>
                                    </td>
                                    <td class="px-6 py-5 text-right">
                                        <div class="flex justify-end items-center space-x-2 transition-all" v-if="user.role !== 'admin' || user.id !== $page.props.auth.user.id">
                                            <button v-if="user.role !== 'admin'" @click="openEditForm(user)" class="p-2 text-geo-slate hover:text-geo-blue hover:bg-blue-50 rounded-lg transition-colors" title="Modify Record">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <button v-if="user.role !== 'admin'" @click="deleteUser(user)" class="p-2 text-geo-slate hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Purge Identity">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                            <span v-else class="text-[9px] font-bold text-geo-slate/40 uppercase tracking-widest italic pr-2">Protected Identity</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>
