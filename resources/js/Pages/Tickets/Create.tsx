import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { Category } from '@/types';
import { FormEventHandler } from 'react';

export default function Create({ categories }: { categories: Category[] }) {
    const { data, setData, post, processing, errors, progress } = useForm<{
        title: string;
        category_id: number | '';
        priority: string;
        description: string;
        attachment: File | null;
    }>({
        title: '',
        category_id: categories[0]?.id ?? '',
        priority: 'medium',
        description: '',
        attachment: null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('tickets.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Tiket Baru
                </h1>
            }
        >
            <Head title="Tiket Baru" />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="title" value="Judul" />
                        <TextInput
                            id="title"
                            name="title"
                            maxLength={100}
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            className="mt-1 block w-full"
                            isFocused
                        />
                        <InputError className="mt-2" message={errors.title} />
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="category_id"
                                value="Kategori"
                            />
                            <select
                                id="category_id"
                                value={data.category_id}
                                onChange={(e) =>
                                    setData(
                                        'category_id',
                                        Number(e.target.value),
                                    )
                                }
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            <InputError
                                className="mt-2"
                                message={errors.category_id}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="priority" value="Prioritas" />
                            <select
                                id="priority"
                                value={data.priority}
                                onChange={(e) =>
                                    setData('priority', e.target.value)
                                }
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="low">Rendah</option>
                                <option value="medium">Sedang</option>
                                <option value="high">Tinggi</option>
                                <option value="urgent">Mendesak</option>
                            </select>
                            <InputError
                                className="mt-2"
                                message={errors.priority}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="description"
                            value="Deskripsi"
                        />
                        <textarea
                            id="description"
                            name="description"
                            rows={6}
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-brand-500 focus:ring-brand-500"
                            placeholder="Jelaskan masalah, pesan error, langkah-langkah untuk reproduksi..."
                        />
                        <InputError
                            className="mt-2"
                            message={errors.description}
                        />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="attachment"
                            value="Lampiran (opsional)"
                        />
                        <input
                            id="attachment"
                            type="file"
                            accept="image/jpeg,image/png,application/pdf"
                            onChange={(e) =>
                                setData(
                                    'attachment',
                                    e.target.files?.[0] ?? null,
                                )
                            }
                            className="mt-1 block w-full text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100"
                        />
                        <p className="mt-1 text-xs text-gray-500">
                            JPG, PNG, atau PDF. Maksimal 5MB.
                        </p>
                        {progress && (
                            <p className="mt-1 text-xs text-gray-500">
                                Mengunggah: {progress.percentage}%
                            </p>
                        )}
                        <InputError
                            className="mt-2"
                            message={errors.attachment}
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            Kirim Tiket
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
