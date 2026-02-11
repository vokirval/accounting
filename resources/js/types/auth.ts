export type User = {
    id: number;
    name: string;
    email: string;
    role: 'user' | 'accountant' | 'admin';
    blocked_at: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
    permissions?: {
        manage_expense_types?: boolean;
    };
};

export type TwoFactorConfigContent = {
    title: string;
    description: string;
    buttonText: string;
};
