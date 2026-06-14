<?php

namespace Database\Seeders;

use App\Models\DrugDictionary;
use Illuminate\Database\Seeder;

class DrugDictionarySeeder extends Seeder
{
    public function run(): void
    {
        $drugs = [
            [
                'generic_name' => 'Paracetamol',
                'brand_name'   => 'Panadol',
                'aliases'      => ['PCM', 'Para', 'Acetaminophen'],
                'nafdac_code'  => 'A4-0001',
            ],
            [
                'generic_name' => 'Amoxicillin',
                'brand_name'   => 'Amoxil',
                'aliases'      => ['Amox', 'AMX'],
                'nafdac_code'  => 'A4-0002',
            ],
            [
                'generic_name' => 'Metronidazole',
                'brand_name'   => 'Flagyl',
                'aliases'      => ['Metro', 'MTZ'],
                'nafdac_code'  => 'A4-0003',
            ],
            [
                'generic_name' => 'Artemether/Lumefantrine',
                'brand_name'   => 'Coartem',
                'aliases'      => ['AL', 'Coartem'],
                'nafdac_code'  => 'A4-0004',
            ],
            [
                'generic_name' => 'Ciprofloxacin',
                'brand_name'   => 'Ciprolet',
                'aliases'      => ['Cipro', 'CIP'],
                'nafdac_code'  => 'A4-0005',
            ],
            [
                'generic_name' => 'Diclofenac',
                'brand_name'   => 'Voltaren',
                'aliases'      => ['Diclo', 'Diclofenac Sodium'],
                'nafdac_code'  => 'A4-0006',
            ],
            [
                'generic_name' => 'Omeprazole',
                'brand_name'   => 'Losec',
                'aliases'      => ['PPI', 'Ome'],
                'nafdac_code'  => 'A4-0007',
            ],
            [
                'generic_name' => 'Amlodipine',
                'brand_name'   => 'Norvasc',
                'aliases'      => ['Amlod', 'CCB'],
                'nafdac_code'  => 'A4-0008',
            ],
            [
                'generic_name' => 'Lisinopril',
                'brand_name'   => 'Zestril',
                'aliases'      => ['Lisin', 'ACE'],
                'nafdac_code'  => 'A4-0009',
            ],
            [
                'generic_name' => 'Metformin',
                'brand_name'   => 'Glucophage',
                'aliases'      => ['Met', 'Glucophage'],
                'nafdac_code'  => 'A4-0010',
            ],
            [
                'generic_name' => 'Azithromycin',
                'brand_name'   => 'Zithromax',
                'aliases'      => ['Azith', 'AZM', 'Zithromax'],
                'nafdac_code'  => 'A4-0011',
            ],
            [
                'generic_name' => 'Cotrimoxazole',
                'brand_name'   => 'Septrin',
                'aliases'      => ['Co-trimox', 'SMX/TMP', 'Bactrim'],
                'nafdac_code'  => 'A4-0012',
            ],
            [
                'generic_name' => 'Ibuprofen',
                'brand_name'   => 'Brufen',
                'aliases'      => ['IBU', 'Brufen'],
                'nafdac_code'  => 'A4-0013',
            ],
            [
                'generic_name' => 'Doxycycline',
                'brand_name'   => 'Vibramycin',
                'aliases'      => ['Doxy', 'DOX'],
                'nafdac_code'  => 'A4-0014',
            ],
            [
                'generic_name' => 'Hydrochlorothiazide',
                'brand_name'   => 'HCT',
                'aliases'      => ['HCTZ', 'HCT'],
                'nafdac_code'  => 'A4-0015',
            ],
        ];

        foreach ($drugs as $drug) {
            DrugDictionary::firstOrCreate(
                ['generic_name' => $drug['generic_name']],
                array_merge($drug, ['is_active' => true]),
            );
        }
    }
}
