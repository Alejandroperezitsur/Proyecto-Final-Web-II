<?php
/**
 * Development-only stubs to satisfy IDE type resolution for PhpSpreadsheet.
 * Not loaded at runtime; real classes should come from Composer or lib autoloaders.
 */

namespace PhpOffice\PhpSpreadsheet {
    class Spreadsheet {
        public function __construct() {}
        public function getProperties(): Document\Properties { return new Document\Properties(); }
        public function getActiveSheet(): Worksheet\Worksheet { return new Worksheet\Worksheet(); }
        public function createSheet(): Worksheet\Worksheet { return new Worksheet\Worksheet(); }
        /** @return array<int, Worksheet\Worksheet> */
        public function getAllSheets(): array { return [new Worksheet\Worksheet()]; }
    }
}

namespace PhpOffice\PhpSpreadsheet\Writer {
    class Xlsx {
        public function __construct($spreadsheet) {}
        public function save(string $output): void {}
    }
}

namespace PhpOffice\PhpSpreadsheet\Document {
    class Properties {
        public function setCreator(string $creator): self { return $this; }
        public function setTitle(string $title): self { return $this; }
    }
}

namespace PhpOffice\PhpSpreadsheet\Worksheet {
    class Worksheet {
        public function setTitle(string $title): void {}
        public function fromArray(array $source, $nullValue = null, string $startCell = 'A1'): void {}
        public function setCellValue(string $cell, $value): void {}
        public function getColumnDimension(string $column): ColumnDimension { return new ColumnDimension(); }
    }

    class ColumnDimension {
        public function setAutoSize(bool $value): void {}
    }
}