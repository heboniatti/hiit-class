export type UserRole = 'admin' | 'teacher' | 'student';

export type UserStatus = 'active' | 'inactive';

export type User = {
    id: number;
    name: string;
    /** The login identifier, stored as digits only. */
    phone: string;
    role: UserRole;
    status: UserStatus;
    /** Optional: students sign in with a phone and a PIN. */
    email: string | null;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

/* @chisel-passkeys */
export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};
/* @end-chisel-passkeys */
