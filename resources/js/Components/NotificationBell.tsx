import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { AppNotification } from '@/types';

const POLL_MS = 30000;

export default function NotificationBell() {
    const [open, setOpen] = useState(false);
    const [unread, setUnread] = useState(0);
    const [items, setItems] = useState<AppNotification[]>([]);
    const ref = useRef<HTMLDivElement>(null);
    // Monotonic request counter. The 30s poll and the mark actions can have
    // requests in flight at the same time; without ordering, a slow poll that
    // started *before* a mark can resolve *after* it and overwrite the UI back
    // to unread (the intermittent "sometimes it doesn't mark" bug). Only the
    // response from the newest request is allowed to update state.
    const reqSeq = useRef(0);

    const fetchNotif = async () => {
        const seq = ++reqSeq.current;
        try {
            const { data } = await window.axios.get(
                route('notifications.index'),
            );
            if (seq !== reqSeq.current) return; // superseded by a newer request
            setUnread(data.unread_count);
            setItems(data.recent);
        } catch {
            // ignore polling errors
        }
    };

    useEffect(() => {
        fetchNotif();
        const id = setInterval(fetchNotif, POLL_MS);
        return () => clearInterval(id);
    }, []);

    useEffect(() => {
        const close = (e: MouseEvent) => {
            if (ref.current && !ref.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', close);
        return () => document.removeEventListener('mousedown', close);
    }, []);

    const markAllRead = async () => {
        const now = new Date().toISOString();
        // Invalidate any in-flight poll so its (stale) response can't undo this.
        reqSeq.current++;
        // Optimistically clear the badge and highlight; resync from the
        // server afterwards so a failed request can't leave a false state.
        setUnread(0);
        setItems((prev) =>
            prev.map((n) => ({ ...n, read_at: n.read_at ?? now })),
        );
        try {
            await window.axios.post(route('notifications.read-all'));
        } finally {
            fetchNotif();
        }
    };

    const openNotif = async (n: AppNotification) => {
        if (!n.read_at) {
            const now = new Date().toISOString();
            // Invalidate any in-flight poll so its stale response can't undo this.
            reqSeq.current++;
            setItems((prev) =>
                prev.map((m) =>
                    m.id === n.id ? { ...m, read_at: now } : m,
                ),
            );
            setUnread((c) => Math.max(0, c - 1));
            try {
                await window.axios.post(route('notifications.read', n.id));
            } catch {
                // Mark failed — pull the real state back from the server.
                fetchNotif();
            }
        }
        setOpen(false);
        if (n.data.ticket_id) {
            router.visit(route('tickets.show', n.data.ticket_id));
        }
    };

    const formatTime = (iso: string) => {
        const d = new Date(iso);
        const diffMs = Date.now() - d.getTime();
        const mins = Math.floor(diffMs / 60000);
        if (mins < 1) return 'baru saja';
        if (mins < 60) return `${mins} menit lalu`;
        const hours = Math.floor(mins / 60);
        if (hours < 24) return `${hours} jam lalu`;
        const days = Math.floor(hours / 24);
        if (days < 7) return `${days} hari lalu`;
        return d.toLocaleDateString('id-ID');
    };

    return (
        <div ref={ref} className="relative">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="relative rounded-md p-2 text-gray-500 hover:bg-gray-100"
                aria-label="Notifikasi"
            >
                <svg
                    className="h-6 w-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="1.5"
                        d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                    />
                </svg>
                {unread > 0 && (
                    <span className="absolute right-1 top-1 flex h-4 min-w-[16px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                        {unread > 99 ? '99+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-40 mt-2 w-80 overflow-hidden rounded-md border border-gray-200 bg-white shadow-lg">
                    <div className="flex items-center justify-between border-b border-gray-200 px-4 py-2">
                        <span className="text-sm font-semibold text-gray-700">
                            Notifikasi
                        </span>
                        {unread > 0 && (
                            <button
                                type="button"
                                onClick={markAllRead}
                                className="text-xs text-brand-600 hover:underline"
                            >
                                Tandai semua dibaca
                            </button>
                        )}
                    </div>
                    <ul className="max-h-96 divide-y divide-gray-100 overflow-y-auto">
                        {items.length === 0 ? (
                            <li className="px-4 py-6 text-center text-sm text-gray-500">
                                Belum ada notifikasi.
                            </li>
                        ) : (
                            items.map((n) => (
                                <li key={n.id}>
                                    <button
                                        type="button"
                                        onClick={() => openNotif(n)}
                                        className={`flex w-full flex-col items-start gap-1 px-4 py-3 text-left text-sm hover:bg-gray-50 ${!n.read_at ? 'bg-brand-50/40' : ''}`}
                                    >
                                        <span className="font-medium text-gray-900">
                                            {n.data.message}
                                        </span>
                                        <span className="text-xs text-gray-500">
                                            {formatTime(n.created_at)}
                                        </span>
                                    </button>
                                </li>
                            ))
                        )}
                    </ul>
                </div>
            )}
        </div>
    );
}
