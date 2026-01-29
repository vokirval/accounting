<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CreditCard, FileText, Shapes, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
const role = computed(() => page.props.auth.user.role);
const homeHref = '/payment-requests';

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Платіжні заявки',
            href: homeHref,
            icon: FileText,
        },
    ];

    if (role.value === 'admin') {
        items.push(
            {
                title: 'Типи витрат',
                href: '/admin/expense-types',
                icon: Shapes,
            },
            {
                title: 'Рахунки для оплати',
                href: '/admin/payment-accounts',
                icon: CreditCard,
            },
            {
                title: 'Користувачі',
                href: '/admin/users',
                icon: Users,
            },
        );
    }

    return items;
});

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="homeHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
