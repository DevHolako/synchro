export interface NotificationItemData {
    id: string;
    data: {
        title?: string;
        message?: string;
        type?: string;
        link?: string;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
}
