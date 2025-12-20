<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'code' => 'JOD',
                'name' => ['en' => 'Jordanian Dinar', 'ar' => 'دينار أردني'],
                'symbol' => 'د.ا',
                'exchange_rate' => 1.000000,
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'code' => 'USD',
                'name' => ['en' => 'US Dollar', 'ar' => 'دولار أمريكي'],
                'symbol' => '$',
                'exchange_rate' => 0.709220,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EUR',
                'name' => ['en' => 'Euro', 'ar' => 'يورو'],
                'symbol' => '€',
                'exchange_rate' => 0.651820,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'GBP',
                'name' => ['en' => 'British Pound', 'ar' => 'جنيه إسترليني'],
                'symbol' => '£',
                'exchange_rate' => 0.558940,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'SAR',
                'name' => ['en' => 'Saudi Riyal', 'ar' => 'ريال سعودي'],
                'symbol' => 'ر.س',
                'exchange_rate' => 2.659500,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'AED',
                'name' => ['en' => 'UAE Dirham', 'ar' => 'درهم إماراتي'],
                'symbol' => 'د.إ',
                'exchange_rate' => 2.604720,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'EGP',
                'name' => ['en' => 'Egyptian Pound', 'ar' => 'جنيه مصري'],
                'symbol' => 'ج.م',
                'exchange_rate' => 21.890000,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'ILS',
                'name' => ['en' => 'Israeli Shekel', 'ar' => 'شيكل إسرائيلي'],
                'symbol' => '₪',
                'exchange_rate' => 2.574000,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'KWD',
                'name' => ['en' => 'Kuwaiti Dinar', 'ar' => 'دينار كويتي'],
                'symbol' => 'د.ك',
                'exchange_rate' => 0.217500,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'QAR',
                'name' => ['en' => 'Qatari Riyal', 'ar' => 'ريال قطري'],
                'symbol' => 'ر.ق',
                'exchange_rate' => 2.581000,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'BHD',
                'name' => ['en' => 'Bahraini Dinar', 'ar' => 'دينار بحريني'],
                'symbol' => 'د.ب',
                'exchange_rate' => 0.267000,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'code' => 'OMR',
                'name' => ['en' => 'Omani Rial', 'ar' => 'ريال عماني'],
                'symbol' => 'ر.ع',
                'exchange_rate' => 0.272800,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
