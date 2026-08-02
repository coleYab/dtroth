export type EnvironmentName = 'sandbox' | 'production';

export type AppEnvironment = {
    id: number;
    name: EnvironmentName;
    is_enabled: boolean;
    is_live: boolean;
    redirect_uris: string[];
    allowed_origins: string[];
};

export type DeveloperApp = {
    id: number;
    name: string;
    image: string | null;
    status: 'active' | 'disabled';
    redirect_uris: string[];
    allowed_origins: string[];
    environments: AppEnvironment[];
    created_at: string | null;
};

export type ActivityEventType = 'key_rotation' | 'failed_verification' | 'scope_change';

export type ActivityEvent = {
    id: number;
    type: ActivityEventType;
    description: string;
    created_at: string | null;
    app: { id: number; name: string } | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    meta: {
        current_page: number;
        from: number | null;
        to: number | null;
        last_page: number;
        per_page: number;
        total: number;
        links: PaginationLink[];
    };
};

export type DashboardStats = {
    activeApps: number;
    apiCalls30d: number;
    verifications30d: number;
    activeAlerts: number;
};

export type ApiKey = {
    id: number;
    name: string;
    public_key: string;
    secret_preview: string;
    environment: 'sandbox' | 'production' | 'shared';
    expires_at: string | null;
    last_used_at: string | null;
    created_at: string | null;
    is_revoked: boolean;
    revoked_at: string | null;
};

export type RevealedKey = {
    id: number;
    name: string;
    environment: 'sandbox' | 'production';
    public_key: string;
    secret_key: string;
};
