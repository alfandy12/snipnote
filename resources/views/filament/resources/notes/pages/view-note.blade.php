<x-filament-panels::page>

    {{ $this->infolist }}
    {{ $this->form }}

    @if (count($relationManagers = $this->getRelationManagers()))
        <x-filament-panels::resources.relation-managers :active-manager="$this->activeRelationManager" :managers="$relationManagers" :owner-record="$record"
            :page-class="static::class" />
    @endif
</x-filament-panels::page>
