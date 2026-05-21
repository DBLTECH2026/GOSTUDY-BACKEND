<?php

namespace Database\Seeders;

use App\Models\Usuario;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Crea un único administrador de acceso. El resto de los datos
     * (docentes, estudiantes, inscripciones, matrículas, pagos) se
     * crean desde la propia aplicación durante las pruebas.
     */
    public function run(): void
    {
        $this->call(CatalogosSeeder::class);
        $this->call(SeccionesSeeder::class);
        $this->call(CursosSeeder::class);
        $this->call(CalendarioAcademicoSeeder::class);
        $this->call(HorariosDemoSeeder::class);

        Usuario::updateOrCreate(
            ['email' => 'admin@trilce.edu.pe'],
            [
                'nombres'   => 'Administrador',
                'apellidos' => 'Trilce',
                'password'  => 'admin123',
                'rol'       => 'admin',
                'estado'    => 'activo',
            ]
        );
    }
}
