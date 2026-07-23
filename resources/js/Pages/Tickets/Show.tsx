import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import StatusBadge from '@/Components/StatusBadge';
import PriorityBadge from '@/Components/PriorityBadge';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, router, useForm } from '@inertiajs/react';
import {
    ActivityAction,
    Ticket,
    TicketActivity,
    TicketComment,
    User,
} from '@/types';
import { FormEventHandler } from 'react';

interface Props {
    ticket: Ticket & {
        requestor: User;
        assignee: User | null;
        comments: TicketComment[];
        activities: TicketActivity[];
    };
    can: {
        assign: boolean;
        update_status: boolean;
        comment: boolean;
        delete: boolean;
        view_activities: boolean;
    };
    it_support_users: Pick<User, 'id' | 'name' | 'user_id'>[];
}

const roleLabel: Record<string, { label: string; cls: string }> = {
    admin: { label: 'Admin', cls: 'bg-purple-100 text-purple-700' },
    it_support: {
        label: 'Dukungan TI',
        cls: 'bg-blue-100 text-blue-700',
    },
    staff: { label: 'Staf', cls: 'bg-gray-100 text-gray-700' },
};

const statusLabel: Record<Ticket['status'], string> = {
    new: 'Baru',
    in_progress: 'Dikerjakan',
    resolved: 'Selesai',
};

function activityMessage(activity: TicketActivity): string {
    const actor = activity.user?.name ?? 'Sistem';
    const meta = activity.meta ?? {};
    const statusMap: Record<string, string> = statusLabel as Record<
        string,
        string
    >;
    switch (activity.action as ActivityAction) {
        case 'created':
            return `${actor} membuat tiket ini.`;
        case 'assigned':
            return `${actor} menugaskan tiket ke ${meta.assignee_name ?? '—'}.`;
        case 'reassigned':
            return `${actor} memindahkan tugas ke ${meta.assignee_name ?? '—'}.`;
        case 'status_changed':
            return `${actor} mengubah status dari ${statusMap[String(meta.old)] ?? meta.old} menjadi ${statusMap[String(meta.new)] ?? meta.new}.`;
        default:
            return `${actor}: ${activity.action}`;
    }
}

function formatDateTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString('id-ID').replace(/(\d)\.(\d)/g, '$1:$2');
}

function attachmentUrl(path: string): string {
    return `/storage/${path}`;
}

function AttachmentLink({ path }: { path: string }) {
    const filename = path.split('/').pop() ?? path;
    const isImage = /\.(jpe?g|png)$/i.test(path);
    return (
        <a
            href={attachmentUrl(path)}
            target="_blank"
            rel="noopener noreferrer"
            className="mt-2 inline-flex items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-700 hover:bg-gray-100"
        >
            <svg
                className="h-4 w-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
                />
            </svg>
            <span>{isImage ? 'Lihat gambar' : 'Unduh lampiran'}</span>
            <span className="font-mono text-[10px] text-gray-500">
                {filename}
            </span>
        </a>
    );
}

export default function Show({ ticket, can, it_support_users }: Props) {
    const comment = useForm<{ body: string; attachment: File | null }>({
        body: '',
        attachment: null,
    });
    const assignForm = useForm({ assignee_id: ticket.assignee_id ?? '' });
    const statusForm = useForm({ status: ticket.status });

    const submitComment: FormEventHandler = (e) => {
        e.preventDefault();
        comment.post(route('tickets.comments.store', ticket.id), {
            onSuccess: () => comment.reset('body', 'attachment'),
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const submitAssign: FormEventHandler = (e) => {
        e.preventDefault();
        assignForm.patch(route('tickets.assign', ticket.id), {
            preserveScroll: true,
        });
    };

    const changeStatus = (status: string) => {
        statusForm.setData('status', status as Ticket['status']);
        router.patch(
            route('tickets.status', ticket.id),
            { status },
            { preserveScroll: true },
        );
    };

    const comments = ticket.comments ?? [];
    const activities = [...(ticket.activities ?? [])].sort((a, b) =>
        a.created_at.localeCompare(b.created_at),
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-start justify-between gap-4">
                    
                    {can.delete && (
                        <button
                            type="button"
                            onClick={() => {
                                if (window.confirm(`Hapus tiket ${ticket.ticket_code}? Tindakan ini tidak dapat dibatalkan.`)) {
                                    router.delete(route('tickets.destroy', ticket.id));
                                }
                            }}
                            className="shrink-0 rounded-md border border-red-300 bg-white px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50"
                        >
                            Hapus Tiket
                        </button>
                    )}
                </div>
            }
        >
            <Head title={ticket.ticket_code} />
            <div className='mb-5'>
                <h1 className="text-xl font-semibold text-gray-900">{ticket.title}
                </h1>
                <p className="font-mono text-xs text-gray-500">
                    {ticket.ticket_code}
                </p>
            </div>
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <section className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        
                        <h2 className="mb-2 text-sm font-semibold text-gray-700">
                            Deskripsi
                        </h2>
                        <p className="whitespace-pre-wrap text-sm text-gray-800">
                            {ticket.description}
                        </p>
                        {ticket.attachment_path && (
                            <AttachmentLink path={ticket.attachment_path} />
                        )}
                    </section>

                    <section className="rounded-lg border border-gray-200 bg-white shadow-sm">
                        <div className="border-b border-gray-200 px-6 py-3">
                            <h2 className="text-sm font-semibold text-gray-700">
                                Percakapan
                            </h2>
                        </div>
                        <ul className="divide-y divide-gray-100 px-6">
                            {comments.length === 0 ? (
                                <li className="py-6 text-center text-sm text-gray-500">
                                    Belum ada balasan.
                                </li>
                            ) : (
                                comments.map((c) => (
                                    <CommentRow key={c.id} c={c} />
                                ))
                            )}
                        </ul>

                        {can.comment && (
                            <form
                                onSubmit={submitComment}
                                className="border-t border-gray-100 px-6 py-4"
                            >
                                <textarea
                                    rows={3}
                                    value={comment.data.body}
                                    onChange={(e) =>
                                        comment.setData('body', e.target.value)
                                    }
                                    placeholder="Tulis balasan..."
                                    className="block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                                <InputError
                                    className="mt-2"
                                    message={comment.errors.body}
                                />
                                <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,application/pdf"
                                        onChange={(e) =>
                                            comment.setData(
                                                'attachment',
                                                e.target.files?.[0] ?? null,
                                            )
                                        }
                                        className="text-xs text-gray-700 file:mr-2 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200"
                                    />
                                    <PrimaryButton
                                        disabled={comment.processing}
                                    >
                                        Kirim Balasan
                                    </PrimaryButton>
                                </div>
                                <InputError
                                    className="mt-2"
                                    message={comment.errors.attachment}
                                />
                            </form>
                        )}
                    </section>
                </div>

                <aside className="space-y-6">
                    <section className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                        <h2 className="mb-4 text-sm font-semibold text-gray-700">
                            Detail
                        </h2>
                        <dl className="space-y-3 text-sm">
                            <Row
                                label="Pelapor"
                                value={`${ticket.requestor.name}`}
                            />
                            <Row
                                label="Penangan"
                                value={
                                    ticket.assignee
                                        ? `${ticket.assignee.name}`
                                        : 'Belum ditugaskan'
                                }
                            />
                            <Row
                                label="Kategori"
                                value={ticket.category?.name ?? '-'}
                            />
                            <Row
                                label="Prioritas"
                                value={
                                    <PriorityBadge priority={ticket.priority} />
                                }
                            />
                            <Row
                                label="Status"
                                value={<StatusBadge status={ticket.status} />}
                            />
                            {ticket.resolved_at && (
                                <Row
                                    label="Selesai"
                                    value={formatDateTime(ticket.resolved_at)}
                                />
                            )}
                        </dl>
                    </section>

                    {can.view_activities && (
                        <section className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="mb-3 text-sm font-semibold text-gray-700">
                                Riwayat
                            </h2>
                            {activities.length === 0 ? (
                                <p className="text-xs text-gray-500">
                                    Belum ada aktivitas.
                                </p>
                            ) : (
                                <ol className="space-y-3 border-l border-gray-200 pl-4">
                                    {activities.map((a) => (
                                        <li key={a.id} className="relative">
                                            <span className="absolute -left-[21px] top-1.5 inline-flex h-2 w-2 rounded-full bg-gray-300 ring-2 ring-white" />
                                            <p className="text-xs leading-snug text-gray-700">
                                                {activityMessage(a)}
                                            </p>
                                            <p className="mt-0.5 text-[10px] text-gray-400">
                                                {formatDateTime(a.created_at)}
                                            </p>
                                        </li>
                                    ))}
                                </ol>
                            )}
                        </section>
                    )}

                    {can.assign && (
                        <section className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="mb-3 text-sm font-semibold text-gray-700">
                                Tugaskan Dukungan TI
                            </h2>
                            <form
                                onSubmit={submitAssign}
                                className="space-y-3"
                            >
                                <select
                                    value={assignForm.data.assignee_id}
                                    onChange={(e) =>
                                        assignForm.setData(
                                            'assignee_id',
                                            e.target.value,
                                        )
                                    }
                                    className="block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option value=""> — Pilih — </option>
                                    {it_support_users.map((u) => (
                                        <option key={u.id} value={u.id}>
                                            {u.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError
                                    message={assignForm.errors.assignee_id}
                                />
                                <PrimaryButton
                                    disabled={
                                        assignForm.processing ||
                                        !assignForm.data.assignee_id
                                    }
                                >
                                    Tugaskan
                                </PrimaryButton>
                            </form>
                        </section>
                    )}

                    {can.update_status && (
                        <section className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                            <h2 className="mb-3 text-sm font-semibold text-gray-700">
                                Ubah Status
                            </h2>
                            <div className="flex flex-col gap-2">
                                {(
                                    [
                                        'new',
                                        'in_progress',
                                        'resolved',
                                    ] as const
                                ).map((s) => (
                                    <button
                                        key={s}
                                        type="button"
                                        disabled={ticket.status === s}
                                        onClick={() => changeStatus(s)}
                                        className={`rounded-md border px-3 py-1.5 text-sm font-medium ${ticket.status === s ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}`}
                                    >
                                        {statusLabel[s]}
                                    </button>
                                ))}
                            </div>
                        </section>
                    )}
                </aside>
            </div>
        </AuthenticatedLayout>
    );
}

function CommentRow({ c }: { c: TicketComment }) {
    const rl = roleLabel[c.user.role] ?? roleLabel.staff;
    return (
        <li className="py-4">
            <div className="flex items-center gap-2">
                <span className="text-sm font-medium text-gray-900">
                    {c.user.name}
                </span>
                <span
                    className={`rounded px-1.5 py-0.5 text-[10px] font-medium uppercase ${rl.cls}`}
                >
                    {rl.label}
                </span>
                <span className="text-xs text-gray-400">
                    {formatDateTime(c.created_at)}
                </span>
            </div>
            <p className="mt-1 whitespace-pre-wrap text-sm text-gray-800">
                {c.body}
            </p>
            {c.attachment_path && <AttachmentLink path={c.attachment_path} />}
        </li>
    );
}

function Row({
    label,
    value,
}: {
    label: string;
    value: React.ReactNode;
}) {
    return (
        <div className="flex items-start justify-between gap-3">
            <dt className="text-xs uppercase text-gray-500">{label}</dt>
            <dd className="text-right text-sm text-gray-800">{value}</dd>
        </div>
    );
}
