export type UserRole = 'admin' | 'staff' | 'it_support';

export interface User {
    id: number;
    user_id: string;
    name: string;
    email: string | null;
    role: UserRole;
    department: string | null;
    proyek: string | null;
    is_active: boolean;
}

export interface Category {
    id: number;
    name: string;
    description?: string | null;
}

export type TicketStatus = 'new' | 'in_progress' | 'resolved';
export type TicketPriority = 'low' | 'medium' | 'high' | 'urgent';

export interface Ticket {
    id: number;
    ticket_code: string;
    title: string;
    description: string;
    status: TicketStatus;
    priority: TicketPriority;
    requestor_id: number;
    assignee_id: number | null;
    category_id: number;
    attachment_path: string | null;
    resolved_at: string | null;
    closed_at: string | null;
    created_at: string;
    updated_at: string;
    requestor?: Pick<User, 'id' | 'name' | 'user_id' | 'role'>;
    assignee?: Pick<User, 'id' | 'name' | 'user_id' | 'role'> | null;
    category?: Pick<Category, 'id' | 'name'>;
    comments?: TicketComment[];
    activities?: TicketActivity[];
}

export interface TicketComment {
    id: number;
    ticket_id: number;
    user_id: number;
    body: string;
    attachment_path: string | null;
    created_at: string;
    user: Pick<User, 'id' | 'name' | 'role'>;
}

export type ActivityAction =
    | 'created'
    | 'assigned'
    | 'reassigned'
    | 'status_changed';

export interface TicketActivity {
    id: number;
    ticket_id: number;
    user_id: number | null;
    action: ActivityAction;
    meta: Record<string, unknown> | null;
    created_at: string;
    user: Pick<User, 'id' | 'name' | 'role'> | null;
}

export type ItemStatus = 'available' | 'borrowed';
export type ItemCondition = 'baru' | 'baik' | 'rusak_ringan' | 'rusak_berat';

export interface Item {
    id: number;
    serial_number: string;
    item_name: string;
    brand_name: string;
    mac_address: string | null;
    type: string;
    condition: ItemCondition;
    description: string | null;
    status: ItemStatus;
    item_image: string | null;
    images?: ItemImage[];
    created_at: string;
    updated_at: string;
}

export interface ItemImage {
    id: number;
    item_id: number;
    image_path: string;
    created_at: string;
    updated_at: string;
}

export type BorrowStatus = 'borrowed' | 'returned';

export interface BorrowRecord {
    id: number;
    item_id: number;
    borrower_id: number | null;
    item_name: string;
    serial_number: string;
    borrower_name: string;
    borrow_date: string;
    purpose: string;
    borrow_image: string | null;
    status: BorrowStatus;
    return_date: string | null;
    return_condition: ItemCondition | null;
    notes: string | null;
    return_image: string | null;
    created_at: string;
    updated_at: string;
}

export interface ProcurementRequest {
    id: number;
    request_number: string;
    employee_name: string;
    requested_item: string;
    request_date: string;
    notes: string | null;
    form_file: string | null;
    created_at: string;
    updated_at: string;
    items?: Pick<
        Item,
        'id' | 'serial_number' | 'item_name' | 'brand_name' | 'type' | 'status'
    >[];
}

export type ProcurementRequestOption = Pick<
    ProcurementRequest,
    'id' | 'request_number' | 'requested_item' | 'employee_name'
>;

export interface AppNotification {
    id: string;
    data: {
        type: string;
        ticket_id?: number;
        ticket_code?: string;
        ticket_title?: string;
        actor_name?: string;
        old_status_label?: string;
        new_status_label?: string;
        /** Absent on status-change notifications; compose from the labels instead. */
        message?: string;
    };
    read_at: string | null;
    created_at: string;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
    flash: {
        success?: string | null;
        error?: string | null;
    };
};
