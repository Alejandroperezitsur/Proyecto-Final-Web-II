<?php
namespace App\Controllers\Api;

use App\Services\GradesService;
use App\Services\GroupsService;
use App\Services\SubjectsService;
use PDO;

class KpiController
{
    private PDO $pdo;
    private GradesService $grades;
    private GroupsService $groups;
    private SubjectsService $subjects;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->grades = new GradesService($pdo);
        $this->groups = new GroupsService($pdo);
        $this->subjects = new SubjectsService($pdo);
    }

    public function admin(): void
    {
        header('Content-Type: application/json');
        $totalAlumnos = (int)$this->pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
        $totalProfesores = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn();
        $totalMaterias = $this->subjects->count();
        $promedioGeneral = $this->grades->globalAverage();
        $activosGrupos = $this->groups->count();
        $pendientes = (int)$this->pdo->query('SELECT COUNT(*) FROM calificaciones WHERE final IS NULL')->fetchColumn();
        echo json_encode([
            'alumnos' => $totalAlumnos,
            'profesores' => $totalProfesores,
            'materias' => $totalMaterias,
            'promedio' => $promedioGeneral,
            'grupos' => $activosGrupos,
            'pendientes_evaluacion' => $pendientes,
        ]);
    }

    public function profesorDashboard(int $profesorId): void
    {
        header('Content-Type: application/json');
        $grupos = $this->groups->activeByTeacher($profesorId);
        $totalAlumnos = 0;
        foreach ($grupos as $g) { $totalAlumnos += (int)($g['alumnos'] ?? 0); }
        echo json_encode([
            'grupos_activos' => count($grupos),
            'alumnos' => $totalAlumnos,
            'grupos' => $grupos,
        ]);
    }
}