<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function home(Request $request)
    {
        $monthLabels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        // Datos estáticos de usuarios y equipos creados por mes
        $userData = [5, 8, 12, 6, 9, 15, 20, 18, 10, 7, 5, 4];
        $teamData = [2, 3, 5, 1, 6, 4, 7, 8, 5, 3, 2, 1];

        // Partidos por día de la semana (simulado)
        $matchesByDay = collect([
            ['date' => now()->startOfWeek()->toDateString(), 'count' => 2],
            ['date' => now()->startOfWeek()->addDay()->toDateString(), 'count' => 3],
            ['date' => now()->startOfWeek()->addDays(2)->toDateString(), 'count' => 1],
            ['date' => now()->startOfWeek()->addDays(3)->toDateString(), 'count' => 4],
            ['date' => now()->startOfWeek()->addDays(4)->toDateString(), 'count' => 2],
        ]);

        // Porcentaje de canchas ocupadas (simulado)
        $totalFields = 10;
        $occupiedFields = 6;
        $occupationPercentage = ($totalFields > 0) ? ($occupiedFields / $totalFields) * 100 : 0;

        // Partidos jugados este mes (simulado)
        $matchesPlayedThisMonth = 22;

        // Lista de partidos (simulado)
        $matches = collect([
            [
                'schedule_date' => now()->toDateString(),
                'start_time' => '18:00',
                'field' => 'Cancha 1',
                'teams' => ['Equipo A', 'Equipo B'],
                'status' => 'pendiente'
            ],
            // ... más partidos simulados
        ]);

        // Próximos partidos (simulado)
        $upcomingMatches = collect([
            [
                'schedule_date' => now()->addDay()->toDateString(),
                'start_time' => '19:00',
                'field' => 'Cancha 2',
                'teams' => ['Equipo C', 'Equipo D'],
                'status' => 'pendiente'
            ],
        ]);

        // Lista de canchas (simulado)
        $fields = collect([
            ['id' => 1, 'name' => 'Cancha 1'],
            ['id' => 2, 'name' => 'Cancha 2'],
            ['id' => 3, 'name' => 'Cancha 3'],
        ]);

        return view('dashboard', [
            'newUsersCount' => 8,
            'newTeamsCount' => 3,
            'monthLabels' => $monthLabels,
            'userData' => $userData,
            'teamData' => $teamData,
            'matchesByDay' => $matchesByDay,
            'occupationPercentage' => $occupationPercentage,
            'matchesPlayedThisMonth' => $matchesPlayedThisMonth,
            'matches' => $matches,
            'upcomingMatches' => $upcomingMatches,
            'fields' => $fields,
        ]);
    }
}
