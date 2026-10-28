<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::pluck('id', 'name');

        $menus = [
            [
                'category' => 'Makanan Utama', 'name' => 'Nasi Goreng Kampung', 'price' => 38000,
                'description' => 'Nasi goreng dengan telur mata sapi, ayam suwir, dan sambal terasi rumahan.',
                'image_url' => 'photo-1512058564366-18510be2db19', 'calories' => 620, 'prep_time' => 15,
                'badge' => 'Best Seller', 'is_featured' => true,
            ],
            [
                'category' => 'Makanan Utama', 'name' => 'Nasi Rendang Daging', 'price' => 55000,
                'description' => 'Rendang sapi yang dimasak perlahan 8 jam dengan rempah pilihan.',
                'image_url' => 'photo-1604908176997-125f25cc6f3d', 'calories' => 710, 'prep_time' => 20,
                'badge' => "Chef's Pick", 'is_featured' => true,
            ],
            [
                'category' => 'Makanan Utama', 'name' => 'Sate Ayam Madura', 'price' => 42000,
                'description' => 'Sepuluh tusuk sate ayam empuk dengan bumbu kacang kental dan lontong.',
                'image_url' => 'photo-1529042410759-befb1204b468', 'calories' => 540, 'prep_time' => 18,
                'badge' => 'Favorit', 'is_featured' => true,
            ],
            [
                'category' => 'Makanan Utama', 'name' => 'Mie Goreng Jawa', 'price' => 35000,
                'description' => 'Mie kuning goreng dengan kol, ayam, dan aroma bawang goreng.',
                'image_url' => 'photo-1585032226651-759b368d7246', 'calories' => 580, 'prep_time' => 12,
            ],
            [
                'category' => 'Makanan Utama', 'name' => 'Ayam Bakar Rica-Rica', 'price' => 48000,
                'description' => 'Ayam bakar pedas khas Manado dengan sambal rica dan lalapan segar.',
                'image_url' => 'photo-1598515214211-89d3c73ae83b', 'calories' => 650, 'prep_time' => 22,
                'badge' => 'Pedas', 'is_featured' => true,
            ],
            [
                'category' => 'Makanan Utama', 'name' => 'Ikan Nila Bakar', 'price' => 52000,
                'description' => 'Ikan nila segar dibakar dengan bumbu kecap dan jeruk nipis.',
                'image_url' => 'photo-1519708227418-c8fd9a32b7a2', 'calories' => 430, 'prep_time' => 25,
            ],
            [
                'category' => 'Pembuka', 'name' => 'Gado-Gado Siram', 'price' => 30000,
                'description' => 'Sayuran rebus, tahu, tempe, dan telur dengan saus kacang gurih.',
                'image_url' => 'photo-1512621776951-a57141f2eefd', 'calories' => 380, 'prep_time' => 10,
            ],
            [
                'category' => 'Pembuka', 'name' => 'Soto Ayam Lamongan', 'price' => 32000,
                'description' => 'Kuah kuning bening dengan ayam suwir, koya, dan telur rebus.',
                'image_url' => 'photo-1547592180-85f173990554', 'calories' => 310, 'prep_time' => 10,
                'badge' => 'Hangat',
            ],
            [
                'category' => 'Pembuka', 'name' => 'Lumpia Semarang', 'price' => 27000,
                'description' => 'Empat gulung lumpia renyah dengan isi ayam, rebung, dan saus asam manis.',
                'image_url' => 'photo-1544025162-d76694265947', 'calories' => 290, 'prep_time' => 8,
            ],
            [
                'category' => 'Camilan', 'name' => 'Tahu Bakso Crispy', 'price' => 24000,
                'description' => 'Tahu isi bakso sapi dengan balutan tepung renyah dan saus sambal.',
                'image_url' => 'photo-1567188040759-fb8a883dc6d8', 'calories' => 340, 'prep_time' => 10,
                'badge' => 'Baru',
            ],
            [
                'category' => 'Camilan', 'name' => 'Pisang Goreng Keju', 'price' => 22000,
                'description' => 'Pisang raja goreng dengan topping keju parut dan susu kental manis.',
                'image_url' => 'photo-1566837945700-30057527ade0', 'calories' => 410, 'prep_time' => 8,
            ],
            [
                'category' => 'Minuman', 'name' => 'Es Teh Manis', 'price' => 12000,
                'description' => 'Teh tubruk klasik disajikan dingin dengan gula batu.',
                'image_url' => 'photo-1556679343-c7306c1976bc', 'calories' => 90, 'prep_time' => 3,
            ],
            [
                'category' => 'Minuman', 'name' => 'Es Jeruk Peras', 'price' => 16000,
                'description' => 'Jeruk peras segar tanpa pengawet, manis asam menyegarkan.',
                'image_url' => 'photo-1600271886742-f049cd451bba', 'calories' => 110, 'prep_time' => 4,
            ],
            [
                'category' => 'Minuman', 'name' => 'Kopi Tubruk', 'price' => 18000,
                'description' => 'Kopi robusta lokal diseduh tradisional, pekat dan aromatik.',
                'image_url' => 'photo-1509042239860-f550ce710b93', 'calories' => 15, 'prep_time' => 5,
                'badge' => "Chef's Pick",
            ],
            [
                'category' => 'Minuman', 'name' => 'Jus Alpukat', 'price' => 25000,
                'description' => 'Alpukat mentega pilihan dengan susu dan sedikit madu.',
                'image_url' => 'photo-1623065422902-30a2d299bbe4', 'calories' => 260, 'prep_time' => 6,
            ],
            [
                'category' => 'Dessert', 'name' => 'Es Campur Nusantara', 'price' => 24000,
                'description' => 'Es serut dengan kelapa muda, nangka, cincau, dan sirup gula merah.',
                'image_url' => 'photo-1563805042-7684c019e1cb', 'calories' => 300, 'prep_time' => 6,
                'is_featured' => true,
            ],
            [
                'category' => 'Dessert', 'name' => 'Klepon Gula Jawa', 'price' => 22000,
                'description' => 'Enam butir klepon kenyal berisi gula jawa dengan taburan kelapa.',
                'image_url' => 'photo-1551024506-0bccd828d307', 'calories' => 280, 'prep_time' => 8,
            ],
            [
                'category' => 'Dessert', 'name' => 'Panna Cotta Pandan', 'price' => 32000,
                'description' => 'Panna cotta lembut beraroma pandan dengan saus gula kelapa.',
                'image_url' => 'photo-1488477181946-6428a0291777', 'calories' => 320, 'prep_time' => 5,
                'badge' => 'Baru',
            ],
        ];

        foreach ($menus as $menu) {
            $categoryId = $categories[$menu['category']] ?? $categories->first();

            Menu::updateOrCreate(
                ['name' => $menu['name']],
                [
                    'category_id' => $categoryId,
                    'description' => $menu['description'],
                    'price' => $menu['price'],
                    'image_url' => 'https://images.unsplash.com/'.$menu['image_url'].'?auto=format&fit=crop&w=900&q=80',
                    'calories' => $menu['calories'],
                    'prep_time' => $menu['prep_time'],
                    'badge' => $menu['badge'] ?? null,
                    'is_featured' => $menu['is_featured'] ?? false,
                    'is_available' => true,
                ],
            );
        }
    }
}
