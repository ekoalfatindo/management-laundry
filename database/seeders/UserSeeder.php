<?php

namespace Database\Seeders;

use App\Enums\KategoriPelanggan;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $staff = [
            [
                'name' => 'Octavyan Putra Ramadhan',
                'email' => 'admin@laundry.test',
                'jenis_kelamin' => 'Laki-Laki',
                'alamat' => 'Larangan RT 04/RW 04, Gayam, Sukoharjo',
                'role' => UserRole::Admin,
            ],
            [
                'name' => 'Vavian Ega Ramadhan',
                'email' => 'vavian@laundry.test',
                'jenis_kelamin' => 'Laki-Laki',
                'alamat' => 'Jl. Kyai Gede No. 18, Sukoharjo',
                'role' => UserRole::Karyawan,
            ],
            [
                'name' => 'Sidiq Kurniawan',
                'email' => 'sidiq@laundry.test',
                'jenis_kelamin' => 'Laki-Laki',
                'alamat' => 'Ngaglik RT 02/RW 04, Bendosari, Sukoharjo',
                'role' => UserRole::Karyawan,
            ],
            [
                'name' => 'Fadly Rizky Maulana',
                'email' => 'fadly@laundry.test',
                'jenis_kelamin' => 'Laki-Laki',
                'alamat' => 'Jl. Supriyadi No. 55, Surakarta',
                'role' => UserRole::Karyawan,
            ],
            [
                'name' => 'Puan Maharani',
                'email' => 'puan@laundry.test',
                'jenis_kelamin' => 'Perempuan',
                'alamat' => 'Jl. Sisingamangaraja No. 27, Surakarta',
                'role' => UserRole::Karyawan,
            ],
        ];

        $customers = [
            ['name' => 'Galang Ardiansyah', 'email' => 'galang@laundry.test', 'jenis_kelamin' => 'Laki-Laki', 'alamat' => 'Jl. Kapten Mulyadi No. 12, Surakarta'],
            ['name' => 'Megawati', 'email' => 'mega@laundry.test', 'jenis_kelamin' => 'Perempuan', 'alamat' => 'Jl. Slamet Riyadi No. 88, Surakarta'],
            ['name' => 'Ayu Lestari', 'email' => 'ayu@laundry.test', 'jenis_kelamin' => 'Perempuan', 'alamat' => 'Perum Griya Asri Blok C3, Sukoharjo'],
            ['name' => 'Budi Santoso', 'email' => 'budi@laundry.test', 'jenis_kelamin' => 'Laki-Laki', 'alamat' => 'Jl. Ahmad Yani No. 45, Sukoharjo'],
            ['name' => 'Citra Dewi', 'email' => 'citra@laundry.test', 'jenis_kelamin' => 'Perempuan', 'alamat' => 'Jl. Kartini No. 7, Boyolali'],
            ['name' => 'Eko Prasetyo', 'email' => 'eko@laundry.test', 'jenis_kelamin' => 'Laki-Laki', 'alamat' => 'Jl. Diponegoro No. 21, Klaten'],
            ['name' => 'Fitri Handayani', 'email' => 'fitri@laundry.test', 'jenis_kelamin' => 'Perempuan', 'alamat' => 'Jl. Veteran No. 9, Surakarta'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra@laundry.test', 'jenis_kelamin' => 'Laki-Laki', 'alamat' => 'Jl. Raya Solo–Baki KM 5, Sukoharjo'],
            ['name' => 'Indah Permata', 'email' => 'indah@laundry.test', 'jenis_kelamin' => 'Perempuan', 'alamat' => 'Jl. Melati No. 14, Karanganyar'],
            ['name' => 'Joko Susilo', 'email' => 'joko@laundry.test', 'jenis_kelamin' => 'Laki-Laki', 'alamat' => 'Jl. Mangga No. 3, Sukoharjo'],
        ];

        $memberSinceMonths = [
            'mega@laundry.test' => 10,
            'ayu@laundry.test' => 9,
            'citra@laundry.test' => 8,
            'fitri@laundry.test' => 7,
            'indah@laundry.test' => 6,
        ];

        foreach ($staff as $user) {
            $this->seedUser($user, $user['role']);
        }

        foreach ($customers as $customer) {
            $this->seedUser($customer, UserRole::Pelanggan, $memberSinceMonths[$customer['email']] ?? null);
        }
    }

    /**
     * @param  array{name: string, email: string, jenis_kelamin: string, alamat: string, role?: UserRole}  $data
     */
    private function seedUser(array $data, UserRole $role, ?int $memberSinceMonths = null): void
    {
        unset($data['role']);

        $isMember = $memberSinceMonths !== null;

        $user = User::query()->updateOrCreate(
            ['email' => $data['email']],
            [
                ...$data,
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'kategori_pelanggan' => $isMember ? KategoriPelanggan::Member : KategoriPelanggan::Reguler,
                'member_sejak' => $isMember ? now()->subMonthsNoOverflow($memberSinceMonths)->toDateString() : null,
            ],
        );

        $user->syncRoles([$role->value]);
    }
}
