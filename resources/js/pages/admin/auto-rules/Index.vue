<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Calendar } from '@/components/ui/calendar';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { parseDate } from '@internationalized/date';
import type { AppPageProps, BreadcrumbItem } from '@/types';

type NamedItem = { id: number; name: string };

type AutoRule = {
    id: number;
    name: string;
    expense_type_id: number;
    expense_category_id: number;
    requisites: string | null;
    requisites_file_url: string | null;
    amount: string;
    ready_for_payment: boolean;
    frequency: string;
    interval_days: number | null;
    days_of_week: number[] | null;
    day_of_month: number | null;
    start_date: string;
    run_at: string;
    timezone: string;
    next_run_at: string | null;
    last_run_at: string | null;
    is_active: boolean;
    expense_type?: NamedItem | null;
    expense_category?: NamedItem | null;
};

type AutoRuleLog = {
    id: number;
    level: string;
    message: string;
    created_at: string;
    rule: { id: number; name: string } | null;
    context: Record<string, unknown> | null;
};

type CategoryItem = { id: number; name: string; expense_type_id: number | string | null };

type PageProps = AppPageProps<{
    items: AutoRule[];
    logs: AutoRuleLog[];
    expenseTypes: NamedItem[];
    expenseCategories: CategoryItem[];
    timezone: string;
}>;

const page = usePage<PageProps>();
const props = computed(() => page.props);
const errors = computed(() => (props.value.errors ?? {}) as Record<string, string>);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Адмін', href: '/admin/users' },
    { title: 'Автоправила', href: '/admin/auto-rules' },
];

const drawerOpen = ref(false);
const isEditing = ref(false);
const selected = ref<AutoRule | null>(null);

const form = useForm({
    name: '',
    expense_type_id: '' as number | '',
    expense_category_id: '' as number | '',
    requisites: '',
    requisites_file: null as File | null,
    amount: '',
    ready_for_payment: false,
    frequency: 'monthly',
    interval_days: 3 as number | '',
    days_of_week: [] as number[],
    day_of_month: '' as number | '',
    start_date: '',
    run_at: '09:00',
    timezone: props.value.timezone ?? 'Europe/Kyiv',
    is_active: true,
});

const frequencyLabels: Record<string, string> = {
    once: 'Один раз',
    daily: 'Щодня',
    weekly: 'Щотижня',
    monthly: 'Щомісяця',
    every_n_days: 'Кожні N днів',
};

const weekDays = [
    { value: 1, label: 'Пн' },
    { value: 2, label: 'Вт' },
    { value: 3, label: 'Ср' },
    { value: 4, label: 'Чт' },
    { value: 5, label: 'Пт' },
    { value: 6, label: 'Сб' },
    { value: 7, label: 'Нд' },
];

const filteredCategories = computed(() => {
    if (!form.expense_type_id) {
        return props.value.expenseCategories;
    }
    return props.value.expenseCategories.filter(
        (category) => Number(category.expense_type_id) === Number(form.expense_type_id),
    );
});

watch(
    () => form.expense_type_id,
    () => {
        if (!filteredCategories.value.some((category) => category.id === Number(form.expense_category_id))) {
            form.expense_category_id = '';
        }
    },
);

const openCreate = () => {
    isEditing.value = false;
    selected.value = null;
    form.reset();
    form.clearErrors();
    form.frequency = 'monthly';
    form.interval_days = 3;
    form.days_of_week = [];
    form.day_of_month = '';
    form.start_date = formatDateKey(new Date());
    form.run_at = '09:00';
    form.timezone = props.value.timezone ?? 'Europe/Kyiv';
    form.is_active = true;
    drawerOpen.value = true;
};

const openEdit = (item: AutoRule) => {
    isEditing.value = true;
    selected.value = item;
    form.name = item.name;
    form.expense_type_id = item.expense_type_id;
    form.expense_category_id = item.expense_category_id;
    form.requisites = item.requisites ?? '';
    form.requisites_file = null;
    form.amount = item.amount ?? '';
    form.ready_for_payment = !!item.ready_for_payment;
    form.frequency = item.frequency;
    form.interval_days = item.interval_days ?? '';
    form.days_of_week = item.days_of_week ?? [];
    form.day_of_month = item.day_of_month ?? '';
    form.start_date = item.start_date ? item.start_date.slice(0, 10) : '';
    form.run_at = item.run_at?.slice(0, 5) ?? '09:00';
    form.timezone = item.timezone ?? (props.value.timezone ?? 'Europe/Kyiv');
    form.is_active = !!item.is_active;
    form.clearErrors();
    drawerOpen.value = true;
};

const remove = (item: AutoRule) => {
    const confirmed = window.confirm('Видалити автоправило?');
    if (!confirmed) return;
    router.delete(`/admin/auto-rules/${item.id}`, { preserveScroll: true });
};

const submit = () => {
    const payload = {
        ...form.data(),
        _method: isEditing.value ? 'put' : undefined,
    };

    const url = isEditing.value && selected.value
        ? `/admin/auto-rules/${selected.value.id}`
        : '/admin/auto-rules';

    form.transform(() => payload).post(url, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            drawerOpen.value = false;
            form.reset();
        },
    });
};

const toggleDay = (value: number) => {
    if (form.days_of_week.includes(value)) {
        form.days_of_week = form.days_of_week.filter((day) => day !== value);
        return;
    }
    form.days_of_week = [...form.days_of_week, value].sort((a, b) => a - b);
};

const parseFormDate = (date: string) => {
    if (!date) return null;
    const normalized = date.includes('T') || date.includes(' ')
        ? date.slice(0, 10)
        : date;
    const [y, m, d] = normalized.split('-').map(Number);
    return new Date(y, m - 1, d);
};

const formatDateKey = (date: Date) => {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const buildOccurrences = () => {
    const startDate = parseFormDate(form.start_date);
    if (!startDate) return new Set<string>();

    const occurrences = new Set<string>();
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const limit = new Date(today.getFullYear(), 11, 31);

    const dayOfMonth = Number(form.day_of_month) || startDate.getDate();

    const addDate = (date: Date) => {
        const key = formatDateKey(date);
        occurrences.add(key);
    };

    if (form.frequency === 'once') {
        addDate(startDate);
        return occurrences;
    }

    if (form.frequency === 'daily') {
        const cursor = new Date(startDate);
        while (cursor <= limit) {
            if (cursor >= today) addDate(cursor);
            cursor.setDate(cursor.getDate() + 1);
        }
        return occurrences;
    }

    if (form.frequency === 'every_n_days') {
        const interval = Math.max(1, Number(form.interval_days) || 1);
        const cursor = new Date(startDate);
        while (cursor <= limit) {
            if (cursor >= today) addDate(cursor);
            cursor.setDate(cursor.getDate() + interval);
        }
        return occurrences;
    }

    if (form.frequency === 'weekly') {
        const days = new Set(form.days_of_week);
        const cursor = new Date(startDate);
        while (cursor <= limit) {
            const jsDay = cursor.getDay();
            const isoDay = jsDay === 0 ? 7 : jsDay;
            if (days.has(isoDay) && cursor >= today) addDate(cursor);
            cursor.setDate(cursor.getDate() + 1);
        }
        return occurrences;
    }

    if (form.frequency === 'monthly') {
        const step = 1;
        const cursor = new Date(startDate);
        while (cursor <= limit) {
            const candidate = new Date(cursor.getFullYear(), cursor.getMonth(), dayOfMonth);
            if (candidate >= today) addDate(candidate);
            cursor.setMonth(cursor.getMonth() + step);
        }
        return occurrences;
    }

    return occurrences;
};

const occurrences = computed(() => buildOccurrences());

const calendarPlaceholder = ref(parseDate(formatDateKey(new Date())));

const calendarSelection = computed(() => {
    const dates = Array.from(occurrences.value).map((value) => parseDate(value));
    if (!calendarPlaceholder.value) {
        return dates;
    }
    const { year, month } = calendarPlaceholder.value;
    return dates.filter((date) => date.year === year && date.month === month);
});

</script>

<template>
    <Head title="Автоправила" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Автоправила</h1>
                    <p class="text-sm text-muted-foreground">Автоматичне створення платіжних заявок за шаблонами.</p>
                </div>
                <Button size="sm" @click="openCreate">Створити правило</Button>
            </div>

            <div
                v-if="errors.name"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
            >
                <p>{{ errors.name }}</p>
            </div>

            <div class="grid gap-6 lg:grid-cols-[2fr,1fr]">
                <div class="rounded-xl border border-sidebar-border/70 bg-white p-4 dark:border-sidebar-border dark:bg-neutral-900">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Список правил</h2>
                        <Badge variant="outline">{{ props.items.length }}</Badge>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div
                            v-for="item in props.items"
                            :key="item.id"
                            class="rounded-lg border border-sidebar-border/60 p-4"
                        >
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold">{{ item.name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ item.expense_type?.name ?? '—' }} · {{ item.expense_category?.name ?? '—' }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        Наступний запуск: {{ item.next_run_at ? new Date(item.next_run_at).toLocaleString('uk-UA') : '—' }}
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <Badge :variant="item.is_active ? 'secondary' : 'outline'">
                                        {{ item.is_active ? 'Активне' : 'Вимкнено' }}
                                    </Badge>
                                    <Button size="sm" variant="outline" @click="openEdit(item)">Редагувати</Button>
                                    <Button size="sm" variant="destructive" @click="remove(item)">Видалити</Button>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span>Режим: {{ frequencyLabels[item.frequency] ?? item.frequency }}</span>
                                <span>·</span>
                                <span>Сума: {{ item.amount }}</span>
                                <span>·</span>
                                <span>Час: {{ item.run_at?.slice(0, 5) }}</span>
                            </div>
                        </div>
                        <div v-if="props.items.length === 0" class="text-sm text-muted-foreground">
                            Немає створених правил.
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/70 bg-white p-4 dark:border-sidebar-border dark:bg-neutral-900">
                    <h2 class="text-base font-semibold">Логи</h2>
                    <div class="mt-4 space-y-3 text-xs">
                        <div v-for="log in props.logs" :key="log.id" class="rounded-lg border border-sidebar-border/60 p-3">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ log.rule?.name ?? 'Правило видалено' }}</span>
                                <Badge :variant="log.level === 'error' ? 'destructive' : 'secondary'">
                                    {{ log.level === 'error' ? 'Помилка' : 'OK' }}
                                </Badge>
                            </div>
                            <div class="mt-1 text-muted-foreground">{{ log.message }}</div>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ new Date(log.created_at).toLocaleString('uk-UA') }}</div>
                        </div>
                        <div v-if="props.logs.length === 0" class="text-sm text-muted-foreground">
                            Логів поки що немає.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Drawer :open="drawerOpen" @update:open="drawerOpen = $event">
            <DrawerContent class="h-[90vh] max-h-[90vh]">
                <div class="mx-auto flex h-full w-full max-w-6xl flex-col">
                    <form class="flex h-full flex-col" @submit.prevent="submit">
                        <DrawerHeader class="px-6 pt-6">
                            <DrawerTitle>{{ isEditing ? 'Редагувати автоправило' : 'Створити автоправило' }}</DrawerTitle>
                            <DrawerDescription>
                                Заповніть дані для автоматичного створення заявки.
                            </DrawerDescription>
                        </DrawerHeader>
                        <div class="flex-1 overflow-y-auto px-6 pb-6">
                            <div class="grid gap-4">
                                <div class="grid gap-2">
                                    <Label>Назва правила</Label>
                                    <Input v-model="form.name" type="text" />
                                    <span v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</span>
                                </div>

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
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label>Реквізити для оплати</Label>
                                        <Input v-model="form.requisites" type="text" />
                                        <span v-if="form.errors.requisites" class="text-sm text-red-600">{{ form.errors.requisites }}</span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Файл реквізитів</Label>
                                        <Input type="file" accept=".xls,.xlsx,.csv,.pdf,.p7m,.jpg,.jpeg,.png,.webp" @change="(e) => (form.requisites_file = (e.target as HTMLInputElement).files?.[0] ?? null)" />
                                        <span v-if="form.errors.requisites_file" class="text-sm text-red-600">{{ form.errors.requisites_file }}</span>
                                        <p v-if="selected?.requisites_file_url" class="text-xs text-muted-foreground">
                                            Поточний файл:
                                            <a :href="selected.requisites_file_url" class="underline" target="_blank" rel="noreferrer">переглянути</a>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-2">
                                    <div class="grid gap-2">
                                        <Label>Сума</Label>
                                        <Input v-model="form.amount" type="number" step="0.01" />
                                        <span v-if="form.errors.amount" class="text-sm text-red-600">{{ form.errors.amount }}</span>
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
                                </div>

                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label>Режим</Label>
                                        <select v-model="form.frequency" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                                            <option value="once">Один раз</option>
                                            <option value="daily">Щодня</option>
                                            <option value="weekly">Щотижня</option>
                                            <option value="monthly">Щомісяця</option>
                                            <option value="every_n_days">Кожні N днів</option>
                                        </select>
                                        <span v-if="form.errors.frequency" class="text-sm text-red-600">{{ form.errors.frequency }}</span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Дата старту</Label>
                                        <Input v-model="form.start_date" type="date" />
                                        <span v-if="form.errors.start_date" class="text-sm text-red-600">{{ form.errors.start_date }}</span>
                                    </div>
                                    <div class="grid gap-2">
                                        <Label>Час (Kyiv)</Label>
                                        <Input v-model="form.run_at" type="time" />
                                        <span v-if="form.errors.run_at" class="text-sm text-red-600">{{ form.errors.run_at }}</span>
                                    </div>
                                </div>

                                <div v-if="form.frequency === 'weekly'" class="grid gap-2">
                                    <Label>Дні тижня</Label>
                                    <div class="flex flex-wrap gap-2">
                                        <button
                                            v-for="day in weekDays"
                                            :key="day.value"
                                            type="button"
                                            class="rounded-md border border-input px-3 py-1 text-xs"
                                            :class="form.days_of_week.includes(day.value) ? 'bg-muted font-semibold' : ''"
                                            @click="toggleDay(day.value)"
                                        >
                                            {{ day.label }}
                                        </button>
                                    </div>
                                    <span v-if="form.errors.days_of_week" class="text-sm text-red-600">{{ form.errors.days_of_week }}</span>
                                </div>

                                <div v-if="form.frequency === 'every_n_days'" class="grid gap-2">
                                    <Label>Інтервал (днів)</Label>
                                    <Input v-model="form.interval_days" type="number" min="1" />
                                    <span v-if="form.errors.interval_days" class="text-sm text-red-600">{{ form.errors.interval_days }}</span>
                                </div>

                                <div v-if="form.frequency === 'monthly'" class="grid gap-2">
                                    <Label>День місяця</Label>
                                    <Input v-model="form.day_of_month" type="number" min="1" max="31" />
                                    <span v-if="form.errors.day_of_month" class="text-sm text-red-600">{{ form.errors.day_of_month }}</span>
                                </div>

                                <div class="grid gap-2">
                                    <Label>Активність</Label>
                                    <div
                                        class="flex cursor-pointer items-center gap-3 rounded-md border border-input px-3 py-2"
                                        @click="form.is_active = !form.is_active"
                                    >
                                        <Checkbox v-model="form.is_active" class="h-5 w-5" @click.stop />
                                        <span class="text-sm font-medium">Правило активне</span>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-sidebar-border/60 p-4">
                                    <div class="text-sm font-medium">Попередній перегляд</div>
                                    <div class="mt-3 flex justify-center">
                                        <Calendar
                                            :model-value="calendarSelection"
                                            v-model:placeholder="calendarPlaceholder"
                                            :multiple="true"
                                            :readonly="true"
                                            :prevent-deselect="true"
                                            :paged-navigation="true"
                                            :fixed-weeks="true"
                                            :number-of-months="1"
                                            :week-starts-on="1"
                                            weekday-format="short"
                                            locale="uk-UA"
                                            class="auto-rules-calendar w-fit max-w-[340px]"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DrawerFooter class="px-6 pb-6">
                            <DrawerClose as-child>
                                <Button type="button" variant="outline">Скасувати</Button>
                            </DrawerClose>
                            <Button type="submit" :disabled="form.processing">
                                {{ isEditing ? 'Зберегти' : 'Створити' }}
                            </Button>
                        </DrawerFooter>
                    </form>
                </div>
            </DrawerContent>
        </Drawer>
    </AppLayout>
</template>

<style scoped>
:deep(.auto-rules-calendar [data-slot="calendar-cell-trigger"]) {
    pointer-events: none;
}
</style>
