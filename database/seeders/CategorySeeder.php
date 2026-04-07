<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mendapatkan semua pengguna yang ada, atau jika belum ada buat pengguna dummy sementara.
        // Dalam konteks seeder ini, lebih baik menambahkannya untuk user yang pertama kali ditemukan
        // atau jika user belum ada, abaikan saja karena Category harus miliki user_id.
        $user = User::first();
        if (!$user) {
            $this->command->warn('Tidak ada user di database. Kategori gagal dibuat.');
            return;
        }

        $categories = [
            // Pemasukan
            ['name' => 'Gaji', 'type' => 'income'],
            ['name' => 'Bonus', 'type' => 'income'],
            ['name' => 'Investasi (Profit)', 'type' => 'income'],
            ['name' => 'Usaha Sampingan', 'type' => 'income'],
            ['name' => 'Pemasukan Lainnya', 'type' => 'income'],

            // Pengeluaran
            ['name' => 'Makan & Minum', 'type' => 'expense'],
            ['name' => 'Belanja Bulanan', 'type' => 'expense'],
            ['name' => 'Transportasi', 'type' => 'expense'],
            ['name' => 'Tagihan Listrik', 'type' => 'expense'],
            ['name' => 'Tagihan Air', 'type' => 'expense'],
            ['name' => 'Tagihan Internet', 'type' => 'expense'],
            ['name' => 'Pulsa & Paket Data', 'type' => 'expense'],
            ['name' => 'Hiburan', 'type' => 'expense'],
            ['name' => 'Pakaian & Aksesoris', 'type' => 'expense'],
            ['name' => 'Kesehatan & Medis', 'type' => 'expense'],
            ['name' => 'Pendidikan', 'type' => 'expense'],
            ['name' => 'Cicilan & Utang', 'type' => 'expense'],
            ['name' => 'Keluarga & Anak', 'type' => 'expense'],
            ['name' => 'Zakat & Sedekah', 'type' => 'expense'],
            ['name' => 'Investasi', 'type' => 'expense'],
            ['name' => 'Asuransi', 'type' => 'expense'],
            ['name' => 'Perawatan Kendaraan', 'type' => 'expense'],
            ['name' => 'Pengeluaran Lainnya', 'type' => 'expense'],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate([
                'user_id' => $user->id,
                'name'    => $category['name'],
                'type'    => $category['type'],
            ]);
        }
        
        $this->command->info('Kategori berhasil ditambahkan untuk user ' . $user->name);
    }
}
