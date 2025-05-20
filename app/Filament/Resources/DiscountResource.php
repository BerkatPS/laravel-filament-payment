<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Filament\Resources\DiscountResource\RelationManagers;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Promosi & Diskon';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Diskon')
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
                            ->suffix(fn (callable $get) => $get('type') === 'percentage' ? '%' : '')
                            ->rules([
                                fn (callable $get) => $get('type') === 'percentage' ? 'max:100' : '',
                            ]),
                    ])->columns(2),
                Forms\Components\Section::make('Periode & Status')
                    ->schema([
                        Forms\Components\DatePicker::make('valid_from')
                            ->default(now())
                            ->required()
                            ->label('Berlaku Dari'),
                        Forms\Components\DatePicker::make('valid_until')
                            ->label('Berlaku Hingga')
                            ->after('valid_from'),
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
                        Forms\Components\TextInput::make('max_usage_count')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->label('Maksimum Penggunaan')
                            ->helperText('Kosongkan jika tidak ada batas maksimum penggunaan'),
                        Forms\Components\TextInput::make('max_usage_per_customer')
                            ->numeric()
                            ->integer()
                            ->minValue(0)
                            ->label('Maksimum Penggunaan per Pelanggan')
                            ->helperText('Kosongkan jika tidak ada batas per pelanggan'),
                        Forms\Components\Toggle::make('active')
                            ->label('Aktif')
                            ->default(true),
                        Forms\Components\Select::make('service_id')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Layanan Terkait')
                            ->helperText('Kosongkan jika berlaku untuk semua layanan'),
                    ])->columns(2),
                Forms\Components\Section::make('Deskripsi & Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->label('Deskripsi'),
                        Forms\Components\Textarea::make('terms_and_conditions')
                            ->maxLength(65535)
                            ->label('Syarat dan Ketentuan'),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Layanan')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Penggunaan')
                    ->toggleable(),
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
                Tables\Filters\SelectFilter::make('service_id')
                    ->relationship('service', 'name')
                    ->label('Layanan'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
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
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Aktifkan')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active' => true]))),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Nonaktifkan')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['active' => false]))),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'view' => Pages\ViewDiscount::route('/{record}'),
            'edit' => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
