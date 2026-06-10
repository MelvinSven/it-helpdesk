import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useMemo, useRef, useState } from 'react';

interface ItemOption {
    id: number;
    serial_number: string;
    item_name: string;
    brand_name: string;
    type: string;
}

interface UserOption {
    id: number;
    name: string;
    user_id: string;
    department: string | null;
}

const today = new Date().toISOString().slice(0, 10);

/**
 * Minimal type-ahead combobox over a client-side list. Calls `onChange` with the
 * picked option, or `null` when the field is edited away from a valid selection.
 */
function Combobox<T extends { id: number }>({
    id,
    options,
    primary,
    secondary,
    matches,
    onChange,
    placeholder,
    isFocused,
}: {
    id: string;
    options: T[];
    primary: (o: T) => string;
    secondary: (o: T) => string;
    matches: (o: T, q: string) => boolean;
    onChange: (o: T | null) => void;
    placeholder?: string;
    isFocused?: boolean;
}) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const [highlight, setHighlight] = useState(0);
    const [picked, setPicked] = useState(false);
    const blurTimer = useRef<ReturnType<typeof setTimeout>>();

    const results = useMemo(() => {
        const q = query.trim().toLowerCase();
        const list = q ? options.filter((o) => matches(o, q)) : options;
        return list.slice(0, 8);
    }, [query, options, matches]);

    const choose = (o: T) => {
        onChange(o);
        setQuery(primary(o));
        setPicked(true);
        setOpen(false);
    };

    const onType = (value: string) => {
        setQuery(value);
        setPicked(false);
        onChange(null); // force an explicit re-selection
        setHighlight(0);
        setOpen(true);
    };

    const onKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (!open && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            setOpen(true);
            return;
        }
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            setHighlight((h) => Math.min(h + 1, results.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setHighlight((h) => Math.max(h - 1, 0));
        } else if (e.key === 'Enter') {
            if (open && results[highlight]) {
                e.preventDefault();
                choose(results[highlight]);
            }
        } else if (e.key === 'Escape') {
            setOpen(false);
        }
    };

    return (
        <div className="relative">
            <TextInput
                id={id}
                value={query}
                onChange={(e) => onType(e.target.value)}
                onFocus={() => setOpen(true)}
                onBlur={() => {
                    blurTimer.current = setTimeout(() => setOpen(false), 120);
                }}
                onKeyDown={onKeyDown}
                className="mt-1 block w-full"
                autoComplete="off"
                role="combobox"
                aria-expanded={open}
                aria-autocomplete="list"
                placeholder={placeholder}
                isFocused={isFocused}
            />
            {picked && (
                <span className="pointer-events-none absolute right-3 top-3.5 text-green-600">
                    ✓
                </span>
            )}
            {open && (
                <ul className="absolute z-10 mt-1 max-h-64 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 text-sm shadow-lg">
                    {results.length === 0 ? (
                        <li className="px-3 py-2 text-gray-500">
                            Tidak ada hasil.
                        </li>
                    ) : (
                        results.map((o, i) => (
                            <li
                                key={o.id}
                                // onMouseDown fires before input blur, so the
                                // selection isn't lost to the blur handler.
                                onMouseDown={(e) => {
                                    e.preventDefault();
                                    clearTimeout(blurTimer.current);
                                    choose(o);
                                }}
                                onMouseEnter={() => setHighlight(i)}
                                className={`flex cursor-pointer items-center justify-between px-3 py-2 ${
                                    i === highlight ? 'bg-brand-50' : ''
                                }`}
                            >
                                <span className="font-medium text-gray-900">
                                    {primary(o)}
                                </span>
                                <span className="ml-3 shrink-0 text-xs text-gray-500">
                                    {secondary(o)}
                                </span>
                            </li>
                        ))
                    )}
                </ul>
            )}
        </div>
    );
}

export default function Create({
    items,
    users,
}: {
    items: ItemOption[];
    users: UserOption[];
}) {
    const { auth } = usePage<PageProps<{ items: ItemOption[]; users: UserOption[] }>>().props;
    const isAdmin = auth.user.role === 'admin';

    const { data, setData, post, processing, errors, progress } = useForm<{
        item_id: number | '';
        borrower_id: number | '';
        borrow_date: string;
        purpose: string;
        borrow_image: File | null;
    }>({
        item_id: '',
        borrower_id: isAdmin ? '' : auth.user.id,
        borrow_date: today,
        purpose: '',
        borrow_image: null,
    });

    const [selectedItem, setSelectedItem] = useState<ItemOption | null>(null);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('borrows.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Pinjam Barang
                </h1>
            }
        >
            <Head title="Pinjam Barang" />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="serial_number"
                                value="Nomor"
                            />
                            <Combobox<ItemOption>
                                id="serial_number"
                                options={items}
                                primary={(o) => o.serial_number}
                                secondary={(o) => o.item_name}
                                matches={(o, q) =>
                                    o.serial_number
                                        .toLowerCase()
                                        .includes(q) ||
                                    o.item_name.toLowerCase().includes(q)
                                }
                                onChange={(o) => {
                                    setSelectedItem(o);
                                    setData('item_id', o?.id ?? '');
                                }}
                                placeholder="Ketik nomor barang..."
                                isFocused
                            />
                            <p className="mt-1 text-xs text-gray-500">
                                Hanya barang yang tersedia yang muncul.
                            </p>
                            <InputError
                                className="mt-2"
                                message={errors.item_id}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="item_name"
                                value="Nama Barang"
                            />
                            <TextInput
                                id="item_name"
                                value={selectedItem?.item_name ?? ''}
                                className="mt-1 block w-full bg-gray-50"
                                placeholder="Isi nomor barang"
                                readOnly
                            />
                            {selectedItem && (
                                <p className="mt-1 text-xs text-gray-500">
                                    {selectedItem.brand_name} ·{' '}
                                    {selectedItem.type}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="borrower"
                                value="Nama Peminjam"
                            />
                            {isAdmin ? (
                                <Combobox<UserOption>
                                    id="borrower"
                                    options={users}
                                    primary={(o) => o.name}
                                    secondary={(o) =>
                                        `${o.user_id}${o.department ? ` · ${o.department}` : ''}`
                                    }
                                    matches={(o, q) =>
                                        o.name.toLowerCase().includes(q) ||
                                        o.user_id.toLowerCase().includes(q)
                                    }
                                    onChange={(o) =>
                                        setData('borrower_id', o?.id ?? '')
                                    }
                                    placeholder="Ketik nama pengguna..."
                                />
                            ) : (
                                <TextInput
                                    id="borrower"
                                    value={auth.user.name}
                                    className="mt-1 block w-full bg-gray-50"
                                    readOnly
                                />
                            )}
                            <InputError
                                className="mt-2"
                                message={errors.borrower_id}
                            />
                        </div>
                        <div>
                            <InputLabel
                                htmlFor="borrow_date"
                                value="Tanggal Pinjam"
                            />
                            <TextInput
                                id="borrow_date"
                                type="date"
                                value={data.borrow_date}
                                onChange={(e) =>
                                    setData('borrow_date', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.borrow_date}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="purpose" value="Kegunaan" />
                        <textarea
                            id="purpose"
                            rows={4}
                            value={data.purpose}
                            onChange={(e) => setData('purpose', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Untuk keperluan apa barang ini dipinjam?"
                        />
                        <InputError className="mt-2" message={errors.purpose} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="borrow_image"
                            value="Foto Peminjaman"
                        />
                        <input
                            id="borrow_image"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            required
                            onChange={(e) =>
                                setData(
                                    'borrow_image',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                            className="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            JPG, PNG, atau WEBP. Maksimal 5MB.
                        </p>
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.borrow_image}
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            Pinjam Barang
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
