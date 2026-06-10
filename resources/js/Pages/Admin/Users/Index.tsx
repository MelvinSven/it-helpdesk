import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Paginated, PageProps, User } from '@/types';
import { useRef, useState } from 'react';

interface Props {
    users: Paginated<User>;
    filters: { role?: string; is_active?: string; search?: string };
}

const roleLabel: Record<string, string> = {
    admin: 'Admin',
    it_support: 'Dukungan TI',
    staff: 'Staf',
};

export default function Index({ users, filters }: Props) {
    const { flash } = usePage<PageProps>().props;

    const [form, setForm] = useState({
        search: filters.search ?? '',
        role: filters.role ?? '',
        is_active: filters.is_active ?? '',
    });

    const [showImport, setShowImport] = useState(false);
    const fileRef = useRef<HTMLInputElement>(null);

    const {
        data: importData,
        setData: setImportData,
        post: postImport,
        processing: importing,
        errors: importErrors,
        reset: resetImport,
    } = useForm<{ file: File | null }>({ file: null });

    const apply = (next = form) => {
        router.get(route('admin.users.index'), next, {
            preserveState: true,
            replace: true,
        });
    };

    const deactivate = (user: User) => {
        if (
            confirm(
                `Nonaktifkan ${user.name}? Mereka tidak akan dapat masuk lagi.`,
            )
        ) {
            router.delete(route('admin.users.destroy', user.id), {
                preserveScroll: true,
            });
        }
    };

    const activate = (user: User) => {
        router.patch(
            route('admin.users.activate', user.id),
            {},
            { preserveScroll: true },
        );
    };

    const submitImport = (e: React.FormEvent) => {
        e.preventDefault();
        postImport(route('admin.users.import'), {
            forceFormData: true,
            onSuccess: () => {
                setShowImport(false);
                resetImport();
                if (fileRef.current) fileRef.current.value = '';
            },
        });
    };

    const closeImport = () => {
        setShowImport(false);
        resetImport();
        if (fileRef.current) fileRef.current.value = '';
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Manajemen Pengguna
                </h1>
            }
        >
            <Head title="Pengguna" />

            {flash?.success && (
                <div className="mb-4 rounded-md bg-green-50 px-4 py-3 text-sm text-green-800 border border-green-200">
                    {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 border border-red-200">
                    {flash.error}
                </div>
            )}

            <div className="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div className="flex flex-wrap items-center gap-2">
                    <input
                        type="text"
                        value={form.search}
                        onChange={(e) =>
                            setForm({ ...form, search: e.target.value })
                        }
                        onKeyDown={(e) => e.key === 'Enter' && apply()}
                        placeholder="Cari nama, ID pengguna, email..."
                        className="rounded-md border-gray-300 text-sm"
                    />
                    <select
                        value={form.role}
                        onChange={(e) => {
                            const next = { ...form, role: e.target.value };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Peran</option>
                        <option value="admin">Admin</option>
                        <option value="it_support">Dukungan TI</option>
                        <option value="staff">Staf</option>
                    </select>
                    <select
                        value={form.is_active}
                        onChange={(e) => {
                            const next = {
                                ...form,
                                is_active: e.target.value,
                            };
                            setForm(next);
                            apply(next);
                        }}
                        className="rounded-md border-gray-300 text-sm"
                    >
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Tidak Aktif</option>
                    </select>
                </div>
                <div className="flex items-center gap-2">
                    <button
                        type="button"
                        onClick={() => setShowImport(true)}
                        className="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Impor Excel
                    </button>
                    <Link
                        href={route('admin.users.create')}
                        className="rounded-md bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700"
                    >
                        Pengguna Baru
                    </Link>
                </div>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 whitespace-nowrap text-sm">
                    <thead className="bg-gray-50">
                        <tr className="text-left text-xs uppercase text-gray-500">
                            <th className="px-4 py-3">ID Pengguna</th>
                            <th className="px-4 py-3">Nama</th>
                            <th className="px-4 py-3">Email</th>
                            <th className="px-4 py-3">Peran</th>
                            <th className="px-4 py-3">Departemen</th>
                            <th className="px-4 py-3">Status</th>
                            <th className="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {users.data.length === 0 ? (
                            <tr>
                                <td
                                    colSpan={7}
                                    className="px-4 py-10 text-center text-sm text-gray-500"
                                >
                                    Pengguna tidak ditemukan.
                                </td>
                            </tr>
                        ) : (
                            users.data.map((u) => (
                                <tr key={u.id} className="hover:bg-gray-50">
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {u.user_id}
                                    </td>
                                    <td className="px-4 py-3">{u.name}</td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {u.email ?? '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {roleLabel[u.role]}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {u.department ?? '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {u.is_active ? (
                                            <span className="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-700">
                                                Aktif
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                Tidak Aktif
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Link
                                            href={route(
                                                'admin.users.edit',
                                                u.id,
                                            )}
                                            className="mr-3 text-brand-600 hover:underline"
                                        >
                                            Ubah
                                        </Link>
                                        {u.is_active ? (
                                            <button
                                                type="button"
                                                onClick={() => deactivate(u)}
                                                className="text-red-600 hover:underline"
                                            >
                                                Nonaktifkan
                                            </button>
                                        ) : (
                                            <button
                                                type="button"
                                                onClick={() => activate(u)}
                                                className="text-green-600 hover:underline"
                                            >
                                                Aktifkan
                                            </button>
                                        )}
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {users.last_page > 1 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {users.links.map((link, i) => (
                        <button
                            key={i}
                            disabled={!link.url}
                            onClick={() => link.url && router.visit(link.url)}
                            className={`rounded-md border px-3 py-1 text-sm ${
                                link.active
                                    ? 'border-brand-600 bg-brand-600 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                            } ${!link.url ? 'opacity-40' : ''}`}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ))}
                </div>
            )}

            {/* Import Modal */}
            {showImport && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
                    <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                        <h2 className="mb-1 text-lg font-semibold text-gray-900">
                            Impor Pengguna dari Excel
                        </h2>
                        <p className="mb-4 text-sm text-gray-500">
                            File harus berformat <strong>.xlsx</strong> atau <strong>.xls</strong> dengan header:{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">ID Pengguna</code>,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">Nama</code>,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">Email</code>,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">Peran</code>,{' '}
                            <code className="rounded bg-gray-100 px-1 text-xs">Departemen</code>.
                            Kolom Email dan Departemen bersifat opsional.
                            Nilai Peran: <em>Admin</em>, <em>Staf</em>, atau <em>Dukungan TI</em>.
                            Kata sandi awal semua pengguna: <strong>password</strong>.
                        </p>

                        <div className="mb-4">
                            <a
                                href={route('admin.users.import.template')}
                                className="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline"
                                download
                            >
                                <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                Unduh Template Excel
                            </a>
                        </div>

                        <form onSubmit={submitImport} className="space-y-4">
                            <div>
                                <input
                                    ref={fileRef}
                                    type="file"
                                    accept=".xlsx,.xls,.csv"
                                    onChange={(e) =>
                                        setImportData(
                                            'file',
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border file:border-gray-300 file:bg-white file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-gray-50"
                                />
                                {importErrors.file && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {importErrors.file}
                                    </p>
                                )}
                            </div>

                            <div className="flex justify-end gap-2">
                                <button
                                    type="button"
                                    onClick={closeImport}
                                    className="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                >
                                    Batal
                                </button>
                                <button
                                    type="submit"
                                    disabled={importing || !importData.file}
                                    className="rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                                >
                                    {importing ? 'Mengimpor...' : 'Impor'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
