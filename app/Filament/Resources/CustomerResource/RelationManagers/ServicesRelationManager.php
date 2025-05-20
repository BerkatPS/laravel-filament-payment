<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Service;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->default(now())
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('Tanggal Berakhir'),
                Forms\Components\TextInput::make('price')
                    ->label('Harga Langganan')
                    ->prefix('Rp')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'pending' => 'Tertunda',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kadaluarsa',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Toggle::make('auto_renewal')
                    ->label('Perpanjangan Otomatis')
                    ->default(false),
                Forms\Components\DateTimePicker::make('next_billing_date')
                    ->label('Tanggal Penagihan Berikutnya')
                    ->visible(fn (callable $get) => $get('auto_renewal')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Layanan'),
                Tables\Columns\TextColumn::make('pivot.price')
                    ->money('IDR')
                    ->sortable()
                    ->label('Harga Langganan'),
                Tables\Columns\TextColumn::make('pivot.status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'danger' => 'cancelled',
                        'warning' => 'pending',
                        'gray' => 'inactive',
                        'danger' => 'expired',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('pivot.start_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal Mulai'),
                Tables\Columns\TextColumn::make('pivot.end_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Tanggal Berakhir'),
                Tables\Columns\IconColumn::make('pivot.auto_renewal')
                    ->boolean()
                    ->label('Perpanjangan Otomatis'),
                Tables\Columns\TextColumn::make('pivot.next_billing_date')
                    ->dateTime()
                    ->sortable()
                    ->label('Penagihan Berikutnya'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Tidak Aktif',
                        'pending' => 'Tertunda',
                        'cancelled' => 'Dibatalkan',
                        'expired' => 'Kadaluarsa',
                    ])
                    ->attribute('pivot.status'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        Forms\Components\Select::make('recordId')
                            ->label('Layanan')
                            ->options(Service::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if ($state) {
                                    $service = Service::find($state);
                                    if ($service) {
                                        $set('price', $service->price);
                                    }
                                }
                            }),
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->default(now())
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Tanggal Berakhir'),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Langganan')
                            ->prefix('Rp')
                            ->required()
                            ->numeric(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'pending' => 'Tertunda',
                                'cancelled' => 'Dibatalkan',
                                'expired' => 'Kadaluarsa',
                            ])
                            ->default('active')
                            ->required(),
                        Forms\Components\Toggle::make('auto_renewal')
                            ->label('Perpanjangan Otomatis')
                            ->default(false),
                        Forms\Components\DateTimePicker::make('next_billing_date')
                            ->label('Tanggal Penagihan Berikutnya')
                            ->visible(fn (callable $get) => $get('auto_renewal')),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required(),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Tanggal Berakhir'),
                        Forms\Components\TextInput::make('price')
                            ->label('Harga Langganan')
                            ->prefix('Rp')
                            ->required()
                            ->numeric(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Tidak Aktif',
                                'pending' => 'Tertunda',
                                'cancelled' => 'Dibatalkan',
                                'expired' => 'Kadaluarsa',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('auto_renewal')
                            ->label('Perpanjangan Otomatis'),
                        Forms\Components\DateTimePicker::make('next_billing_date')
                            ->label('Tanggal Penagihan Berikutnya')
                            ->visible(fn (callable $get) => $get('auto_renewal')),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
