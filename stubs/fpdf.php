<?php
/**
 * Development-only stub for FPDF to satisfy IDE type resolution.
 * Not loaded at runtime; real class should come from Composer or lib/fpdf.
 */

if (!class_exists('FPDF')) {
    class FPDF {
        public function AddPage(): void {}
        public function SetFont(string $family, string $style, int $size): void {}
        public function Cell(float $w, float $h, string $txt, int $border = 0, int $ln = 0, string $align = ''): void {}
        public function Ln(float $h = 0): void {}
        public function Output(string $dest = 'I'): void {}
    }
}