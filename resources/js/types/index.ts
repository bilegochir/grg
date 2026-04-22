import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
    agency: Agency | null;
}

export interface Agency {
    id: number;
    name: string;
    slug: string;
    email: string | null;
    phone: string | null;
    website: string | null;
    address: string | null;
}

export interface Flash {
    success?: string | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    auth: Auth;
    flash: Flash;
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
