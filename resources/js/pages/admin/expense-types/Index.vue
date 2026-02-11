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

type Category = { id: number; name: string };
type Item = { id: number; name: string; categories: Category[] };

type PageProps = AppPageProps<{
    items: Item[];
    permissions: {
        manage_types: boolean;
    };
}>;

const page = usePage<PageProps>();
const props = computed(() => page.props);
const errors = computed(() => (props.value.errors ?? {}) as Record<string, string>);
const canManageTypes = computed(() => props.value.permissions.manage_types === true);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Платіжні заявки', href: '/payment-requests' },
    { title: 'Типи витрат', href: '/admin/expense-types' },
];

const dialogOpen = ref(false);
const isEditing = ref(false);
const selected = ref<Item | null>(null);

const categoryDialogOpen = ref(false);
const isCategoryEditing = ref(false);
const selectedType = ref<Item | null>(null);
const selectedCategory = ref<Category | null>(null);

const form = useForm({
    name: '',
});

const categoryForm = useForm({
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
        const confirmed = window.confirm('Підтвердити оновлення типу витрат?');
        if (!confirmed) {
            return;
        }
        form.put(`/admin/expense-types/${selected.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                dialogOpen.value = false;
            },
        });
        return;
    }

    form.post('/admin/expense-types', {
        preserveScroll: true,
        onSuccess: () => {
            dialogOpen.value = false;
            form.reset();
        },
    });
};

const remove = (item: Item) => {
    const confirmed = window.confirm('Видалити тип витрат?');
    if (!confirmed) {
        return;
    }
    router.delete(`/admin/expense-types/${item.id}`, { preserveScroll: true });
};

const openCreateCategory = (type: Item) => {
    isCategoryEditing.value = false;
    selectedType.value = type;
    selectedCategory.value = null;
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryDialogOpen.value = true;
};

const openEditCategory = (type: Item, category: Category) => {
    isCategoryEditing.value = true;
    selectedType.value = type;
    selectedCategory.value = category;
    categoryForm.name = category.name;
    categoryForm.clearErrors();
    categoryDialogOpen.value = true;
};

const submitCategory = () => {
    if (!selectedType.value) {
        return;
    }

    if (isCategoryEditing.value && selectedCategory.value) {
        const confirmed = window.confirm('Підтвердити оновлення категорії?');
        if (!confirmed) {
            return;
        }
        categoryForm.put(`/admin/expense-types/${selectedType.value.id}/categories/${selectedCategory.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                categoryDialogOpen.value = false;
            },
        });
        return;
    }

    categoryForm.post(`/admin/expense-types/${selectedType.value.id}/categories`, {
        preserveScroll: true,
        onSuccess: () => {
            categoryDialogOpen.value = false;
            categoryForm.reset();
        },
    });
};

const removeCategory = (type: Item, category: Category) => {
    const confirmed = window.confirm('Видалити категорію?');
    if (!confirmed) {
        return;
    }
    router.delete(`/admin/expense-types/${type.id}/categories/${category.id}`, { preserveScroll: true });
};
</script>

<template>
    <Head title="Типи витрат" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-xl font-semibold">Типи витрат</h1>
                    <p class="text-sm text-muted-foreground">Налаштування типів та категорій витрат.</p>
                </div>
                <Button v-if="canManageTypes" size="sm" @click="openCreate">Створити тип</Button>
            </div>
            <div
                v-if="errors.delete || errors.delete_category"
                class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-200"
            >
                <p v-if="errors.delete">{{ errors.delete }}</p>
                <p v-if="errors.delete_category">{{ errors.delete_category }}</p>
            </div>
            <div class="grid gap-4">
                <div
                    v-for="item in props.items"
                    :key="item.id"
                    class="rounded-xl border border-sidebar-border/70 bg-white p-4 dark:border-sidebar-border dark:bg-neutral-900"
                >
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <h2 class="text-base font-semibold">{{ item.name }}</h2>
                            <Badge variant="outline" class="text-[11px]">Категорії: {{ item.categories.length }}</Badge>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <Button v-if="canManageTypes" size="sm" variant="outline" @click="openEdit(item)">Редагувати</Button>
                            <Button v-if="canManageTypes" size="sm" variant="destructive" @click="remove(item)">Видалити</Button>
                        </div>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <Button size="sm" variant="secondary" class="h-7 px-2 text-[11px]" @click="openCreateCategory(item)">
                            Додати категорію
                        </Button>
                        <div v-if="item.categories.length === 0" class="text-xs text-muted-foreground">
                            Категорії відсутні.
                        </div>
                        <div v-else class="flex flex-wrap gap-2">
                            <div
                                v-for="category in item.categories"
                                :key="category.id"
                                class="flex items-center gap-2 rounded-md border border-input px-2 py-1 text-[11px]"
                            >
                                <span>{{ category.name }}</span>
                                <button
                                    type="button"
                                    class="text-muted-foreground hover:text-foreground"
                                    @click="openEditCategory(item, category)"
                                >
                                    Ред.
                                </button>
                                <button
                                    type="button"
                                    class="text-red-600 hover:text-red-700"
                                    @click="removeCategory(item, category)"
                                >
                                    Вид.
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="props.items.length === 0"
                    class="rounded-xl border border-sidebar-border/70 bg-white p-6 text-center text-sm text-muted-foreground dark:border-sidebar-border dark:bg-neutral-900"
                >
                    Типи витрат відсутні.
                </div>
            </div>
        </div>

        <Dialog :open="dialogOpen" @update:open="dialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isEditing ? 'Редагувати тип' : 'Створити тип' }}</DialogTitle>
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

        <Dialog :open="categoryDialogOpen" @update:open="categoryDialogOpen = $event">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ isCategoryEditing ? 'Редагувати категорію' : 'Створити категорію' }}</DialogTitle>
                </DialogHeader>
                <form class="mt-4 grid gap-4" @submit.prevent="submitCategory">
                    <div class="grid gap-2">
                        <Label>Назва</Label>
                        <Input v-model="categoryForm.name" />
                        <span v-if="categoryForm.errors.name" class="text-sm text-red-600">{{ categoryForm.errors.name }}</span>
                    </div>
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="ghost" @click="categoryDialogOpen = false">Скасувати</Button>
                        <Button type="submit" :disabled="categoryForm.processing">Зберегти</Button>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
