export type ClassCard = {
    id: number;
    name: string;
    date: string;
    weekday: string;
    starts_at: string;
    ends_at: string;
    teacher: string;
    max_students: number;
    available_spots: number;
    is_full: boolean;
    status: string;
    is_checked_in: boolean;
    can_check_in: boolean;
    /** Why the check-in button is off, straight from the business rules. */
    denial_reason: string | null;
    can_cancel: boolean;
    notes: string | null;
};

export type PlanSummary = {
    has_plan: boolean;
    plan_name: string | null;
    allowance: string | null;
    started_at: string | null;
    /** Null means the plan has no weekly limit. */
    available_check_ins: number | null;
    is_unlimited: boolean;
    payment_status: string;
    payment_is_blocking: boolean;
    next_due_date: string | null;
    amount: string | null;
    /** True while the trial is offered but has not been claimed by a check-in. */
    is_trial_offer: boolean;
};

export type RecentAttendance = {
    attended: number;
    weeks: number;
};

export type HistoryRow = {
    id: number;
    name: string;
    date: string;
    starts_at: string;
    status: string;
    attended: boolean;
};

export type PaymentRow = {
    id: number;
    amount: string;
    due_date: string;
    paid_at: string | null;
    status: string;
    is_overdue: boolean;
    is_settled: boolean;
    has_receipt: boolean;
    receipt_uploaded_at: string | null;
};
