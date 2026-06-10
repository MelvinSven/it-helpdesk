import Dropdown from '@/Components/Dropdown';
import NotificationBell from '@/Components/NotificationBell';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useEffect, useState } from 'react';
import logo from '../../images/lixicon-logo.png';
import { PageProps } from '@/types';

type NavItem = {
    name: string;
    href: string;
    routeName: string;
    icon: ReactNode;
};

const iconCls = 'mr-3 h-5 w-5 shrink-0';

const DashboardIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"
        />
    </svg>
);

const TicketIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M3 9a2 2 0 012-2h14a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H5a2 2 0 01-2-2v-2a2 2 0 100-4V9z"
        />
        <path strokeLinecap="round" strokeLinejoin="round" d="M13 7v10" />
    </svg>
);

const ItemIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M20 7L12 3 4 7m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
        />
    </svg>
);

const BorrowIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"
        />
    </svg>
);

const ProcurementIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
    </svg>
);

const UsersIcon = (
    <svg
        className={iconCls}
        fill="none"
        stroke="currentColor"
        strokeWidth="1.8"
        viewBox="0 0 24 24"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H2v-2a4 4 0 014-4h4a4 4 0 014 4v2h-3M16 3.13a4 4 0 010 7.75M12 7a4 4 0 11-8 0 4 4 0 018 0z"
        />
    </svg>
);

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, flash } = usePage<PageProps>().props;
    const user = auth.user;
    const [open, setOpen] = useState(false);
    const [toast, setToast] = useState<string | null>(null);

    useEffect(() => {
        if (flash?.success) {
            setToast(flash.success);
            const t = setTimeout(() => setToast(null), 3000);
            return () => clearTimeout(t);
        }
    }, [flash?.success]);

    const nav: NavItem[] = [
        {
            name: 'Dasbor',
            href: route('dashboard'),
            routeName: 'dashboard',
            icon: DashboardIcon,
        },
        {
            name: 'Tiket',
            href: route('tickets.index'),
            routeName: 'tickets.*',
            icon: TicketIcon,
        },
        {
            name: 'Barang',
            href: route('items.index'),
            routeName: 'items.*',
            icon: ItemIcon,
        },
        {
            name: 'Peminjaman',
            href: route('borrows.index'),
            routeName: 'borrows.*',
            icon: BorrowIcon,
        },
    ];

    if (user.role === 'admin') {
        nav.push({
            name: 'Pengajuan Barang',
            href: route('admin.procurements.index'),
            routeName: 'admin.procurements.*',
            icon: ProcurementIcon,
        });
        nav.push({
            name: 'Pengguna',
            href: route('admin.users.index'),
            routeName: 'admin.users.*',
            icon: UsersIcon,
        });
    }

    const roleLabel: Record<string, string> = {
        admin: 'Admin Sistem',
        it_support: 'Dukungan TI',
        staff: 'Staf',
    };

    return (
        <div className="min-h-screen bg-gray-50">
            <aside
                className={`fixed inset-y-0 left-0 z-30 w-64 transform border-r border-gray-200 bg-white transition-transform lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}
            >
                <div className="flex h-16 items-center border-b border-gray-200 px-6">
                    <Link href={route('dashboard')} className="flex items-center gap-3">
                        <img src={logo} alt="Lixicon" className="h-8 w-auto" />
                        <div className="flex flex-col">
                            <span className="text-base font-semibold text-gray-900">
                                IT Helpdesk
                            </span>
                            <span className="text-xs text-gray-500">
                                PT LIXICON INDONESIA
                            </span>
                        </div>
                    </Link>
                </div>
                <nav className="px-3 py-4">
                    {nav.map((item) => {
                        const active = route().current(item.routeName);
                        return (
                            <Link
                                key={item.name}
                                href={item.href}
                                className={`mt-1 flex items-center rounded-md px-3 py-2 text-sm font-medium transition ${active ? 'bg-brand-50 text-brand-700' : 'text-gray-700 hover:bg-gray-100'}`}
                            >
                                {item.icon}
                                {item.name}
                            </Link>
                        );
                    })}
                </nav>
            </aside>

            <div className="lg:pl-64">
                <header className="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6 lg:px-8">
                    <button
                        type="button"
                        onClick={() => setOpen(!open)}
                        className="rounded-md p-2 text-gray-500 lg:hidden"
                        aria-label="Buka menu"
                    >
                        <svg
                            className="h-6 w-6"
                            stroke="currentColor"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <div className="flex-1">{header}</div>

                    <NotificationBell />

                    <Dropdown>
                        <Dropdown.Trigger>
                            <button
                                type="button"
                                className="flex items-center gap-2 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                            >
                                <div className="flex h-8 w-8 items-center justify-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div className="hidden text-left sm:block">
                                    <div className="text-sm font-medium">
                                        {user.name}
                                    </div>
                                    <div className="text-xs text-gray-500">
                                        {roleLabel[user.role]}
                                    </div>
                                </div>
                            </button>
                        </Dropdown.Trigger>
                        <Dropdown.Content>
                            <Dropdown.Link href={route('profile.edit')}>
                                Profil
                            </Dropdown.Link>
                            <Dropdown.Link
                                href={route('logout')}
                                method="post"
                                as="button"
                            >
                                Keluar
                            </Dropdown.Link>
                        </Dropdown.Content>
                    </Dropdown>
                </header>

                <main className="p-4 sm:p-6 lg:p-8">{children}</main>
            </div>

            {open && (
                <div
                    onClick={() => setOpen(false)}
                    className="fixed inset-0 z-20 bg-black/40 lg:hidden"
                />
            )}

            {toast && (
                <div className="fixed bottom-4 right-4 z-50 rounded-md bg-gray-900 px-4 py-2 text-sm text-white shadow-lg">
                    {toast}
                </div>
            )}
        </div>
    );
}
