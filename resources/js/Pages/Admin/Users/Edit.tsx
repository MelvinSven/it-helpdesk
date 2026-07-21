import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { User } from '@/types';
import { FormEventHandler } from 'react';

export default function Edit({ managed_user }: { managed_user: User }) {
    const { data, setData, patch, processing, errors } = useForm({
        name: managed_user.name,
        email: managed_user.email ?? '',
        role: managed_user.role,
        department: managed_user.department ?? '',
        proyek: managed_user.proyek ?? '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('admin.users.update', managed_user.id));
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Ubah Pengguna
                </h1>
            }
        >
            <Head title={`Ubah ${managed_user.name}`} />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <div className="mb-5 rounded-md bg-gray-50 px-4 py-2 text-sm text-gray-600">
                    ID Pengguna:{' '}
                    <span className="font-mono">{managed_user.user_id}</span>
                </div>

                <form onSubmit={submit} className="space-y-5">
                    <div>
                        <InputLabel htmlFor="name" value="Nama Lengkap" />
                        <TextInput
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError className="mt-2" message={errors.name} />
                    </div>

                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel htmlFor="email" value="Email" />
                            <TextInput
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                className="mt-1 block w-full"
                            />
                            <InputError
                                className="mt-2"
                                message={errors.email}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="role" value="Peran" />
                            <select
                                id="role"
                                value={data.role}
                                onChange={(e) =>
                                    setData(
                                        'role',
                                        e.target.value as User['role'],
                                    )
                                }
                                className="mt-1 block w-full rounded-md border-gray-300 text-sm"
                            >
                                <option value="staff">Staf</option>
                                <option value="it_support">Dukungan TI</option>
                                <option value="admin">Admin</option>
                            </select>
                            <InputError
                                className="mt-2"
                                message={errors.role}
                            />
                        </div>
                    </div>

                    <div>
                        <InputLabel htmlFor="department" value="Departemen" />
                        <TextInput
                            id="department"
                            value={data.department}
                            onChange={(e) =>
                                setData('department', e.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError
                            className="mt-2"
                            message={errors.department}
                        />
                    </div>

                    <div>
                        <InputLabel htmlFor="proyek" value="Proyek" />
                        <TextInput
                            id="proyek"
                            value={data.proyek}
                            onChange={(e) => setData('proyek', e.target.value)}
                            className="mt-1 block w-full"
                        />
                        <InputError className="mt-2" message={errors.proyek} />
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password"
                            value="Reset Kata Sandi (kosongkan untuk mempertahankan)"
                        />
                        <TextInput
                            id="password"
                            type="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                            className="mt-1 block w-full"
                        />
                        <InputError
                            className="mt-2"
                            message={errors.password}
                        />
                    </div>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>
                            Simpan Perubahan
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
