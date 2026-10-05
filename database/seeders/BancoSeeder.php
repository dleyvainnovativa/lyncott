<?php

namespace Database\Seeders;

use App\Models\Banco;
use Illuminate\Database\Seeder;

class BancoSeeder extends Seeder
{
    /**
     * Bancos de México (claves ABM de 3 dígitos). Lista representativa para
     * el <select>; amplíala según se necesite.
     */
    public function run(): void
    {
        $bancos = [
            ['002', 'Banamex (Citibanamex)'],
            ['006', 'Banco Nacional de Comercio Exterior'],
            ['009', 'Banobras'],
            ['012', 'BBVA México'],
            ['014', 'Santander'],
            ['019', 'Banjército'],
            ['021', 'HSBC'],
            ['030', 'BanBajío'],
            ['036', 'Inbursa'],
            ['042', 'Mifel'],
            ['044', 'Scotiabank'],
            ['058', 'Banregio'],
            ['059', 'Invex'],
            ['060', 'Bansi'],
            ['062', 'Afirme'],
            ['072', 'Banorte'],
            ['106', 'Bank of America'],
            ['108', 'MUFG Bank'],
            ['110', 'JP Morgan'],
            ['112', 'Banco Monex'],
            ['113', 'Ve por Más'],
            ['127', 'Banco Azteca'],
            ['128', 'Autofin'],
            ['129', 'Barclays'],
            ['130', 'Compartamos'],
            ['132', 'Multiva'],
            ['133', 'Actinver'],
            ['136', 'Intercam Banco'],
            ['137', 'BanCoppel'],
            ['138', 'ABC Capital'],
            ['140', 'Banco Shinhan'],
            ['141', 'Volkswagen Bank'],
            ['143', 'CIBanco'],
            ['145', 'BBASE (Banco Base)'],
            ['147', 'Bankaool'],
            ['148', 'Banco PagaTodo'],
            ['150', 'Banco Inmobiliario Mexicano'],
            ['152', 'Banco Bancrea'],
            ['156', 'Sabadell'],
            ['166', 'Banco del Bienestar'],
            ['168', 'Sociedad Hipotecaria Federal'],
            ['600', 'Monex Casa de Bolsa'],
            ['646', 'STP'],
            ['652', 'Crezcamos (Credit)'],
            ['656', 'Covalto'],
        ];

        foreach ($bancos as [$clave, $nombre]) {
            Banco::updateOrCreate(['nombre' => $nombre], ['clave' => $clave, 'activo' => true]);
        }
    }
}
