export type NotificationItem = {
    id: string;
    type: string | null;
    title: string;
    body: string;
    url: string;
    read_at: string | null;
    created_at: string | null;
    created_at_diff: string | null;
};

export type NotificationsSummary = {
    unread_count: number;
    items: NotificationItem[];
};

export type PushConfig = {
    enabled: boolean;
    publicKey: string | null;
};
