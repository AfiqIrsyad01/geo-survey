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
                closeForm();
            },
        });
    } else {
        form.post(route('register'), {
            onFinish: () => form.reset('password', 'password_confirmation'),
            onSuccess: () => {
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
    if (confirm(`PURGE WARNING: Are you sure you want to permanently delete identity '${user.name}'? This action is irreversible.`)) {
        router.delete(route('users.destroy', user.id));
    }
};

const toggleStatus = (user) => {
    router.post(route('users.toggle-status', user.id));
};

const stats = computed(() => {
    return {
        total: props.users.length,
        active: props.users.filter(u => u.is_active).length,
        staff: props.users.filter(u => u.role === 'staff').length,
    };
});
</script>

<template>
    <Head title="Personnel Management" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h2 class="font-bold text-2xl text-geo-navy leading-tight">Identity Registry</h2>
                    <p class="text-sm text-geo-slate mt-1 italic">Managing personnel clearance and system access levels.</p>
                </div>
                
                <div class="flex items-center space-x-3">
                    <Link :href="route('dashboard')" class="bg-white text-geo-navy border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-bold shadow-sm hover:bg-gray-50 transition-all active:scale-95 flex items-center space-x-2">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Back to Dashboard</span>
                    </Link>
                    <button @click="openAddForm" class="bg-geo-teal text-geo-navy px-6 py-2.5 rounded-xl text-sm font-bold shadow-lg hover:brightness-110 active:scale-95 transition-all">
                        + Initialize Personnel
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12 bg-gray-50 min-h-screen">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- Quick Stats -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
                        <div class="p-3 bg-geo-navy/5 text-geo-navy rounded-2xl">
                            <i class="fa-solid fa-users fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate uppercase tracking-widest">Total Identities</p>
                            <p class="text-2xl font-black text-geo-navy">{{ stats.total }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
                        <div class="p-3 bg-green-50 text-green-600 rounded-2xl">
                            <i class="fa-solid fa-user-check fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate uppercase tracking-widest">Active Clearance</p>
                            <p class="text-2xl font-black text-geo-navy">{{ stats.active }}</p>
                        </div>
                    </div>
                    <div class="bg-white p-6 rounded-3xl shadow-sm border border-gray-100 flex items-center space-x-4">
                        <div class="p-3 bg-geo-teal/10 text-geo-teal rounded-2xl">
                            <i class="fa-solid fa-hard-hat fa-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-geo-slate uppercase tracking-widest">Field Operators</p>
                            <p class="text-2xl font-black text-geo-navy">{{ stats.staff }}</p>
                        </div>
                    </div>
                </div>

                <!-- Modals / Forms -->
                <div v-if="showForm" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-geo-navy/40 backdrop-blur-sm" @click="closeForm"></div>
                    <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl relative z-[101] overflow-hidden border border-gray-100 animate-fade-in">
                        <div class="h-1.5 bg-gradient-to-r from-geo-teal to-geo-blue"></div>
                        <div class="p-8">
                            <h3 class="text-xl font-bold text-geo-navy mb-2">{{ editingUser ? 'Modify Identity' : 'Register New Personnel' }}</h3>
                            <p class="text-xs text-geo-slate mb-6 italic">{{ editingUser ? 'Updating records for ' + editingUser.name : 'Initializing a new identity in the Geosurvey Core.' }}</p>

                            <form @submit.prevent="submit" class="space-y-4">
                                <div>
                                    <InputLabel for="name" value="Full Name" class="text-[10px] font-black uppercase text-geo-slate tracking-[0.2em]" />
                                    <TextInput id="name" type="text" class="mt-1 block w-full border-gray-100 bg-gray-50 focus:bg-white placeholder:text-gray-300" v-model="form.name" required autofocus />
                                    <InputError class="mt-2" :message="form.errors.name" />
                                </div>

                                <div>
                                    <InputLabel for="email" value="Official Email" class="text-[10px] font-black uppercase text-geo-slate tracking-[0.2em]" />
                                    <TextInput id="email" type="email" pattern="[^@\s]+@[^@\s]+\.[^@\s]+" title="Please enter a valid email address (e.g. user@example.com)" class="mt-1 block w-full border-gray-100 bg-gray-50 focus:bg-white placeholder:text-gray-300" v-model="form.email" required />
                                    <InputError class="mt-2" :message="form.errors.email" />
                                </div>

                                <div>
                                    <InputLabel for="role" value="Access Clearance" class="text-[10px] font-black uppercase text-geo-slate tracking-[0.2em]" />
                                    <select id="role" v-model="form.role" class="mt-1 block w-full border-gray-100 bg-gray-50 focus:ring-geo-teal focus:border-geo-teal rounded-xl text-sm" required>
                                        <option value="staff">Field Staff</option>
                                        <option value="hod">HOD (Approval Authority)</option>
                                        <option value="admin">System Administrator</option>
                                    </select>
                                    <InputError class="mt-2" :message="form.errors.role" />
                                </div>

                                <div v-if="!editingUser">
                                    <InputLabel for="password" value="Initial Password" class="text-[10px] font-black uppercase text-geo-slate tracking-[0.2em]" />
                                    <TextInput id="password" type="password" class="mt-1 block w-full border-gray-100 bg-gray-50 focus:bg-white placeholder:text-gray-300" v-model="form.password" required />
                                    
                                    <!-- Password Strength Indicator -->
                                    <div class="mt-3 p-3 bg-slate-50 rounded-xl border border-gray-100 space-y-2">
                                        <p class="text-[9px] font-bold text-geo-navy uppercase tracking-widest mb-1">Security Compliance:</p>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="form.password.length >= 8 ? 'bg-green-500' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="form.password.length >= 8 ? 'text-green-600 font-bold' : 'text-gray-400'">Minimum 8 Characters</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="/[0-9]/.test(form.password) ? 'bg-green-500' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="/[0-9]/.test(form.password) ? 'text-green-600 font-bold' : 'text-gray-400'">At least one number</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-[10px]">
                                            <div :class="/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) ? 'bg-green-500' : 'bg-gray-300'" class="w-2 h-2 rounded-full transition-colors"></div>
                                            <span :class="/[!@#$%^&*(),.?\':{}|<>]/.test(form.password) ? 'text-green-600 font-bold' : 'text-gray-400'">At least one symbol</span>
                                        </div>
                                    </div>
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div v-if="!editingUser">
                                    <InputLabel for="password_confirmation" value="Verify Password" class="text-[10px] font-black uppercase text-geo-slate tracking-[0.2em]" />
                                    <TextInput id="password_confirmation" type="password" class="mt-1 block w-full border-gray-100 bg-gray-50 focus:bg-white placeholder:text-gray-300" v-model="form.password_confirmation" required />
                                </div>

                                <div class="flex gap-3 pt-6">
                                    <button type="button" @click="closeForm" class="flex-1 py-3 bg-gray-100 text-geo-slate font-bold rounded-xl hover:bg-gray-200 transition-all uppercase text-xs tracking-widest">Cancel</button>
                                    <PrimaryButton 
                                        class="flex-[2] justify-center py-3 bg-geo-navy text-white font-bold rounded-xl shadow-lg border-none hover:bg-geo-light-navy transition-all uppercase text-xs tracking-widest" 
                                        :class="{ 'opacity-25': form.processing || (!editingUser && (form.password.length < 8 || !/[0-9]/.test(form.password) || !/[!@#$%^&*(),.?\':{}|<>]/.test(form.password))) }" 
                                        :disabled="form.processing || (!editingUser && (form.password.length < 8 || !/[0-9]/.test(form.password) || !/[!@#$%^&*(),.?\':{}|<>]/.test(form.password)))"
                                    >
                                        {{ editingUser ? 'Update Records' : 'Initialize Identity' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- User Table -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-6 border-b border-gray-50 flex justify-between items-center">
                        <h3 class="font-bold text-geo-navy">Operational UserRegistry</h3>
                        <div class="flex gap-2">
                             <div class="px-3 py-1 bg-green-50 text-green-700 text-[10px] font-black uppercase rounded-full border border-green-100">Live Connection</div>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50/50 text-[10px] font-black text-geo-slate uppercase tracking-widest">
                                <tr>
                                    <th class="px-6 py-4">Identity</th>
                                    <th class="px-6 py-4">Clearance</th>
                                    <th class="px-6 py-4">Status</th>
                                    <th class="px-6 py-4 text-right">Operations</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                <tr v-for="user in users" :key="user.id" class="hover:bg-blue-50/20 transition-all group">
                                    <td class="px-6 py-5">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-2xl bg-geo-navy flex items-center justify-center text-white text-xs font-bold uppercase shadow-inner">
                                                {{ user.name.charAt(0) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-geo-navy leading-none">{{ user.name }}</p>
                                                <p class="text-[10px] text-geo-slate mt-1 italic">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-5">
                                        <span :class="{
                                            'text-blue-600 bg-blue-50': user.role === 'admin',
                                            'text-geo-teal bg-teal-50': user.role === 'hod',
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
