<?php

namespace App\Livewire;

use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class ReportTable extends Component implements HasTable
{
    use InteractsWithTable;

    public string $tableId;
    public string $parentId;
    public ?string $parentClass = null;

    protected static array $tableRegistry = [];

    public function mount(string $tableId, string $parentId, ?string $parentClass = null)
    {
        $this->tableId = $tableId;
        $this->parentId = $parentId;
        $this->parentClass = $parentClass;
    }

    public function table(Table $table): Table
    {
        // Try to get table from registry first
        $registryKey = $this->parentId . ':' . $this->tableId;
        if (isset(self::$tableRegistry[$registryKey])) {
            return self::$tableRegistry[$registryKey];
        }

        // If not in registry, try to call parent's getTable method via Livewire
        // Use Livewire's component system to find parent
        try {
            // In Livewire 3, we need to use a different approach
            // Since we can't directly get component instances, we'll use a callback pattern
            // For now, return empty table and let parent register tables
        } catch (\Exception $e) {
            // Fallback: return empty table
        }
        
        return Table::make();
    }

    public static function registerTable(string $parentId, string $tableId, Table $table): void
    {
        self::$tableRegistry[$parentId . ':' . $tableId] = $table;
    }

    public function makeFilamentTranslatableContentDriver(): ?\Filament\Support\Contracts\TranslatableContentDriver
    {
        return null;
    }

    public function render()
    {
        return view('livewire.report-table');
    }
}

