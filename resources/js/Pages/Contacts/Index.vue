<script setup lang="ts">
import { useForm, Head, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    Plus, Search, Phone, Mail, X, 
    Trash2, User, UserPlus, SearchX, Edit2, 
    ChevronRight
} from 'lucide-vue-next';

// 1. Explicitly define the route type for Ziggy/TypeScript compatibility
declare function route(name?: string, params?: any): string;

// --------------------------------------------------------------------------
// 1. DATA & STATE
// --------------------------------------------------------------------------
interface Contact {
    id: number;
    name: string;
    phone: string;
    email: string | null;
}

// Props: expects an array of Contact objects as 'contacts' for initial rendering and reactivity.
const props = defineProps<{ contacts: Contact[] }>();

// UI States
const isEntryModalOpen = ref(false);   
const isDetailModalOpen = ref(false);  
const isConfirmOpen = ref(false);      
const selectedContact = ref<Contact | null>(null);
const searchQuery = ref('');

// Fixed: Initialize ref with a simpler type hint to avoid "deep instantiation"
const localContacts = ref<Contact[]>(props.contacts ? [...props.contacts] : []);

// Simplified Watcher
watch(() => props.contacts, (newVal) => { 
    if (newVal) localContacts.value = [...newVal]; 
}, { deep: true });

const form = useForm({
    name: '',
    phone: '',
    email: '',
});

// --------------------------------------------------------------------------
// 2. VALIDATIONS & INTERCEPTORS
// --------------------------------------------------------------------------
const blockNameInput = (e: KeyboardEvent) => {
    if (!/^[a-zA-Z\s]*$/.test(e.key)) e.preventDefault();
};

const blockPhoneInput = (e: KeyboardEvent) => {
    if (!/^\d*$/.test(e.key) || form.phone.length >= 11) e.preventDefault();
};

const isDuplicateName = computed((): boolean => {
    if (!form.name) return false;
    const nameInput = form.name.toLowerCase().trim();
    return props.contacts.some((c: Contact) => 
        c.name.toLowerCase().trim() === nameInput && 
        c.id !== selectedContact.value?.id
    );
});

const isEmailValid = computed((): boolean => {
    if (!form.email) return true;
    const pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return pattern.test(form.email);
});

const isPhoneComplete = computed((): boolean => form.phone.length === 11);

const canSubmit = computed((): boolean => {
    return form.name.trim().length >= 2 && 
           isPhoneComplete.value && 
           isEmailValid.value && 
           !isDuplicateName.value && 
           !form.processing;
});

// --------------------------------------------------------------------------
// 3. DISPLAY LOGIC
// --------------------------------------------------------------------------
const filteredList = computed((): Contact[] => {
    const query = searchQuery.value.toLowerCase().trim();
    if (!query) return localContacts.value;
    
    return localContacts.value.filter((c: Contact) => 
        c.name.toLowerCase().includes(query) || c.phone.includes(query)
    );
});

const groupedContacts = computed((): Record<string, Contact[]> => {
    const groups: Record<string, Contact[]> = {};
    const items = [...filteredList.value];
    
    items.sort((a, b) => a.name.localeCompare(b.name)).forEach((c: Contact) => {
        const char = c.name.charAt(0).toUpperCase();
        if (!groups[char]) groups[char] = [];
        groups[char].push(c);
    });
    return groups;
});

const showInitialEmpty = computed((): boolean => localContacts.value.length === 0);
const showNoMatch = computed((): boolean => localContacts.value.length > 0 && filteredList.value.length === 0);

// --------------------------------------------------------------------------
// 4. ACTION METHODS
// --------------------------------------------------------------------------
const viewContact = (contact: Contact) => {
    selectedContact.value = contact;
    isDetailModalOpen.value = true;
};

const startEdit = () => {
    if (!selectedContact.value) return;
    
    // Explicitly setting as strings to satisfy TS unknown/string mapping
    form.name = String(selectedContact.value.name);
    form.phone = String(selectedContact.value.phone);
    form.email = selectedContact.value.email ? String(selectedContact.value.email) : '';
    
    isDetailModalOpen.value = false;
    isEntryModalOpen.value = true;
};

const openAddModal = () => {
    selectedContact.value = null;
    form.reset();
    isEntryModalOpen.value = true;
};

const save = () => {
    if (!canSubmit.value) return;
    
    const options = { 
        onSuccess: () => { 
            isEntryModalOpen.value = false; 
            form.reset(); 
            selectedContact.value = null; 
        } 
    };

    if (selectedContact.value) {
        form.put(route('contacts.update', selectedContact.value.id), options as any);
    } else {
        form.post(route('contacts.store'), options as any);
    }
};

const executeDelete = () => {
    if (!selectedContact.value) return;
    
    const contactId = selectedContact.value.id;
    const original = [...localContacts.value];
    
    // Optimistic UI Update
    localContacts.value = localContacts.value.filter((c: Contact) => c.id !== contactId);
    
    isConfirmOpen.value = false;
    isDetailModalOpen.value = false;
    
    router.delete(route('contacts.destroy', contactId), {
        onError: () => { localContacts.value = original; }
    });
};
</script>

<template>
    <!-- Template remains exactly as provided as the structure is valid -->
    <Head title="Contacts Management" />

    <div class="min-h-screen bg-[#F2F2F7] text-[#1C1C1E] font-sans antialiased">
        <header class="bg-[#F2F2F7]/80 backdrop-blur-xl sticky top-0 z-40 border-b border-[#D1D1D6]">
            <div class="max-w-2xl mx-auto px-6 py-8">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-3xl font-bold">Contacts</h1>
                    <button @click="openAddModal()" class="bg-[#007AFF] text-white p-2.5 rounded-full shadow-lg active:scale-95 transition-transform hover:scale-110 hover:shadow-xl">
                        <Plus class="w-5 h-5" />
                    </button>
                </div>
                <div class="relative">
                    <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-[#8E8E93]" />
                    <input v-model="searchQuery" id="search" type="text" placeholder="Search" 
                        class="w-full bg-[#E3E3E8] border-none rounded-xl py-3 pl-11 focus:ring-2 focus:ring-[#007AFF]/20 transition-all text-sm outline-none" />
                </div>
            </div>
        </header>

        <main class="max-w-2xl mx-auto px-6 py-8 pb-32">
            <div v-if="showInitialEmpty || showNoMatch" class="flex flex-col items-center justify-center py-24 text-center">
                <div v-if="showInitialEmpty" class="animate-in fade-in zoom-in duration-300">
                    <UserPlus class="w-14 h-14 text-[#C7C7CC] mb-4 mx-auto" />
                    <h3 class="text-xl font-bold">No Contacts Recorded</h3>
                    <p class="text-sm text-[#8E8E93] mt-2">Click the plus button to add a new contact.</p>
                </div>
                <div v-else-if="showNoMatch" class="animate-in fade-in zoom-in duration-300">
                    <SearchX class="w-14 h-14 text-[#C7C7CC] mb-4 mx-auto" />
                    <h3 class="text-xl font-bold">No Match Contact</h3>
                    <p class="text-sm text-[#8E8E93] mt-2">We couldn't find anyone matching "{{ searchQuery }}"</p>
                </div>
            </div>

            <div v-else v-for="(items, letter) in groupedContacts" :key="letter" class="mb-8 animate-in slide-in-from-bottom-2 duration-500">
                <h2 class="text-[#8E8E93] text-[11px] font-bold px-4 mb-2 uppercase tracking-widest">{{ letter }}</h2>
                <div class="bg-white rounded-2xl border border-[#E5E5EA] overflow-hidden shadow-sm">
                    <button v-for="c in items" :key="c.id" 
                        @click="viewContact(c)"
                        class="w-full flex items-center justify-between p-4 text-left hover:bg-[#F9F9F9] active:bg-[#F2F2F7] border-b last:border-none border-[#F2F2F7] transition-colors outline-none hover:scale-[1.01]">
                        <span class="text-[17px] font-medium">{{ c.name }}</span>
                        <ChevronRight class="w-4 h-4 text-[#C7C7CC]" />
                    </button>
                </div>
            </div>
        </main>

        <Transition name="fade">
            <div v-if="isDetailModalOpen && selectedContact" class="fixed inset-0 z-[60] flex items-center justify-center p-6">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isDetailModalOpen = false"></div>
                <div class="relative bg-white w-full max-w-sm rounded-[32px] shadow-2xl overflow-hidden p-8 text-center">
                    <div class="w-20 h-20 bg-[#F2F2F7] rounded-full flex items-center justify-center mx-auto mb-4">
                        <User class="w-10 h-10 text-[#8E8E93]" />
                    </div>
                    <h2 class="text-2xl font-bold">{{ selectedContact.name }}</h2>
                    <div class="mt-6 space-y-4 text-left bg-[#F2F2F7] p-5 rounded-2xl">
                        <div class="flex items-center gap-4">
                            <Phone class="w-4 h-4 text-[#007AFF]" />
                            <span class="text-sm font-semibold">{{ selectedContact.phone }}</span>
                        </div>
                        <div v-if="selectedContact.email" class="flex items-center gap-4">
                            <Mail class="w-4 h-4 text-[#007AFF]" />
                            <span class="text-sm font-semibold">{{ selectedContact.email }}</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 mt-8">
                        <button @click="startEdit" class="w-full py-4 bg-[#007AFF] text-white rounded-xl font-bold flex items-center justify-center gap-2 hover:bg-[#0056CC] hover:scale-105 transition-all">
                            <Edit2 class="w-4 h-4" /> Edit Contact
                        </button>
                        <button @click="isConfirmOpen = true" class="w-full py-4 text-[#FF3B30] font-bold hover:bg-red-50 rounded-xl transition-colors hover:scale-105">
                            Delete Contact
                        </button>
                        <button @click="isDetailModalOpen = false" class="text-[#8E8E93] font-medium text-sm mt-2 hover:bg-[#F2F2F7] hover:scale-105 transition-all rounded px-2 py-1">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="isEntryModalOpen" class="fixed inset-0 z-[70] flex items-center justify-center p-6">
                <div class="absolute inset-0 bg-black/40 backdrop-blur-md" @click="isEntryModalOpen = false"></div>
                <div class="relative bg-white w-full max-w-md rounded-[32px] shadow-2xl overflow-hidden">
                    <div class="px-8 pt-10 pb-4 flex justify-between items-start">
                        <h2 class="text-2xl font-bold">{{ selectedContact ? 'Edit Contact' : 'New Contact' }}</h2>
                        <button @click="isEntryModalOpen = false" class="bg-[#F2F2F7] p-2 rounded-full text-[#8E8E93] hover:bg-[#E5E5EA] hover:scale-110 transition-all"><X class="w-4 h-4" /></button>
                    </div>
                    <div class="p-8 space-y-6">
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#8E8E93] uppercase ml-1">Full Name</label>
                            <input v-model="form.name" @keypress="blockNameInput" type="text" placeholder="First Name, Last Name" 
                                :class="isDuplicateName ? 'bg-red-50 ring-1 ring-red-200' : 'bg-[#F2F2F7]'"
                                class="w-full border-none rounded-xl py-4 px-4 outline-none font-medium text-[16px]" />
                            <p v-if="isDuplicateName" class="text-[11px] text-[#FF3B30] font-bold ml-1">This name is already used.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#8E8E93] uppercase ml-1">Phone Number</label>
                            <input v-model="form.phone" @keypress="blockPhoneInput" type="tel" placeholder="11 digits" 
                                :class="(form.phone.length > 0 && !isPhoneComplete) ? 'bg-orange-50' : 'bg-[#F2F2F7]'"
                                class="w-full border-none rounded-xl py-4 px-4 outline-none font-medium text-[16px]" />
                            <p v-if="form.phone.length > 0 && !isPhoneComplete" class="text-[11px] text-orange-600 font-bold ml-1">Must be exactly 11 digits.</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-[11px] font-bold text-[#8E8E93] uppercase ml-1">Email (Optional)</label>
                            <input v-model="form.email" type="email" placeholder="example@mail.com" class="w-full bg-[#F2F2F7] border-none rounded-xl py-4 px-4 outline-none font-medium" />
                        </div>
                    </div>
                    <div class="p-8 pt-0">
                        <button @click="save" :disabled="!canSubmit"
                            :class="canSubmit ? 'bg-[#007AFF] text-white shadow-md hover:bg-[#0056CC] hover:scale-105' : 'bg-[#E5E5EA] text-[#8E8E93]'"
                            class="w-full py-4 rounded-xl font-bold transition-all">Save Contact</button>
                    </div>
                </div>
            </div>
        </Transition>

        <Transition name="fade">
            <div v-if="isConfirmOpen" class="fixed inset-0 z-[80] flex items-center justify-center p-6">
                <div class="absolute inset-0 bg-black/60 backdrop-blur-md" @click="isConfirmOpen = false"></div>
                <div class="relative bg-white w-full max-w-sm rounded-[32px] p-8 text-center shadow-2xl">
                    <h3 class="text-xl font-bold">Delete Contact?</h3>
                    <p class="text-sm text-[#8E8E93] mt-2 mb-8 leading-relaxed">This person will be permanently removed from your contact list.</p>
                    <div class="flex gap-3">
                        <button @click="isConfirmOpen = false" class="flex-1 py-4 bg-[#F2F2F7] rounded-xl font-bold hover:bg-[#E5E5EA] hover:scale-105 transition-all">Cancel</button>
                        <button @click="executeDelete" class="flex-1 py-4 bg-[#FF3B30] text-white rounded-xl font-bold hover:bg-[#CC2A1F] hover:scale-105 transition-all">Delete</button>
                    </div>
                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: all 0.2s ease-out; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: scale(0.95); }

::-webkit-scrollbar { display: none; }
input:focus { outline: none !important; }
</style>