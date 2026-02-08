<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { type AppPageProps, type NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
const { state } = useSidebar();
const page = usePage<AppPageProps>();

const storageUsage = computed(() => page.props.storageUsage);
const isStorageWarning = computed(() => (storageUsage.value?.usedPercent ?? 0) >= 80);

function formatGigabytes(bytes: number): string {
    return `${(bytes / 1024 ** 3).toFixed(1)} GB`;
}
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>

        <div v-if="state === 'expanded' && storageUsage" class="mt-4 rounded-md border px-3 py-2 text-xs bg-white/50">
            <div class="mb-2 flex items-center justify-between">
                <span class="font-medium">Сховище файлів</span>
                <span :class="isStorageWarning ? 'text-red-600' : 'text-muted-foreground'">
                    {{ storageUsage.usedPercent.toFixed(1) }}%
                </span>
            </div>

            <div class="h-2 overflow-hidden rounded-full bg-muted">
                <div
                    class="h-full rounded-full transition-all"
                    :class="isStorageWarning ? 'bg-red-500' : 'bg-sidebar-primary'"
                    :style="{ width: `${storageUsage.usedPercent}%` }"
                />
            </div>

            <p class="mt-2 text-muted-foreground">
                {{ formatGigabytes(storageUsage.freeBytes) }} вільно з
                {{ formatGigabytes(storageUsage.totalBytes) }}
            </p>
        </div>
    </SidebarGroup>
</template>
