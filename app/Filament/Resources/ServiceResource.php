<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceResource\Pages;
use App\Filament\Resources\ServiceResource\RelationManagers;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationGroup = 'Pelanggan & Layanan';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Layanan')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->label('Nama Layanan'),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'SRV-' . strtoupper(substr(uniqid(), -6)))
                            ->label('Kode Layanan')
                            ->disabled(fn ($record) => $record !== null),
                        Forms\Components\TextInput::make('price')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->label('Harga'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'discontinued' => 'Dihentikan',
                            ])
                            ->default('active')
                            ->required()
                            ->label('Status'),
                    ])->columns(2),
                Forms\Components\Section::make('Detail Layanan')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->label('Deskripsi'),
                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'one_time' => 'Satu Kali',
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan',
                                'monthly' => 'Bulanan',
                                'quarterly' => 'Triwulan',
                                'semi_annually' => 'Per Semester',
                                'annually' => 'Tahunan',
                            ])
                            ->default('monthly')
                            ->required()
                            ->label('Siklus Penagihan'),
                        Forms\Components\TextInput::make('billing_cycle_days')
                            ->numeric()
                            ->default(30)
                            ->label('Durasi Siklus (Hari)')
                            ->helperText('Jumlah hari dalam satu siklus penagihan'),
                        Forms\Components\Checkbox::make('taxable')
                            ->label('Kena Pajak')
                            ->default(true),
                        Forms\Components\TextInput::make('tax_percentage')
                            ->numeric()
                            ->default(11)
                            ->label('Persentase Pajak (%)')
                            ->suffix('%')
                            ->visible(fn (callable $get) => $get('taxable')),
                    ])->columns(2),
                Forms\Components\Section::make('Diskon')
                    ->schema([
                        Forms\Components\Repeater::make('discounts')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Nama Diskon'),
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(table: 'discounts', ignoreRecord: true)
                                    ->label('Kode Diskon'),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'percentage' => 'Persentase',
                                        'fixed' => 'Nominal Tetap',
                                    ])
                                    ->required()
                                    ->label('Tipe Diskon'),
                                Forms\Components\TextInput::make('value')
                                    ->required()
                                    ->numeric()
                                    ->label('Nilai Diskon')
                                    ->prefix(fn (callable $get) => $get('type') === 'fixed' ? 'Rp' : '')
                                    ->suffix(fn (callable $get) => $get('type') === 'percentage' ? '%' : ''),
                                Forms\Components\DatePicker::make('valid_from')
                                    ->required()
                                    ->label('Berlaku Dari'),
                                Forms\Components\DatePicker::make('valid_until')
                                    ->label('Berlaku Hingga'),
                                Forms\Components\Toggle::make('active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->label('Diskon Tersedia')
                            ->defaultItems(0)
                            ->orderColumn('sort')
                            ->columns(2)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->label('Kode Layanan'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nama Layanan'),
                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Harga'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'discontinued',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('billing_cycle')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'one_time' => 'Satu Kali',
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                        'quarterly' => 'Triwulan',
                        'semi_annually' => 'Per Semester',
                        'annually' => 'Tahunan',
                        default => $state,
                    })
                    ->label('Siklus Penagihan'),
                Tables\Columns\IconColumn::make('taxable')
                    ->boolean()
                    ->label('Kena Pajak'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Diperbarui'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'discontinued' => 'Dihentikan',
                    ]),
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query) => $query->where('status', 'active'))
                    ->label('Hanya yang Aktif'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'active' => 'Aktif',
                                    'inactive' => 'Tidak Aktif',
                                    'discontinued' => 'Dihentikan',
                                ])
                                ->required()
                                ->label('Status Baru'),
                        ])
                        ->action(function ($records, array $data) {
                            foreach ($records as $record) {
                                $record->status = $data['status'];
                                $record->save();
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CustomersRelationManager::class,
            RelationManagers\DiscountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
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
