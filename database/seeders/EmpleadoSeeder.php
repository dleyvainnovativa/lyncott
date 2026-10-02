<?php

namespace Database\Seeders;

use App\Models\Empleado;
use Illuminate\Database\Seeder;

class EmpleadoSeeder extends Seeder
{
    /**
     * Dummy employees for the demo. Look one up on step 1 with its
     * número de empleado + RFC. All data is fictional.
     */
    public function run(): void
    {
        $empleados = [
            [
                'numero_empleado' => '082907558',
                'rfc'             => 'PASK820101XYZ',
                'nombre_completo' => 'Angel Samuel Chávez Camacho',
                'correo'          => 'achavez@lyncott.mx',
                'puesto'          => 'Desarrollador de Sistemas',
                'gerencia'        => 'Tecnologías de la Información',
                'departamento'    => 'Tecnologías de la Información',
                'direccion'       => 'Dirección de Administración y Finanzas',
                'dias_disponibles'=> 4,
                'centro_costos'   => 'TI-001',
                'clabe'           => '012180004567890123',
                'banco'           => 'BBVA',
                'sucursal_cedis'  => 'Corporativo CDMX',
            ],
            [
                'numero_empleado' => '100245',
                'rfc'             => 'GOMA900512AB3',
                'nombre_completo' => 'María Fernanda Gómez Alarcón',
                'correo'          => 'mgomez@lyncott.mx',
                'puesto'          => 'Ejecutiva de Ventas',
                'gerencia'        => 'Comercial',
                'departamento'    => 'Ventas Nacionales',
                'direccion'       => 'Dirección Comercial',
                'dias_disponibles'=> 3,
                'centro_costos'   => 'COM-014',
                'clabe'           => '014180009876543210',
                'banco'           => 'Santander',
                'sucursal_cedis'  => 'CEDIS Guadalajara',
            ],
            [
                'numero_empleado' => '100318',
                'rfc'             => 'LOHR8807151K9',
                'nombre_completo' => 'Ricardo López Hernández',
                'correo'          => 'rlopez@lyncott.mx',
                'puesto'          => 'Supervisor de Operaciones',
                'gerencia'        => 'Operaciones',
                'departamento'    => 'Producción',
                'direccion'       => 'Dirección de Operaciones',
                'dias_disponibles'=> 2,
                'centro_costos'   => 'OPS-007',
                'clabe'           => '002180001112223334',
                'banco'           => 'Banamex',
                'sucursal_cedis'  => 'Planta Toluca',
            ],
            [
                'numero_empleado' => '100422',
                'rfc'             => 'VACL950320PT4',
                'nombre_completo' => 'Claudia Vázquez Luna',
                'correo'          => 'cvazquez@lyncott.mx',
                'puesto'          => 'Analista de Compras',
                'gerencia'        => 'Abastecimiento',
                'departamento'    => 'Compras',
                'direccion'       => 'Dirección de Administración y Finanzas',
                'dias_disponibles'=> 5,
                'centro_costos'   => 'ABA-022',
                'clabe'           => '072180005556667778',
                'banco'           => 'Banorte',
                'sucursal_cedis'  => 'Corporativo CDMX',
            ],
            [
                'numero_empleado' => '100537',
                'rfc'             => 'SARJ8811029Z1',
                'nombre_completo' => 'Jorge Sánchez Ramírez',
                'correo'          => 'jsanchez@lyncott.mx',
                'puesto'          => 'Gerente de Logística',
                'gerencia'        => 'Logística',
                'departamento'    => 'Distribución',
                'direccion'       => 'Dirección de Operaciones',
                'dias_disponibles'=> 6,
                'centro_costos'   => 'LOG-003',
                'clabe'           => '021180008889990001',
                'banco'           => 'HSBC',
                'sucursal_cedis'  => 'CEDIS Monterrey',
            ],
        ];

        foreach ($empleados as $e) {
            Empleado::updateOrCreate(['numero_empleado' => $e['numero_empleado']], $e);
        }
    }
}
