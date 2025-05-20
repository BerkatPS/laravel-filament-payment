<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Discount;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->default(fn () => 'INV-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)))
                    ->label('Nomor Invoice')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null),
                Forms\Components\DatePicker::make('issue_date')
                    ->label('Tanggal Diterbitkan')
                    ->default(now())
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Tanggal Jatuh Tempo')
                    ->default(fn () => now()->addDays(7))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Jumlah')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),
                Forms\Components\TextInput::make('tax_amount')
                    ->label('Jumlah Pajak')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0),
                Forms\Components\Select::make('discount_id')
                    ->relationship('discount', 'name')
                    ->label('Diskon')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set, callable $get) => 
                        $this->calculateFinalAmount($set, $get)
                    ),
                Forms\Components\TextInput::make('discount_amount')
                    ->label('Jumlah Diskon')
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('final_amount')
                    ->label('Jumlah Akhir')
                    ->numeric()
                    ->prefix('Rp')
                    ->required()
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\Select::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'paid' => 'Dibayar',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->default('draft')
                    ->required()
                    ->label('Status'),
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable()
                    ->label('Nomor Invoice'),
                Tables\Columns\TextColumn::make('issue_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Diterbitkan'),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
                    ->sortable()
                    ->label('Tanggal Jatuh Tempo'),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->money('IDR')
                    ->sortable()
                    ->label('Jumlah Akhir'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'danger' => 'overdue',
                        'warning' => 'draft',
                        'success' => 'paid',
                        'primary' => 'sent',
                        'gray' => 'cancelled',
                    ])
                    ->label('Status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Dibuat'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'sent' => 'Terkirim',
                        'paid' => 'Dibayar',
                        'overdue' => 'Terlambat',
                        'cancelled' => 'Dibatalkan',
                    ]),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query) => $query->whereIn('status', ['draft', 'sent', 'overdue']))
                    ->label('Belum Dibayar'),
                Tables\Filters\Filter::make('due_today')
                    ->query(fn (Builder $query) => $query->where('due_date', now()->format('Y-m-d')))
                    ->label('Jatuh Tempo Hari Ini'),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query) => $query->where('due_date', '<', now()->format('Y-m-d'))->whereNotIn('status', ['paid', 'cancelled']))
                    ->label('Terlambat'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->using(function (array $data, string $model) {
                        // Calculate discount amount and final amount
                        $discountAmount = 0;
                        if (isset($data['discount_id']) && $data['discount_id']) {
                            $discount = Discount::find($data['discount_id']);
                            if ($discount) {
                                $discountAmount = $discount->calculateDiscount($data['amount']);
                            }
                        }
                        
                        $finalAmount = $data['amount'] + $data['tax_amount'] - $discountAmount;
                        
                        $data['discount_amount'] = $discountAmount;
                        $data['final_amount'] = $finalAmount;
                        
                        return $model::create($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('markAsPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->action(function ($record) {
                        $record->status = 'paid';
                        $record->save();
                    })
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'sent', 'overdue'])),
                Tables\Actions\Action::make('cancel')
                    ->label('Batalkan')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function ($record) {
                        $record->status = 'cancelled';
                        $record->save();
                    })
                    ->visible(fn ($record) => $record->status !== 'cancelled' && $record->status !== 'paid'),
                Tables\Actions\Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('invoices.print', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('markAsPaid')
                        ->label('Tandai Dibayar')
                        ->icon('heroicon-o-currency-dollar')
                        ->action(fn ($records) => $records->each(function ($record) {
                            $record->status = 'paid';
                            $record->save();
                        })),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    private function calculateFinalAmount($set, $get)
    {
        $amount = (float)$get('amount') ?: 0;
        $taxAmount = (float)$get('tax_amount') ?: 0;
        $discountAmount = 0;
        
        if ($get('discount_id')) {
            $discount = Discount::find($get('discount_id'));
            if ($discount) {
                $discountAmount = $discount->calculateDiscount($amount);
            }
        }
        
        $finalAmount = $amount + $taxAmount - $discountAmount;
        
        $set('discount_amount', $discountAmount);
        $set('final_amount', max(0, $finalAmount));
    }
}
