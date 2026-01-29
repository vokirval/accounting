<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { BreadcrumbItem, AppPageProps } from '@/types';

type Item = { id: number; name: string };

type PageProps = AppPageProps<{
    items: Item[];
}>;

const page = usePage<PageProps>();
const props = computed(() => page.props);
const errors = computed(() => (props.value.errors ?? {}) as Record<string, string>);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Адмін', href: '/admin/users' },
    { title: 'Рахунки для оплати', href: '/admin/payment-accounts' },
];

const dialogOpen = ref(false);
const isEditing = ref(false);
const selected = ref<Item | null>(null);

const form = useForm({
    name: '',
});

const openCreate = () => {
    isEditing.value = false;
    selected.value = null;
    form.reset();
    form.clearErrors();
    dialogOpen.value = true;
};

const openEdit = (item: Item) => {
    isEditing.value = true;
    selected.value = item;
    form.name = item.name;
    form.clearErrors();
    dialogOpen.value = true;
};

const submit = () => {
    if (isEditing.value && selected.value) {
        const confirmed = window.confirm('Підтвердити оновлення рахунку?');
        if (!confirmed) {
            return;
        }
        form.put(`/admin/payment-accounts/${selected.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
        return;
    }

    form.post('/admin/payment-accounts', {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
};

const remove = (item: Item) => {
    const confirmed = window.confirm('Видалити рахунок?');
    if (!confirmed) {
        return;
    }
    router.delete(`/admin/payment-accounts/${item.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Рахунки для оплати" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Рахунки для оплати</h1>
                    <p class="text-sm text-muted-foreground">Налаштування лише для адміністратора.</p>
                </div>
                <Button @click="openCreate">Створити рахунок</Button>
            </div>
            <div
                v-if="errors.delete"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
            >
                <p>{{ errors.delete }}</p>
            </div>
            <div class="overflow-hidden rounded-xl border border-sidebar-border/70 bg-white dark:border-sidebar-border dark:bg-neutral-900">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-sidebar-border/70 text-xs uppercase text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">Назва</th>
                            <th class="px-4 py-3">Дії</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in props.items" :key="item.id" class="border-b border-sidebar-border/40">
                            <td class="px-4 py-3">{{ item.name }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    <Button size="sm" variant="outline" @click="openEdit(item)">Редагувати</Button>
                                    <Button size="sm" variant="destructive" @click="remove(item)">Видалити</Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="props.items.length === 0">
                            <td class="px-4 py-6 text-center text-muted-foreground" colspan="2">Рахунки для оплати відсутні.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Редагувати рахунок' : 'Створити рахунок' }}</DialogTitle>
                </DialogHeader>
                <form class="mt-4 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-2">
                        <Label>Назва</Label>
                        <Input v-model="form.name" />
                        <span v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</span>
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
