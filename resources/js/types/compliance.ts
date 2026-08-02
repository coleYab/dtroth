import type { AppEnvironment } from './developer';

export type SectionApp = {
    id: number;
    name: string;
    image: string | null;
    status: 'active' | 'disabled';
    environments: AppEnvironment[];
};

export type AgeQueryType = 'equal_above' | 'equal_below';

export type AgeQueryPreset = {
    id: number;
    label: string;
    type: AgeQueryType;
    value: number;
    description: string | null;
    is_enabled: boolean;
};

export type VerificationSettings = {
    consent_text: string;
    re_verify_months: number;
    consent_updated_at: string | null;
};

export type VerificationFlow = {
    logo_url: string | null;
    brand_color: string;
    success_redirect_url: string | null;
    failure_redirect_url: string | null;
    callback_url: string | null;
    updated_at: string | null;
};

export type WebhookEvent = {
    name: string;
    label: string;
};

export type Webhook = {
    id: number;
    url: string;
    description: string | null;
    secret_preview: string;
    events: WebhookEvent[];
    last_triggered_at: string | null;
    is_failing: boolean;
    recent_failure_count: number;
    created_at: string | null;
};

export type RevealedSecret = {
    id: number;
    url: string;
    secret: string;
};

export type WebhookDelivery = {
    id: number;
    event: string;
    payload: Record<string, unknown>;
    response_status: number | null;
    response_body: string | null;
    attempts: number;
    status: 'pending' | 'retrying' | 'success' | 'failed';
    delivered_at: string | null;
    created_at: string | null;
};

export type ConsentStatus = 'active' | 'revoked' | 'expired';

export type ConsentEvent = {
    type: 'granted' | 'revoked' | 'expired';
    created_at: string | null;
};

export type Consent = {
    id: number;
    subject_identifier: string;
    query_type: AgeQueryType | null;
    query_value: number | null;
    age_query: string | null;
    result: boolean | null;
    status: ConsentStatus;
    expires_at: string | null;
    revoked_at: string | null;
    created_at: string | null;
    events: ConsentEvent[];
};

export type AuditLogEntry = {
    id: number;
    action: string;
    description: string;
    app_id: number | null;
    app_name: string | null;
    actor: string | null;
    created_at: string | null;
};

export type AuditLogAction = {
    value: string;
    label: string;
};

export type AppFilter = {
    id: number | null;
    name: string;
};

export type ApiCallLog = {
    id: number;
    method: string;
    path: string;
    query_type: AgeQueryType | null;
    query_value: number | null;
    age_query: string | null;
    result: boolean | null;
    status_code: number;
    response_time_ms: number | null;
    request_payload: Record<string, unknown> | null;
    response_payload: Record<string, unknown> | null;
    created_at: string | null;
};

export type UsageMetrics = {
    total_calls: number;
    total_verifications: number;
    success_rate: number;
    most_used_query: string | null;
    daily: Array<{ date: string; calls: number; verifications: number }>;
    by_query: Array<{ query: string; calls: number }>;
};

export type BillingPlan = {
    slug: string;
    name: string;
    price_etb: number;
    description: string;
    is_current: boolean;
    price_formatted: string;
};

export type SubscriptionState = {
    status: string;
    channel: string | null;
    renews_at: string | null;
    trial_ends_at: string;
    has_payment_method: boolean;
    payment_method: string | null;
};

export type Invoice = {
    id: number;
    number: string;
    amount: string;
    currency: string;
    status: 'pending' | 'paid' | 'failed';
    channel: string | null;
    paid_at: string | null;
    created_at: string | null;
};

export type BillingUsageCycle = {
    total_calls: number;
    total_verifications: number;
    estimated_cost_etb: number;
    cost_formatted: string;
    plan_calls: number;
    usage_percent: number;
    days_remaining: number;
    period_start: string;
    period_end: string;
};

export type BillingUsageApp = {
    app_id: number;
    app_name: string;
    calls: number;
    verifications: number;
    estimated_cost_etb: number;
};
