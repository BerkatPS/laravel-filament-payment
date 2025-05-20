<?php

namespace App\Filament\Resources\ServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountsRelationManager extends RelationManager
{
    protected static string $relationship = 'discounts';

    protected static ?string $recordTitleAttribute = 'name';
    
    // Menonaktifkan sorting default
    protected static bool $hasSortableRows = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Diskon'),
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(fn () => 'DISC-' . strtoupper(substr(uniqid(), -6)))
                    ->label('Kode Diskon')
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\Select::make('type')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed' => 'Nominal Tetap',
                    ])
                    ->required()
                    ->reactive()
                    ->label('Tipe Diskon'),
                Forms\Components\TextInput::make('value')
                    ->required()
                    ->numeric()
                    ->label('Nilai Diskon')
                    ->prefix(fn (callable $get) => $get('type') === 'fixed' ? 'Rp' : '')
                    ->suffix(fn (callable $get) => $get('type') === 'percentage' ? '%' : ''),
                Forms\Components\DatePicker::make('valid_from')
                    ->default(now())
                    ->required()
                    ->label('Berlaku Dari'),
                Forms\Components\DatePicker::make('valid_until')
                    ->label('Berlaku Hingga'),
                Forms\Components\TextInput::make('minimum_order')
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Minimum Pembelian')
                    ->helperText('Kosongkan jika tidak ada minimum pembelian'),
                Forms\Components\TextInput::make('maximum_discount')
                    ->numeric()
                    ->prefix('Rp')
                    ->label('Maksimum Diskon')
                    ->helperText('Kosongkan jika tidak ada batas maksimum discount')
                    ->visible(fn (callable $get) => $get('type') === 'percentage'),
                Forms\Components\Toggle::make('active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\Textarea::make('description')
                    ->maxLength(65535)
                    ->columnSpanFull()
                    ->label('Deskripsi'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->reorder()->orderBy('created_at', 'desc'))
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Kode Diskon'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Diskon'),
                Tables\Columns\TextColumn::make('discount_display')
                    ->formatStateUsing(function ($record) {
                        if ($record->type === 'percentage') {
                            return $record->value . '%';
                        } else {
                            return 'Rp ' . number_format($record->value, 0, ',', '.');
                        }
                    })
                    ->label('Nilai Diskon'),
                Tables\Columns\TextColumn::make('valid_from')
                    ->date()
                    ->sortable()
                    ->label('Berlaku Dari'),
                Tables\Columns\TextColumn::make('valid_until')
                    ->date()
                    ->sortable()
                    ->label('Berlaku Hingga'),
                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->label('Aktif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'percentage' => 'Persentase',
                        'fixed' => 'Nominal Tetap',
                    ])
                    ->label('Tipe Diskon'),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query) => $query->where('active', true))
                    ->label('Hanya yang Aktif'),
                Tables\Filters\Filter::make('valid_now')
                    ->query(function (Builder $query) {
                        return $query->where('valid_from', '<=', now())
                            ->where(function (Builder $query) {
                                $query->where('valid_until', '>=', now())
                                    ->orWhereNull('valid_until');
                            });
                    })
                    ->label('Berlaku Saat Ini'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\Action::make('toggle')
                        ->label(fn ($record) => $record->active ? 'Nonaktifkan' : 'Aktifkan')
                        ->icon(fn ($record) => $record->active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->active ? 'danger' : 'success')
                        ->action(function ($record) {
                            $record->active = !$record->active;
                            $record->save();
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active' => true]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active' => false]))),
                ]),
            ]);
    }
}
