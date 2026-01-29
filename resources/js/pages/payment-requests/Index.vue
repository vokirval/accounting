<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { BreadcrumbItem, AppPageProps } from '@/types';
import { toast } from 'vue-sonner';
import {
    Check,
    ChevronDown,
    Clipboard,
    Filter,
    History,
    Paperclip,
    Pencil,
} from 'lucide-vue-next';

type NamedItem = { id: number; name: string };
type CategoryItem = { id: number; name: string; expense_type_id: number | null };




type PaymentRequestHistory = {
    id: number;
    user: { id: number; name: string };
    action: string;
    changed_fields: Record<string, { old: unknown; new: unknown } | unknown>;
    created_at: string;
};

type PaymentRequestRow = {
    id: number;
    author: { id: number; name: string };
    expense_type: NamedItem;
    expense_category: NamedItem;
    paid_account: NamedItem | null;
    participants: { id: number; name: string }[];
    requisites: string;
    requisites_file_url: string | null;
    amount: string;
    commission: string | null;
    purchase_reference: string | null;
    ready_for_payment: boolean;
    paid: boolean;
    paid_account_id: number | null;
    receipt_url: string | null;
    created_at: string;
    updated_at: string;
    history: PaymentRequestHistory[];
    can: {
        update: boolean;
        view: boolean;
    };
};

type Pagination<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type PageProps = AppPageProps<{
    paymentRequests: Pagination<PaymentRequestRow>;
    filters: {
        author_id: string | null;
        participant_id: string | null;
        expense_type_id: string | null;
        expense_category_id: string | null;
        paid_account_id: string | null;
        purchase_reference: string | null;
        ready_for_payment: string | null;
        paid: string | null;
        created_from: string | null;
        created_to: string | null;
        per_page?: number | string | null;
    };
    expenseTypes: NamedItem[];
    expenseCategories: CategoryItem[];
    paymentAccounts: NamedItem[];
    users: { id: number; name: string; email: string; role: string }[];
    totals?: {
        amount: number;
        commission: number;
    } | null;
    permissions: {
        create: boolean;
    };
}>;

const page = usePage<PageProps>();
const props = computed(() => page.props);

const formatMoney = (value: number) =>
    new Intl.NumberFormat('uk-UA', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);

const role = computed(() => props.value.auth.user.role);
const isUser = computed(() => role.value === 'user');
const canMarkPaid = computed(() => role.value !== 'user');
const canManageReceipt = computed(() => role.value !== 'user');
const canManageCommission = computed(() => role.value !== 'user');
const canManagePaidAccount = computed(() => role.value !== 'user');

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Платіжні заявки', href: '/payment-requests' },
];

const filterForm = reactive({
    author_id: props.value.filters.author_id ?? '',
    participant_id: props.value.filters.participant_id ?? '',
    expense_type_id: props.value.filters.expense_type_id ?? '',
    expense_category_id: props.value.filters.expense_category_id ?? '',
    paid_account_id: props.value.filters.paid_account_id ?? '',
    purchase_reference: props.value.filters.purchase_reference ?? '',
    ready_for_payment: props.value.filters.ready_for_payment ?? '',
    paid: props.value.filters.paid ?? '',
    created_from: props.value.filters.created_from ?? '',
    created_to: props.value.filters.created_to ?? '',
    per_page: props.value.filters.per_page ?? 30,
});

const appliedFilters = computed(() => {
    const items: { label: string; value: string }[] = [];
    const userName = (id: string) => {
        const found = props.value.users.find((user) => user.id === Number(id));
        return found?.name ?? `#${id}`;
    };
    const expenseTypeName = (id: string) => {
        const found = props.value.expenseTypes.find((type) => type.id === Number(id));
        return found?.name ?? `#${id}`;
    };
    const expenseCategoryName = (id: string) => {
        const found = props.value.expenseCategories.find((category) => category.id === Number(id));
        return found?.name ?? `#${id}`;
    };
    const accountName = (id: string) => {
        const found = props.value.paymentAccounts.find((account) => account.id === Number(id));
        return found?.name ?? `#${id}`;
    };

    if (filterForm.author_id) {
        items.push({ label: 'Автор', value: userName(filterForm.author_id) });
    }
    if (filterForm.participant_id) {
        items.push({ label: 'Учасник', value: userName(filterForm.participant_id) });
    }
    if (filterForm.expense_type_id) {
        items.push({ label: 'Тип витрат', value: expenseTypeName(filterForm.expense_type_id) });
    }
    if (filterForm.expense_category_id) {
        items.push({ label: 'Категорія', value: expenseCategoryName(filterForm.expense_category_id) });
    }
    if (filterForm.paid_account_id) {
        items.push({ label: 'Рахунок', value: accountName(filterForm.paid_account_id) });
    }
    if (filterForm.purchase_reference) {
        items.push({ label: '№ закупки', value: filterForm.purchase_reference });
    }
    if (filterForm.ready_for_payment !== '') {
        items.push({
            label: 'Готово до оплати',
            value: filterForm.ready_for_payment === '1' ? 'Так' : 'Ні',
        });
    }
    if (filterForm.paid !== '') {
        items.push({ label: 'Оплачено', value: filterForm.paid === '1' ? 'Так' : 'Ні' });
    }
    if (filterForm.created_from) {
        items.push({ label: 'Від', value: filterForm.created_from });
    }
    if (filterForm.created_to) {
        items.push({ label: 'До', value: filterForm.created_to });
    }

    return items;
});

const drawerOpen = ref(false);
const historyOpen = ref(false);
const isEditing = ref(false);
const selected = ref<PaymentRequestRow | null>(null);
const receiptMode = ref<'url' | 'file'>('url');
const filtersOpen = ref(false);
const COLUMN_VISIBILITY_KEY = 'payment-requests.column-visibility';

const columnVisibility = reactive({
    id: true,
    created_at: true,
    author: true,
    expense_type: true,
    expense_category: true,
    requisites: true,
    amount: true,
    purchase_reference: true,
    paid_account: true,
    receipt_url: true,
    participants: true,
    status: true,
});

const columnItems: { key: keyof typeof columnVisibility; label: string }[] = [
    { key: 'id', label: 'ID' },
    { key: 'created_at', label: 'Дата' },
    { key: 'author', label: 'Хто подав' },
    { key: 'expense_type', label: 'Тип витрат' },
    { key: 'expense_category', label: 'Категорія витрат' },
    { key: 'requisites', label: 'Реквізити' },
    { key: 'amount', label: 'Сума/Комісія' },
    { key: 'purchase_reference', label: '№ Закупки' },
    { key: 'paid_account', label: 'Рахунок' },
    { key: 'status', label: 'Статус' },
    { key: 'receipt_url', label: 'Квитанція' },
    { key: 'participants', label: 'Учасники' },
    
];

const visibleColumnsCount = computed(
    () => Object.values(columnVisibility).filter(Boolean).length + 1,
);


const form = useForm({
    expense_type_id: '' as number | '',
    expense_category_id: '' as number | '',
    requisites: '',
    requisites_file: null as File | null,
    amount: '',
    commission: '' as string | '',
    purchase_reference: '',
    paid_account_id: '' as number | '',
    ready_for_payment: false,
    paid: false,
    receipt_url: '',
    receipt_file: null as File | null,
});

type FormPayload = typeof form extends { data: () => infer D } ? D : never;

const resetFormState = () => {
    form.reset();
    form.clearErrors();
    form.ready_for_payment = false;
    form.paid = false;
    form.receipt_url = '';
    form.receipt_file = null;
    form.requisites_file = null;
    receiptMode.value = 'url';
    isEditing.value = false;
    selected.value = null;
};

const statusLabel = (item: PaymentRequestRow) => {
    if (item.paid) return 'Оплачено';
    if (item.ready_for_payment) return 'Готово до оплати';
    return 'Чернетка';
};

const statusVariant = (item: PaymentRequestRow) => {
    if (item.paid) return 'default';
    if (item.ready_for_payment) return 'secondary';
    return 'outline';
};

const historyFieldLabels: Record<string, string> = {
    expense_type_id: 'Тип витрат',
    expense_category_id: 'Категорія витрат',
    requisites: 'Реквізити для оплати',
    requisites_file_url: 'Файл реквізитів',
    amount: 'Сума',
    commission: 'Комісія',
    purchase_reference: 'Номер з таблиці по закупці',
    ready_for_payment: 'Готово до оплати',
    paid: 'Оплачено',
    paid_account_id: 'Рахунок оплати',
    receipt_url: 'Квитанція (посилання)',
};


const filteredCategories = computed(() => {
    if (!form.expense_type_id) {
        return props.value.expenseCategories;
    }
    return props.value.expenseCategories.filter(
        (category) => category.expense_type_id === Number(form.expense_type_id),
    );
});

const filteredFilterCategories = computed(() => {
    if (!filterForm.expense_type_id) {
        return props.value.expenseCategories;
    }
    return props.value.expenseCategories.filter(
        (category) => category.expense_type_id === Number(filterForm.expense_type_id),
    );
});

const selectedExpenseType = computed(() => {
    if (!form.expense_type_id) {
        return null;
    }
    return props.value.expenseTypes.find(
        (type) => type.id === Number(form.expense_type_id),
    );
});

const showPurchaseReference = computed(() => selectedExpenseType.value?.name === 'Оплата за товар');

const formatHistoryValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }
    if (typeof value === 'boolean') {
        return value ? 'Так' : 'Ні';
    }
    return String(value);
};

const actionLabel = (action: string) => {
    if (action === 'created') return 'Створив заявку';
    if (action === 'updated') return 'Оновив заявку';
    if (action === 'status_changed') return 'Змінив статус';
    if (action === 'requisites_file_pruned') return 'Автоматично видалено файл реквізитів';
    return action;
};

onMounted(() => {
    try {
        const raw = localStorage.getItem(COLUMN_VISIBILITY_KEY);
        if (!raw) return;
        const parsed = JSON.parse(raw) as Partial<typeof columnVisibility>;
        for (const key of Object.keys(columnVisibility) as (keyof typeof columnVisibility)[]) {
            if (typeof parsed[key] === 'boolean') {
                columnVisibility[key] = parsed[key] as boolean;
            }
        }
    } catch {
        // ignore malformed localStorage values
    }
});

watch(
    columnVisibility,
    (value) => {
        try {
            localStorage.setItem(COLUMN_VISIBILITY_KEY, JSON.stringify(value));
        } catch {
            // ignore storage write errors
        }
    },
    { deep: true },
);

watch(
    () => form.expense_type_id,
    () => {
        if (!filteredCategories.value.some((category) => category.id === Number(form.expense_category_id))) {
            form.expense_category_id = '';
        }
    },
);

watch(
    () => filterForm.expense_type_id,
    () => {
        if (!filteredFilterCategories.value.some((category) => category.id === Number(filterForm.expense_category_id))) {
            filterForm.expense_category_id = '';
        }
    },
);

const setReceiptMode = (mode: 'url' | 'file') => {
    receiptMode.value = mode;
    if (mode === 'url') {
        form.receipt_file = null;
    }
};

const toBool = (value: unknown): boolean => {
    if (value === true || value === 'true' || value === 1 || value === '1') {
        return true;
    }
    return false;
};

const openCreate = () => {
    resetFormState();
    drawerOpen.value = true;
};

const openEdit = (item: PaymentRequestRow) => {
    isEditing.value = true;
    selected.value = item;
    form.clearErrors();
    form.expense_type_id = item.expense_type?.id ?? '';
    form.expense_category_id = item.expense_category?.id ?? '';
    form.requisites = item.requisites ?? '';
    form.amount = item.amount ?? '';
    form.commission = item.commission ?? '';
    form.purchase_reference = item.purchase_reference ?? '';
    form.paid_account_id = item.paid_account_id ?? '';
    form.ready_for_payment = toBool(item.ready_for_payment);
    form.paid = toBool(item.paid);
    form.receipt_url = item.receipt_url ?? '';
    form.receipt_file = null;
    form.requisites_file = null;
    receiptMode.value = 'url';
    drawerOpen.value = true;

    console.log(item.ready_for_payment, typeof item.ready_for_payment)
};

const openHistory = (item: PaymentRequestRow) => {
    selected.value = item;
    historyOpen.value = true;
};

const normalizePayload = (data: FormPayload) => ({
    ...data,
    ready_for_payment: data.ready_for_payment ? 1 : 0,
    paid: data.paid ? 1 : 0,
});

const submit = () => {
    if (isUser.value) {
        form.paid = false;
        form.receipt_url = '';
        form.receipt_file = null;
        form.commission = '';
    }

    // Only drop the URL when a new file is actually selected.
    if (receiptMode.value === 'file' && form.receipt_file) {
        form.receipt_url = '';
    }

    if (isEditing.value && selected.value) {
        const confirmed = window.confirm('Підтвердити оновлення заявки?');
        if (!confirmed) {
            return;
        }
        form.transform((data) => ({
            ...normalizePayload(data),
            _method: 'put',
        })).post(`/payment-requests/${selected.value.id}`, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                drawerOpen.value = false;
                resetFormState();
                toast.success('Заявку оновлено');
            },
            onError: () => toast.error('Не вдалося оновити заявку'),
        });
        return;
    }

    form.transform((data) => normalizePayload(data)).post('/payment-requests', {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            drawerOpen.value = false;
            resetFormState();
            toast.success('Заявку створено');
        },
        onError: () => toast.error('Не вдалося створити заявку'),
    });
};

const applyFilters = () => {
    router.get('/payment-requests', filterForm, {
        preserveState: true,
        replace: true,
    });
};

const changePerPage = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    filterForm.per_page = target.value;
    applyFilters();
};

const clearFilters = () => {
    filterForm.author_id = '';
    filterForm.participant_id = '';
    filterForm.expense_type_id = '';
    filterForm.expense_category_id = '';
    filterForm.paid_account_id = '';
    filterForm.purchase_reference = '';
    filterForm.ready_for_payment = '';
    filterForm.paid = '';
    filterForm.created_from = '';
    filterForm.created_to = '';
    filterForm.per_page = 30;
    applyFilters();
};

const onReceiptFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.receipt_file = target.files?.[0] ?? null;
};

const onRequisitesFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.requisites_file = target.files?.[0] ?? null;
};

const copyReceiptUrl = async (url: string) => {
    try {
        await navigator.clipboard.writeText(url);
        toast.success('Посилання скопійовано');
    } catch {
        toast.error('Не вдалося скопіювати');
    }
};

const copyValue = async (value: unknown, label: string) => {
    const text = value === null || value === undefined || value === '' ? '—' : String(value);
    try {
        await navigator.clipboard.writeText(text);
        toast.success(`${label} скопійовано`);
    } catch {
        toast.error('Не вдалося скопіювати');
    }
};
</script>

<template>
    <Head title="Платіжні заявки" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="rounded-xl border border-sidebar-border/70 bg-white p-4 dark:border-sidebar-border dark:bg-neutral-900">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-xl font-semibold">Платіжні заявки</h1>
                        <p class="text-sm text-muted-foreground">
                            Облік заявок, зміни статусів та історія учасників.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <Collapsible v-model:open="filtersOpen">
                            <CollapsibleTrigger as-child>
                                <Button variant="outline" size="sm" type="button" class="h-8 gap-1.5 px-3 text-xs">
                                    <Filter class="h-4 w-4" />
                                    Фільтри
                                    <ChevronDown
                                        class="h-4 w-4 transition-transform"
                                        :class="filtersOpen ? 'rotate-180' : ''"
                                    />
                                </Button>
                            </CollapsibleTrigger>
                        </Collapsible>
                        <Button
                            v-if="props.permissions.create"
                            size="sm"
                            class="h-8 px-3 text-xs"
                            @click="openCreate"
                        >
                            Створити заявку
                        </Button>
                    </div>
                </div>

                <Collapsible v-model:open="filtersOpen" class="mt-4">
                    <CollapsibleContent>
                        <div class="mb-3 space-y-1.5 rounded-lg border border-border/60 bg-muted/20 p-3">
                            <Label class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">Видимість колонок</Label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="col in columnItems"
                                    :key="col.key"
                                    type="button"
                                    class="rounded-md border px-2 py-1 text-[11px] leading-none transition-colors"
                                    :class="columnVisibility[col.key] ? 'border-primary text-primary' : 'border-input text-muted-foreground'"
                                    @click="columnVisibility[col.key] = !columnVisibility[col.key]"
                                >
                                    {{ columnVisibility[col.key] ? '✓' : '○' }} {{ col.label }}
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2.5 md:grid-cols-4">
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Хто подав</Label>
                                <select v-model="filterForm.author_id" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Учасник</Label>
                                <select v-model="filterForm.participant_id" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option v-for="user in props.users" :key="user.id" :value="String(user.id)">{{ user.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Тип витрат</Label>
                                <select v-model="filterForm.expense_type_id" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option v-for="type in props.expenseTypes" :key="type.id" :value="String(type.id)">{{ type.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Категорія</Label>
                                <select v-model="filterForm.expense_category_id" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option v-for="category in filteredFilterCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Рахунок</Label>
                                <select v-model="filterForm.paid_account_id" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option v-for="account in props.paymentAccounts" :key="account.id" :value="String(account.id)">{{ account.name }}</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">№ Закупки</Label>
                                <Input v-model="filterForm.purchase_reference" type="text" class="h-8 w-full min-w-0 px-2.5 text-[12px]" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Готово</Label>
                                <select v-model="filterForm.ready_for_payment" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option value="1">Так</option>
                                    <option value="0">Ні</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Оплачено</Label>
                                <select v-model="filterForm.paid" class="h-8 w-full min-w-0 rounded-md border border-input bg-transparent px-2.5 text-[12px]">
                                    <option value="">Усі</option>
                                    <option value="1">Так</option>
                                    <option value="0">Ні</option>
                                </select>
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Дата від</Label>
                                <Input v-model="filterForm.created_from" type="date" class="h-8 w-full min-w-0 px-2.5 text-[12px]" />
                            </div>
                            <div class="grid gap-1.5">
                                <Label class="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">Дата до</Label>
                                <Input v-model="filterForm.created_to" type="date" class="h-8 w-full min-w-0 px-2.5 text-[12px]" />
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button variant="secondary" size="sm" class="h-8 px-3 text-xs" @click="applyFilters">Застосувати</Button>
                            <Button variant="ghost" size="sm" class="h-8 px-3 text-xs" @click="clearFilters">Очистити</Button>
                        </div>
                    </CollapsibleContent>
                </Collapsible>
            </div>

            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="border-b border-sidebar-border/70 text-[11px] uppercase text-muted-foreground">
                            <tr>
                                <th v-if="columnVisibility.id" class="px-3 py-2">ID</th>
                                <th v-if="columnVisibility.created_at" class="px-3 py-2">Дата</th>
                                <th v-if="columnVisibility.author" class="px-3 py-2 whitespace-nowrap">Хто подав</th>
                                <th v-if="columnVisibility.expense_type" class="px-3 py-2 whitespace-nowrap">Тип витрат</th>
                                <th v-if="columnVisibility.expense_category" class="px-3 py-2 whitespace-nowrap">Категорія витрат</th>
                                <th v-if="columnVisibility.requisites" class="px-3 py-2">Реквізити</th>
                                <th v-if="columnVisibility.amount" class="px-3 py-2">Сума / Комісія</th>
                                <th v-if="columnVisibility.purchase_reference" class="px-3 py-2 whitespace-nowrap">№ Закупки</th>
                                <!--<th class="px-3 py-2 whitespace-nowrap">Готово до оплати</th>-->
                                <th v-if="columnVisibility.paid_account" class="px-3 py-2">Рахунок</th>
                                <!--<th class="px-3 py-2 whitespace-nowrap">Оплачено</th>-->
                                <th v-if="columnVisibility.status" class="px-3 py-2">Статус</th>
                                <th v-if="columnVisibility.receipt_url" class="px-3 py-2">Квитанція</th>
                                <th v-if="columnVisibility.participants" class="px-3 py-2">Учасники</th>
                                
                                <th class="px-3 py-2 text-right">Дії</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in props.paymentRequests.data" :key="item.id" class="border-b border-sidebar-border/40 align-top">
                                <td v-if="columnVisibility.id" class="px-3 py-2 font-semibold">
                                    <div class="group inline-flex items-center gap-1">
                                        <span class="text-muted-foreground">{{ item.id }}</span>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.created_at" class="px-3 py-2 whitespace-nowrap">
                                    <div class="group inline-flex items-center gap-1">
                                        <span class="text-muted-foreground">{{ new Date(item.created_at).toLocaleString().slice(0, -3) }}</span>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.author" class="px-3 py-2">
                                    <div class="group inline-flex items-center gap-1">
                                        <span>{{ item.author?.name ?? '—' }}</span>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.expense_type" class="px-3 py-2 whitespace-nowrap">
                                    <div class="group inline-flex items-center gap-1">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="max-w-45 truncate">
                                                        {{ item.expense_type?.name ?? '—' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <span class="text-xs">{{ item.expense_type?.name ?? '—' }}</span>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.expense_category" class="px-3 py-2 whitespace-nowrap">
                                    <div class="group inline-flex items-center gap-1">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="max-w-45 truncate">
                                                        {{ item.expense_category?.name ?? '—' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <span class="text-xs">{{ item.expense_category?.name ?? '—' }}</span>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.requisites" class="px-3 py-2">
                                    <div class="group flex max-w-55 items-start gap-1">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="truncate leading-5 max-w-32">{{ item.requisites ?? '—' }}</span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <span class="text-xs">{{ item.requisites ?? '—' }}</span>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                        <button
                                            v-if="item.requisites"
                                            type="button"
                                            class="mt-0.5"
                                            @click="copyValue(item.requisites, 'Реквізити')"
                                            aria-label="Скопіювати реквізити"
                                        >
                                            <Clipboard class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                                        </button>
                                        <div v-if="item.requisites_file_url" class="mt-0.5">
                                            <TooltipProvider :delay-duration="100">
                                                <Tooltip>
                                                    <TooltipTrigger as-child>
                                                        <button
                                                            type="button"
                                                            class=""
                                                            @click="copyValue(item.requisites_file_url, 'Файл реквізитів')"
                                                            aria-label="Скопіювати файл реквізитів"
                                                        >
                                                            <Paperclip class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                                                        </button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>
                                                        <span class="text-xs">{{ item.requisites_file_url }}</span>
                                                    </TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                        </div>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.amount" class="px-3 py-2 font-medium">
                                    <div class="group flex flex-col">
                                        <div class="inline-flex items-center gap-1">
                                            <span>{{ item.amount }}</span>
                                            <button
                                                type="button"
                                                class=""
                                                @click="copyValue(item.amount, 'Суму')"
                                                aria-label="Скопіювати суму"
                                            >
                                                <Clipboard class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                                            </button>
                                        </div>
                                        <span v-if="item.commission" class="text-[11px] text-muted-foreground">
                                            Комісія: {{ item.commission }}
                                        </span>
                                    </div>
                                </td>
                                <td v-if="columnVisibility.purchase_reference" class="px-3 py-2">
                                    <div class="group inline-flex items-center gap-1">
                                        <span>{{ item.purchase_reference ?? '—' }}</span>
                                        <button
                                            v-if="item.purchase_reference"
                                            type="button"
                                            class=""
                                            @click="copyValue(item.purchase_reference, 'Номер закупки')"
                                            aria-label="Скопіювати номер закупки"
                                        >
                                            <Clipboard class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                                        </button>
                                    </div>
                                </td>
                                <!--<td class="px-3 py-2">
                                    <div class="group inline-flex items-center gap-1">
                                        <Badge :variant="item.ready_for_payment ? 'default' : 'outline'">
                                            {{ item.ready_for_payment ? 'Так' : 'Ні' }}
                                        </Badge>
                                      
                                    </div>
                                </td>-->
                                <td v-if="columnVisibility.paid_account" class="px-3 py-2 whitespace-nowrap">
                                    <div class="group inline-flex items-center gap-1">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="max-w-45 truncate">
                                                        {{ item.paid_account?.name ?? '—' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <span class="text-xs">{{ item.paid_account?.name ?? '—' }}</span>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                       
                                    </div>
                                </td>
                                <!--<td class="px-3 py-2">
                                    <div class="group inline-flex items-center gap-1">
                                        <Badge :variant="item.paid ? 'default' : 'outline'">
                                            {{ item.paid ? 'Так' : 'Ні' }}
                                        </Badge>
                                        
                                    </div>
                                </td>-->

                                <td v-if="columnVisibility.status" class="px-3 py-2">
                                    <div class="group inline-flex items-center gap-1">
                                        <Badge :variant="statusVariant(item)">{{ statusLabel(item) }}</Badge>
                                    </div>
                                </td>

                                <td v-if="columnVisibility.receipt_url" class="px-3 py-2">
                                    <div v-if="item.receipt_url" class="flex items-center gap-2">
                                        <Check class="h-4 w-4 text-green-600" />
                                        <template v-if="!isUser">
                                            <div class="group inline-flex items-center gap-1">
                                                <TooltipProvider :delay-duration="100">
                                                    <Tooltip>
                                                        <TooltipTrigger as-child>
                                                            <span class="max-w-32 truncate text-[11px] text-muted-foreground">
                                                                {{ item.receipt_url }}
                                                            </span>
                                                        </TooltipTrigger>
                                                        <TooltipContent>
                                                            <span class="text-xs">{{ item.receipt_url }}</span>
                                                        </TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                                <button
                                                    type="button"
                                                    class=""
                                                    @click="copyReceiptUrl(item.receipt_url)"
                                                    aria-label="Скопіювати посилання"
                                                >
                                                    <Clipboard class="h-3.5 w-3.5 text-muted-foreground hover:text-foreground" />
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td v-if="columnVisibility.participants" class="px-3 py-2">
                                    <div class="group flex max-w-45 items-start gap-1">
                                        <TooltipProvider :delay-duration="100">
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <span class="truncate leading-5 text-muted-foreground">
                                                        {{ item.participants.map((p) => p.name).join(', ') || '—' }}
                                                    </span>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <span class="text-xs">
                                                        {{ item.participants.map((p) => p.name).join(', ') || '—' }}
                                                    </span>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    </div>
                                </td>
                                
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button
                                            size="icon-sm"
                                            variant="outline"
                                            @click="openEdit(item)"
                                            :disabled="!item.can.update"
                                            aria-label="Редагувати"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        <Button
                                            size="icon-sm"
                                            variant="secondary"
                                            @click="openHistory(item)"
                                            aria-label="Історія"
                                        >
                                            <History class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="props.paymentRequests.data.length === 0">
                                <td class="px-4 py-6 text-center text-muted-foreground" :colspan="visibleColumnsCount">Заявок не знайдено.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-wrap items-center gap-3 border-t border-sidebar-border/70 px-4 py-3">
                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                        <span>На сторінці:</span>
                        <select
                            :value="String(filterForm.per_page)"
                            class="h-8 rounded-md border border-input bg-transparent px-2 text-xs text-foreground"
                            @change="changePerPage"
                        >
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="30">30</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <template v-for="link in props.paymentRequests.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            class="rounded-md border border-input px-3 py-1 text-sm"
                            :class="link.active ? 'bg-muted font-semibold' : 'text-muted-foreground'"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="rounded-md border border-input px-3 py-1 text-sm text-muted-foreground opacity-60"
                            v-html="link.label"
                        />
                    </template>
                </div>

                <div class="border-t border-sidebar-border/70 px-4 py-3 text-xs">
                    <div class="flex flex-wrap items-center gap-2 text-muted-foreground">
                        <span class="font-medium text-foreground">Активні фільтри:</span>
                        <template v-if="appliedFilters.length">
                            <span
                                v-for="item in appliedFilters"
                                :key="`${item.label}-${item.value}`"
                                class="rounded-md border border-input/60 bg-background px-2 py-1 text-foreground"
                            >
                                {{ item.label }}: {{ item.value }}
                            </span>
                        </template>
                        <span v-else>немає</span>
                    </div>
                </div>

                <div
                    v-if="props.totals"
                    class="border-t border-sidebar-border/70 bg-muted/30 px-4 py-3"
                >
                    <div class="flex flex-col gap-2 text-sm">
                        <span class="text-xs text-muted-foreground">
                            Підсумок по всіх заявках, що відповідають фільтрам за заданий період:
                        </span>
                        <div class="flex flex-wrap items-center gap-4 font-semibold">
                            <span>Сума: {{ formatMoney(props.totals.amount) }}</span>
                            <span>Комісія: {{ formatMoney(props.totals.commission) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Drawer
            :open="drawerOpen"
            @update:open="(value) => { drawerOpen = value; if (!value) resetFormState(); }"
        >
            <DrawerContent class="h-[90vh]">
                <div class="mx-auto flex h-full w-full max-w-4xl flex-col">
                    <form class="flex h-full flex-col" @submit.prevent="submit">
                        <DrawerHeader class="px-6 pt-6">
                            <DrawerTitle>
                                <span v-if="isEditing">Редагувати заявку</span>
                                <span v-else>Створити заявку</span>
                            </DrawerTitle>
                            <DrawerDescription>
                                Заповніть обов'язкові поля та підтвердіть дію.
                            </DrawerDescription>
                        </DrawerHeader>

                        <div class="flex-1 overflow-y-auto px-6 pb-6">
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label>Тип витрат</Label>
                                    <select v-model="form.expense_type_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                                        <option value="">Оберіть тип</option>
                                        <option v-for="type in props.expenseTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                    </select>
                                    <span v-if="form.errors.expense_type_id" class="text-sm text-red-600">{{ form.errors.expense_type_id }}</span>
                                </div>
                                <div class="grid gap-2">
                                    <Label>Категорія витрат</Label>
                                    <select v-model="form.expense_category_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm" :disabled="!form.expense_type_id">
                                        <option value="">Оберіть категорію</option>
                                        <option v-for="category in filteredCategories" :key="category.id" :value="category.id">{{ category.name }}</option>
                                    </select>
                                    <span v-if="form.errors.expense_category_id" class="text-sm text-red-600">{{ form.errors.expense_category_id }}</span>
                                </div>
                                <div v-if="showPurchaseReference" class="grid gap-2">
                                    <Label>Номер з таблиці по закупці</Label>
                                    <Input v-model="form.purchase_reference" type="text" />
                                    <span v-if="form.errors.purchase_reference" class="text-sm text-red-600">{{ form.errors.purchase_reference }}</span>
                                </div>
                                <div class="grid gap-2 md:col-span-1">
                                    <Label>Реквізити для оплати</Label>
                                    <Input v-model="form.requisites" type="text" />
                                    <span v-if="form.errors.requisites" class="text-sm text-red-600">{{ form.errors.requisites }}</span>
                                </div>
                                <div class="grid gap-2 md:col-span-1">
                                    <Label>Файл реквізитів</Label>
                                    <Input
                                        type="file"
                                        accept=".xls,.xlsx,.csv,.pdf,.jpg,.jpeg,.png,.webp"
                                        @change="onRequisitesFileChange"
                                    />
                                    <span v-if="form.errors.requisites_file" class="text-sm text-red-600">{{ form.errors.requisites_file }}</span>
                                    <p v-if="selected?.requisites_file_url" class="text-xs text-muted-foreground">
                                        Поточний файл:
                                        <a :href="selected.requisites_file_url" class="underline" target="_blank" rel="noreferrer">переглянути</a>
                                    </p>
                                </div>
                                
                                <div class="grid gap-2">
                                    <Label>Сума</Label>
                                    <Input v-model="form.amount" type="number" step="0.01" />
                                    <span v-if="form.errors.amount" class="text-sm text-red-600">{{ form.errors.amount }}</span>
                                </div>
                                <div v-if="canManageCommission" class="grid gap-2">
                                    <Label>Комісія</Label>
                                    <Input v-model="form.commission" type="number" step="0.01" />
                                    <span v-if="form.errors.commission" class="text-sm text-red-600">{{ form.errors.commission }}</span>
                                </div>
                                
                                <div v-if="canManagePaidAccount"class="grid gap-2 md:col-span-2">
                                    <Label>Рахунок, з якого була здійснена оплата</Label>
                                    <select v-model="form.paid_account_id" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                                        <option value="">Оберіть рахунок</option>
                                        <option v-for="account in props.paymentAccounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                                    </select>
                                    <span v-if="form.errors.paid_account_id" class="text-sm text-red-600">{{ form.errors.paid_account_id }}</span>
                                </div>
                                <div class="grid gap-2">
                                    <Label>Готово до оплати</Label>
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-md border border-input px-3 py-2"
                                        @click="form.ready_for_payment = !form.ready_for_payment"
                                    >
                                        <Checkbox v-model="form.ready_for_payment" class="h-5 w-5" @click.stop />
                                        <span class="text-sm font-medium">Готово</span>
                                    </div>
                                </div>
                                <div v-if="canMarkPaid" class="grid gap-2">
                                    <Label>Оплачено</Label>
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-md border border-input px-3 py-2"
                                        @click="form.paid = !form.paid"
                                    >
                                        <Checkbox v-model="form.paid" class="h-5 w-5" @click.stop />
                                        <span class="text-sm font-medium">Оплачено</span>
                                    </div>
                                </div>

                                <template v-if="canManageReceipt">
                                    <div class="grid gap-2 md:col-span-2">
                                        <Label>Спосіб завантаження квитанції</Label>
                                        <div class="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                :class="receiptMode === 'url' ? 'border-primary text-primary' : ''"
                                                @click="setReceiptMode('url')"
                                            >
                                                Посилання
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                :class="receiptMode === 'file' ? 'border-primary text-primary' : ''"
                                                @click="setReceiptMode('file')"
                                            >
                                                Файл PDF
                                            </Button>
                                        </div>
                                    </div>
                                    <div v-if="receiptMode === 'url'" class="grid gap-2 md:col-span-2">
                                        <Label>Посилання на квитанцію або підтвердження оплати</Label>
                                        <Input v-model="form.receipt_url" type="text" />
                                        <span v-if="form.errors.receipt_url" class="text-sm text-red-600">{{ form.errors.receipt_url }}</span>
                                    </div>
                                    <div v-else class="grid gap-2 md:col-span-2">
                                        <Label>Файл квитанції (PDF)</Label>
                                        <Input type="file" accept=".pdf" @change="onReceiptFileChange" />
                                        <span v-if="form.errors.receipt_file" class="text-sm text-red-600">{{ form.errors.receipt_file }}</span>
                                        <p v-if="selected?.receipt_url" class="text-xs text-muted-foreground">
                                            Поточний файл/посилання:
                                            <a :href="selected.receipt_url" class="underline" target="_blank" rel="noreferrer">переглянути</a>
                                        </p>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <DrawerFooter class="px-6 pb-6">
                            <Button type="submit" :disabled="form.processing">
                                {{ isEditing ? 'Зберегти' : 'Створити' }}
                            </Button>
                            <DrawerClose as-child>
                                <Button type="button" variant="outline">Скасувати</Button>
                            </DrawerClose>
                        </DrawerFooter>
                    </form>
                </div>
            </DrawerContent>
        </Drawer>

        <Drawer :open="historyOpen" @update:open="historyOpen = $event">
            <DrawerContent class="h-[80vh]">
                <div class="mx-auto flex h-full w-full max-w-3xl flex-col">
                    <DrawerHeader class="px-6 pt-6">
                        <DrawerTitle>Історія заявки</DrawerTitle>
                        <DrawerDescription>
                            Хто, що і коли змінював.
                        </DrawerDescription>
                    </DrawerHeader>
                    <div class="flex-1 overflow-y-auto px-6 pb-6">
                        <div v-if="selected" class="space-y-4">
                            <div v-for="entry in selected.history" :key="entry.id" class="rounded-lg border border-sidebar-border/60 p-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">{{ entry.user?.name }}</span>
                                    <span class="text-xs text-muted-foreground">{{ new Date(entry.created_at).toLocaleString() }}</span>
                                </div>
                                <div class="mt-1 text-xs uppercase text-muted-foreground">{{ actionLabel(entry.action) }}</div>
                                <div class="mt-3 space-y-2 text-sm">
                                    <div v-for="(change, key) in entry.changed_fields" :key="key" class="grid gap-1">
                                        <div class="text-xs font-semibold uppercase text-muted-foreground">
                                            {{ historyFieldLabels[String(key)] ?? String(key) }}
                                        </div>
                                        <div v-if="typeof change === 'object' && change !== null && 'old' in change && 'new' in change" class="space-y-1 text-xs">
                                            <span class="text-muted-foreground">Було:</span>
                                            <div class="break-all rounded-sm bg-muted/40 px-2 py-1">
                                                {{ formatHistoryValue((change as { old: unknown }).old) }}
                                            </div>
                                            <span class="mx-2 text-muted-foreground">→</span>
                                            <span class="text-muted-foreground">Стало:</span>
                                            <div class="break-all rounded-sm bg-muted/40 px-2 py-1">
                                                {{ formatHistoryValue((change as { new: unknown }).new) }}
                                            </div>
                                        </div>
                                        <div v-else class="break-all rounded-sm bg-muted/40 px-2 py-1 text-xs">
                                            {{ formatHistoryValue(change) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <p v-if="selected.history.length === 0" class="text-sm text-muted-foreground">Історія відсутня.</p>
                        </div>
                    </div>
                    <DrawerFooter class="px-6 pb-6">
                        <DrawerClose as-child>
                            <Button type="button" variant="outline">Закрити</Button>
                        </DrawerClose>
                    </DrawerFooter>
                </div>
            </DrawerContent>
        </Drawer>
    </AppLayout>
</template>
