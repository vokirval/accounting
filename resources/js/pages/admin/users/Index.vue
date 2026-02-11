<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { BreadcrumbItem, AppPageProps } from '@/types';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'accountant' | 'admin';
    blocked_at: string | null;
    editable_expense_type_ids: number[];
};

type ExpenseTypeOption = {
    id: number;
    name: string;
};

type PageProps = AppPageProps<{
    users: UserRow[];
    expenseTypes: ExpenseTypeOption[];
}>;

const page = usePage<PageProps>();
const props = computed(() => page.props);
const errors = computed(() => (props.value.errors ?? {}) as Record<string, string>);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Адмін', href: '/admin/users' },
    { title: 'Користувачі', href: '/admin/users' },
];

const dialogOpen = ref(false);
const isEditing = ref(false);
const selected = ref<UserRow | null>(null);

const form = useForm({
    name: '',
    email: '',
    role: 'user',
    password: '',
    editable_expense_type_ids: [] as number[],
});

const openCreate = () => {
    isEditing.value = false;
    selected.value = null;
    form.reset();
    form.clearErrors();
    form.editable_expense_type_ids = [];
    dialogOpen.value = true;
};

const openEdit = (user: UserRow) => {
    isEditing.value = true;
    selected.value = user;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = '';
    form.editable_expense_type_ids = [...(user.editable_expense_type_ids ?? [])];
    form.clearErrors();
    dialogOpen.value = true;
};

const toggleExpenseTypeAccess = (expenseTypeId: number) => {
    if (form.editable_expense_type_ids.includes(expenseTypeId)) {
        form.editable_expense_type_ids = form.editable_expense_type_ids.filter((id) => id !== expenseTypeId);
        return;
    }

    form.editable_expense_type_ids = [...form.editable_expense_type_ids, expenseTypeId];
};

const submit = () => {
    if (isEditing.value && selected.value) {
        const confirmed = window.confirm('Підтвердити оновлення користувача?');
        if (!confirmed) {
            return;
        }
        form.transform((data) => {
            const payload = { ...data };
            if (!payload.password) {
                delete payload.password;
            }
            return payload;
        });
        form.put(`/admin/users/${selected.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
        return;
    }

    form.transform((data) => data);
    form.post('/admin/users', {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
};

const blockUser = (user: UserRow) => {
    const confirmed = window.confirm('Заблокувати користувача?');
    if (!confirmed) {
        return;
    }
    router.patch(`/admin/users/${user.id}/block`, {}, { preserveScroll: true });
};

const unblockUser = (user: UserRow) => {
    const confirmed = window.confirm('Розблокувати користувача?');
    if (!confirmed) {
        return;
    }
    router.patch(`/admin/users/${user.id}/unblock`, {}, { preserveScroll: true });
};

const removeUser = (user: UserRow) => {
    const confirmed = window.confirm('Видалити користувача?');
    if (!confirmed) {
        return;
    }
    router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Користувачі" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Користувачі</h1>
                    <p class="text-sm text-muted-foreground">Керування користувачами лише для адміністратора.</p>
                </div>
                <Button @click="openCreate">Створити користувача</Button>
            </div>
            <div
                v-if="errors.user"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
            >
                <p>{{ errors.user }}</p>
            </div>
            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-sidebar-border/70 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Ім'я</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Роль</th>
                            <th class="px-4 py-3">Статус</th>
                            <th class="px-4 py-3">Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in props.users" :key="user.id" class="border-b border-sidebar-border/40">
                            <td class="px-4 py-3">{{ user.name }}</td>
                            <td class="px-4 py-3">{{ user.email }}</td>
                            <td class="px-4 py-3">{{ user.role }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="user.blocked_at ? 'destructive' : 'secondary'">
                                    {{ user.blocked_at ? 'Заблоковано' : 'Активний' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-2">
                                    <Button size="sm" variant="outline" @click="openEdit(user)">Редагувати</Button>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        v-if="!user.blocked_at"
                                        @click="blockUser(user)"
                                    >
                                        Заблокувати
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="secondary"
                                        v-else
                                        @click="unblockUser(user)"
                                    >
                                        Розблокувати
                                    </Button>
                                    <Button size="sm" variant="destructive" @click="removeUser(user)">Видалити</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.users.length === 0">
                            <td class="px-4 py-6 text-center text-muted-foreground" colspan="5">Користувачі відсутні.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Редагувати користувача' : 'Створити користувача' }}</DialogTitle>
                </DialogHeader>
                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label>Ім'я</Label>
                        <Input v-model="form.name" />
                        <span v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label>Email</Label>
                        <Input v-model="form.email" type="email" />
                        <span v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label>Роль</Label>
                        <select v-model="form.role" class="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                            <option value="user">Користувач</option>
                            <option value="accountant">Бухгалтер</option>
                            <option value="admin">Адміністратор</option>
                        </select>
                        <span v-if="form.errors.role" class="text-sm text-red-600">{{ form.errors.role }}</span>
                    </div>
                    <div class="grid gap-2">
                        <Label>Доступ до типів витрат (категорії)</Label>
                        <div class="max-h-40 space-y-2 overflow-y-auto rounded-md border border-input p-3">
                            <label
                                v-for="expenseType in props.expenseTypes"
                                :key="expenseType.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    type="checkbox"
                                    class="h-4 w-4"
                                    :checked="form.editable_expense_type_ids.includes(expenseType.id)"
                                    @change="toggleExpenseTypeAccess(expenseType.id)"
                                />
                                <span>{{ expenseType.name }}</span>
                            </label>
                            <p v-if="props.expenseTypes.length === 0" class="text-xs text-muted-foreground">
                                Типи витрат відсутні.
                            </p>
                        </div>
                        <span v-if="form.errors.editable_expense_type_ids" class="text-sm text-red-600">
                            {{ form.errors.editable_expense_type_ids }}
                        </span>
                    </div>
                    <div class="grid gap-2">
                        <Label>Пароль</Label>
                        <Input v-model="form.password" type="password" :placeholder="isEditing ? 'Залиште порожнім, щоб не змінювати' : ''" />
                        <span v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="ghost" @click="dialogOpen = false">Скасувати</Button>
                        <Button type="submit" :disabled="form.processing">Зберегти</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
