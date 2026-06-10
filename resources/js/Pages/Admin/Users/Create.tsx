import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        user_id: '',
        name: '',
        email: '',
        role: 'staff',
        department: '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('admin.users.store'));
    };

    return (
        <AuthenticatedLayout
            header={
                <h1 className="text-xl font-semibold text-gray-900">
                    Pengguna Baru
                </h1>
            }
        >
            <Head title="Pengguna Baru" />

            <div className="max-w-2xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <form onSubmit={submit} className="space-y-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <InputLabel
                                htmlFor="user_id"
                                value="ID Pengguna"
                            />
                            <TextInput
                                id="user_id"
                                value={data.user_id}
                                onChange={(e) =>
                                    setData('user_id', e.target.value)
                                }
                                className="mt-1 block w-full"
                                isFocused
                            />
                            <InputError
                                className="mt-2"
                                message={errors.user_id}
                            />
                        </div>
                        <div>
                            <InputLabel htmlFor="role" value="Peran" />
                            <select
                                id="role"
                                value={data.role}
                                onChange={(e) =>
                                    setData('role', e.target.value)
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
                            <InputLabel
                                htmlFor="department"
                                value="Departemen"
                            />
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
                    </div>

                    <div>
                        <InputLabel
                            htmlFor="password"
                            value="Kata Sandi Awal"
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
                            Buat Pengguna
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
